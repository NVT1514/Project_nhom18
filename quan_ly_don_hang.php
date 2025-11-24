<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php";

if (file_exists("Database/log_activity.php")) {
    include "Database/log_activity.php";
} else {
    function log_activity($conn, $category, $action) {}
}

// ================== ĐỊNH NGHĨA TRẠNG THÁI (MAPPING) ==================
// 1: Chờ xác nhận, 2: Đang chuẩn bị, 3: Đang giao, 4: Đã giao, 0: Đã hủy
$statuses = [
    1 => ['text' => 'Chờ xác nhận', 'class' => 'pending', 'label' => 'Chờ xác nhận'],
    2 => ['text' => 'Đang chuẩn bị hàng', 'class' => 'preparing', 'label' => 'Đang chuẩn bị hàng'],
    3 => ['text' => 'Đang giao', 'class' => 'shipping', 'label' => 'Đang giao'],
    4 => ['text' => 'Đã giao', 'class' => 'done', 'label' => 'Đã giao'],
    0 => ['text' => 'Đã hủy', 'class' => 'cancelled', 'label' => 'Đã hủy']
];
ksort($statuses);

// ================== CÁC HÀM XỬ LÝ KHO ĐÃ SỬA TÊN CỘT ==================

/**
 * Lấy trạng thái hiện tại của đơn hàng.
 */
function get_current_status($conn, $order_id)
{
    $sql = "SELECT status FROM don_hang WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $status = $result->fetch_assoc()['status'] ?? null;
        $stmt->close();
        return $status;
    }
    return null;
}

/**
 * Lấy chi tiết sản phẩm và số lượng từ đơn hàng.
 */
function get_order_details($conn, $order_id)
{
    $details = [];
    $sql = "SELECT product_id, quantity FROM chi_tiet_don_hang WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $details[] = $row;
        }
        $stmt->close();
    }
    return $details;
}

/**
 * Hoàn lại số lượng tồn kho (status -> 0: Đã hủy).
 * Tăng SỐ LƯỢNG TỒN KHO (`so_luong`) và Giảm SỐ LƯỢNG ĐÃ BÁN (`so_luong_ban`).
 */
