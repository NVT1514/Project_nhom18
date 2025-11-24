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

// ================== XỬ LÝ TẠO PHIẾU KIỂM KÊ ==================
if (isset($_POST['create_inventory'])) {
    $title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
    $note = htmlspecialchars($_POST['note'], ENT_QUOTES, 'UTF-8');

    // Tạo phiếu kiểm kê
    $insert_sql = "INSERT INTO phieu_kiem_ke (title, note, status, created_at, created_by) 
                   VALUES (?, ?, 'pending', NOW(), ?)";
    $stmt_insert = $conn->prepare($insert_sql);
    $username = $_SESSION['username'] ?? 'Admin';
    $stmt_insert->bind_param("sss", $title, $note, $username);

    if ($stmt_insert->execute()) {
        $inventory_id = $conn->insert_id;

        // Tự động thêm tất cả sản phẩm vào chi tiết kiểm kê
        $products_sql = "SELECT id, so_luong FROM san_pham";
        $products_result = $conn->query($products_sql);

        $insert_detail_sql = "INSERT INTO chi_tiet_kiem_ke (inventory_id, product_id, system_quantity, actual_quantity, difference) 
                              VALUES (?, ?, ?, 0, ?)";
        $stmt_detail = $conn->prepare($insert_detail_sql);

        while ($product = $products_result->fetch_assoc()) {
            $product_id = $product['id'];
            $system_qty = $product['so_luong'];
            $difference = -$system_qty; // Chênh lệch ban đầu

            $stmt_detail->bind_param("iiii", $inventory_id, $product_id, $system_qty, $difference);
            $stmt_detail->execute();
        }

        $stmt_detail->close();
        log_activity($conn, "Quản lý Kho", "đã tạo phiếu kiểm kê: $title");

        header("Location: chi_tiet_kiem_ke.php?id=" . $inventory_id);
        exit();
    }
    $stmt_insert->close();
}

