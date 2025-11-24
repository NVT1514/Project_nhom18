<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php";

$user_id = $_SESSION['user_id'] ?? 0;
// Đảm bảo user_id hợp lệ
if ($user_id === 0) {
    header("Location: login.php");
    exit();
}

// ================== ĐỊNH NGHĨA TRẠNG THÁI ==================
// 0: Chờ xác nhận, 4: Đang chuẩn bị, 1: Đang giao, 2: Đã giao, 3: Đã hủy
$statuses = [
    0 => ['text' => 'Chờ xác nhận', 'class' => 'pending', 'filter' => 'pending', 'icon' => 'fa-clock'],
    4 => ['text' => 'Đang chuẩn bị hàng', 'class' => 'preparing', 'filter' => 'preparing', 'icon' => 'fa-box'],
    1 => ['text' => 'Đang giao hàng', 'class' => 'shipping', 'filter' => 'shipping', 'icon' => 'fa-truck-fast'],
    2 => ['text' => 'Đã giao hàng thành công', 'class' => 'done', 'filter' => 'done', 'icon' => 'fa-circle-check'],
    3 => ['text' => 'Đã hủy', 'class' => 'cancelled', 'filter' => 'cancelled', 'icon' => 'fa-circle-xmark']
];

// Lấy bộ lọc từ URL (Mặc định là 'pending' - Chờ xác nhận)
$filter_slug = $_GET['filter'] ?? 'pending';
$filter_status_id = null; // Biến lưu ID trạng thái số để truy vấn

// Xác định ID trạng thái số từ slug
foreach ($statuses as $id => $info) {
    if ($info['filter'] === $filter_slug) {
        $filter_status_id = $id;
        break;
    }
}

// Nếu filter_slug là 'all' (Mặc dù đã bỏ tab, nhưng vẫn có thể dùng qua URL)
if ($filter_slug === 'all') {
    $filter_status_id = 'all';
}

// ================== XỬ LÝ HỦY ĐƠN HÀNG ==================
if (isset($_POST['cancel_order'])) {
    $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);

    $check_sql = "SELECT status FROM don_hang WHERE id = ? AND user_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ii", $order_id, $user_id);
    $stmt_check->execute();
    $order_status = $stmt_check->get_result()->fetch_assoc()['status'] ?? null;
    $stmt_check->close();

    // CHỈ CHO PHÉP HỦY NẾU TRẠNG THÁI LÀ 0 (Chờ xác nhận)
    if ($order_status !== null && $order_status == 0) {
        $cancel_sql = "UPDATE don_hang SET status = 3 WHERE id = ?";
        $stmt_cancel = $conn->prepare($cancel_sql);
        $stmt_cancel->bind_param("i", $order_id);
        $stmt_cancel->execute();
        $stmt_cancel->close();

        // Chuyển hướng để refresh trang và giữ nguyên filter
        header("Location: " . $_SERVER['PHP_SELF'] . "?filter=" . urlencode($filter_slug));
        exit();
    }
}


// ================== TRUY VẤN ĐƠN HÀNG (CÓ LỌC) ==================
$sql = "SELECT id, order_id, fullname, phone, address, total, payment_method, created_at, status 
        FROM don_hang 
        WHERE user_id = ?";
$params = [$user_id];
$types = 'i';

