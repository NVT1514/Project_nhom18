<?php
session_start();
include "Database/connectdb.php";

// ===== Kiểm tra đăng nhập =====
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập để xem chi tiết đơn hàng!'); window.location.href='../login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// ===== Nhận ID đơn hàng =====
$order_id = intval($_GET['id'] ?? 0);
if ($order_id <= 0) {
    echo "<script>alert('Mã đơn hàng không hợp lệ!'); window.history.back();</script>";
    exit();
}

// ===== Lấy thông tin đơn hàng =====
$sql_order = "SELECT * FROM don_hang WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql_order);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.history.back();</script>";
    exit();
}

// ===== Lấy danh sách sản phẩm trong đơn =====
$sql_items = "SELECT * FROM chi_tiet_don_hang WHERE order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result();

// Định nghĩa trạng thái với màu sắc và icon
$statuses = [
    0 => ['text' => 'Chờ xác nhận', 'class' => 'pending', 'icon' => 'fa-clock'],
    4 => ['text' => 'Đang chuẩn bị hàng', 'class' => 'preparing', 'icon' => 'fa-box'],
    1 => ['text' => 'Đang giao hàng', 'class' => 'shipping', 'icon' => 'fa-truck-fast'],
    2 => ['text' => 'Đã giao hàng thành công', 'class' => 'done', 'icon' => 'fa-circle-check'],
    3 => ['text' => 'Đã hủy', 'class' => 'cancelled', 'icon' => 'fa-circle-xmark']
];

$current_status = $order['status'];
$status_info = $statuses[$current_status] ?? $statuses[0];

