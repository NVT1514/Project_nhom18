<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// === LẤY DỮ LIỆU NGƯỜI DÙNG TỪ SESSION ===
$ho_ten_display = htmlspecialchars($_SESSION['ho_ten'] ?? 'Quản trị viên');
$username_display = htmlspecialchars($_SESSION['tk'] ?? 'Admin');
$avatar_path = !empty($_SESSION['avatar'])
    ? htmlspecialchars($_SESSION['avatar'])
    : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
?>

<link rel="stylesheet" href="../css/sidebar.css">

<!-- Sidebar cố định -->
<div class="sidebar">
    <div class="user-info-container">
        <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="sidebar-avatar">
        <h2 class="ho-ten-display"><?php echo $ho_ten_display; ?></h2>
        <p class="username-display"><?php echo $username_display; ?></p>
        <span class="role-badge">Admin</span>
    </div>

    <ul class="sidebar-menu">
        <li><i class="fa fa-chart-column"></i><a href="thong_ke.php">Thống kê</a></li>
        <li><i class="fa fa-users"></i><a href="quanlinguoidung_admin.php">Quản lí người dùng</a></li>
        <li><i class="fa fa-truck"></i><a href="quan_ly_don_hang.php">Quản lí đơn hàng</a></li>
        <li><i class="fa fa-product-hunt"></i><a href="themsanpham.php">Thêm sản phẩm</a></li>
        <li><i class="fa fa-box"></i><a href="phanloaisanpham.php">Phân loại sản phẩm</a></li>
        <li><i class="fa fa-warehouse"></i><a href="khohang.php">Kho hàng</a></li>
        <li><i class="fa fa-image"></i><a href="quan_ly_banner.php">Quản lí Banner</a></li>
        <li><i class="fa fa-tags"></i><a href="admin_voucher.php">Quản lí Voucher</a></li>
        <li><i class="fa fa-sign-out-alt"></i><a href="login.php">Đăng xuất</a></li>
    </ul>
</div>

<script>
    // 🔹 Đánh dấu menu đang active
    const currentUrl = window.location.href;
    document.querySelectorAll('.sidebar ul li a').forEach(link => {
        const li = link.parentElement;
        if (currentUrl.includes(link.getAttribute('href'))) {
            document.querySelectorAll('.sidebar ul li').forEach(l => l.classList.remove('active'));
            li.classList.add('active');
        }
    });
</script>