// ÁP DỤNG LỌC
if ($filter_status_id !== null && $filter_status_id !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $filter_status_id;
    $types .= 'i';
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

// Khối logic bind param an toàn hơn:
if ($filter_status_id !== null && $filter_status_id !== 'all') {
    // Nếu có lọc trạng thái, types là 'ii' và params là [$user_id, $filter_status_id]
    $stmt->bind_param('ii', $user_id, $filter_status_id);
} else {
    // Nếu filter là 'all' hoặc mặc định 'pending' (chỉ có $user_id trong $params[0])
    $stmt->bind_param('i', $user_id);
}


$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trạng thái đơn hàng</title>
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
            text-align: center;
        }

        .page-header h1 {
            color: #2d3748;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-header h1 i {
            color: #667eea;
            font-size: 2.2rem;
        }

        .page-header p {
            color: #718096;
            font-size: 1.05rem;
            margin: 0;
        }

        /* Order Container */
        .order-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Tab Filters - Modern Design */
        .tab-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding: 5px;
            background: #f7fafc;
            border-radius: 15px;
            padding: 8px;
        }

        .tab-filter {
            padding: 12px 24px;
            text-decoration: none;
            color: #4a5568;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            background: white;
            border: 2px solid transparent;
        }

        .tab-filter:hover {
            background: #edf2f7;
            transform: translateY(-2px);
        }

        .tab-filter.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            border-color: transparent;
        }

        .tab-filter i {
            font-size: 1.1rem;
        }

        /* Table Styles - Modern Card Design */
        .orders-grid {
            display: grid;
            gap: 20px;
        }

        .order-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .order-card:hover {
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            transform: translateY(-3px);
        }

        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f7fafc;
        }

        .order-id {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-id i {
            color: #667eea;
        }

        .order-date {
            color: #718096;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .order-card-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .order-info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .order-info-label {
            color: #a0aec0;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-info-value {
            color: #2d3748;
            font-size: 1rem;
            font-weight: 600;
        }

        .order-info-value.price {
            color: #667eea;
            font-size: 1.2rem;
        }

        /* Status Badges - Improved */
        .status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .status.pending {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
        }

        .status.preparing {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }

        .status.shipping {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .status.done {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .status.cancelled {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .order-card-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 15px;
            border-top: 2px solid #f7fafc;
        }

        /* Buttons - Modern Design */
        .btn-detail {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f7fafc;
            border-radius: 15px;
            margin: 40px 0;
        }

        .empty-state i {
            font-size: 5rem;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #4a5568;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #718096;
            font-size: 1.05rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.5rem;
                flex-direction: column;
            }

            .tab-filters {
                flex-wrap: nowrap;
                justify-content: flex-start;
            }

            .order-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .order-card-body {
                grid-template-columns: 1fr;
            }

            .order-card-footer {
                flex-direction: column;
            }

            .btn-detail,
            .btn-cancel {
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

        .order-card {
            animation: fadeIn 0.5s ease-out;
        }

        /* Scrollbar Styling */
        .tab-filters::-webkit-scrollbar {
            height: 6px;
        }

        .tab-filters::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .tab-filters::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }

        .tab-filters::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
    </style>
</head>

<body>
    <?php include "sidebar_user.php"; ?>

    <div class="main-container">
        <?php
        $breadcrumb_title = "Trạng thái đơn hàng";
        $breadcrumb_items = [
            ["label" => "Trang chủ", "link" => "maincustomer.php"],
            ["label" => $breadcrumb_title]
        ];
        include "breadcrumb.php";
        ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fa-solid fa-clipboard-list"></i>
                Quản lý đơn hàng của tôi
            </h1>
            <p>Theo dõi và quản lý tất cả đơn hàng của bạn</p>
        </div>

        <div class="order-container">
            <!-- CÁC TABS LỌC TRẠNG THÁI -->
            <div class="tab-filters">
                <?php foreach ($statuses as $id => $info): ?>
                    <a href="?filter=<?= $info['filter'] ?>"
                        class="tab-filter <?= $filter_slug == $info['filter'] ? 'active' : '' ?>">
                        <i class="fa-solid <?= $info['icon'] ?>"></i>
                        <?= $info['text'] ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="orders-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $current_status = $row['status'];
                        $status_info = $statuses[$current_status] ?? $statuses[0];
                        $status_text = $status_info['text'];
                        $status_class = $status_info['class'];
                        $status_icon = $status_info['icon'];
                        ?>
                        <div class="order-card">
                            <!-- Order Header -->
                            <div class="order-card-header">
                                <div>
                                    <div class="order-id">
                                        <i class="fa-solid fa-hashtag"></i>
                                        <?= htmlspecialchars($row['order_id']) ?>
                                    </div>
                                    <div class="order-date">
                                        <i class="fa-regular fa-calendar"></i>
                                        <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                    </div>
                                </div>
                                <span class="status <?= $status_class ?>">
                                    <i class="fa-solid <?= $status_icon ?>"></i>
                                    <?= $status_text ?>
                                </span>
                            </div>

                            <!-- Order Body -->
                            <div class="order-card-body">
                                <div class="order-info-item">
                                    <span class="order-info-label">
                                        <i class="fa-solid fa-user"></i> Người nhận
                                    </span>
                                    <span class="order-info-value">
                                        <?= htmlspecialchars($row['fullname']) ?>
                                    </span>
                                </div>

                                <div class="order-info-item">
                                    <span class="order-info-label">
                                        <i class="fa-solid fa-phone"></i> Số điện thoại
                                    </span>
                                    <span class="order-info-value">
                                        <?= htmlspecialchars($row['phone']) ?>
                                    </span>
                                </div>

                                <div class="order-info-item">
                                    <span class="order-info-label">
                                        <i class="fa-solid fa-location-dot"></i> Địa chỉ
                                    </span>
                                    <span class="order-info-value">
                                        <?= htmlspecialchars($row['address']) ?>
                                    </span>
                                </div>

                                <div class="order-info-item">
                                    <span class="order-info-label">
                                        <i class="fa-solid fa-money-bill-wave"></i> Tổng tiền
                                    </span>
                                    <span class="order-info-value price">
                                        <?= number_format($row['total'], 0, ',', '.') ?>đ
                                    </span>
                                </div>
                            </div>

                            <!-- Order Footer -->
                            <div class="order-card-footer">
                                <a href="chi_tiet_don_hang.php?id=<?= $row['id'] ?>" class="btn-detail">
                                    <i class="fa-solid fa-eye"></i>
                                    Xem chi tiết
                                </a>

                                <?php if ($current_status == 0): ?>
                                    <form method="POST"
                                        onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');"
                                        style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="cancel_order" class="btn-cancel">
                                            <i class="fa-solid fa-xmark"></i>
                                            Hủy đơn
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <h3>Không có đơn hàng nào</h3>
                    <p>Bạn chưa có đơn hàng nào trong mục này</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>