<?php
ob_start();
error_reporting(E_ALL & ~E_NOTICE);

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'User session expired. Please log in again.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Extract parameters from json structure array
$deliveryCompany = isset($data['delivery_company']) ? trim($data['delivery_company']) : '';
$cartItems = isset($data['cart']) ? $data['cart'] : [];

if (empty($cartItems) || !is_array($cartItems) || empty($deliveryCompany)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Cart elements or delivery courier selection missing.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert order metadata record row
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, delivery_company) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $deliveryCompany]);
    $orderId = $pdo->lastInsertId();

    // Statement to create part if it is completely missing inside table catalog
    $partCheckStmt = $pdo->prepare("INSERT IGNORE INTO parts (element_id, name, color, image_url) VALUES (?, ?, ?, ?)");
    
    // Statement to save items inside order relational table link map
    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, part_id, quantity) VALUES (?, ?, ?)");

    foreach ($cartItems as $item) {
        // Run check to make sure part exists in parent table to fulfill foreign keys constraint
        $partCheckStmt->execute([
            $item['id'], 
            $item['name'], 
            $item['color'] ?? 'Black', 
            $item['img']
        ]);

        // Save row safely inside child table array map loop
        $itemStmt->execute([$orderId, $item['id'], $item['qty']]);
    }

    $pdo->commit();
    
    ob_clean();
    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (\Throwable $e) {
    // If any operation fails rollback transaction immediately
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'System Engine Courier Failure: ' . $e->getMessage()]);
}
?>