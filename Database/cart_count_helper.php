<?php
// Yêu cầu kết nối DB (Chắc chắn connectdb.php đã được include ở trang gọi file này!)
// include "Database/connectdb.php"; // KHÔNG NÊN include connectdb.php hai lần

// Đảm bảo session_start() đã được gọi ở đầu trang chính (ví dụ: index.php, product.php)
// Và $conn (kết nối DB) đã được thiết lập.

$user_id = $_SESSION['user_id'] ?? 0; // Lấy user_id nếu đã đăng nhập

// Nếu chưa đăng nhập, giỏ hàng coi như 0 mục
if ($user_id == 0) {
    $cart_count = 0;
} else {
    // Truy vấn CHỈ để đếm số lượng mục hàng trong gio_hang
    $sql_count = "SELECT COUNT(id) AS cart_count FROM gio_hang WHERE user_id = $user_id";

    // Lưu ý: Đảm bảo $conn (kết nối DB) đã được khai báo và có sẵn
    if (isset($conn)) {
        $result_count = mysqli_query($conn, $sql_count);
        $row_count = mysqli_fetch_assoc($result_count);
        $cart_count = intval($row_count['cart_count']);
    } else {
        $cart_count = 0; // Đặt về 0 nếu kết nối DB bị lỗi
    }
}
// Biến $cart_count đã sẵn sàng để sử dụng
