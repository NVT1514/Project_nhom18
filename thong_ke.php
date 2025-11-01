<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
include "Database/connectdb.php";

// ================= KIỂM TRA QUYỀN ADMIN =================
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'superadmin')) {
    echo "<script>alert('Bạn không có quyền truy cập trang này!'); window.location.href='login.php';</script>";
    exit();
}


// =================== LẤY DỮ LIỆU THỐNG KÊ ===================
$sql_orders = "SELECT COUNT(*) AS total_orders FROM don_hang";
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, $sql_orders))['total_orders'] ?? 0;

$sql_revenue = "SELECT SUM(total) AS total_revenue FROM don_hang";
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, $sql_revenue))['total_revenue'] ?? 0;

$sql_users = "SELECT COUNT(*) AS total_users FROM user WHERE role='user'";
$total_users = mysqli_fetch_assoc(mysqli_query($conn, $sql_users))['total_users'] ?? 0;

$sql_products = "SELECT COUNT(*) AS total_products FROM san_pham";
$total_products = mysqli_fetch_assoc(mysqli_query($conn, $sql_products))['total_products'] ?? 0;

// =================== ĐƠN HÀNG GẦN ĐÂY ===================
$sql_recent_orders = "
    SELECT dh.id, dh.order_id, dh.fullname, dh.total, dh.status, dh.created_at, u.Ho_Ten AS ten_nguoi_dung
    FROM don_hang dh
    LEFT JOIN user u ON dh.user_id = u.id
    ORDER BY dh.created_at DESC
    LIMIT 5
";
$recent_orders = mysqli_query($conn, $sql_recent_orders);

// =================== DỮ LIỆU BIỂU ĐỒ ===================
$sql_chart = "
    SELECT DATE_FORMAT(created_at, '%m/%Y') AS month, SUM(total) AS revenue
    FROM don_hang
    GROUP BY DATE_FORMAT(created_at, '%m/%Y')
    ORDER BY MIN(created_at)
";
$result_chart = mysqli_query($conn, $sql_chart);

$months = [];
$revenues = [];
while ($row = mysqli_fetch_assoc($result_chart)) {
    $months[] = $row['month'];
    $revenues[] = (float)$row['revenue'];
}

$months_json = json_encode($months);
$revenues_json = json_encode($revenues);

// =================== TOP SELLER ===================
$sql_top_seller = "
    SELECT 
        ten_san_pham, 
        hinh_anh, 
        so_luong_ban, 
        (so_luong_ban * gia) AS total_revenue
    FROM san_pham
    ORDER BY so_luong_ban DESC
    LIMIT 5
