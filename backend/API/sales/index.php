<?php
require_once '../../config.php';

$role = $_SESSION['role'] ?? '';

if (!isset($_SESSION['username'])) {
    respond(['success' => false, 'message' => 'Unauthorized access.'], 403);
}

if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE']) && !in_array($role, ['Admin', 'Sales'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Support override for PUT via POST FormData
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['method']) && $_GET['method'] === 'PUT') {
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    // ✅ Tidak perlu parse_str karena FormData otomatis masuk ke $_POST
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

function respond($data, $status = 200) {
    http_response_code($status);
    if (ob_get_length()) ob_clean();
    echo json_encode($data);
    exit;
}

// 🧾 Generate Auto Invoice
if ($method === 'GET' && $path === 'generate_invoice') {
    $prefix = "INV-SL";
    $today = date("Ymd");

    $like = "$prefix-$today%";
    $query = "SELECT invoice_no FROM sales WHERE invoice_no LIKE ? ORDER BY invoice_no DESC LIMIT 1";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Gagal prepare statement: " . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $lastNo = 0;
    if ($row = $result->fetch_assoc()) {
        $lastNo = intval(substr($row['invoice_no'], -4));
    }

    $newNo = str_pad($lastNo + 1, 4, "0", STR_PAD_LEFT);
    $invoice_no = "$prefix-$today$newNo";

    echo json_encode([
        "success" => true,
        "invoice_no" => $invoice_no
    ]);
    exit;
}

// 🔍 Autocomplete Customer
if ($method === 'GET' && $path === 'search_customers' && isset($_GET['q'])) {
    $keyword = '%' . $conn->real_escape_string($_GET['q']) . '%';
    $stmt = $conn->prepare("SELECT name FROM customers WHERE name LIKE ? ORDER BY name ASC LIMIT 10");
    $stmt->bind_param("s", $keyword);
    $stmt->execute();
    $res = $stmt->get_result();

    $suggestions = [];
    while ($row = $res->fetch_assoc()) {
        $suggestions[] = $row['name'];
    }

    respond(['success' => true, 'data' => $suggestions]);
}

// 📄 GET single sale by ID (jika frontend pakai ?path=sale&id=123)
if ($method === 'GET' && $path === 'sales' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id <= 0) {
        respond(['success' => false, 'message' => 'Invalid ID'], 400);
    }

    $stmt = $conn->prepare("
        SELECT p.*, s.name AS customer_name
        FROM sales p 
        LEFT JOIN customers s ON p.customer_id = s.id 
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $sale = $res->fetch_assoc();

    if (!$sale) {
        respond(['success' => false, 'message' => 'Sale not found.'], 404);
    }

    $items = [];
    $items_stmt = $conn->prepare("
  SELECT pi.product_name, pi.quantity, pi.unit_price, pi.subtotal, p.sku 
  FROM sales_items pi
  LEFT JOIN products p ON pi.product_id = p.id
  WHERE pi.sale_id = ?
");
    $items_stmt->bind_param("i", $id);
    $items_stmt->execute();
    $item_res = $items_stmt->get_result();
    while ($row = $item_res->fetch_assoc()) {
        $items[] = $row;
    }

    $sale['items'] = $items;

    respond(['success' => true, 'data' => $sale]);
}

// 🚀 Proses Create Sale
if ($method === 'POST' && $path === 'sales') {
    $invoice_no     = $_POST['invoice_no'] ?? '';
    $date           = $_POST['date'] ?? '';
    $customer_name  = $_POST['customer_name'] ?? '';
    $status         = $_POST['status'] ?? 'ordered';
    $payment_status = $_POST['payment_status'] ?? 'unpaid';
    $paid           = floatval(preg_replace('/[^\d]/', '', $_POST['paid'] ?? '0'));
    $grand_total    = floatval(preg_replace('/[^\d]/', '', $_POST['grand_total'] ?? '0'));
    $due            = floatval(preg_replace('/[^\d]/', '', $_POST['due'] ?? '0'));
    $notes          = $_POST['notes'] ?? '';

    $raw_data = [];
    foreach ($_POST as $key => $value) {
        if (preg_match('/^products\[(\d+)\]\[(name|quantity|unit_price)\]$/', trim($key), $matches)) {
            $index = (int)$matches[1];
            $field = $matches[2];
            $raw_data[$index][$field] = trim($value);
        }
    }

    if (isset($_POST['products']) && is_array($_POST['products'])) {
        $raw_data = $_POST['products'];
    }

    $products = [];
    foreach ($raw_data as $p) {
        $name = trim($p['name'] ?? '');
        $qty = intval($p['quantity'] ?? 0);
        $price = floatval(preg_replace('/[^\d]/', '', $p['unit_price'] ?? '0'));

        if ($name !== '' && $qty > 0) {
            $products[] = [
                'name' => $name,
                'quantity' => $qty,
                'unit_price' => $price,
            ];
        }
    }

    if (!$invoice_no || !$date || !$customer_name || empty($products)) {
        respond(['success' => false, 'message' => 'Data penjualan tidak lengkap.'], 400);
    }

    $conn->begin_transaction();

    try {
        $cust_stmt = $conn->prepare("SELECT id FROM customers WHERE name = ? LIMIT 1");
        if (!$cust_stmt) throw new Exception("Gagal prepare supplier SELECT: " . $conn->error);

        $cust_stmt->bind_param("s", $customer_name);
        $cust_stmt->execute();
        $cust_result = $cust_stmt->get_result();
        $customer = $cust_result->fetch_assoc();
        $cust_stmt->close();

        if (!$customer) {
            $insert_customer = $conn->prepare("INSERT INTO customers (name) VALUES (?)");
            if (!$insert_customer) throw new Exception("Gagal prepare INSERT supplier: " . $conn->error);

            $insert_customer->bind_param("s", $customer_name);
            $insert_customer->execute();
            $customer_id = $insert_customer->insert_id;
            $insert_customer->close();
        } else {
            $customer_id = $customer['id'];
        }

        $stmt = $conn->prepare("INSERT INTO sales (invoice_no, date, customer_id, status, payment_status, paid, grand_total, due, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Gagal prepare INSERT sales: " . $conn->error);

        $stmt->bind_param("ssissddds", $invoice_no, $date, $customer_id, $status, $payment_status, $paid, $grand_total, $due, $notes);
        $stmt->execute();
        $sale_id = $stmt->insert_id;
        $stmt->close();

        foreach ($products as $product) {
            $name = trim($product['name']);
            $qty = $product['quantity'];
            $price = $product['unit_price'];

            $check = $conn->prepare("SELECT id, stock_quantity FROM products WHERE LOWER(product_name) = LOWER(?) LIMIT 1");
            if (!$check) throw new Exception("Gagal prepare SELECT produk: " . $conn->error);

            $check->bind_param("s", $name);
            $check->execute();
            $res = $check->get_result();
            $check->close();

            if ($res->num_rows > 0) {
                $existing = $res->fetch_assoc();
                $product_id = $existing['id'];

                if ($status === 'completed') {
                    $updateStock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                    if (!$updateStock) throw new Exception("Gagal prepare update stock: " . $conn->error);
                
                    $updateStock->bind_param("ii", $qty, $product_id);
                    $updateStock->execute();
                    $updateStock->close();
                
                    // Jika stok masih ada, set status produk aktif
                    $stockCheck = $conn->query("SELECT stock_quantity FROM products WHERE id = $product_id");
                    $stockQty = $stockCheck->fetch_assoc()['stock_quantity'] ?? 0;
                    if ($stockQty > 0) {
                        $conn->query("UPDATE products SET status = 'active' WHERE id = $product_id");
                    } else {
                        // Jika stok habis, set status produk nonaktif
                        $conn->query("UPDATE products SET status = 'inactive' WHERE id = $product_id");
                    }
                }
            } else {
                $initQty = ($status === 'completed') ? $qty : 0;
                $statusNew = 'inactive';
                $auto_created = 1;
                $highlight_color = 'yellow';
                $sku = 'AUTO-' . uniqid(mt_rand(), true);

                $insertProduct = $conn->prepare("INSERT INTO products (product_name, sku, stock_quantity, status, auto_created, highlight_color) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$insertProduct) throw new Exception("Gagal prepare insert produk baru: " . $conn->error);

                $insertProduct->bind_param("ssisds", $name, $sku, $initQty, $statusNew, $auto_created, $highlight_color);
                $insertProduct->execute();
                $product_id = $insertProduct->insert_id;
                $insertProduct->close();
            }

            if (!isset($product_id)) {
                throw new Exception("Gagal memproses produk '$name'.");
            }

            $subtotal = $qty * $price;
            $insertItem = $conn->prepare("INSERT INTO sales_items (sale_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$insertItem) throw new Exception("Gagal prepare insert item penjualan: " . $conn->error);

            $insertItem->bind_param("iisidd", $sale_id, $product_id, $name, $qty, $price, $subtotal);
            $insertItem->execute();
            $insertItem->close();
        }

        $conn->commit();
        respond(['success' => true, 'message' => 'Sale created successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
}

// 🔍 GET all sale with items
if ($method === 'GET' && $path === 'sales' && !isset($_GET['id'])) {
    $page  = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, intval($_GET['limit'] ?? 10));
    $offset = ($page - 1) * $limit;

    // Total data
    $countRes = $conn->query("SELECT COUNT(*) as total FROM sales");
    $count = $countRes->fetch_assoc()['total'] ?? 0;
    $total_pages = ceil($count / $limit);

    // Ambil data paginated
    $stmt = $conn->prepare("
        SELECT p.*, s.name AS customer_name
        FROM sales p
        LEFT JOIN customers s ON p.customer_id = s.id
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $row['items'] = [];

        $itemStmt = $conn->prepare("SELECT product_name, quantity, unit_price FROM sales_items WHERE sale_id = ?");
        $itemStmt->bind_param("i", $row['id']);
        $itemStmt->execute();
        $items = $itemStmt->get_result();

        while ($item = $items->fetch_assoc()) {
            $row['items'][] = $item;
        }

        $data[] = $row;
    }

    respond([
        'success' => true,
        'data' => $data,
        'page' => $page,
        'limit' => $limit,
        'total' => $count,
        'total_pages' => $total_pages
    ]);
}

if ($method === 'PUT' && empty($_POST)) {
    parse_str(file_get_contents("php://input"), $_POST);
}

// ✏️ Update full sale
if ($method === 'PUT' && $path === 'sales') {

    $put = $_POST;
    $sale_id = intval($put['sale_id'] ?? 0);


    if ($sale_id <= 0) {
        respond(['success' => false, 'message' => 'Invalid sale ID'], 400);
    }

    $invoice_no     = $put['invoice_no'] ?? '';
    $date           = $put['date'] ?? '';
    $customer_name = $put['customer_name'] ?? '';
    $status         = $put['status'] ?? 'ordered';
    $payment_status = $put['payment_status'] ?? 'unpaid';
    $paid           = floatval(preg_replace('/[^\d]/', '', $put['paid'] ?? '0'));
    $grand_total    = floatval(preg_replace('/[^\d]/', '', $put['grand_total'] ?? '0'));
    $due            = floatval(preg_replace('/[^\d]/', '', $put['due'] ?? '0'));
    $notes          = $put['notes'] ?? '';

    // Ambil produk
    $products = [];
foreach ($put as $key => $value) {
    if (preg_match('/^products\[(\d+)\]\[(name|quantity|unit_price)\]$/', $key, $m)) {
        $index = (int)$m[1];
        $field = $m[2];
        $products[$index][$field] = trim($value);
    }
}

// Tambahan: jika struktur sudah berupa array
if (isset($put['products']) && is_array($put['products']) && empty($products)) {
    $products = $put['products'];
}

    if (!$invoice_no || !$date || !$customer_name|| empty($products)) {
        respond(['success' => false, 'message' => 'Data pembelian tidak lengkap.'], 400);
    }

    $conn->begin_transaction();

    try {
        // Supplier
        $supplier_stmt = $conn->prepare("SELECT id FROM customers WHERE name = ? LIMIT 1");
        $supplier_stmt->bind_param("s", $customer_name);
        $supplier_stmt->execute();
        $supplier_result = $supplier_stmt->get_result();
        $supplier = $supplier_result->fetch_assoc();

        if (!$supplier) {
            $insert_supplier = $conn->prepare("INSERT INTO customers (name) VALUES (?)");
            $insert_supplier->bind_param("s", $customer_name);
            $insert_supplier->execute();
            $customer_id = $insert_supplier->insert_id;
        } else {
            $customer_id = $supplier['id'];
        }

        // Update master data
        $stmt = $conn->prepare("UPDATE sales SET invoice_no=?, date=?, customer_id=?, status=?, payment_status=?, paid=?, grand_total=?, due=?, notes=? WHERE id=?");
        $stmt->bind_param("ssissddssi", $invoice_no, $date, $customer_id, $status, $payment_status, $paid, $grand_total, $due, $notes, $sale_id);
        $stmt->execute();

        // Hapus item lama
        $conn->query("DELETE FROM sales_items WHERE sale_id = $sale_id");

        // Tambah item baru
        foreach ($products as $product) {
            $name = trim($product['name'] ?? '');
            $qty = intval($product['quantity'] ?? 0);
            $price = floatval(preg_replace('/[^\d]/', '', $product['unit_price'] ?? '0'));

            if (!$name || $qty <= 0) continue;

            $check = $conn->prepare("SELECT id FROM products WHERE LOWER(product_name) = LOWER(?) LIMIT 1");
            $check->bind_param("s", $name);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $product_id = $res->fetch_assoc()['id'];
            
                if ($status === 'completed') {
                    // Cek stok saat ini
                    $checkStock = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ? LIMIT 1");
                    $checkStock->bind_param("i", $product_id);
                    $checkStock->execute();
                    $resStock = $checkStock->get_result();
                    $currentStock = $resStock->fetch_assoc()['stock_quantity'] ?? 0;
                    $checkStock->close();
            
                    // Hitung stok baru, pastikan tidak negatif
                    $newStock = max(0, $currentStock - $qty);
            
                    // Update stok produk
                    $updateStock = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                    $updateStock->bind_param("ii", $newStock, $product_id);
                    $updateStock->execute();
                    $updateStock->close();
                }
            } else {
                $sku = 'AUTO-' . uniqid(mt_rand(), true);
                $initQty = ($status === 'completed') ? $qty : 0;
                $auto_created = 1;
                $statusNew = 'inactive';
                $highlight_color = 'yellow';

                $insertProduct = $conn->prepare("INSERT INTO products (product_name, sku, stock_quantity, status, auto_created, highlight_color) VALUES (?, ?, ?, ?, ?, ?)");
                $insertProduct->bind_param("ssisds", $name, $sku, $initQty, $statusNew, $auto_created, $highlight_color);
                $insertProduct->execute();
                $product_id = $insertProduct->insert_id;
            }

            $subtotal = $qty * $price;
            $insertItem = $conn->prepare("INSERT INTO sales_items (sale_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $insertItem->bind_param("iisidd", $sale_id, $product_id, $name, $qty, $price, $subtotal);
            $insertItem->execute();
        }

        $conn->commit();
        respond(['success' => true, 'message' => 'Sale updated successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
    }
}

// 🔍 Search return products by sale ID
if ($method === 'GET' && $path === 'search_return_products' && isset($_GET['id']) && isset($_GET['q'])) {
    $saleId = intval($_GET['id']);
    $keyword = '%' . $conn->real_escape_string($_GET['q']) . '%';

    $stmt = $conn->prepare("
    SELECT DISTINCT pi.product_name, pi.unit_price 
    FROM sales_items pi
    WHERE pi.sale_id = ? AND pi.product_name LIKE ?
    ORDER BY pi.product_name ASC
    LIMIT 10
");
    $stmt->bind_param("is", $saleId, $keyword);
    $stmt->execute();
    $res = $stmt->get_result();

    $products = [];
while ($row = $res->fetch_assoc()) {
    $products[] = [
        'product_name' => $row['product_name'],
        'unit_price' => (float) $row['unit_price']
    ];
}

    respond(['success' => true, 'data' => $products]);
}


// 🗑️ DELETE sale
if ($method === 'DELETE' && $path === 'sales' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id <= 0) {
        respond(['success' => false, 'message' => 'ID tidak valid'], 400);
    }

    // Cek status dulu
    $check = $conn->prepare("SELECT status FROM sales WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    
    // Jika sale tidak ditemukan
    if ($result->num_rows === 0) {
        respond(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
    }

    $row = $result->fetch_assoc();
    
    // Jika status sudah 'completed', tidak bisa dihapus
    if ($row['status'] === 'completed') {
        respond(['success' => false, 'message' => 'Sale yang sudah completed tidak bisa dihapus'], 403);
    }

    // Hapus items dan master
    $conn->begin_transaction();
    try {
        // Hapus item terkait sale
        $deleteItems = $conn->prepare("DELETE FROM sales_items WHERE sale_id = ?");
        $deleteItems->bind_param("i", $id);
        $deleteItems->execute();

        // Hapus sale master
        $deleteSale = $conn->prepare("DELETE FROM sales WHERE id = ?");
        $deleteSale->bind_param("i", $id);
        $deleteSale->execute();

        $conn->commit();
        respond(['success' => true, 'message' => 'Data berhasil dihapus']);
    } catch (Exception $e) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
}

// 🆕 CREATE Sale Return
if ($method === 'POST' && $path === 'sales_return') {
    $sale_id = intval($_POST['sale_id'] ?? 0);

    if ($sale_id <= 0 && empty($_POST)) {
        parse_str(file_get_contents("php://input"), $_POST);
        $sale_id = intval($_POST['sale_id'] ?? 0);
    }

    // Validasi status sale (harus completed)
    $checkSale = $conn->prepare("SELECT status FROM sales WHERE id = ?");
    $checkSale->bind_param("i", $sale_id);
    $checkSale->execute();
    $saleRes = $checkSale->get_result();
    $saleData = $saleRes->fetch_assoc();

    if (!$saleData || $saleData['status'] !== 'completed') {
        respond(['success' => false, 'message' => 'Hanya pembelian dengan status completed yang bisa direturn'], 400);
    }

    
    // Ambil data produk return dari 'products' atau fallback ke 'items'
    $products = [];

foreach ($_POST as $key => $value) {
    if (preg_match('/^items\[(\d+)\]\[(sku|quantity|unit_price|product_name)\]$/', $key, $matches)) {
        $index = (int)$matches[1];
        $field = $matches[2];
        $products[$index][$field] = $value;
    }
}
ksort($products); // biar urut berdasarkan index

// Fallback jika dikirim langsung sebagai array
if (empty($products) && isset($_POST['items']) && is_array($_POST['items'])) {
    $products = $_POST['items'];
}

    if (empty($products) || !is_array($products)) {
        respond(['success' => false, 'message' => 'Data produk return kosong atau tidak valid'], 400);
    }

    $conn->begin_transaction();
    try {
        $created_at = date("Y-m-d H:i:s");
        $insertReturn = $conn->prepare("INSERT INTO sales_returns (sale_id, created_at) VALUES (?, ?)");
        if (!$insertReturn) {
            throw new Exception("Gagal prepare INSERT return: " . $conn->error);
        }
        $insertReturn->bind_param("is", $sale_id, $created_at);
        $insertReturn->execute();
        $return_id = $insertReturn->insert_id;

        foreach ($products as $p) {
            $sku = trim($p['sku'] ?? '');
            $qty = intval($p['quantity'] ?? 0);
            $price = floatval(preg_replace('/[^\d]/', '', $p['unit_price'] ?? '0'));

            if (!$sku || $qty <= 0 || $price <= 0) continue;

            $getProd = $conn->prepare("SELECT id, product_name, stock_quantity FROM products WHERE sku = ? LIMIT 1");
            if (!$getProd) throw new Exception("Gagal prepare SELECT product: " . $conn->error);

            $getProd->bind_param("s", $sku);
            $getProd->execute();
            $resProd = $getProd->get_result();
            $prod = $resProd->fetch_assoc();

            if (!$prod) {
                file_put_contents("missing_products.log", "SKU '$sku' tidak ditemukan\n", FILE_APPEND);
                continue;
            }

            $product_id = $prod['id'];
            $product_name = $prod['product_name'];
            $stock_now = (int)$prod['stock_quantity'];
            $new_stock = max(0, $stock_now - $qty);
            $subtotal = $qty * $price;

            $insertItem = $conn->prepare("INSERT INTO sales_return_items (return_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$insertItem) {
                throw new Exception("Gagal prepare INSERT return item: " . $conn->error);
            }
            $insertItem->bind_param("iisidd", $return_id, $product_id, $product_name, $qty, $price, $subtotal);
            if (!$insertItem->execute()) {
                throw new Exception("Insert return item gagal: " . $insertItem->error);
            }

            $updateStock = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            if (!$updateStock) throw new Exception("Gagal prepare UPDATE stok: " . $conn->error);

            $updateStock->bind_param("ii", $new_stock, $product_id);
            if (!$updateStock->execute()) {
                throw new Exception("Update stok gagal: " . $updateStock->error);
            }

            if ($new_stock <= 0) {
                $conn->query("UPDATE products SET status = 'inactive' WHERE id = $product_id");
            }
        }

        $conn->commit();
        respond(['success' => true, 'message' => 'Data return berhasil disimpan']);
    } catch (Exception $e) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Gagal menyimpan return: ' . $e->getMessage()]);
    }
}

// 🔍 Autocomplete Product
if ($method === 'GET' && $path === 'search_products' && isset($_GET['q'])) {
    $keyword = '%' . $conn->real_escape_string($_GET['q']) . '%';

    $stmt = $conn->prepare("
        SELECT product_name, selling_price, minimal_purchase
        FROM products 
        WHERE 
            LOWER(status) = 'active' 
            AND auto_created = 1
            AND stock_quantity > 0
            AND product_name LIKE ?
        ORDER BY product_name ASC 
        LIMIT 10
    ");
    $stmt->bind_param("s", $keyword);
    $stmt->execute();
    $res = $stmt->get_result();

    $results = [];
    while ($row = $res->fetch_assoc()) {
        $results[] = [
            'name' => $row['product_name'],
            'selling_price' => (int) $row['selling_price'],
            'minimal_purchase' => (int) $row['minimal_purchase']
        ];
    }

    respond([
        'success' => true,
        'data' => $results
    ]);
}