// ================== XỬ LÝ XÓA PHIẾU KIỂM KÊ ==================
if (isset($_POST['delete_inventory'])) {
    $inventory_id = filter_var($_POST['inventory_id'], FILTER_SANITIZE_NUMBER_INT);

    if ($inventory_id) {
        // Xóa chi tiết trước
        $delete_details = "DELETE FROM chi_tiet_kiem_ke WHERE inventory_id = ?";
        $stmt_delete_details = $conn->prepare($delete_details);
        $stmt_delete_details->bind_param("i", $inventory_id);
        $stmt_delete_details->execute();
        $stmt_delete_details->close();

        // Xóa phiếu kiểm kê
        $delete_sql = "DELETE FROM phieu_kiem_ke WHERE id = ?";
        $stmt_delete = $conn->prepare($delete_sql);
        $stmt_delete->bind_param("i", $inventory_id);

        if ($stmt_delete->execute()) {
            log_activity($conn, "Quản lý Kho", "đã xóa phiếu kiểm kê #$inventory_id");
        }
        $stmt_delete->close();

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ================== BỘ LỌC ==================
$filter_sql = "";
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$params = [];
$param_types = '';

if ($status_filter && $status_filter != 'all') {
    $filter_sql .= " AND status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($start_date && $end_date) {
    $filter_sql .= " AND created_at >= ? AND created_at <= ?";
    $params[] = $start_date;
    $params[] = $end_date . ' 23:59:59';
    $param_types .= 'ss';
}

// ================== PHÂN TRANG ==================
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(id) AS total FROM phieu_kiem_ke WHERE 1=1" . $filter_sql;
$stmt_count = $conn->prepare($count_sql);

if ($filter_sql && count($params) > 0) {
    $bind_params = [$param_types];
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array([$stmt_count, 'bind_param'], $bind_params);
}

$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$total_pages = ceil($total_records / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
} else if ($page < 1) {
    $page = 1;
    $offset = 0;
}

// ================== LẤY DANH SÁCH PHIẾU KIỂM KÊ ==================
$sql = "SELECT * FROM phieu_kiem_ke WHERE 1=1" . $filter_sql . " ORDER BY created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

$current_param_types = $param_types . 'ii';
$current_params = array_merge($params, [$limit, $offset]);

if (count($current_params) > 0) {
    $bind_params = [$current_param_types];
    foreach ($current_params as $key => $value) {
        $bind_params[] = &$current_params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_params);
}

$stmt->execute();
$result = $stmt->get_result();

// Định nghĩa trạng thái
$statuses = [
    'pending' => ['text' => 'Đang kiểm kê', 'class' => 'status-pending', 'icon' => 'fa-clock'],
    'completed' => ['text' => 'Hoàn thành', 'class' => 'status-completed', 'icon' => 'fa-check-circle'],
    'cancelled' => ['text' => 'Đã hủy', 'class' => 'status-cancelled', 'icon' => 'fa-times-circle']
];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Kiểm Kê</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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

        .container {
            max-width: 1200px;
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
        }

        .user-menu-btn:hover {
            background: #f1f3f6;
        }

        .user-menu-btn img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
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

        .dropdown-menu a:hover,
        .dropdown-menu button:hover {
            background: #f1f3f6;
        }

        /* Header */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .btn-create {
            background: #17a2b8;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-create:hover {
            background: #138496;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
        }

        /* Bộ lọc */
        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 15px;
            background: #e9ecef;
            border-radius: 8px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-form label {
            font-weight: 600;
            color: #495057;
        }

        .filter-form input[type="date"],
        .filter-form select {
            padding: 8px 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-btn-apply,
        .filter-btn-clear {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }

        .filter-btn-apply {
            background: #007bff;
            color: white;
        }

        .filter-btn-apply:hover {
            background: #0056b3;
        }

        .filter-btn-clear {
            background: #6c757d;
            color: white;
        }

        .filter-btn-clear:hover {
            background: #5a6268;
        }

        /* Bảng */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #17a2b8;
            color: white;
            font-weight: 600;
        }

        .table tr:hover {
            background: #f9fafc;
        }

        /* Trạng thái */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* Nút hành động */
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
        }

        .btn-view {
            background: #007bff;
            color: white;
        }

        .btn-view:hover {
            background: #0056b3;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background-color: #fff;
            margin: 8% auto;
            padding: 30px;
            border-radius: 12px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.5rem;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #17a2b8;
            box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-submit {
            background: #17a2b8;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: #138496;
        }

        /* Phân trang */
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

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 16px;
        }

        .info-note {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            color: #0c5460;
        }

        .info-note i {
            margin-right: 8px;
        }
    </style>
</head>

<body>
    <?php include "sidebar_admin.php"; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="search-box">
                <h1>Quản lý Kiểm Kê</h1>
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

        <div class="container">
            <div class="header-section">
                <h2 style="margin: 0; color: #2c3e50;">Danh sách Phiếu Kiểm Kê</h2>
                <button class="btn-create" onclick="openModal()">
                    <i class="fa-solid fa-plus"></i>
                    Tạo Phiếu Kiểm Kê
                </button>
            </div>

            <div class="info-note">
                <i class="fa-solid fa-info-circle"></i>
                <strong>Lưu ý:</strong> Phiếu kiểm kê sẽ tự động lấy toàn bộ sản phẩm trong kho. Sau khi tạo, bạn có thể cập nhật số lượng thực tế.
            </div>

            <!-- Bộ lọc -->
            <form method="get" class="filter-form">
                <label for="status"><i class="fa-solid fa-filter"></i> Trạng thái:</label>
                <select name="status" id="status">
                    <option value="all" <?= $status_filter == 'all' || !$status_filter ? 'selected' : '' ?>>Tất cả</option>
                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Đang kiểm kê</option>
                    <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                    <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                </select>

                <label for="start_date"><i class="fa-solid fa-calendar-day"></i> Từ ngày:</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">

                <label for="end_date">Đến ngày:</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">

                <button type="submit" class="filter-btn-apply">
                    <i class="fa-solid fa-search"></i> Lọc
                </button>
                <button type="button" onclick="window.location.href='<?= $_SERVER['PHP_SELF'] ?>'" class="filter-btn-clear">
                    <i class="fa-solid fa-xmark"></i> Xóa lọc
                </button>
            </form>

            <!-- Bảng danh sách -->
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tiêu đề</th>
                            <th>Trạng thái</th>
                            <th>Người tạo</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stt = $offset + 1;
                        while ($row = $result->fetch_assoc()):
                            $status = $row['status'];
                            $status_info = $statuses[$status] ?? $statuses['pending'];
                        ?>
                            <tr>
                                <td><?= $stt++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['title']) ?></strong>
                                    <?php if ($row['note']): ?>
                                        <br><small style="color: #6c757d;"><?= htmlspecialchars($row['note']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $status_info['class'] ?>">
                                        <i class="fa-solid <?= $status_info['icon'] ?>"></i>
                                        <?= $status_info['text'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['created_by']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="chi_tiet_kiem_ke.php?id=<?= $row['id'] ?>" class="btn-action btn-view">
                                        <i class="fa-solid fa-eye"></i> Xem
                                    </a>
                                    <?php if ($status == 'pending'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phiếu kiểm kê này?');">
                                            <input type="hidden" name="inventory_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_inventory" class="btn-action btn-delete">
                                                <i class="fa-solid fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Phân trang -->
                <div class="pagination">
                    <?php
                    $filter_params = $_GET;
                    unset($filter_params['page']);
                    $query_string = http_build_query($filter_params);
                    $base_url = $_SERVER['PHP_SELF'] . '?' . $query_string . (empty($query_string) ? '' : '&') . 'page=';

                    if ($total_pages > 1) {
                        if ($page > 1) {
                            echo '<a href="' . $base_url . ($page - 1) . '">Trước</a>';
                        } else {
                            echo '<span class="disabled">Trước</span>';
                        }

                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);

                        if ($start > 1) {
                            echo '<a href="' . $base_url . '1">1</a>';
                            if ($start > 2) echo '<span>...</span>';
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            if ($i == $page) {
                                echo '<span class="current-page">' . $i . '</span>';
                            } else {
                                echo '<a href="' . $base_url . $i . '">' . $i . '</a>';
                            }
                        }

                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) echo '<span>...</span>';
                            echo '<a href="' . $base_url . $total_pages . '">' . $total_pages . '</a>';
                        }

                        if ($page < $total_pages) {
                            echo '<a href="' . $base_url . ($page + 1) . '">Sau</a>';
                        } else {
                            echo '<span class="disabled">Sau</span>';
                        }
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa-solid fa-clipboard-list" style="font-size: 48px; color: #dee2e6; margin-bottom: 10px;"></i>
                    <p>Chưa có phiếu kiểm kê nào.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Tạo Phiếu Kiểm Kê -->
    <div id="inventoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tạo Phiếu Kiểm Kê Mới</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Tiêu đề <span style="color: red;">*</span></label>
                    <input type="text" name="title" id="title" required placeholder="VD: Kiểm kê tháng 11/2025">
                </div>

                <div class="form-group">
                    <label for="note">Ghi chú</label>
                    <textarea name="note" id="note" placeholder="Ghi chú về đợt kiểm kê (tùy chọn)"></textarea>
                </div>

                <div class="info-note">
                    <i class="fa-solid fa-lightbulb"></i>
                    Hệ thống sẽ tự động lấy tất cả sản phẩm trong kho vào phiếu kiểm kê.
                </div>

                <button type="submit" name="create_inventory" class="btn-submit">
                    <i class="fa-solid fa-check"></i> Tạo phiếu kiểm kê
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            document.querySelector('.user-menu').classList.toggle('active');
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

        function openModal() {
            document.getElementById('inventoryModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('inventoryModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('inventoryModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

    <?php
    if (isset($stmt)) $stmt->close();
    $conn->close();
    ?>
</body>

</html>