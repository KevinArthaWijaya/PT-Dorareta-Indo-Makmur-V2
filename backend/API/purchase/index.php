<?php
require_once '../../config.php';

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
    $res = $conn->query("SELECT invoice_no FROM purchases ORDER BY id DESC LIMIT 1");
    $last = $res->fetch_assoc();
    $newNumber = 1;

    if ($last && preg_match('/INV-(\d+)/', $last['invoice_no'], $m)) {
        $newNumber = intval($m[1]) + 1;
    }

    $invoice_no = 'INV-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    respond(['success' => true, 'invoice_no' => $invoice_no]);
}

// 🔍 Autocomplete Supplier
if ($method === 'GET' && $path === 'search_suppliers' && isset($_GET['q'])) {
    $keyword = '%' . $conn->real_escape_string($_GET['q']) . '%';
    $stmt = $conn->prepare("SELECT name FROM suppliers WHERE name LIKE ? ORDER BY name ASC LIMIT 10");
    $stmt->bind_param("s", $keyword);
    $stmt->execute();
    $res = $stmt->get_result();

    $suggestions = [];
    while ($row = $res->fetch_assoc()) {
        $suggestions[] = $row['name'];
    }

    respond(['success' => true, 'data' => $suggestions]);
}

// 🔍 Autocomplete Produk
if ($method === 'GET' && $path === 'search_products' && isset($_GET['q'])) {
    $q = $conn->real_escape_string($_GET['q']);
    $res = $conn->query("SELECT DISTINCT product_name FROM products WHERE product_name LIKE '%$q%' ORDER BY product_name LIMIT 10");

    $names = [];
    while ($row = $res->fetch_assoc()) {
        $names[] = $row['product_name'];
    }

    respond(['success' => true, 'data' => $names]);
}

