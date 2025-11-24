<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php"; // Đảm bảo đường dẫn file kết nối CSDL là chính xác

// Chức năng: Truy vấn dữ liệu thống kê cho trang tổng quan
// Sử dụng các mã trạng thái mở rộng để phù hợp với 6 thẻ
// 1: Chờ lấy hàng | 4: Đã lấy hàng | 5: Đang giao hàng | 6: Chờ giao lại | 2: Đã hoàn hàng (Thành công) | 3: Đã hủy

$stats_sql = "
    SELECT
        -- 1. Chờ lấy hàng (status = 1)
        COUNT(CASE WHEN status = 1 THEN 1 END) AS cho_lay_hang_count,
        SUM(CASE WHEN status = 1 AND payment_method = 'cod' THEN total ELSE 0 END) AS cho_lay_hang_cod,
        SUM(CASE WHEN status = 1 THEN total ELSE 0 END) AS cho_lay_hang_total,

        -- 2. Đã lấy hàng (status = 4)
        COUNT(CASE WHEN status = 4 THEN 1 END) AS da_lay_hang_count,
        SUM(CASE WHEN status = 4 AND payment_method = 'cod' THEN total ELSE 0 END) AS da_lay_hang_cod,
        SUM(CASE WHEN status = 4 THEN total ELSE 0 END) AS da_lay_hang_total,

        -- 3. Đang giao hàng (status = 5)
        COUNT(CASE WHEN status = 5 THEN 1 END) AS dang_giao_hang_count,
        SUM(CASE WHEN status = 5 AND payment_method = 'cod' THEN total ELSE 0 END) AS dang_giao_hang_cod,
        SUM(CASE WHEN status = 5 THEN total ELSE 0 END) AS dang_giao_hang_total,

        -- 4. Chờ giao lại (status = 6)
        COUNT(CASE WHEN status = 6 THEN 1 END) AS cho_giao_lai_count,
        SUM(CASE WHEN status = 6 AND payment_method = 'cod' THEN total ELSE 0 END) AS cho_giao_lai_cod,
        SUM(CASE WHEN status = 6 THEN total ELSE 0 END) AS cho_giao_lai_total,

        -- 5. Đã hoàn hàng (status = 2 - Thành công)
        COUNT(CASE WHEN status = 2 THEN 1 END) AS da_hoan_hang_count,
        SUM(CASE WHEN status = 2 AND payment_method = 'cod' THEN total ELSE 0 END) AS da_hoan_hang_cod,
        SUM(CASE WHEN status = 2 THEN total ELSE 0 END) AS da_hoan_hang_total,

        -- 6. Đã hủy/Hoàn (status = 3 - Hủy/Hoàn trả)
        COUNT(CASE WHEN status = 3 THEN 1 END) AS da_huy_count,
        -- Đơn hủy thường không có COD/Tổng cộng đáng kể, nhưng vẫn tính nếu có
        SUM(CASE WHEN status = 3 AND payment_method = 'cod' THEN total ELSE 0 END) AS da_huy_cod,
        SUM(CASE WHEN status = 3 THEN total ELSE 0 END) AS da_huy_total,

        -- Tổng cộng
        COUNT(id) AS total_orders
    FROM don_hang
";

$stats_result = $conn->query($stats_sql);
$data = $stats_result->fetch_assoc();

