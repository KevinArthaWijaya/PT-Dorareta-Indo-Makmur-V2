<?php
header("Content-Type: application/json");
require '../../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? null;

// Ambil ID dari DELETE jika dikirim lewat body (php://input)
parse_str(file_get_contents("php://input"), $input);
$id = intval($_GET['id'] ?? $input['id'] ?? 0);

// ============================
// 📦 GET: Kategori & Unit
// ============================
if ($method === 'GET' && $path) {
    if ($path === 'categories') {
        $res = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
        $data = [];
        while ($row = $res->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => true, 'data' => $data]); // ✅ FIXED
        exit;
    }

    if ($path === 'units') {
        $res = $conn->query("SELECT id, name FROM units ORDER BY name ASC");
        $data = [];
        while ($row = $res->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => true, 'data' => $data]); // ✅ FIXED
        exit;
    }

    if ($path === 'generate_sku' && isset($_GET['category_id'])) {
        $categoryId = intval($_GET['category_id']);
        $catRes = $conn->query("SELECT sku_prefix FROM categories WHERE id = $categoryId");

if (!$catRes || $catRes->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => false, 'message' => 'Kategori tidak ditemukan.']);
    exit;
}

$prefix = $catRes->fetch_assoc()['sku_prefix'] ?? 'SKU';

        $res = $conn->query("
            SELECT sku 
            FROM products 
            WHERE category_id = $categoryId 
              AND LOWER(sku) REGEXP '^" . strtolower($prefix) . "[0-9]{3}$' 
            ORDER BY LENGTH(sku) DESC, sku DESC 
            LIMIT 1
        ");

        $lastSku = $res ? ($res->fetch_assoc()['sku'] ?? null) : null;
        $number = $lastSku ? intval(substr($lastSku, strlen($prefix))) + 1 : 1;
        $newSku = $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
        echo json_encode(['sku' => $newSku]);
        exit;
    }

    if ($path === 'single_product' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $res = $conn->query("SELECT * FROM products WHERE id = $id");
        if ($res && $res->num_rows > 0) {
            echo json_encode($res->fetch_assoc());
        } else {
            http_response_code(404);
            echo json_encode(['status' => false, 'message' => 'Produk tidak ditemukan']);
        }
        exit;
    }
}

