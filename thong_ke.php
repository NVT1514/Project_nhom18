<?php
include "Database/connectdb.php"; // Kết nối CSDL

// =================== LẤY SỐ LIỆU THỐNG KÊ ===================

// Tổng số đơn hàng
$sql_orders = "SELECT COUNT(*) AS total_orders FROM orders";
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, $sql_orders))['total_orders'] ?? 0;

// Tổng doanh thu
$sql_revenue = "SELECT SUM(total) AS total_revenue FROM orders";
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, $sql_revenue))['total_revenue'] ?? 0;

// Tổng số người dùng
$sql_users = "SELECT COUNT(*) AS total_users FROM user WHERE role='user'";
$total_users = mysqli_fetch_assoc(mysqli_query($conn, $sql_users))['total_users'] ?? 0;

// Tổng số sản phẩm
$sql_products = "SELECT COUNT(*) AS total_products FROM san_pham";
$total_products = mysqli_fetch_assoc(mysqli_query($conn, $sql_products))['total_products'] ?? 0;

// Bình luận (giả lập nếu chưa có bảng comment)
$total_comments = 54;

// =================== DỮ LIỆU DOANH THU THEO THÁNG ===================
$sql_chart = "
    SELECT 
        DATE_FORMAT(created_at, '%m/%Y') AS month,
        SUM(total) AS revenue,
        COUNT(*) AS orders
    FROM orders
    GROUP BY DATE_FORMAT(created_at, '%m/%Y')
    ORDER BY MIN(created_at)
";

$result_chart = mysqli_query($conn, $sql_chart);

$months = [];
$revenues = [];
$order_counts = [];

while ($row = mysqli_fetch_assoc($result_chart)) {
    $months[] = $row['month'];
    $revenues[] = (float)$row['revenue'];
    $order_counts[] = (int)$row['orders'];
}

$months_json = json_encode($months);
$revenues_json = json_encode($revenues);
$order_counts_json = json_encode($order_counts);

// =================== DỮ LIỆU CHO BIỂU ĐỒ TRÒN ===================
$sql_pie = "
    SELECT phan_loai AS category, COUNT(*) AS total
    FROM san_pham
    GROUP BY phan_loai
";
$result_pie = mysqli_query($conn, $sql_pie);

$categories = [];
$category_counts = [];
while ($row = mysqli_fetch_assoc($result_pie)) {
    $categories[] = $row['category'];
    $category_counts[] = (int)$row['total'];
}

$categories_json = json_encode($categories);
$category_counts_json = json_encode($category_counts);

// =================== TỶ LỆ (Progress Circle) ===================
$percent_comments = 75;
$percent_users = $total_users > 0 ? min(100, round(($total_users / 20) * 100)) : 50;
$percent_visitors = 30;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background: #f7f7f7;
            font-family: "Segoe UI", sans-serif;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .main-content h1 {
            margin: 0 0 30px 0;
            color: #333;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px #e0e0e0;
            flex: 1;
            text-align: center;
        }

        .stat-box i {
            font-size: 2rem;
            color: #2196f3;
            margin-bottom: 10px;
        }

        .stat-box .number {
            font-size: 2rem;
            font-weight: bold;
        }

        .stat-box .desc {
            color: #888;
            font-size: 1rem;
        }

        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .chart {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #e0e0e0;
        }

        canvas {
            width: 100% !important;
            height: 350px !important;
        }

        .progress-cards {
            display: flex;
            gap: 20px;
            margin-top: 30px;
        }

        .progress-card {
            background: #fff;
            border-radius: 10px;
            flex: 1;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px #e0e0e0;
        }

        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 6px solid #2196f3;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px auto;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .progress-card.comments .progress-circle {
            border-color: #4caf50;
        }

        .progress-card.users .progress-circle {
            border-color: #ffc107;
        }

        .progress-card.visitors .progress-circle {
            border-color: #00bcd4;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
            </div>

            <!-- Thống kê tổng -->
            <div class="stats">
                <div class="stat-box">
                    <i class="fa fa-shopping-cart"></i>
                    <div class="number"><?php echo $total_orders; ?></div>
                    <div class="desc">TOTAL ORDERS</div>
                </div>
                <div class="stat-box">
                    <i class="fa fa-comments"></i>
                    <div class="number"><?php echo $total_comments; ?></div>
                    <div class="desc">COMMENTS</div>
                </div>
                <div class="stat-box">
                    <i class="fas fa-chart-line"></i>
                    <div class="number">$<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="desc">REVENUE GENERATED</div>
                </div>
                <div class="stat-box">
                    <i class="fa fa-users"></i>
                    <div class="number"><?php echo $total_users; ?></div>
                    <div class="desc">USERS</div>
                </div>
            </div>

            <!-- Các biểu đồ -->
            <div class="chart-container">
                <div class="chart">
                    <h3>Biểu đồ doanh thu theo tháng</h3>
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="chart">
                    <h3>So sánh đơn hàng và doanh thu</h3>
                    <canvas id="compareChart"></canvas>
                </div>
            </div>

            <div class="chart">
                <h3>Tỉ lệ loại sản phẩm bán ra</h3>
                <canvas id="pieChart"></canvas>
            </div>

            <!-- Tiến trình -->
            <div class="progress-cards">
                <div class="progress-card comments">
                    <div class="progress-circle">
                        <span><?php echo $percent_comments; ?>%</span>
                    </div>
                    <div class="label">Comments</div>
                </div>
                <div class="progress-card users">
                    <div class="progress-circle">
                        <span><?php echo $percent_users; ?>%</span>
                    </div>
                    <div class="label">Users</div>
                </div>
                <div class="progress-card visitors">
                    <div class="progress-circle">
                        <span><?php echo $percent_visitors; ?>%</span>
                    </div>
                    <div class="label">Visitors</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js scripts -->
    <script>
        const months = <?php echo $months_json; ?>;
        const revenues = <?php echo $revenues_json; ?>;
        const orders = <?php echo $order_counts_json; ?>;
        const categories = <?php echo $categories_json; ?>;
        const categoryCounts = <?php echo $category_counts_json; ?>;

        // Biểu đồ doanh thu theo tháng (Line)
        new Chart(document.getElementById("revenueChart"), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: revenues,
                    borderColor: '#2196f3',
                    backgroundColor: 'rgba(33,150,243,0.2)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 5
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => value.toLocaleString('vi-VN') + ' ₫'
                        }
                    }
                }
            }
        });

        // Biểu đồ cột - So sánh đơn hàng & doanh thu
        new Chart(document.getElementById("compareChart"), {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                        label: 'Số đơn hàng',
                        data: orders,
                        backgroundColor: 'rgba(255,193,7,0.7)'
                    },
                    {
                        label: 'Doanh thu (VNĐ)',
                        data: revenues,
                        backgroundColor: 'rgba(33,150,243,0.7)'
                    }
                ]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Biểu đồ tròn - Tỉ lệ sản phẩm
        new Chart(document.getElementById("pieChart"), {
            type: 'pie',
            data: {
                labels: categories,
                datasets: [{
                    data: categoryCounts,
                    backgroundColor: ['#2196f3', '#4caf50', '#ffc107', '#ff5722', '#9c27b0'],
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    title: {
                        display: true,
                        text: 'Tỉ lệ loại sản phẩm bán ra'
                    }
                }
            }
        });
    </script>
</body>

</html>