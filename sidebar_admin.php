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

<link rel="stylesheet" href="css/sidebar.css">

<!-- Sidebar cố định -->
<div class="sidebar">
    <div class="user-info-container">
        <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="sidebar-avatar">
        <h2 class="ho-ten-display"><?php echo $ho_ten_display; ?></h2>
        <p class="username-display"><?php echo $username_display; ?></p>
        <span class="role-badge">Admin</span>
    </div>

    <ul class="sidebar-menu">
        <li>
            <div class="menu-header">
                <i class="fa fa-home"></i>
                <a href="thong_ke.php">Thống kê</a>
            </div>
        </li>

        <li>
            <div class="menu-header">
                <i class="fa fa-users"></i>
                <span>Quản lý người dùng</span>
                <i class="fa fa-chevron-right toggle-submenu"></i>
            </div>
            <ul class="submenu">
                <li><a href="quanlinguoidung_admin.php">Quản lý Admin</a></li>
                <li><a href="quanliuser.php">Quản lý khách hàng</a></li>
            </ul>
        </li>

        <li>
            <div class="menu-header">
                <i class="fa fa-clipboard"></i>
                <span>Đơn hàng</span>
                <i class="fa fa-chevron-right toggle-submenu"></i>
            </div>
            <ul class="submenu">
                <li><a href="quan_ly_don_hang.php">Danh sách đơn hàng</a></li>
                <li><a href="van_chuyen.php">Vận chuyển</a></li>
            </ul>
        </li>

        <li>
            <div class="menu-header">
                <i class="fa fa-boxes-stacked"></i>
                <a href="quan_ly_san_pham.php">Sản phẩm</a>
            </div>
        </li>

        <li>
            <div class="menu-header">
                <i class="fa fa-warehouse"></i>
                <span>Kho hàng</span>
                <i class="fa fa-chevron-right toggle-submenu"></i>
            </div>
            <ul class="submenu">
                <li><a href="quan_ly_kho_hang.php">Tồn kho</a></li>
                <li><a href="nhap_kho.php">Nhập kho</a></li>
                <li><a href="xuat_kho.php">Xuất kho</a></li>
                <li><a href="kiem_ke.php">Kiểm kê</a></li>
            </ul>
        </li>

        <li>
            <div class="menu-header">
                <i class="fa fa-sitemap"></i>
                <a href="quan_ly_phan_loai.php">Quản lý danh mục</a>
            </div>
        </li>

        <li>
            <div class="menu-header">
                <i class="fa fa-home"></i>
                <a href="quan_ly_banner.php">Banner</a>
            </div>
        </li>

        <li>
            <div class="menu-header">
                <i class="fa fa-ticket-simple"></i>
                <a href="quan_ly_voucher.php">Voucher</a>
            </div>
        </li>
    </ul>

    <!-- Trang khách hàng cố định ở dưới cùng -->
    <ul class="sidebar-menu sidebar-bottom">
        <li>
            <div class="menu-header">
                <i class="fa fa-globe"></i>
                <a href="maincustomer.php">Giao diện web</a>
            </div>
        </li>
    </ul>
</div>

<script>
    // 🔹 Xử lý menu cấp 2
    document.querySelectorAll('.toggle-submenu').forEach(toggle => {
        toggle.parentElement.addEventListener('click', function(e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;
            const menuItem = this.closest('li');

            // Xóa class active từ tất cả menu-header
            document.querySelectorAll('.sidebar ul li>.menu-header.active').forEach(header => {
                header.classList.remove('active');
            });

            // Toggle class
            menuItem.classList.toggle('active-submenu');
            submenu.classList.toggle('show');

            // Đóng các submenu khác
            document.querySelectorAll('.sidebar ul li.active-submenu').forEach(item => {
                if (item !== menuItem) {
                    item.classList.remove('active-submenu');
                    item.querySelector('.submenu').classList.remove('show');
                }
            });
        });
    });

    // 🔹 Đánh dấu menu đang active
    const currentUrl = window.location.href;
    document.querySelectorAll('.sidebar ul li a').forEach(link => {
        if (currentUrl.includes(link.getAttribute('href'))) {
            const li = link.closest('li');
            const menuHeader = li.querySelector('.menu-header');

            // Nếu là submenu, mở parent
            if (li.closest('.submenu')) {
                const parentLi = li.closest('.submenu').parentElement;
                parentLi.classList.add('active-submenu');
                parentLi.querySelector('.submenu').classList.add('show');
            }

            li.classList.add('active');
            if (menuHeader) {
                menuHeader.classList.add('active');
            }
        }
    });
</script>