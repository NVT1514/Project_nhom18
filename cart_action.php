<?php
session_start();
include "../Database/connectdb.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_POST['action'] === 'pay_done') {
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $total = floatval($_POST['total']);
    $orderId = $_POST['orderId'];
    $payment_method = $_POST['payment'] ?? 'vnpay';

    // ✅ Lưu đơn hàng vào bảng don_hang (status = 1)
    $stmt = $conn->prepare("
        INSERT INTO don_hang (user_id, fullname, phone, address, total, payment_method, order_id, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt->bind_param("isssdss", $user_id, $fullname, $phone, $address, $total, $payment_method, $orderId);

    if ($stmt->execute()) {
        $don_hang_id = $stmt->insert_id;

        // ✅ Lưu chi tiết từng sản phẩm
        $cart_items = $conn->query("
            SELECT sp.id AS product_id, sp.ten_san_pham, sp.gia, gh.so_luong, gh.size 
            FROM gio_hang gh 
            JOIN san_pham sp ON gh.san_pham_id = sp.id 
            WHERE gh.user_id = $user_id
        ");

        while ($item = $cart_items->fetch_assoc()) {
            $stmt2 = $conn->prepare("
                INSERT INTO chi_tiet_don_hang (order_id, product_id, product_name, price, quantity, size)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt2->bind_param("iisdis", $don_hang_id, $item['product_id'], $item['ten_san_pham'], $item['gia'], $item['so_luong'], $item['size']);
            $stmt2->execute();
        }

        // ✅ Xóa giỏ hàng sau khi lưu
        $conn->query("DELETE FROM gio_hang WHERE user_id = $user_id");

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
}
