<?php
// Đảm bảo session được bắt đầu trước khi truy cập $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Lấy thông tin từ SESSION
$username_display = isset($_SESSION['tk']) ? htmlspecialchars($_SESSION['tk']) : 'Guest';
$ho_ten_display = isset($_SESSION['ho_ten']) ? htmlspecialchars($_SESSION['ho_ten']) : 'Tên người dùng';

// 2. Lấy đường dẫn avatar hoặc dùng ảnh mặc định
$avatar_path = isset($_SESSION['avatar']) && $_SESSION['avatar']
    ? htmlspecialchars($_SESSION['avatar'])
    : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; // Ảnh mặc định
?>
<link rel="stylesheet" href="../css/sidebar.css">

<div class="sidebar">
    <div class="user-info-container">
        <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="sidebar-avatar">
        <h2 class="ho-ten-display"><?php echo $ho_ten_display; ?></h2>
        <p class="username-display"><?php echo $username_display; ?></p>
    </div>

    <ul>
        <li><i class="fa fa-home"></i><a href="admin.php">Hồ sơ</a></li>
        <li><i class="fa fa-chart-bar"></i><a href="thong_ke.php">Thống kê</a></li>
        <li><i class="fa fa-map"></i><a href="google_map.php">Google Map</a></li>
        <li><i class="fa fa-users"></i><a href="quanlinguoidung_admin.php">Quản lí người dùng</a></li>
        <li><i class="fa fa-product-hunt"></i><a href="themsanpham.php">Thêm sản phẩm</a></li>
        <li><i class="fa fa-box"></i><a href="phanloaisanpham.php">Phân loại sản phẩm</a></li>
        <li><i class="fa fa-warehouse"></i><a href="khohang.php">Kho hàng</a></li>
        <li><i class="fa fa-sign-out-alt"></i><a href="login.php">Đăng xuất</a></li>
    </ul>
</div>

<script>
    // Đánh dấu mục đang active
    const currentUrl = window.location.href;
    document.querySelectorAll('.sidebar ul li a').forEach(link => {
        const li = link.parentElement;
        if (currentUrl.includes(link.getAttribute('href'))) {
            document.querySelectorAll('.sidebar ul li').forEach(l => l.classList.remove('active'));
            li.classList.add('active');
        }
    });
</script>