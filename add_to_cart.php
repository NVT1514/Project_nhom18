<?php
session_start();
include "Database/connectdb.php";

// Kiểm tra dữ liệu đầu vào
if (!isset($_POST['id'])) {
    die("Thiếu thông tin sản phẩm!");
}

$product_id = intval($_POST['id']);
$quantity = intval($_POST['quantity'] ?? 1);

// Lấy thông tin sản phẩm từ cơ sở dữ liệu
$sql = "SELECT * FROM san_pham WHERE id = $product_id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Không tìm thấy sản phẩm!");
}

$product = mysqli_fetch_assoc($result);

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Nếu sản phẩm đã có trong giỏ -> tăng số lượng
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
} else {
    // Nếu chưa có -> thêm mới với đầy đủ thông tin sản phẩm
    $_SESSION['cart'][$product_id] = [
        'id' => $product['id'],
        'ten_san_pham' => $product['ten_san_pham'],
        'gia' => $product['gia'],
        'hinh_anh' => $product['hinh_anh'],
        'mo_ta' => $product['mo_ta'],
        'quantity' => $quantity
    ];
}

// Chuyển hướng về trang giỏ hàng
header("Location: cart.php");
exit();
