<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php"; // Đảm bảo đường dẫn file kết nối CSDL là chính xác

// Hàm định dạng tiền tệ
function format_currency($amount)
{
    return number_format($amount, 0, ',', '.') . '₫';
}

// =======================================================
// I. TRUY VẤN DỮ LIỆU THẺ TỔNG QUAN VẬN CHUYỂN (TỪ PHẦN 1)
// =======================================================
// Mã trạng thái mở rộng cho vận chuyển: 
// 1: Chờ lấy hàng | 4: Đã lấy hàng | 5: Đang giao hàng | 6: Chờ giao lại | 2: Đã hoàn hàng (Thành công) | 3: Đã hủy

$stats_sql = "
    SELECT
        COUNT(CASE WHEN status = 1 THEN 1 END) AS cho_lay_hang_count,
        SUM(CASE WHEN status = 1 AND payment_method = 'cod' THEN total ELSE 0 END) AS cho_lay_hang_cod,
        SUM(CASE WHEN status = 1 THEN total ELSE 0 END) AS cho_lay_hang_total,

        COUNT(CASE WHEN status = 4 THEN 1 END) AS da_lay_hang_count,
        SUM(CASE WHEN status = 4 AND payment_method = 'cod' THEN total ELSE 0 END) AS da_lay_hang_cod,
        SUM(CASE WHEN status = 4 THEN total ELSE 0 END) AS da_lay_hang_total,

        COUNT(CASE WHEN status = 5 THEN 1 END) AS dang_giao_hang_count,
        SUM(CASE WHEN status = 5 AND payment_method = 'cod' THEN total ELSE 0 END) AS dang_giao_hang_cod,
        SUM(CASE WHEN status = 5 THEN total ELSE 0 END) AS dang_giao_hang_total,

        COUNT(CASE WHEN status = 6 THEN 1 END) AS cho_giao_lai_count,
        SUM(CASE WHEN status = 6 AND payment_method = 'cod' THEN total ELSE 0 END) AS cho_giao_lai_cod,
        SUM(CASE WHEN status = 6 THEN total ELSE 0 END) AS cho_giao_lai_total,

        COUNT(CASE WHEN status = 2 THEN 1 END) AS da_hoan_hang_count,
        SUM(CASE WHEN status = 2 AND payment_method = 'cod' THEN total ELSE 0 END) AS da_hoan_hang_cod,
        SUM(CASE WHEN status = 2 THEN total ELSE 0 END) AS da_hoan_hang_total,

        COUNT(CASE WHEN status = 3 THEN 1 END) AS da_huy_count,
        SUM(CASE WHEN status = 3 AND payment_method = 'cod' THEN total ELSE 0 END) AS da_huy_cod,
        SUM(CASE WHEN status = 3 THEN total ELSE 0 END) AS da_huy_total,

        COUNT(id) AS total_orders
    FROM don_hang
";
$stats_result = $conn->query($stats_sql);
$data = $stats_result->fetch_assoc();


// =======================================================
// II. TRUY VẤN DỮ LIỆU BIỂU ĐỒ (TỪ PHẦN 2 - 5 TRẠNG THÁI CHÍNH)
// =======================================================
$statuses_chart = [
    // Đây là 5 trạng thái được sử dụng trong Biểu đồ tròn
    0 => ['text' => 'Đã hủy', 'color' => '#dc3545'],
    1 => ['text' => 'Chờ xác nhận/lấy hàng', 'color' => '#ff9800'],
    2 => ['text' => 'Đang chuẩn bị hàng/Đã hoàn', 'color' => '#6c757d'],
    3 => ['text' => 'Đang giao', 'color' => '#03a9f4'],
    4 => ['text' => 'Đã giao', 'color' => '#4caf50']
];
ksort($statuses_chart);

$chart_labels = [];
$chart_counts = [];
$chart_colors = [];
$stats_data_chart = []; // Lưu trữ count và total cho bảng thống kê

$sql_stats = "SELECT status, COUNT(id) AS count, SUM(total) as total, SUM(CASE WHEN payment_method = 'cod' THEN total ELSE 0 END) as cod FROM don_hang GROUP BY status";
$result_stats = mysqli_query($conn, $sql_stats);

