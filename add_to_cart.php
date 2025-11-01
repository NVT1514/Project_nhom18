<?php
include "Database/connectdb.php";
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập trước khi thêm vào giỏ hàng!'); window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$san_pham_id = intval($_POST['id']);
$so_luong = intval($_POST['quantity']);
$size = $_POST['size'] ?? 'M'; // nếu không có chọn size thì mặc định là M

// Kiểm tra sản phẩm có tồn tại trong DB không
$sql_check_sp = "SELECT * FROM san_pham WHERE id = $san_pham_id LIMIT 1";
$result_sp = mysqli_query($conn, $sql_check_sp);
if (!$result_sp || mysqli_num_rows($result_sp) == 0) {
    echo "<script>alert('Sản phẩm không tồn tại!'); history.back();</script>";
    exit();
}

// Kiểm tra sản phẩm đã có trong giỏ hàng chưa
$sql_check = "SELECT * FROM gio_hang WHERE user_id = $user_id AND san_pham_id = $san_pham_id AND size = '$size'";
$result = mysqli_query($conn, $sql_check);

if (mysqli_num_rows($result) > 0) {
    // Nếu đã có thì cập nhật số lượng
    $sql_update = "UPDATE gio_hang SET so_luong = so_luong + $so_luong 
                   WHERE user_id = $user_id AND san_pham_id = $san_pham_id AND size = '$size'";
    mysqli_query($conn, $sql_update);
} else {
    // Nếu chưa có thì thêm mới
    $sql_insert = "INSERT INTO gio_hang (user_id, san_pham_id, so_luong, size)
                   VALUES ($user_id, $san_pham_id, $so_luong, '$size')";
    mysqli_query($conn, $sql_insert);
}

// Thông báo và quay lại
echo "<script>
    alert('✅ Đã thêm sản phẩm vào giỏ hàng!');
    window.location.href = 'chitietsanpham.php?id=$san_pham_id';
</script>";
exit();
