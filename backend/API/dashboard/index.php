<?php
header("Content-Type: application/json");
require_once '../../config.php'; // arahkan ke koneksi DB

$path = $_GET['path'] ?? '';

if ($path === 'summary') {
    try {
        $stmt = $conn->query("SELECT COUNT(*) AS total FROM products WHERE status = 'active'");
        $totalProducts = $stmt->fetch_assoc()['total'] ?? 0;

        $stmt = $conn->query("SELECT COUNT(*) AS out_of_stock FROM products WHERE stock_quantity <= 0 AND status = 'active'");
        $outOfStock = $stmt->fetch_assoc()['out_of_stock'] ?? 0;

        $stmt = $conn->query("SELECT SUM(grand_total) AS total FROM purchases WHERE status = 'received'");
        $totalPurchases = $stmt->fetch_assoc()['total'] ?? 0;

        $stmt = $conn->query("SELECT SUM(grand_total) AS total FROM sales WHERE status = 'completed'");
        $totalSales = $stmt->fetch_assoc()['total'] ?? 0;

        echo json_encode([
            "success" => true,
            "data" => [
                "total_products" => (int)$totalProducts,
                "total_purchases" => (int)$totalPurchases,
                "total_sales" => (int)$totalSales,
                "out_of_stock" => (int)$outOfStock
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

else if ($path === 'weekly-trend') {
    try {
        $result = ["labels" => [], "sales" => [], "purchases" => []];
        $hari = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($hari as $day) {
            $stmt = $conn->prepare("SELECT SUM(grand_total) AS total FROM sales WHERE status = 'completed' AND DAYNAME(date) = ?");
            $stmt->bind_param("s", $day);
            $stmt->execute();
            $sales = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

            $stmt2 = $conn->prepare("SELECT SUM(grand_total) AS total FROM purchases WHERE status = 'received' AND DAYNAME(date) = ?");
            $stmt2->bind_param("s", $day);
            $stmt2->execute();
            $purchase = $stmt2->get_result()->fetch_assoc()['total'] ?? 0;

            $result['labels'][] = $day;
            $result['sales'][] = (int)$sales;
            $result['purchases'][] = (int)$purchase;
        }

        echo json_encode(["success" => true, "data" => $result]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

else if ($path === 'top-products') {
    try {
        $stmt = $conn->query("
            SELECT p.product_name, SUM(si.quantity) AS total_qty
            FROM sales_items si
            JOIN products p ON si.product_id = p.id
            GROUP BY si.product_id
            ORDER BY total_qty DESC
            LIMIT 5
        ");

        $labels = [];
        $data = [];

        while ($row = $stmt->fetch_assoc()) {
            $labels[] = $row['product_name'];
            $data[] = (int)$row['total_qty'];
        }

        echo json_encode([
            "success" => true,
            "data" => [
                "labels" => $labels,
                "values" => $data
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

else if ($path === 'top-customers') {
    try {
        $query = "
            SELECT c.name, SUM(s.grand_total) AS total_spent
            FROM sales s
            JOIN customers c ON s.customer_id = c.id
            WHERE s.status = 'completed'
            GROUP BY s.customer_id
            ORDER BY total_spent DESC
            LIMIT 5
        ";

        $stmt = $conn->query($query);

        if (!$stmt) {
            throw new Exception("Query gagal: " . $conn->error);
        }

        $labels = [];
        $values = [];

        while ($row = $stmt->fetch_assoc()) {
            $labels[] = $row['name'];
            $values[] = (int)$row['total_spent'];
        }

        echo json_encode([
            "success" => true,
            "data" => [
                "labels" => $labels,
                "values" => $values
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

else if ($path === 'stock-alert') {
    try {
        $stmt = $conn->query("
            SELECT 
                sku, 
                product_name, 
                stock_quantity, 
                min_stock_warning 
            FROM products 
            WHERE stock_quantity <= min_stock_warning 
              AND status = 'active'
            ORDER BY stock_quantity ASC
            LIMIT 10
        ");

        $data = [];
        while ($row = $stmt->fetch_assoc()) {
            $data[] = [
                "sku" => $row['sku'],
                "name" => $row['product_name'],
                "qty" => (int)$row['stock_quantity'],
                "min_qty" => (int)$row['min_stock_warning']
            ];
        }

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

else if ($path === 'top-returns') {
    try {
        $stmt = $conn->query("
            SELECT p.product_name, SUM(sri.quantity) AS total_returned
            FROM sales_return_items sri
            JOIN products p ON sri.product_id = p.id
            GROUP BY sri.product_id
            ORDER BY total_returned DESC
            LIMIT 5
        ");

        $labels = [];
        $values = [];

        while ($row = $stmt->fetch_assoc()) {
            $labels[] = $row['product_name'];
            $values[] = (int)$row['total_returned'];
        }

        echo json_encode([
            "success" => true,
            "data" => [
                "labels" => $labels,
                "values" => $values
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

else if ($path === 'recent-invoices') {
    try {
        $stmt = $conn->query("
            SELECT s.invoice_no, c.name, s.grand_total, s.payment_status
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            ORDER BY s.date DESC
            LIMIT 5
        ");

        $data = [];

        while ($row = $stmt->fetch_assoc()) {
            $data[] = [
                "invoice_no" => $row['invoice_no'],
                "customer" => $row['name'] ?? 'Unknown',
                "grand_total" => (int)$row['grand_total'],
                "status" => $row['payment_status']
            ];
        }

        echo json_encode(["success" => true, "data" => $data]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

else if ($path === 'sales-target' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $monthYear = date('Y-m');
        $stmt = $conn->prepare("SELECT target FROM sales_targets WHERE month_year = ?");
        $stmt->bind_param("s", $monthYear);
        $stmt->execute();
        $res = $stmt->get_result();
        $target = $res->fetch_assoc()['target'] ?? 50000000; // default Rp 50jt

        $stmt2 = $conn->query("
            SELECT SUM(grand_total) AS total
            FROM sales
            WHERE status = 'completed'
              AND MONTH(date) = MONTH(CURDATE())
              AND YEAR(date) = YEAR(CURDATE())
        ");
        $totalSales = $stmt2->fetch_assoc()['total'] ?? 0;

        echo json_encode([
            "success" => true,
            "data" => [
                "target" => (int)$target,
                "achieved" => (int)$totalSales
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

else if ($path === 'sales-target' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $target = (int)($_POST['target'] ?? 0);
        $monthYear = date('Y-m');

        $check = $conn->prepare("SELECT id FROM sales_targets WHERE month_year = ?");
        $check->bind_param("s", $monthYear);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();

        if ($exists) {
            $stmt = $conn->prepare("UPDATE sales_targets SET target = ? WHERE month_year = ?");
        } else {
            $stmt = $conn->prepare("INSERT INTO sales_targets (target, month_year) VALUES (?, ?)");
        }

        $stmt->bind_param("is", $target, $monthYear);
        $stmt->execute();

        echo json_encode(["success" => true, "message" => "Target berhasil diperbarui"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

else {
    echo json_encode([
        "success" => false,
        "message" => "Path tidak dikenali."
    ]);
}
