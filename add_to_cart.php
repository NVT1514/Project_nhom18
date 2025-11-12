<?php
include "Database/connectdb.php";
session_start();

// --- 1. KIỂM TRA ĐĂNG NHẬP VÀ LẤY DỮ LIỆU ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$san_pham_id = intval($_POST['id']);
$so_luong = intval($_POST['quantity']);
$size = $_POST['size'] ?? 'M';

// --- 2. KIỂM TRA SẢN PHẨM TỒN TẠI VÀ LẤY CHI TIẾT SẢN PHẨM CHO TOAST ---
$sql_check_sp = "SELECT ten_san_pham, gia, hinh_anh FROM san_pham WHERE id = $san_pham_id LIMIT 1";
$result_sp = mysqli_query($conn, $sql_check_sp);

if (!$result_sp || mysqli_num_rows($result_sp) == 0) {
    header("Location: chitietsanpham.php?id=$san_pham_id&error=product_not_found");
    exit();
}
$product_data = mysqli_fetch_assoc($result_sp);

// Định dạng dữ liệu để truyền qua URL
$product_name_for_url = urlencode($product_data['ten_san_pham']);
$product_price_for_url = urlencode(number_format($product_data['gia'], 0, ',', '.') . 'đ');
$product_image_for_url = urlencode($product_data['hinh_anh']);
$product_size_for_url = urlencode($size);


// --- 3. THÊM/CẬP NHẬT GIỎ HÀNG DÙNG DB (gio_hang) ---
$sql_check = "SELECT * FROM gio_hang WHERE user_id = $user_id AND san_pham_id = $san_pham_id AND size = '$size'";
$result = mysqli_query($conn, $sql_check);

if (mysqli_num_rows($result) > 0) {
    $sql_update = "UPDATE gio_hang SET so_luong = so_luong + $so_luong 
                   WHERE user_id = $user_id AND san_pham_id = $san_pham_id AND size = '$size'";
    mysqli_query($conn, $sql_update);
} else {
    $sql_insert = "INSERT INTO gio_hang (user_id, san_pham_id, so_luong, size)
                   VALUES ($user_id, $san_pham_id, $so_luong, '$size')";
    mysqli_query($conn, $sql_insert);
}


// --- 4. LOGIC CHUYỂN HƯỚNG MỚI (ĐỒNG BỘ VỚI TOAST) ---

if (isset($_POST['redirect_to_cart']) && $_POST['redirect_to_cart'] === 'true') {
    // Mua ngay: Chuyển thẳng đến trang giỏ hàng
    header('Location: cart.php');
    exit();
}

// Thêm vào giỏ: Chuyển hướng về trang chi tiết sản phẩm, kèm tham số TOAST
$redirect_url = "chitietsanpham.php?id=$san_pham_id&add_to_cart_success=true"
    . "&product_name={$product_name_for_url}"
    . "&product_size={$product_size_for_url}"
    . "&product_price={$product_price_for_url}"
    . "&product_image={$product_image_for_url}";

header("Location: " . $redirect_url);
exit();