if ($result_stats) {
    while ($row = mysqli_fetch_assoc($result_stats)) {
        $status_key = intval($row['status']);
        $stats_data_chart[$status_key] = [
            'count' => $row['count'],
            'total' => $row['total'],
            'cod' => $row['cod']
        ];
    }
}
// Chuyển dữ liệu sang mảng Biểu đồ (chỉ lấy các status có trong $statuses_chart)
foreach ($statuses_chart as $key => $info) {
    $count = $stats_data_chart[$key]['count'] ?? 0;

    $chart_labels[] = $info['text'] . " ($count)";
    $chart_counts[] = $count;
    $chart_colors[] = $info['color'];
}

$js_labels = json_encode($chart_labels);
$js_counts = json_encode($chart_counts);
$js_colors = json_encode($chart_colors);

// Đóng kết nối
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tổng quan vận chuyển</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS DÙNG LẠI TỪ PHẦN 1 */
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f6f8fa;
            margin: 0;
            display: flex;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            padding-top: 100px;
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
            object-fit: cover;
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

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

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

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            /* Chia đều cho 5 cột */
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

        /* CSS MỚI CHO BIỂU ĐỒ & BẢNG */
        .chart-container {
            position: relative;
            height: 250px;
        }

        .cod-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .cod-table th,
        .cod-table td {
            padding: 8px 10px;
            text-align: left;
            font-size: 0.9rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .cod-table th {
            background-color: #f9f9f9;
            color: #555;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }

        .cod-table .cod-value {
            color: #dc3545;
            font-weight: 600;
        }

        .cod-table .total-value {
            color: #007bff;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <?php include "sidebar_admin.php"; ?>

    <div class="main-content">
        <div class="dashboard-container">
            <div class="topbar">
                <div class="search-box">
                    <h1>Vận chuyển</h1>
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
                    <h3>Chờ xác nhận</h3>
                    <p><?= htmlspecialchars($data['cho_lay_hang_count']) ?></p>
                    <div class="cod-info">
                        <span class="cod-line">COD: <span class="cod-value"><?= format_currency($data['cho_lay_hang_cod']) ?></span></span>
                        <span class="total-line">Tổng: <span class="total-value"><?= format_currency($data['cho_lay_hang_total']) ?></span></span>
                    </div>
                </div>

                <div class="summary-card card-da-hoan-hang">
                    <h3>Đang chuẩn bị hàng</h3>
                    <p><?= htmlspecialchars($data['da_hoan_hang_count']) ?></p>
                    <div class="cod-info">
                        <span class="cod-line">COD: <span class="cod-value"><?= format_currency($data['da_hoan_hang_cod']) ?></span></span>
                        <span class="total-line">Tổng: <span class="total-value"><?= format_currency($data['da_hoan_hang_total']) ?></span></span>
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

                <div class="summary-card card-da-lay">
                    <h3>Đã giao hàng</h3>
                    <p><?= htmlspecialchars($data['da_lay_hang_count']) ?></p>
                    <div class="cod-info">
                        <span class="cod-line">COD: <span class="cod-value"><?= format_currency($data['da_lay_hang_cod']) ?></span></span>
                        <span class="total-line">Tổng: <span class="total-value"><?= format_currency($data['da_lay_hang_total']) ?></span></span>
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
                    <h3>Tỉ lệ đơn hàng theo Trạng thái (Tổng quan)</h3>
                    <div class="chart-container">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>💰 Thống kê COD & Giá trị đơn hàng</h3>
                    <div style="height: 250px; overflow-y: auto;">
                        <table class="cod-table">
                            <thead>
                                <tr>
                                    <th>Trạng thái</th>
                                    <th>Đơn hàng</th>
                                    <th>COD (Phải thu)</th>
                                    <th>Tổng giá trị</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statuses_chart as $key => $info):
                                    $count = $stats_data_chart[$key]['count'] ?? 0;
                                    $cod = $stats_data_chart[$key]['cod'] ?? 0;
                                    $total = $stats_data_chart[$key]['total'] ?? 0;
                                ?>
                                    <tr>
                                        <td><?= $info['text'] ?></td>
                                        <td><?= number_format($count) ?></td>
                                        <td class="cod-value"><?= format_currency($cod) ?></td>
                                        <td class="total-value"><?= format_currency($total) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

        // --- VẼ BIỂU ĐỒ TRÒN (TỪ PHẦN 2) ---
        const labels = <?= $js_labels ?>;
        const data = <?= $js_counts ?>;
        const colors = <?= $js_colors ?>;

        const ctx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20
                        }
                    },
                    title: {
                        display: false
                    }
                }
            }
        });

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