// ============================
// 📝 POST: Create / Edit Produk
// ============================
if ($method === 'POST' && !$path) {
    $requiredFields = [
        "product_name", "sku", "category_id", "unit_id",
        "selling_price", "minimal_purchase", "stock_quantity", "min_stock_warning"
    ];

    $errors = [];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $errors[] = "$field wajib diisi.";
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'status' => false,
            'message' => 'Validasi gagal',
            'errors' => $errors
        ]);
        exit;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
    $product_name = trim($_POST['product_name']);
    $sku = trim($_POST['sku']);
    $category_id = intval($_POST['category_id']);
    $unit_id = intval($_POST['unit_id']);
    $selling_price = intval(preg_replace("/[^\d]/", "", $_POST['selling_price']));
    $minimal_purchase = intval($_POST['minimal_purchase']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $min_stock_warning = intval($_POST['min_stock_warning']);
    $status = ($stock_quantity === 0) ? 'inactive' : ($_POST['status'] ?? 'active');
    $highlight_color = $product_id ? '' : ($_POST['highlight_color'] ?? '');


    // 🔍 Cek SKU Unik
    $skuQuery = $product_id
        ? $conn->prepare("SELECT COUNT(*) as total FROM products WHERE sku = ? AND id != ?")
        : $conn->prepare("SELECT COUNT(*) as total FROM products WHERE sku = ?");
    if ($product_id) {
        $skuQuery->bind_param("si", $sku, $product_id);
    } else {
        $skuQuery->bind_param("s", $sku);
    }
    $skuQuery->execute();
    $skuRes = $skuQuery->get_result()->fetch_assoc();
    $skuQuery->close();

    if ($skuRes['total'] > 0) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'SKU sudah digunakan. Gunakan yang lain.']);
        exit;
    }

    // 📷 Handle Gambar
    $image_path = 'assets/image/default_product.png';
    $upload_path = '../../uploads/products_image/';
    $existingImage = '';

    if ($product_id) {
        $res = $conn->query("SELECT product_image FROM products WHERE id = $product_id");
        if ($res && $res->num_rows > 0) {
            $existingImage = $res->fetch_assoc()['product_image'];
        }
    }

    if (!empty($_FILES['product_image']['name'])) {
        $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid("product_", true) . '.' . $ext;
        $targetFile = $upload_path . $filename;

        if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);

        if ($product_id && $existingImage && $existingImage !== 'assets/image/default_product.png') {
            $oldPath = '../../' . $existingImage;
            if (file_exists($oldPath)) unlink($oldPath);
        }

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
            $image_path = 'uploads/products_image/' . $filename;
        } else {
            http_response_code(500);
            echo json_encode(['status' => false, 'message' => 'Upload gambar gagal.']);
            exit;
        }
    } else if ($product_id && $existingImage) {
        $image_path = $existingImage;
    }

    // 💾 Insert / Update Query
    if ($product_id) {
        $stmt = $conn->prepare("UPDATE products SET 
product_name=?, sku=?, category_id=?, unit_id=?, selling_price=?, 
minimal_purchase=?, stock_quantity=?, min_stock_warning=?, 
status=?, product_image=?, highlight_color=? WHERE id=?");
$stmt->bind_param("ssiidiiisssi", $product_name, $sku, $category_id, $unit_id, $selling_price,
    $minimal_purchase, $stock_quantity, $min_stock_warning, $status, $image_path, $highlight_color, $product_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO products 
(product_name, sku, category_id, unit_id, selling_price, minimal_purchase, stock_quantity, min_stock_warning, status, product_image, highlight_color)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssiidiiisss", $product_name, $sku, $category_id, $unit_id, $selling_price,
    $minimal_purchase, $stock_quantity, $min_stock_warning, $status, $image_path, $highlight_color);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'status' => true,
            'message' => $product_id ? 'Produk berhasil diubah.' : 'Produk berhasil ditambahkan.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => false,
            'message' => 'Gagal menyimpan data produk.',
            'error' => $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ============================
// 🗑️ DELETE Produk
// ============================
if ($method === 'DELETE' && $path === 'delete_product' && $id > 0) {
    // Ambil nama file gambar produk sebelum hapus
    $imageQuery = $conn->prepare("SELECT product_image FROM products WHERE id = ?");
    $imageQuery->bind_param("i", $id);
    $imageQuery->execute();
    $imageResult = $imageQuery->get_result();

    if ($imageResult && $imageResult->num_rows > 0) {
        $product = $imageResult->fetch_assoc();
        $imagePath = $product['product_image'];

        // Hapus file gambar jika bukan default
        if ($imagePath && $imagePath !== 'assets/image/default_product.png') {
            $fullPath = '../../' . $imagePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }
    $imageQuery->close();

    // Hapus data produk dari database
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => true, 'message' => 'Produk berhasil dihapus.']);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'Gagal menghapus produk.',
            'error' => $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ============================
// 🔍 FILTER Produk
// ============================
if ($method === 'GET' && $path === 'filter') {
    $sku = $_GET['sku'] ?? '';
    $category = $_GET['category'] ?? '';
    $unit = $_GET['unit'] ?? '';
    $stock_condition = $_GET['stock_condition'] ?? '';
    $status = $_GET['status'] ?? '';

    $conditions = [];
    $params = [];
    $types = "";

    if (!empty($sku)) {
        $conditions[] = "p.sku LIKE ?";
        $params[] = $sku . '%';
        $types .= "s";
    }

    if (!empty($category)) {
        $conditions[] = "p.category_id = ?";
        $params[] = $category;
        $types .= "i";
    }

    if (!empty($unit)) {
        $conditions[] = "p.unit_id = ?";
        $params[] = $unit;
        $types .= "i";
    }

    if (!empty($stock_condition)) {
        if ($stock_condition === 'less_or_equal') {
            $conditions[] = "p.stock_quantity <= p.min_stock_warning";
        } elseif ($stock_condition === 'more') {
            $conditions[] = "p.stock_quantity > p.min_stock_warning";
        }
    }

    if (!empty($status)) {
        $conditions[] = "p.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }

    $sql = "
        SELECT p.*, c.name AS category_name, u.name AS unit_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN units u ON p.unit_id = u.id
        $whereClause
        ORDER BY p.id DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
while ($row = $result->fetch_assoc()) {
    $row['is_low_stock'] = $row['stock_quantity'] < $row['min_stock_warning'];
    $row['highlight_color'] = $row['is_low_stock'] ? 'yellow' : '';
    $products[] = $row;
}

    echo json_encode(['status' => true, 'data' => $products]);

    $stmt->close();
    $conn->close();
    exit;
}

// ============================
// 🔎 SEARCH Produk by Name or SKU
// ============================
if ($method === 'GET' && $path === 'search' && isset($_GET['q'])) {
    $search = trim($_GET['q']);

    if ($search === '') {
        echo json_encode(['status' => false, 'message' => 'Parameter pencarian kosong']);
        exit;
    }

    $sql = "
        SELECT p.*, c.name AS category_name, u.name AS unit_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN units u ON p.unit_id = u.id
        WHERE p.product_name LIKE ? OR p.sku LIKE ?
        ORDER BY p.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $likeSearch = "%{$search}%";
    $stmt->bind_param("ss", $likeSearch, $likeSearch);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $row['is_low_stock'] = $row['stock_quantity'] < $row['min_stock_warning'];
        $data[] = $row;
    }

    echo json_encode(['status' => true, 'data' => $data]);

    $stmt->close();
    $conn->close();
    exit;
}

// ============================
// 📥 IMPORT PRODUK (Excel/CSV)
// ============================
if ($path === "import" && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawData = json_decode(file_get_contents("php://input"), true);

    if (!is_array($rawData)) {
        echo json_encode([
            "status" => false,
            "message" => "Format data tidak valid",
        ]);
        exit;
    }

    $requiredFields = [
        "product_name", "sku", "category", "unit",
        "selling_price", "minimal_purchase", "stock_quantity", "min_stock_warning"
    ];

    $success = 0;
    $failed = [];

    foreach ($rawData as $index => $item) {
        $row = [];
        foreach ($item as $key => $value) {
            $normalizedKey = str_replace(' ', '_', strtolower(trim($key)));
            $row[$normalizedKey] = trim($value);
        }

        $missing = array_filter($requiredFields, fn($f) => empty($row[$f]));
        if (!empty($missing)) {
            $failed[] = "Baris " . ($index + 2) . ": Kolom wajib kosong: " . implode(", ", $missing);
            continue;
        }

        $selling_price = preg_replace("/[^\d]/", "", $row['selling_price']);
        $stock_quantity = (int) $row['stock_quantity'];
        $min_stock_warning = (int) $row['min_stock_warning'];
        $status = $stock_quantity === 0 ? 'inactive' : 'active';
        $image = "assets/image/default_product.png";

        $categoryName = $conn->real_escape_string($row['category']);
        $unitName = $conn->real_escape_string($row['unit']);

        $catRes = $conn->query("SELECT id FROM categories WHERE LOWER(name) = LOWER('$categoryName')");
        $unitRes = $conn->query("SELECT id FROM units WHERE LOWER(name) = LOWER('$unitName')");
        $category_id = $catRes && $catRes->num_rows > 0 ? $catRes->fetch_assoc()['id'] : null;
        $unit_id = $unitRes && $unitRes->num_rows > 0 ? $unitRes->fetch_assoc()['id'] : null;

        if (!$category_id || !$unit_id) {
            $failed[] = "Baris " . ($index + 2) . ": Kategori/unit tidak ditemukan. 
Category='$categoryName', Unit='$unitName'";
            continue;
        }

        $stmt = $conn->prepare("INSERT INTO products 
            (product_name, sku, category_id, unit_id, selling_price, minimal_purchase, stock_quantity, min_stock_warning, status, product_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssiiiiisss",
            $row['product_name'],
            $row['sku'],
            $category_id,
            $unit_id,
            $selling_price,
            $row['minimal_purchase'],
            $stock_quantity,
            $min_stock_warning,
            $status,
            $image
        );

        if ($stmt->execute()) {
            $success++;
        } else {
            $failed[] = "Baris " . ($index + 2) . ": Gagal menyimpan data.";
        }

        $stmt->close();
    }

    echo json_encode([
        "status" => $success > 0,
        "message" => "$success produk berhasil diimport",
        "failed" => $failed,
    ]);
    exit;
}

if ($method === 'GET' && $path === 'products') {
    $sort_by = $_GET['sort_by'] ?? null;
    $sort_dir = $_GET['sort_dir'] ?? 'asc';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, intval($_GET['limit'] ?? 10));
    $offset = ($page - 1) * $limit;

    $allowed_columns = [
        'product_name' => 'p.product_name',
        'sku' => 'p.sku',
        'category_name' => 'c.name',
        'unit_name' => 'u.name',
        'selling_price' => 'p.selling_price',
        'stock_quantity' => 'p.stock_quantity',
        'status' => 'p.status'
    ];
    $allowed_directions = ['asc', 'desc'];

    $sort_column = $allowed_columns[$sort_by] ?? 'p.id';
    $sort_direction = in_array(strtolower($sort_dir), $allowed_directions) ? strtoupper($sort_dir) : 'DESC';

    // Hitung total produk
    $countResult = $conn->query("SELECT COUNT(*) as total FROM products");
    $total = $countResult ? (int) $countResult->fetch_assoc()['total'] : 0;
    $total_pages = ceil($total / $limit);

    // Ambil produk dengan limit + offset
    $query = "SELECT p.*, c.name AS category_name, u.name AS unit_name
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              LEFT JOIN units u ON p.unit_id = u.id
              ORDER BY $sort_column $sort_direction
              LIMIT $limit OFFSET $offset";

    $result = $conn->query($query);
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $row['is_low_stock'] = $row['stock_quantity'] < $row['min_stock_warning'];
        $products[] = $row;
    }

    echo json_encode([
        'status' => true,
        'data' => $products,
        'total' => $total,
        'page' => $page,
        'total_pages' => $total_pages
    ]);
    exit;
}


// ============================
// 🚫 METHOD TIDAK DIIZINKAN
// ============================
http_response_code(405);
echo json_encode([
    'status' => false,
    'message' => 'Metode tidak diizinkan.'
]);