function restore_inventory($conn, $order_id)
{
    $products = get_order_details($conn, $order_id);
    foreach ($products as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        // SỬA TÊN CỘT: Cập nhật bảng san_pham: Hoàn kho (so_luong +), Giảm đã bán (so_luong_ban -)
        $sql = "UPDATE san_pham SET so_luong = so_luong + ?, so_luong_ban = so_luong_ban - ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iii", $quantity, $quantity, $product_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/**
 * Trừ số lượng tồn kho (1 -> 2, 3, 4: Đang xử lý/Đã bán).
 * Giảm SỐ LƯỢNG TỒN KHO (`so_luong`) và Tăng SỐ LƯỢNG ĐÃ BÁN (`so_luong_ban`).
 */
function deduct_inventory($conn, $order_id)
{
    $products = get_order_details($conn, $order_id);
    foreach ($products as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        // SỬA TÊN CỘT: Cập nhật bảng san_pham: Trừ kho (so_luong -), Tăng đã bán (so_luong_ban +)
        $sql = "UPDATE san_pham SET so_luong = so_luong - ?, so_luong_ban = so_luong_ban + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iii", $quantity, $quantity, $product_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// ================== XỬ LÝ CẬP NHẬT TRẠNG THÁI ==================
if (isset($_POST['update_status'])) {
    $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
    $new_status = filter_var($_POST['new_status'], FILTER_SANITIZE_NUMBER_INT);

    if ($order_id && isset($statuses[$new_status])) {

        $old_status = get_current_status($conn, $order_id);
        $inventory_action = "";

        // 1. Logic HOÀN KHO (Chuyển sang ĐÃ HỦY: status = 0)
        if ($old_status != 0 && $new_status == 0) {
            restore_inventory($conn, $order_id);
            $inventory_action = " (Đã hoàn kho)";
        }

        // 2. Logic TRỪ KHO (Chuyển từ CHỜ XÁC NHẬN: status = 1 sang 2, 3, 4)
        elseif ($old_status == 1 && in_array($new_status, [2, 3, 4])) {
            deduct_inventory($conn, $order_id);
            $inventory_action = " (Đã trừ kho)";
        }

        // 3. Logic TRỪ KHO (Chuyển từ ĐÃ HỦY: status = 0 sang 1: Chờ xác nhận) - Khôi phục
        elseif ($old_status == 0 && $new_status == 1) {
            deduct_inventory($conn, $order_id);
            $inventory_action = " (Đã trừ kho)";
        }

        // 4. Cập nhật trạng thái đơn hàng trong DB
        $update_sql = "UPDATE don_hang SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("ii", $new_status, $order_id);

        if ($stmt_update->execute()) {
            $status_name = $statuses[$new_status]['text'] ?? 'Không xác định';
            $log_action = "đã cập nhật trạng thái đơn hàng #$order_id thành: **$status_name**" . $inventory_action;
            log_activity($conn, "Quản lý Đơn hàng", $log_action);
        }

        $stmt_update->close();
        $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET);
        header("Location: " . $redirect_url);
        exit();
    }
}

// Xử lý đánh dấu đã giao (status = 4)
if (isset($_POST['mark_done'])) {
    $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
    if ($order_id) {
        $new_status = 4; // Đã giao
        $old_status = get_current_status($conn, $order_id);
        $inventory_action = "";

        // Trừ kho nếu trạng thái cũ là Chờ xác nhận (1)
        if ($old_status == 1) {
            deduct_inventory($conn, $order_id);
            $inventory_action = " (Đã trừ kho)";
        }

        $update_sql = "UPDATE don_hang SET status = ? WHERE id = ?";
        $stmt_mark_done = $conn->prepare($update_sql);
        $stmt_mark_done->bind_param("ii", $new_status, $order_id);

        if ($stmt_mark_done->execute()) {
            $status_name = $statuses[$new_status]['text'] ?? 'Đã giao';
            $log_action = "đã đánh dấu đơn hàng #$order_id là: **$status_name**" . $inventory_action;
            log_activity($conn, "Quản lý Đơn hàng", $log_action);
        }
        $stmt_mark_done->close();
        $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET);
        header("Location: " . $redirect_url);
        exit();
    }
}

// --- Xử lý Bộ lọc Ngày/Tháng ---
$filter_sql = "";
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$params = [];
$param_types = '';

if ($start_date && $end_date) {
    $filter_sql = " AND created_at >= ? AND created_at <= ?";
    $params[] = $start_date;
    $params[] = $end_date . ' 23:59:59';
    $param_types = 'ss';
}

function get_date_value($key)
{
    return isset($_GET[$key]) ? htmlspecialchars($_GET[$key]) : '';
}

// --- 1. Lấy Thống kê Tổng quan ---
$stats_sql = "
    SELECT
        COUNT(CASE WHEN status IN (1, 2) THEN 1 END) AS processing_count, 
        COUNT(CASE WHEN status = 3 THEN 1 END) AS shipping_count,
        COUNT(CASE WHEN status = 4 THEN 1 END) AS done_count,
        COUNT(CASE WHEN status = 0 THEN 1 END) AS cancelled_count
    FROM don_hang WHERE 1=1 {$filter_sql}
";

$stmt_stats = $conn->prepare($stats_sql);
if ($filter_sql) {
    $stmt_stats->bind_param($param_types, ...$params);
}
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result();
$stats = $stats_result->fetch_assoc();
$stmt_stats->close();

// ================== PHÂN TRANG ==================
$limit = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- 2. Lấy Tổng số đơn hàng ---
$count_sql = "SELECT COUNT(id) AS total FROM don_hang WHERE 1=1 {$filter_sql}";

$stmt_count = $conn->prepare($count_sql);
if ($filter_sql) {
    $stmt_count->bind_param($param_types, ...$params);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_orders = $count_result->fetch_assoc()['total'];
$stmt_count->close();

$total_pages = ceil($total_orders / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
} else if ($page < 1) {
    $page = 1;
    $offset = 0;
}

// --- 3. Lấy danh sách đơn hàng ---
$sql = "SELECT id, order_id, status, created_at FROM don_hang WHERE 1=1 {$filter_sql} ORDER BY created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

$current_param_types = $param_types . 'ii';
$current_params = array_merge($params, [$limit, $offset]);

if ($current_params) {
    array_unshift($current_params, $current_param_types);

    $bind_params = [];
    foreach ($current_params as $key => $value) {
        $bind_params[$key] = &$current_params[$key];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind_params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* CSS được giữ nguyên */
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

        .order-container {
            width: 900px;
            background: #fff;
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin: 0 auto;
        }

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

        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .table th,
        .table td {
            padding: 14px 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .table th:nth-child(1),
        .table td:nth-child(1) {
            width: 25%;
        }

        .table th:nth-child(2),
        .table td:nth-child(2) {
            width: 25%;
        }

        .table th:nth-child(3),
        .table td:nth-child(3) {
            width: 25%;
        }

        .table th:nth-child(4),
        .table td:nth-child(4) {
            width: 25%;
        }

        .table th {
            background: #007bff;
            color: white;
        }

        .table tr:hover {
            background: #f9fafc;
        }

        .status {
            padding: 6px 12px;
            border-radius: 6px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
            white-space: nowrap;
        }

        .status.pending {
            background-color: #ff9800;
        }

        .status.shipping {
            background-color: #03a9f4;
        }

        .status.done {
            background-color: #4caf50;
        }

        .status.cancelled {
            background-color: #f44336;
        }

        .status.preparing {
            background-color: #6c757d;
        }

        .stat-pending {
            border-left-color: #ff9800;
        }

        .stat-pending p {
            color: #ff9800;
        }

        .stat-shipping {
            border-left-color: #03a9f4;
        }

        .stat-shipping p {
            color: #03a9f4;
        }

        .stat-done {
            border-left-color: #4caf50;
        }

        .stat-done p {
            color: #4caf50;
        }

        .stat-cancelled {
            border-left-color: #f44336;
        }

        .stat-cancelled p {
            color: #f44336;
        }

        .stat-preparing {
            border-left-color: #6c757d;
        }

        .stat-preparing p {
            color: #6c757d;
        }

        .btn {
            background: #007bff;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            font-size: 14px;
            white-space: nowrap;
        }

        .btn-detail {
            background: #6c757d;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-detail:hover {
            background: #5a6268;
        }

        .no-orders {
            text-align: center;
            color: #777;
            font-size: 16px;
            padding: 20px;
        }

        .order-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 15px;
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            flex: 1;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
            border-left: 5px solid;
        }

        .stat-card h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }

        .stat-card p {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 15px;
            background: #e9ecef;
            border-radius: 8px;
            margin-bottom: 25px;
            justify-content: flex-start;
        }

        .filter-form label {
            font-weight: 600;
            color: #495057;
            white-space: nowrap;
        }

        .filter-form input[type="date"] {
            padding: 8px 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            width: 140px;
        }

        .filter-form button {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }

        .filter-btn-apply {
            background: #28a745;
            color: white;
        }

        .filter-btn-apply:hover {
            background: #1e7e34;
        }

        .filter-btn-clear {
            background: #6c757d;
            color: white;
        }

        .filter-btn-clear:hover {
            background: #5a6268;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px 0;
            gap: 5px;
        }

        .pagination a,
        .pagination span {
            display: block;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #007bff;
            background-color: #fff;
            transition: all 0.2s;
            font-size: 14px;
        }

        .pagination a:hover {
            background-color: #e9ecef;
            border-color: #007bff;
        }

        .pagination .current-page {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
            font-weight: bold;
        }

        .pagination .disabled {
            color: #6c757d;
            pointer-events: none;
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <?php include "sidebar_admin.php"; ?>

    <div class="main-content">
        <div class="order-container">
            <div class="topbar">
                <div class="search-box">
                    <h1>Quản lý đơn hàng</h1>
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
            <div class="order-stats">
                <div class="stat-card stat-pending">
                    <h3>Đang xử lý</h3>
                    <p><?= htmlspecialchars($stats['processing_count'] ?? 0) ?></p>
                </div>
                <div class="stat-card stat-shipping">
                    <h3>Đang giao hàng</h3>
                    <p><?= htmlspecialchars($stats['shipping_count'] ?? 0) ?></p>
                </div>
                <div class="stat-card stat-done">
                    <h3>Đã giao hàng</h3>
                    <p><?= htmlspecialchars($stats['done_count'] ?? 0) ?></p>
                </div>
                <div class="stat-card stat-cancelled">
                    <h3>Đã hủy</h3>
                    <p><?= htmlspecialchars($stats['cancelled_count'] ?? 0) ?></p>
                </div>
            </div>
            <form method="get" class="filter-form">
                <label for="start_date"><i class="fa-solid fa-calendar-day"></i> Từ ngày:</label>
                <input type="date" id="start_date" name="start_date" value="<?= get_date_value('start_date') ?>">

                <label for="end_date">Đến ngày:</label>
                <input type="date" id="end_date" name="end_date" value="<?= get_date_value('end_date') ?>">

                <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">

                <button type="submit" class="filter-btn-apply"><i class="fa-solid fa-filter"></i> Lọc</button>
                <button type="button" onclick="window.location.href='quan_ly_don_hang.php'" class="filter-btn-clear"><i class="fa-solid fa-xmark"></i> Xóa lọc</button>
            </form>
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="table">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Trạng thái</th>
                        <th>Xem chi tiết</th>
                        <th>Hành động</th>
                    </tr>

                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $current_status = $row['status'];
                        $status_info = $statuses[$current_status] ?? $statuses[1];
                        $status_text = $status_info['text'];
                        $status_class = $status_info['class'];
                        ?>
                        <tr>
                            <td>#<?= htmlspecialchars($row['order_id']) ?></td>

                            <td><span class="status <?= $status_class ?>"><?= $status_text ?></span></td>

                            <td>
                                <a href="chi_tiet.php?id=<?= $row['id'] ?>" class="btn btn-detail">Xem chi tiết</a>
                            </td>

                            <td>
                                <?php if ($current_status == 4 || $current_status == 0): ?>
                                    <span class="status <?= $status_class ?>"><?= $status_info['label'] ?></span>
                                <?php else: ?>
                                    <form method="POST" action="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET) ?>" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="update_status" value="1">

                                        <select name="new_status" class="action-select" onchange="this.form.submit()" style="padding: 8px 10px; border-radius: 5px; border: 1px solid #ccc; background-color: #f9f9f9; cursor: pointer; font-size: 14px;">
                                            <?php foreach ($statuses as $key => $status): ?>
                                                <?php
                                                // Không cho phép chuyển từ Đang chuẩn bị (2) hoặc Đang giao (3) về Chờ xác nhận (1)
                                                if ($key == 1 && ($current_status == 2 || $current_status == 3)) continue;

                                                // Loại bỏ trạng thái 'Đã hủy' (0) khỏi dropdown nếu đơn hàng đã 'Đang giao' (3)
                                                if ($key == 0 && $current_status == 3) continue;
                                                ?>
                                                <option
                                                    value="<?= $key ?>"
                                                    <?= ($key == $current_status) ? 'selected' : '' ?>>
                                                    <?= $status['label'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>

                <div class="pagination">
                    <?php
                    $filter_params = $_GET;
                    unset($filter_params['page']);
                    $query_string = http_build_query($filter_params);
                    $base_url = $_SERVER['PHP_SELF'] . '?' . $query_string . (empty($query_string) ? '' : '&') . 'page=';

                    if ($total_pages > 1) {
                        $prev_page = $page - 1;
                        if ($page > 1) {
                            echo '<a href="' . $base_url . $prev_page . '">Trước</a>';
                        } else {
                            echo '<span class="disabled">Trước</span>';
                        }

                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);

                        if ($start > 1) {
                            echo '<a href="' . $base_url . '1">1</a>';
                            if ($start > 2) {
                                echo '<span>...</span>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            if ($i == $page) {
                                echo '<span class="current-page">' . $i . '</span>';
                            } else {
                                echo '<a href="' . $base_url . $i . '">' . $i . '</a>';
                            }
                        }

                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) {
                                echo '<span>...</span>';
                            }
                            echo '<a href="' . $base_url . $total_pages . '">' . $total_pages . '</a>';
                        }

                        $next_page = $page + 1;
                        if ($page < $total_pages) {
                            echo '<a href="' . $base_url . $next_page . '">Sau</a>';
                        } else {
                            echo '<span class="disabled">Sau</span>';
                        }
                    }
                    ?>
                </div>
            <?php else: ?>
                <p class="no-orders">Chưa có đơn hàng nào.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            const userMenu = document.querySelector('.user-menu');
            userMenu.classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const userBtn = document.querySelector('.user-menu-btn');
            if (!userMenu.contains(event.target) && !userBtn.contains(event.target)) {
                userMenu.classList.remove('active');
            }
        });

        function logoutUser() {
            if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                window.location.href = 'login.php';
            }
        }
    </script>

    <?php $conn->close(); ?>
</body>

</html>