// Tính toán timeline
$timeline_steps = [
    ['status' => 0, 'label' => 'Đặt hàng', 'icon' => 'fa-cart-shopping'],
    ['status' => 4, 'label' => 'Chuẩn bị', 'icon' => 'fa-box'],
    ['status' => 1, 'label' => 'Vận chuyển', 'icon' => 'fa-truck'],
    ['status' => 2, 'label' => 'Hoàn thành', 'icon' => 'fa-circle-check']
];

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?= htmlspecialchars($order['order_id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
        }

        .main-container {
            flex: 1;
            padding: 30px 20px;
            width: 100%;
        }

        /* Header Section */
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h1 {
            color: #2d3748;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-left h1 i {
            color: #667eea;
        }

        .order-meta {
            color: #718096;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-badge {
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .status-badge.pending {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
        }

        .status-badge.preparing {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }

        .status-badge.shipping {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .status-badge.done {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .status-badge.cancelled {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        /* Container */
        .detail-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Order Timeline */
        .timeline-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .timeline-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .timeline-title i {
            color: #667eea;
        }

        .order-timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 20px 0;
        }

        .timeline-line {
            position: absolute;
            top: 45px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e2e8f0;
            z-index: 0;
        }

        .timeline-progress {
            position: absolute;
            top: 45px;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            z-index: 1;
            transition: width 1s ease;
        }

        .timeline-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .timeline-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            border: 4px solid #e2e8f0;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #cbd5e0;
            transition: all 0.3s;
        }

        .timeline-step.active .timeline-icon,
        .timeline-step.completed .timeline-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
            color: white;
            transform: scale(1.1);
        }

        .timeline-label {
            font-size: 0.9rem;
            color: #a0aec0;
            font-weight: 600;
        }

        .timeline-step.active .timeline-label,
        .timeline-step.completed .timeline-label {
            color: #2d3748;
        }

        /* Order Info Cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .info-card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f7fafc;
        }

        .info-card-title i {
            color: #667eea;
            font-size: 1.2rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f7fafc;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #718096;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .info-value {
            color: #2d3748;
            font-weight: 600;
            text-align: right;
        }

        /* Products Section */
        .products-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #667eea;
        }

        .product-card {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .product-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.15);
        }

        .product-image {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .product-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #718096;
            font-size: 0.9rem;
        }

        .meta-item i {
            color: #667eea;
        }

        .product-pricing {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }

        .product-price {
            color: #667eea;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .product-total {
            color: #2d3748;
            font-weight: 700;
            font-size: 1.2rem;
        }

        /* Summary Section */
        .summary-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .summary-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 1rem;
        }

        .summary-row.total {
            font-size: 1.5rem;
            font-weight: 700;
            padding-top: 20px;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
            margin-top: 10px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-action {
            padding: 14px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: white;
            color: #667eea;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-timeline {
                flex-direction: column;
                gap: 20px;
            }

            .timeline-line,
            .timeline-progress {
                display: none;
            }

            .product-card {
                flex-direction: column;
            }

            .product-image {
                width: 100%;
                height: 200px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .info-card,
        .products-section,
        .summary-section {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body>
    <?php include 'sidebar_user.php'; ?>

    <div class="main-container">
        <?php
        $breadcrumb_title = "Chi tiết đơn hàng";
        $breadcrumb_items = [
            ["label" => "Trang chủ", "link" => "maincustomer.php"],
            ["label" => "Đơn hàng", "link" => "trang_thai_don_hang.php"],
            ["label" => $breadcrumb_title]
        ];
        include "breadcrumb.php";
        ?>

        <div class="detail-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <div class="header-left">
                        <h1>
                            <i class="fa-solid fa-receipt"></i>
                            Đơn hàng #<?= htmlspecialchars($order['order_id']) ?>
                        </h1>
                        <div class="order-meta">
                            <i class="fa-regular fa-calendar"></i>
                            Đặt ngày: <?= date("d/m/Y H:i", strtotime($order['created_at'])) ?>
                        </div>
                    </div>
                    <div class="status-badge <?= $status_info['class'] ?>">
                        <i class="fa-solid <?= $status_info['icon'] ?>"></i>
                        <?= $status_info['text'] ?>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <?php if ($current_status != 3): // Không hiển thị timeline nếu đã hủy 
            ?>
                <div class="timeline-section">
                    <div class="timeline-title">
                        <i class="fa-solid fa-timeline"></i>
                        Tiến trình đơn hàng
                    </div>
                    <div class="order-timeline">
                        <div class="timeline-line"></div>
                        <div class="timeline-progress" style="width: <?= min(($current_status == 2 ? 3 : ($current_status == 4 ? 1 : $current_status)) / 3 * 100, 100) ?>%;"></div>
                        <?php foreach ($timeline_steps as $index => $step):
                            $is_completed = ($current_status == 2 && $index <= 3) ||
                                ($current_status == 1 && $index <= 2) ||
                                ($current_status == 4 && $index <= 1) ||
                                ($current_status == 0 && $index == 0);
                            $is_active = ($current_status == 2 && $index == 3) ||
                                ($current_status == 1 && $index == 2) ||
                                ($current_status == 4 && $index == 1) ||
                                ($current_status == 0 && $index == 0);
                        ?>
                            <div class="timeline-step <?= $is_completed ? 'completed' : '' ?> <?= $is_active ? 'active' : '' ?>">
                                <div class="timeline-icon">
                                    <i class="fa-solid <?= $step['icon'] ?>"></i>
                                </div>
                                <div class="timeline-label"><?= $step['label'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Order Info Grid -->
            <div class="info-grid">
                <!-- Customer Info -->
                <div class="info-card">
                    <div class="info-card-title">
                        <i class="fa-solid fa-user"></i>
                        Thông tin người nhận
                    </div>
                    <div class="info-item">
                        <span class="info-label">Họ tên:</span>
                        <span class="info-value"><?= htmlspecialchars($order['fullname']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Số điện thoại:</span>
                        <span class="info-value"><?= htmlspecialchars($order['phone']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Địa chỉ:</span>
                        <span class="info-value"><?= htmlspecialchars($order['address']) ?></span>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="info-card">
                    <div class="info-card-title">
                        <i class="fa-solid fa-credit-card"></i>
                        Thông tin thanh toán
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phương thức:</span>
                        <span class="info-value"><?= strtoupper(htmlspecialchars($order['payment_method'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Mã đơn hàng:</span>
                        <span class="info-value">#<?= htmlspecialchars($order['order_id']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Trạng thái:</span>
                        <span class="info-value"><?= $status_info['text'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Products List -->
            <div class="products-section">
                <div class="section-title">
                    <i class="fa-solid fa-shopping-bag"></i>
                    Sản phẩm đã đặt
                </div>

                <?php
                // Reset pointer
                $items->data_seek(0);
                while ($item = $items->fetch_assoc()):
                    $product_sql = mysqli_query($conn, "SELECT hinh_anh FROM san_pham WHERE id = {$item['product_id']}");
                    $product_img = mysqli_fetch_assoc($product_sql)['hinh_anh'] ?? 'no-image.png';
                ?>
                    <div class="product-card">
                        <img src="<?= htmlspecialchars($product_img) ?>" alt="Product" class="product-image">
                        <div class="product-info">
                            <div>
                                <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                <div class="product-meta">
                                    <div class="meta-item">
                                        <i class="fa-solid fa-ruler"></i>
                                        Size: <?= htmlspecialchars($item['size']) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fa-solid fa-box"></i>
                                        Số lượng: <?= $item['quantity'] ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fa-solid fa-tag"></i>
                                        <?= number_format($item['price'], 0, ',', '.') ?>đ
                                    </div>
                                </div>
                            </div>
                            <div class="product-pricing">
                                <span class="product-price">Đơn giá: <?= number_format($item['price'], 0, ',', '.') ?>đ</span>
                                <span class="product-total">Thành tiền: <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Order Summary -->
            <div class="summary-section">
                <div class="summary-title">
                    <i class="fa-solid fa-calculator"></i>
                    Tổng kết đơn hàng
                </div>
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <span><?= number_format($order['total'], 0, ',', '.') ?>đ</span>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển:</span>
                    <span>Miễn phí</span>
                </div>
                <div class="summary-row total">
                    <span>Tổng cộng:</span>
                    <span><?= number_format($order['total'], 0, ',', '.') ?>đ</span>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="trang_thai_don_hang.php" class="btn-action btn-primary">
                        <i class="fa-solid fa-arrow-left"></i>
                        Quay lại danh sách
                    </a>
                    <?php if ($current_status == 2): ?>
                        <a href="#" class="btn-action btn-secondary">
                            <i class="fa-solid fa-star"></i>
                            Đánh giá sản phẩm
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>