// Hàm định dạng tiền tệ
function format_currency($amount)
{
    return number_format($amount, 0, ',', '.') . '₫';
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tổng quan vận chuyển</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* CSS Cơ bản */
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f6f8fa;
            margin: 0;
            display: flex;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        /* ==== TOP BAR ==== */
        .topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 245px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 25px;
            border-radius: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 0;
            z-index: 100;
        }

        .main-content {
            padding-top: 100px;
        }

        .search-box h1 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin: 0;
        }

        .user-box {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-box img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        /* ==== USER DROPDOWN ==== */
        .user-menu {
            position: relative;
        }

        .user-menu-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            color: #2c3e50;
        }

        .user-menu-btn:hover {
            background: #f1f3f6;
        }

        .user-menu-btn img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .dropdown-menu {
            position: absolute;
            top: 60px;
            right: 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .user-menu.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a,
        .dropdown-menu button {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 12px 16px;
            border: none;
            background: transparent;
            color: #898c95ff;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f3f3f3;
            text-align: left;
        }

        .dropdown-menu a:first-child,
        .dropdown-menu button:first-child {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .dropdown-menu a:last-child,
        .dropdown-menu button:last-child {
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            border-bottom: none;
        }

        .dropdown-menu a:hover,
        .dropdown-menu button:hover {
            background: #f1f3f6;
        }

        .dropdown-menu a i,
        .dropdown-menu button i {
            width: 20px;
            font-size: 1.1rem;
            color: #898c95ff;
        }

        .dropdown-menu button {
            color: #898c95ff;
        }

        .dropdown-menu button i {
            color: #898c95ff;
        }


        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        h2.title {
            color: #333;
            font-size: 24px;
            margin-bottom: 25px;
            font-weight: 600;
        }

        /* --- Thanh Bộ lọc (Tùy chỉnh để giống mẫu) --- */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .filter-bar button,
        .filter-bar select {
            padding: 8px 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background-color: #fff;
            cursor: pointer;
            font-size: 14px;
        }

        .filter-bar .date-range-btn {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            font-weight: 600;
        }

        .connect-btn {
            margin-left: auto;
            background-color: #007bff;
            color: white;
            border: none;
            font-weight: 600;
            padding: 10px 20px;
        }

        .connect-btn:hover {
            background-color: #0056b3;
        }

        /* --- Thẻ Tổng quan (6 thẻ) --- */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border-top: 4px solid #007bff;
        }

        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 15px;
            color: #495057;
            font-weight: 600;
            white-space: nowrap;
        }

        .summary-card p {
            font-size: 26px;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .cod-info {
            font-size: 13px;
            color: #6c757d;
            display: flex;
            flex-direction: column;
            /* Đổi thành cột để COD và Tổng cộng xuống dòng */
            gap: 2px;
            border-top: 1px dashed #eee;
            padding-top: 5px;
        }

        .cod-value,
        .total-value {
            font-weight: 600;
            color: #333;
        }

        .cod-line,
        .total-line {
            display: flex;
            justify-content: space-between;
        }

        /* Màu cho từng thẻ */
        .card-cho-lay {
            border-top-color: #ff9800;
        }

        .card-da-lay {
            border-top-color: #28a745;
        }

        .card-dang-giao {
            border-top-color: #03a9f4;
        }

        .card-cho-giao-lai {
            border-top-color: #e6e600;
        }

        .card-da-hoan-hang {
            border-top-color: #4caf50;
        }

        .card-da-huy {
            border-top-color: #dc3545;
        }

        /* --- Biểu đồ lớn (Placeholders) --- */
        .chart-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            min-height: 250px;
        }

        .chart-card h3 {
            font-size: 16px;
            color: #333;
            margin: 0 0 15px 0;
            font-weight: 600;
        }

        .placeholder {
            text-align: center;
            padding: 30px 0;
            color: #aaa;
            font-style: italic;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .placeholder i {
            font-size: 40px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php include "sidebar_admin.php"; ?>

    <div class="main-content">
        <div class="dashboard-container">
            <div class="topbar">
                <div class="search-box">
                    <h1>Tổng quan vận chuyển</h1>
                </div>
                <div class="user-box">
                    <i class="fa-regular fa-bell"></i>
                    <div class="user-menu">
                        <button class="user-menu-btn" onclick="toggleUserMenu()">
                            <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Avatar">
                            <span><?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="admin.php">
                                <i class="fa-solid fa-user"></i>
                                <span>Tài khoản của tôi</span>
                            </a>
                            <a href="#">
                                <i class="fa-solid fa-file-upload"></i>
                                <span>Lịch sử xuất nhập file</span>
                            </a>
                            <button onclick="logoutUser()">
                                <i class="fa-solid fa-sign-out-alt"></i>
                                <span>Đăng xuất</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="filter-bar">
                <button class="date-range-btn"><i class="fa-solid fa-calendar-alt"></i> 7 ngày qua (Tùy chỉnh)</button>
                <select>
                    <option>Tất cả chi nhánh</option>
                </select>
                <select>
                    <option>Khu vực</option>
                </select>
                <button class="connect-btn"><i class="fa-solid fa-shipping-fast"></i> Kết nối vận chuyển</button>
            </div>

            <div class="summary-cards">

                <div class="summary-card card-cho-lay">
                    <h3>Chờ lấy hàng</h3>
                    <p><?= htmlspecialchars($data['cho_lay_hang_count']) ?></p>
                    <div class="cod-info">
                        <span class="cod-line">COD: <span class="cod-value"><?= format_currency($data['cho_lay_hang_cod']) ?></span></span>
                        <span class="total-line">Tổng: <span class="total-value"><?= format_currency($data['cho_lay_hang_total']) ?></span></span>
                    </div>
                </div>

                <div class="summary-card card-da-lay">
                    <h3>Đã lấy hàng</h3>
                    <p><?= htmlspecialchars($data['da_lay_hang_count']) ?></p>
                    <div class="cod-info">
                        <span class="cod-line">COD: <span class="cod-value"><?= format_currency($data['da_lay_hang_cod']) ?></span></span>
                        <span class="total-line">Tổng: <span class="total-value"><?= format_currency($data['da_lay_hang_total']) ?></span></span>
                    </div>
                </div>

                <div class="summary-card card-dang-giao">
                    <h3>Đang giao hàng</h3>
                    <p><?= htmlspecialchars($data['dang_giao_hang_count']) ?></p>
                    <div class="cod-info">
                        <span class="cod-line">COD: <span class="cod-value"><?= format_currency($data['dang_giao_hang_cod']) ?></span></span>
                        <span class="total-line">Tổng: <span class="total-value"><?= format_currency($data['dang_giao_hang_total']) ?></span></span>
                    </div>
                </div>

                <div class="summary-card card-cho-giao-lai">
                    <h3>Chờ giao lại</h3>
                    <p><?= htmlspecialchars($data['cho_giao_lai_count']) ?></p>
                    <div class="cod-info">
                        <span class="cod-line">COD: <span class="cod-value"><?= format_currency($data['cho_giao_lai_cod']) ?></span></span>
                        <span class="total-line">Tổng: <span class="total-value"><?= format_currency($data['cho_giao_lai_total']) ?></span></span>
                    </div>
                </div>

                <div class="summary-card card-da-hoan-hang">
                    <h3>Đã hoàn hàng</h3>
                    <p><?= htmlspecialchars($data['da_hoan_hang_count']) ?></p>
                    <div class="cod-info">
                        <span class="cod-line">COD: <span class="cod-value"><?= format_currency($data['da_hoan_hang_cod']) ?></span></span>
                        <span class="total-line">Tổng: <span class="total-value"><?= format_currency($data['da_hoan_hang_total']) ?></span></span>
                    </div>
                </div>

                <div class="summary-card card-da-huy">
                    <h3>Đã hủy</h3>
                    <p><?= htmlspecialchars($data['da_huy_count']) ?></p>
                    <div class="cod-info">
                        <span class="cod-line">COD: <span class="cod-value"><?= format_currency($data['da_huy_cod']) ?></span></span>
                        <span class="total-line">Tổng: <span class="total-value"><?= format_currency($data['da_huy_total']) ?></span></span>
                    </div>
                </div>
            </div>

            <div class="chart-row">
                <div class="chart-card">
                    <h3>Thời gian lấy hàng thành công trung bình</h3>
                    <div class="placeholder">
                        <i class="fa-solid fa-chart-pie"></i>
                        <p>Chưa có dữ liệu báo cáo</p>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Thời gian giao hàng thành công trung bình</h3>
                    <div class="placeholder">
                        <i class="fa-solid fa-chart-pie"></i>
                        <p>Chưa có dữ liệu báo cáo</p>
                    </div>
                </div>
            </div>

            <div class="chart-row">
                <div class="chart-card">
                    <h3>Tỉ lệ giao hàng thành công</h3>
                    <div class="placeholder">
                        <p>Tổng đơn hàng: **<?= htmlspecialchars($data['total_orders']) ?>**</p>
                        <p>Chưa có dữ liệu báo cáo</p>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Tỉ trọng vận đơn</h3>
                    <div class="placeholder">
                        <p>Chưa có dữ liệu báo cáo</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Toggle user dropdown menu
        function toggleUserMenu() {
            const userMenu = document.querySelector('.user-menu');
            userMenu.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const userBtn = document.querySelector('.user-menu-btn');
            if (!userMenu.contains(event.target) && !userBtn.contains(event.target)) {
                userMenu.classList.remove('active');
            }
        });

        // Logout function
        function logoutUser() {
            if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                window.location.href = 'login.php';
            }
        }
    </script>
</body>

</html>