// 📄 GET single purchase by ID (jika frontend pakai ?path=purchase&id=123)
if ($method === 'GET' && $path === 'purchase' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id <= 0) {
        respond(['success' => false, 'message' => 'Invalid ID'], 400);
    }

    $stmt = $conn->prepare("
        SELECT p.*, s.name AS supplier_name 
        FROM purchases p 
        LEFT JOIN suppliers s ON p.supplier_id = s.id 
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $purchase = $res->fetch_assoc();

    if (!$purchase) {
        respond(['success' => false, 'message' => 'Purchase not found.'], 404);
    }

    $items = [];
    $items_stmt = $conn->prepare("
  SELECT pi.product_name, pi.quantity, pi.unit_price, pi.subtotal, p.sku 
  FROM purchase_items pi
  LEFT JOIN products p ON pi.product_id = p.id
  WHERE pi.purchase_id = ?
");
    $items_stmt->bind_param("i", $id);
    $items_stmt->execute();
    $item_res = $items_stmt->get_result();
    while ($row = $item_res->fetch_assoc()) {
        $items[] = $row;
    }

    $purchase['items'] = $items;

    respond(['success' => true, 'data' => $purchase]);
}

// 🚀 Proses Create Purchase
if ($method === 'POST' && $path === 'purchase') {
    $invoice_no     = $_POST['invoice_no'] ?? '';
    $date           = $_POST['date'] ?? '';
    $supplier_name  = $_POST['supplier_name'] ?? '';
    $status         = $_POST['status'] ?? 'ordered';
    $payment_status = $_POST['payment_status'] ?? 'unpaid';
    $paid           = floatval(preg_replace('/[^\d]/', '', $_POST['paid'] ?? '0'));
    $grand_total    = floatval(preg_replace('/[^\d]/', '', $_POST['grand_total'] ?? '0'));
    $due            = floatval(preg_replace('/[^\d]/', '', $_POST['due'] ?? '0'));
    $notes          = $_POST['notes'] ?? '';

    // ✅ Cek format FormData manual (products[0][field]) atau array langsung (products[])
    $raw_data = [];

    // Cara 1: Nested FormData seperti products[0][name]
    foreach ($_POST as $key => $value) {
        if (preg_match('/^products\[(\d+)\]\[(name|quantity|unit_price)\]$/', trim($key), $matches)) {
            $index = (int)$matches[1];
            $field = $matches[2];
            $raw_data[$index][$field] = trim($value);
        }
    }

    // Cara 2: Jika dikirim sebagai array langsung (products = [ {...}, {...} ])
    if (isset($_POST['products']) && is_array($_POST['products'])) {
        $raw_data = $_POST['products'];
    }

    // 🔄 Normalisasi & Validasi Produk
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

    if (!$invoice_no || !$date || !$supplier_name || empty($products)) {
        respond(['success' => false, 'message' => 'Data pembelian tidak lengkap.'], 400);
    }

    $conn->begin_transaction();

    try {
        // 🔍 Supplier
        $supplier_stmt = $conn->prepare("SELECT id FROM suppliers WHERE name = ? LIMIT 1");
        $supplier_stmt->bind_param("s", $supplier_name);
        $supplier_stmt->execute();
        $supplier_result = $supplier_stmt->get_result();
        $supplier = $supplier_result->fetch_assoc();

        if (!$supplier) {
            $insert_supplier = $conn->prepare("INSERT INTO suppliers (name) VALUES (?)");
            $insert_supplier->bind_param("s", $supplier_name);
            $insert_supplier->execute();
            $supplier_id = $insert_supplier->insert_id;
        } else {
            $supplier_id = $supplier['id'];
        }

        // 🧾 Insert purchase
        $stmt = $conn->prepare("INSERT INTO purchases (invoice_no, date, supplier_id, status, payment_status, paid, grand_total, due, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissddds", $invoice_no, $date, $supplier_id, $status, $payment_status, $paid, $grand_total, $due, $notes);
        $stmt->execute();
        $purchase_id = $stmt->insert_id;

        // 🔁 Produk
        foreach ($products as $product) {
            $name = trim($product['name']);
            $qty = $product['quantity'];
            $price = $product['unit_price'];

            $check = $conn->prepare("SELECT id, stock_quantity FROM products WHERE LOWER(product_name) = LOWER(?) LIMIT 1");
            $check->bind_param("s", $name);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $existing = $res->fetch_assoc();
                $product_id = $existing['id'];

                if ($status === 'received') {
                    $updateStock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $updateStock->bind_param("ii", $qty, $product_id);
                    $updateStock->execute();
                    $updateStock->close();

                    $stockCheck = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                    $stockCheck->bind_param("i", $product_id);
                    $stockCheck->execute();
                    $stockRes = $stockCheck->get_result();
                    $stockCheck->close();

                    if ($stockRes && $stockRes->num_rows > 0) {
                        $stockQty = (int)$stockRes->fetch_assoc()['stock_quantity'];
                        if ($stockQty > 0) {
                            $activate = $conn->prepare("UPDATE products SET status = 'active' WHERE id = ?");
                            $activate->bind_param("i", $product_id);
                            $activate->execute();
                            $activate->close();
                        }
                    }
                }
            } else {
                $initQty = ($status === 'received') ? $qty : 0;
                $statusNew = 'inactive';
                $auto_created = 1;
                $highlight_color = 'yellow';

                $sku = 'AUTO-' . uniqid(mt_rand(), true);
                $insertProduct = $conn->prepare("INSERT INTO products (product_name, sku, stock_quantity, status, auto_created, highlight_color) VALUES (?, ?, ?, ?, ?, ?)");
                $insertProduct->bind_param("ssisds", $name, $sku, $initQty, $statusNew, $auto_created, $highlight_color);
                if ($insertProduct->execute()) {
                    $product_id = $insertProduct->insert_id;
                } else {
                    throw new Exception("Gagal membuat produk baru: " . $insertProduct->error);
                }
                $insertProduct->close();
            }

            $check->close();
            if (!isset($product_id)) {
                throw new Exception("Gagal memproses produk '$name'.");
            }

            $subtotal = $qty * $price;
            $insertItem = $conn->prepare("INSERT INTO purchase_items (purchase_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $insertItem->bind_param("iisidd", $purchase_id, $product_id, $name, $qty, $price, $subtotal);
            $insertItem->execute();
            $insertItem->close();
        }

        $conn->commit();
        respond(['success' => true, 'message' => 'Purchase created successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
}

// 🔍 GET all purchase with items
if ($method === 'GET' && $path === 'purchase' && !isset($_GET['id'])) {
    $page  = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, intval($_GET['limit'] ?? 10));
    $offset = ($page - 1) * $limit;

    // Total data
    $countRes = $conn->query("SELECT COUNT(*) as total FROM purchases");
    $count = $countRes->fetch_assoc()['total'] ?? 0;
    $total_pages = ceil($count / $limit);

    // Ambil data paginated
    $stmt = $conn->prepare("
        SELECT p.*, s.name AS supplier_name 
        FROM purchases p
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $row['items'] = [];

        $itemStmt = $conn->prepare("SELECT product_name, quantity, unit_price FROM purchase_items WHERE purchase_id = ?");
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

// ✏️ Update full purchase
if ($method === 'PUT' && $path === 'purchase') {

    $put = $_POST;
    $purchase_id = intval($put['purchase_id'] ?? 0);


    if ($purchase_id <= 0) {
        respond(['success' => false, 'message' => 'Invalid purchase ID'], 400);
    }

    $invoice_no     = $put['invoice_no'] ?? '';
    $date           = $put['date'] ?? '';
    $supplier_name  = $put['supplier_name'] ?? '';
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

    if (!$invoice_no || !$date || !$supplier_name || empty($products)) {
        respond(['success' => false, 'message' => 'Data pembelian tidak lengkap.'], 400);
    }

    $conn->begin_transaction();

    try {
        // Supplier
        $supplier_stmt = $conn->prepare("SELECT id FROM suppliers WHERE name = ? LIMIT 1");
        $supplier_stmt->bind_param("s", $supplier_name);
        $supplier_stmt->execute();
        $supplier_result = $supplier_stmt->get_result();
        $supplier = $supplier_result->fetch_assoc();

        if (!$supplier) {
            $insert_supplier = $conn->prepare("INSERT INTO suppliers (name) VALUES (?)");
            $insert_supplier->bind_param("s", $supplier_name);
            $insert_supplier->execute();
            $supplier_id = $insert_supplier->insert_id;
        } else {
            $supplier_id = $supplier['id'];
        }

        // Update master data
        $stmt = $conn->prepare("UPDATE purchases SET invoice_no=?, date=?, supplier_id=?, status=?, payment_status=?, paid=?, grand_total=?, due=?, notes=? WHERE id=?");
        $stmt->bind_param("ssissddssi", $invoice_no, $date, $supplier_id, $status, $payment_status, $paid, $grand_total, $due, $notes, $purchase_id);
        $stmt->execute();

        // Hapus item lama
        $conn->query("DELETE FROM purchase_items WHERE purchase_id = $purchase_id");

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

                if ($status === 'received') {
                    $conn->query("UPDATE products SET stock_quantity = stock_quantity + $qty WHERE id = $product_id");
                }
            } else {
                $sku = 'AUTO-' . uniqid(mt_rand(), true);
                $initQty = ($status === 'received') ? $qty : 0;
                $auto_created = 1;
                $statusNew = 'inactive';
                $highlight_color = 'yellow';

                $insertProduct = $conn->prepare("INSERT INTO products (product_name, sku, stock_quantity, status, auto_created, highlight_color) VALUES (?, ?, ?, ?, ?, ?)");
                $insertProduct->bind_param("ssisds", $name, $sku, $initQty, $statusNew, $auto_created, $highlight_color);
                $insertProduct->execute();
                $product_id = $insertProduct->insert_id;
            }

            $subtotal = $qty * $price;
            $insertItem = $conn->prepare("INSERT INTO purchase_items (purchase_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $insertItem->bind_param("iisidd", $purchase_id, $product_id, $name, $qty, $price, $subtotal);
            $insertItem->execute();
        }

        $conn->commit();
        respond(['success' => true, 'message' => 'Purchase updated successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
    }
}

// 🔍 Search return products by purchase ID
if ($method === 'GET' && $path === 'search_return_products' && isset($_GET['id']) && isset($_GET['q'])) {
    $purchaseId = intval($_GET['id']);
    $keyword = '%' . $conn->real_escape_string($_GET['q']) . '%';

    $stmt = $conn->prepare("
    SELECT DISTINCT pi.product_name, pi.unit_price 
    FROM purchase_items pi
    WHERE pi.purchase_id = ? AND pi.product_name LIKE ?
    ORDER BY pi.product_name ASC
    LIMIT 10
");
    $stmt->bind_param("is", $purchaseId, $keyword);
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


// 🗑️ DELETE purchase
if ($method === 'DELETE' && $path === 'purchase' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id <= 0) {
        respond(['success' => false, 'message' => 'ID tidak valid'], 400);
    }

    // Cek status dulu
    $check = $conn->prepare("SELECT status FROM purchases WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    
    // Jika purchase tidak ditemukan
    if ($result->num_rows === 0) {
        respond(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
    }

    $row = $result->fetch_assoc();
    
    // Jika status sudah 'received', tidak bisa dihapus
    if ($row['status'] === 'received') {
        respond(['success' => false, 'message' => 'Purchase yang sudah received tidak bisa dihapus'], 403);
    }

    // Hapus items dan master
    $conn->begin_transaction();
    try {
        // Hapus item terkait purchase
        $deleteItems = $conn->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
        $deleteItems->bind_param("i", $id);
        $deleteItems->execute();

        // Hapus purchase master
        $deletePurchase = $conn->prepare("DELETE FROM purchases WHERE id = ?");
        $deletePurchase->bind_param("i", $id);
        $deletePurchase->execute();

        $conn->commit();
        respond(['success' => true, 'message' => 'Data berhasil dihapus']);
    } catch (Exception $e) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
}

// 🆕 CREATE Purchase Return
if ($method === 'POST' && $path === 'purchase_return') {
    $purchase_id = intval($_POST['purchase_id'] ?? 0);

    if ($purchase_id <= 0 && empty($_POST)) {
        parse_str(file_get_contents("php://input"), $_POST);
        $purchase_id = intval($_POST['purchase_id'] ?? 0);
    }

    // Validasi status purchase (harus received)
    $checkPurchase = $conn->prepare("SELECT status FROM purchases WHERE id = ?");
    $checkPurchase->bind_param("i", $purchase_id);
    $checkPurchase->execute();
    $purchaseRes = $checkPurchase->get_result();
    $purchaseData = $purchaseRes->fetch_assoc();

    if (!$purchaseData || $purchaseData['status'] !== 'received') {
        respond(['success' => false, 'message' => 'Hanya pembelian dengan status received yang bisa direturn'], 400);
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
        $insertReturn = $conn->prepare("INSERT INTO purchase_returns (purchase_id, created_at) VALUES (?, ?)");
        if (!$insertReturn) {
            throw new Exception("Gagal prepare INSERT return: " . $conn->error);
        }
        $insertReturn->bind_param("is", $purchase_id, $created_at);
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

            $insertItem = $conn->prepare("INSERT INTO purchase_return_items (return_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
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
