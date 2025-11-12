<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================
// ✅ Lấy thông tin người dùng từ session
// ==========================
$ho_ten_display = !empty($_SESSION['ho_ten']) ? htmlspecialchars($_SESSION['ho_ten']) : "Người dùng";
$username_display = !empty($_SESSION['tk']) ? htmlspecialchars($_SESSION['tk']) : "Khách";
$role_display = !empty($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : "user";
$avatar_path = !empty($_SESSION['avatar'])
    ? htmlspecialchars($_SESSION['avatar'])
    : "https://cdn-icons-png.flaticon.com/512/149/149071.png";
?>

<!-- ========================== -->
<!-- ✅ CSS -->
<!-- ========================== -->
<link rel="stylesheet" href="css/sidebar_user.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<!-- ========================== -->
<!-- ✅ Sidebar -->
<!-- ========================== -->
<div class="sidebar">
    <div class="user-info-container">
        <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="sidebar-avatar">
        <h2 class="ho-ten-display"><?php echo $ho_ten_display; ?></h2>
        <p class="username-display">@<?php echo $username_display; ?></p>
        <span class="role-badge">
            <i class="fa fa-circle-user"></i> <?php echo ucfirst($role_display); ?>
        </span>
    </div>

    <ul>
        <li>
            <i class="fa fa-user"></i>
            <a href="profile.php">Hồ sơ cá nhân</a>
        </li>
        <li>
            <i class="fa fa-history"></i>
            <a href="lich_su_mua_hang.php">Lịch sử mua hàng</a>
        </li>
        <li>
            <i class="fa fa-box"></i>
            <a href="trang_thai_don_hang.php">Đơn hàng</a>
        </li>
        <li>
            <i class="fa fa-shopping-cart"></i>
            <a href="cart.php">Giỏ hàng</a>
        </li>
        <li>
            <i class="fa fa-sign-out-alt"></i>
            <a href="login.php">Đăng xuất</a>
        </li>
    </ul>
</div>

<!-- ========================== -->
<!-- ✅ JS Active Highlight -->
<!-- ========================== -->
<script>
    const currentUrl = window.location.href;
    document.querySelectorAll('.sidebar ul li a').forEach(link => {
        const li = link.parentElement;
        const href = link.getAttribute('href');
        if (currentUrl.includes(href)) {
            document.querySelectorAll('.sidebar ul li').forEach(l => l.classList.remove('active'));
            li.classList.add('active');
        }
    });
</script>