";
$top_seller = mysqli_query($conn, $sql_top_seller);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng thống kê</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6fa;
            margin: 0;
        }

        .container {
            display: flex;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            padding-top: 0%;
            transition: all 0.3s ease;
        }

        /* ==== TOP BAR ==== */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #f1f3f6;
            border-radius: 8px;
            padding: 8px 12px;
            width: 400px;
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            margin-left: 8px;
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

        /* ==== OVERVIEW CARDS ==== */
        .overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
        }

        .card h4 {
            color: #777;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .card .value {
            font-size: 1.6rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .trend {
            font-size: 0.85rem;
            color: #27ae60;
        }

        /* ==== CHART + TOP SELLER ==== */
        .chart-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .chart-container,
        .top-seller {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .chart-container h3,
        .top-seller h3 {
            margin-bottom: 15px;
            font-size: 1.2rem;
            color: #2c3e50;
        }

        /* ==== BẢNG TOP SELLER ==== */
        .top-seller table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-seller th,
        .top-seller td {
            padding: 10px 8px;
            border-bottom: 1px solid #f3f3f3;
            text-align: left;
            font-size: 0.95rem;
        }

        .top-seller th {
            color: #777;
            font-weight: 500;
        }

        .top-seller td img {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            transition: transform 0.2s ease;
        }

        .top-seller td img:hover {
            transform: scale(1.1);
        }

        /* ==== TOP 1 HIỆU ỨNG NỔI BẬT ==== */
        .top-seller tr.top-1 {
            background: linear-gradient(135deg, #fffbe6, #fff1b8);
            border-left: 4px solid gold;
            animation: topOneEntrance 1.2s ease-out, glowRotate 3s infinite linear;
            transform-origin: center;
            position: relative;
        }

        .top-seller tr.top-1 td {
            font-weight: 600;
            color: #2c3e50;
        }

        .top-seller tr.top-1::before {
            content: "🏆 TOP 1";
            position: absolute;
            top: -10px;
            right: 12px;
            background: gold;
            color: white;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            box-shadow: 0 0 8px rgba(255, 215, 0, 0.7);
        }

        @keyframes topOneEntrance {
            0% {
                opacity: 0;
                transform: scale(0.8) rotate(-3deg);
            }

            60% {
                opacity: 1;
                transform: scale(1.05) rotate(2deg);
            }

            100% {
                transform: scale(1) rotate(0deg);
            }
        }

        @keyframes glowRotate {
            0% {
                box-shadow: 0 0 10px rgba(241, 196, 15, 0.4);
                filter: hue-rotate(0deg);
            }

            50% {
                box-shadow: 0 0 25px rgba(241, 196, 15, 0.7);
                filter: hue-rotate(20deg);
            }

            100% {
                box-shadow: 0 0 10px rgba(241, 196, 15, 0.4);
                filter: hue-rotate(0deg);
            }
        }

        /* ==== RECENT ORDERS & ACTIVITIES ==== */
        .bottom-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .recent-orders,
        .activities {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .recent-orders h3,
        .activities h3 {
            margin-bottom: 15px;
            font-size: 1.2rem;
            color: #2c3e50;
        }

        /* ==== FIX GIAO DIỆN RECENT ORDERS ==== */
        .recent-orders table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-orders th,
        .recent-orders td {
            padding: 10px 8px;
            border-bottom: 1px solid #f3f3f3;
            text-align: left;
            font-size: 0.95rem;
        }

        .recent-orders th {
            color: #777;
            font-weight: 500;
        }

        .recent-orders tr:hover {
            background-color: #f9fafc;
        }

        .status {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            color: #fff;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 75px;
        }

        .pending {
            background: #f39c12;
        }

        .completed {
            background: #27ae60;
        }

        .canceled {
            background: #e74c3c;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include "sidebar_admin.php"; ?>
        <div class="main-content">
            <h1>Dashboard</h1>
            <div class="topbar">
                <div class="search-box">
                    <i class="fa fa-search"></i>
                    <input type="text" placeholder="Tìm kiếm...">
                </div>
                <div class="user-box">
                    <i class="fa-regular fa-bell"></i>
                    <a href="admin.php"><img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Avatar"></a>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="overview">
                <div class="card">
                    <h4>Tổng đơn hàng</h4>
                    <div class="value"><?= $total_orders; ?></div>
                    <div class="trend">+3.1% so với tháng trước</div>
                </div>
                <div class="card">
                    <h4>Tổng doanh thu</h4>
                    <div class="value"><?= number_format($total_revenue, 0, ',', '.'); ?> ₫</div>
                    <div class="trend">+2.8% tăng trưởng</div>
                </div>
                <div class="card">
                    <h4>Người dùng</h4>
                    <div class="value"><?= $total_users; ?></div>
                    <div class="trend">+1.2% tháng này</div>
                </div>
                <div class="card">
                    <h4>Sản phẩm</h4>
                    <div class="value"><?= $total_products; ?></div>
                    <div class="trend">Cập nhật mới nhất</div>
                </div>
            </div>

            <!-- Chart + Top Seller -->
            <div class="chart-section">
                <div class="chart-container">
                    <h3>Doanh thu hàng tháng</h3>
                    <canvas id="revenueChart"></canvas>
                </div>

                <div class="top-seller">
                    <h3><i class="fa-solid fa-crown"></i> Top Seller</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đã bán</th>
                                <th>Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rank = 0;
                            while ($row = mysqli_fetch_assoc($top_seller)) :
                                $rank++;
                                $topClass = ($rank == 1) ? "top-1" : "";
                            ?>
                                <tr class="<?= $topClass; ?>">
                                    <td style="display:flex;align-items:center;gap:10px;">
                                        <img src="<?= htmlspecialchars($row['hinh_anh']); ?>" alt="<?= htmlspecialchars($row['ten_san_pham']); ?>">
                                        <span><?= htmlspecialchars($row['ten_san_pham']); ?></span>
                                    </td>
                                    <td><?= $row['so_luong_ban']; ?></td>
                                    <td><?= number_format($row['total_revenue'], 0, ',', '.'); ?> ₫</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Orders & Activities -->
            <div class="bottom-section">
                <div class="recent-orders">
                    <h3>Recent Orders</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($recent_orders)) :
                                $status_class = ($row['status'] == 1) ? 'completed' : (($row['status'] == 0) ? 'pending' : 'canceled');
                                $status_text = ($row['status'] == 1) ? 'Hoàn tất' : (($row['status'] == 0) ? 'Đang xử lý' : 'Hủy');
                            ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($row['order_id']); ?></td>
                                    <td><?= htmlspecialchars($row['fullname'] ?? $row['ten_nguoi_dung'] ?? 'Khách'); ?></td>
                                    <td><?= number_format($row['total'], 0, ',', '.'); ?> ₫</td>
                                    <td><span class="status <?= $status_class; ?>"><?= $status_text; ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="activities">
                    <h3>Recent Activities</h3>
                    <div class="activity"><i class="fa fa-user-plus"></i>
                        <div class="activity-details"><span>Người dùng mới: <b>hoangminh</b></span><small>5 phút trước</small></div>
                    </div>
                    <div class="activity"><i class="fa fa-shopping-cart"></i>
                        <div class="activity-details"><span>Đơn hàng mới được hoàn tất</span><small>20 phút trước</small></div>
                    </div>
                    <div class="activity"><i class="fa fa-comment"></i>
                        <div class="activity-details"><span>Người dùng <b>lananh</b> đã bình luận sản phẩm</span><small>1 giờ trước</small></div>
                    </div>
                    <div class="activity"><i class="fa fa-box"></i>
                        <div class="activity-details"><span>5 sản phẩm mới đã được thêm</span><small>2 giờ trước</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ChartJS -->
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const months = <?php echo $months_json; ?>;
        const revenues = <?php echo $revenues_json; ?>;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: revenues,
                    backgroundColor: '#4e73df',
                    borderRadius: 8
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => v.toLocaleString('vi-VN') + ' ₫'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>