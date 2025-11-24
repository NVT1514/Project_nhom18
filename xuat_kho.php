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

// ================== XỬ LÝ XUẤT KHO ==================
if (isset($_POST['export_stock'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);
    $reason = htmlspecialchars($_POST['reason'], ENT_QUOTES, 'UTF-8');
    $note = htmlspecialchars($_POST['note'], ENT_QUOTES, 'UTF-8');

    if ($product_id && $quantity > 0) {
        // Kiểm tra số lượng tồn kho
        $check_sql = "SELECT so_luong, ten_san_pham FROM san_pham WHERE id = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("i", $product_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $product = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($product && $product['so_luong'] >= $quantity) {
            // Thêm vào lịch sử xuất kho
            $insert_sql = "INSERT INTO lich_su_xuat_kho (product_id, quantity, reason, note, created_at) 
                           VALUES (?, ?, ?, ?, NOW())";
            $stmt_insert = $conn->prepare($insert_sql);

            if ($stmt_insert) {
                $stmt_insert->bind_param("iiss", $product_id, $quantity, $reason, $note);

                if ($stmt_insert->execute()) {
                    // Cập nhật số lượng sản phẩm (trừ đi)
                    $update_sql = "UPDATE san_pham SET so_luong = so_luong - ? WHERE id = ?";
                    $stmt_update = $conn->prepare($update_sql);

                    if ($stmt_update) {
                        $stmt_update->bind_param("ii", $quantity, $product_id);
                        $stmt_update->execute();
                        $stmt_update->close();
                    }

                    log_activity($conn, "Quản lý Kho", "đã xuất kho $quantity sản phẩm với lý do: $reason");
                    $success_message = "Xuất kho thành công!";
                }
                $stmt_insert->close();
            }
        } else {
            $error_message = "Số lượng tồn kho không đủ! Hiện tại chỉ còn " . ($product['so_luong'] ?? 0) . " sản phẩm.";
        }

        if (!isset($error_message)) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// ================== BỘ LỌC NGÀY THÁNG ==================
$filter_sql = "";
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$params = [];
$param_types = '';

if ($start_date && $end_date) {
    $filter_sql = " AND lsxk.created_at >= ? AND lsxk.created_at <= ?";
    $params[] = $start_date;
    $params[] = $end_date . ' 23:59:59';
    $param_types = 'ss';
}

// ================== PHÂN TRANG ==================
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(lsxk.id) AS total FROM lich_su_xuat_kho lsxk WHERE 1=1" . $filter_sql;
$stmt_count = $conn->prepare($count_sql);

if ($stmt_count === false) {
    die("Lỗi chuẩn bị câu lệnh count: " . $conn->error);
}

if ($filter_sql && count($params) > 0) {
    $stmt_count->bind_param($param_types, ...$params);
}

if (!$stmt_count->execute()) {
    die("Lỗi thực thi câu lệnh count: " . $stmt_count->error);
}

$count_result = $stmt_count->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$stmt_count->close();

$total_pages = ceil($total_records / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
} else if ($page < 1) {
    $page = 1;
    $offset = 0;
}

// ================== LẤY LỊCH SỬ XUẤT KHO ==================
$sql = "SELECT lsxk.*, sp.ten_san_pham as product_name 
        FROM lich_su_xuat_kho lsxk 
        LEFT JOIN san_pham sp ON lsxk.product_id = sp.id 
        WHERE 1=1" . $filter_sql . " ORDER BY lsxk.created_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Lỗi chuẩn bị câu lệnh select: " . $conn->error);
}

// Bind parameters
$current_param_types = $param_types . 'ii';
$current_params = array_merge($params, [$limit, $offset]);

if (count($current_params) > 0) {
    $bind_params = [];
    $bind_params[] = $current_param_types;

    foreach ($current_params as $key => $value) {
        $bind_params[] = &$current_params[$key];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind_params);
}

if (!$stmt->execute()) {
    die("Lỗi thực thi câu lệnh select: " . $stmt->error);
}

$result = $stmt->get_result();

// Lấy danh sách sản phẩm cho modal (chỉ lấy sản phẩm còn hàng)
$products_sql = "SELECT id, ten_san_pham, so_luong FROM san_pham WHERE so_luong > 0 ORDER BY ten_san_pham ASC";
$products_result = $conn->query($products_sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Xuất Kho</title>
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

        /* Header với nút xuất kho */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .btn-export-stock {
            background: #dc3545;
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

        .btn-export-stock:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
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
        }

        .filter-form label {
            font-weight: 600;
            color: #495057;
        }

        .filter-form input[type="date"] {
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

        /* Bảng lịch sử */
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
            background: #dc3545;
            color: white;
            font-weight: 600;
        }

        .table tr:hover {
            background: #f9fafc;
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
            margin: 5% auto;
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
        .form-group select,
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
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .stock-info {
            padding: 10px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 14px;
            color: #856404;
            display: none;
        }

        .btn-submit {
            background: #dc3545;
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
            background: #c82333;
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

        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .reason-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }

        .reason-sale {
            background: #d1ecf1;
            color: #0c5460;
        }

        .reason-damaged {
            background: #f8d7da;
            color: #721c24;
        }

        .reason-return {
            background: #fff3cd;
            color: #856404;
        }

        .reason-other {
            background: #e2e3e5;
            color: #383d41;
        }
    </style>
</head>

<body>
    <?php include "sidebar_admin.php"; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="search-box">
                <h1>Quản lý Xuất Kho</h1>
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

                        <button onclick="logoutUser()">
                            <i class="fa-solid fa-sign-out-alt"></i>
                            <span>Đăng xuất</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i> <?= $success_message ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-circle"></i> <?= $error_message ?>
                </div>
            <?php endif; ?>

            <div class="header-section">
                <h2 style="margin: 0; color: #2c3e50;">Lịch sử Xuất Kho</h2>
                <button class="btn-export-stock" onclick="openModal()">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    Xuất Kho
                </button>
            </div>

            <!-- Bộ lọc -->
            <form method="get" class="filter-form">
                <label for="start_date"><i class="fa-solid fa-calendar-day"></i> Từ ngày:</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">

                <label for="end_date">Đến ngày:</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">

                <button type="submit" class="filter-btn-apply">
                    <i class="fa-solid fa-filter"></i> Lọc
                </button>
                <button type="button" onclick="window.location.href='<?= $_SERVER['PHP_SELF'] ?>'" class="filter-btn-clear">
                    <i class="fa-solid fa-xmark"></i> Xóa lọc
                </button>
            </form>

            <!-- Bảng lịch sử -->
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Lý do xuất</th>
                            <th>Ghi chú</th>
                            <th>Ngày xuất</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stt = $offset + 1;
                        while ($row = $result->fetch_assoc()):
                            // Xác định class cho reason badge
                            $reason_class = 'reason-other';
                            $reason_text = $row['reason'];

                            if (strpos(strtolower($reason_text), 'bán') !== false || strpos(strtolower($reason_text), 'đơn hàng') !== false) {
                                $reason_class = 'reason-sale';
                            } elseif (strpos(strtolower($reason_text), 'hỏng') !== false || strpos(strtolower($reason_text), 'hư') !== false) {
                                $reason_class = 'reason-damaged';
                            } elseif (strpos(strtolower($reason_text), 'trả') !== false || strpos(strtolower($reason_text), 'hoàn') !== false) {
                                $reason_class = 'reason-return';
                            }
                        ?>
                            <tr>
                                <td><?= $stt++ ?></td>
                                <td><?= htmlspecialchars($row['product_name'] ?? 'N/A') ?></td>
                                <td><strong style="color: #dc3545;"><?= htmlspecialchars($row['quantity']) ?></strong></td>
                                <td><span class="reason-badge <?= $reason_class ?>"><?= htmlspecialchars($reason_text) ?></span></td>
                                <td><?= htmlspecialchars($row['note'] ?? '-') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
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
                    <i class="fa-solid fa-box-open" style="font-size: 48px; color: #dee2e6; margin-bottom: 10px;"></i>
                    <p>Chưa có lịch sử xuất kho nào.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Xuất Kho -->
    <div id="exportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Xuất Kho Hàng</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="product_id">Sản phẩm <span style="color: red;">*</span></label>
                    <select name="product_id" id="product_id" required onchange="updateStockInfo()">
                        <option value="">-- Chọn sản phẩm --</option>
                        <?php
                        if ($products_result && $products_result->num_rows > 0):
                            while ($product = $products_result->fetch_assoc()):
                        ?>
                                <option value="<?= $product['id'] ?>" data-stock="<?= $product['so_luong'] ?>">
                                    <?= htmlspecialchars($product['ten_san_pham']) ?> (Tồn: <?= $product['so_luong'] ?>)
                                </option>
                        <?php
                            endwhile;
                        endif;
                        ?>
                    </select>
                    <div id="stockInfo" class="stock-info"></div>
                </div>

                <div class="form-group">
                    <label for="quantity">Số lượng <span style="color: red;">*</span></label>
                    <input type="number" name="quantity" id="quantity" min="1" required placeholder="Nhập số lượng" onchange="checkQuantity()">
                </div>

                <div class="form-group">
                    <label for="reason">Lý do xuất <span style="color: red;">*</span></label>
                    <select name="reason" id="reason" required>
                        <option value="">-- Chọn lý do --</option>
                        <option value="Bán hàng">Bán hàng</option>
                        <option value="Hàng hỏng">Hàng hỏng</option>
                        <option value="Trả hàng nhà cung cấp">Trả hàng nhà cung cấp</option>
                        <option value="Khuyến mãi">Khuyến mãi</option>
                        <option value="Mẫu thử">Mẫu thử</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="note">Ghi chú</label>
                    <textarea name="note" id="note" placeholder="Ghi chú thêm (tùy chọn)"></textarea>
                </div>

                <button type="submit" name="export_stock" class="btn-submit">
                    <i class="fa-solid fa-check"></i> Xác nhận xuất kho
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
            document.getElementById('exportModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('exportModal').style.display = 'none';
            // Reset form
            document.getElementById('stockInfo').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('exportModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        function updateStockInfo() {
            const select = document.getElementById('product_id');
            const stockInfo = document.getElementById('stockInfo');
            const quantityInput = document.getElementById('quantity');

            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const stock = selectedOption.getAttribute('data-stock');

                stockInfo.innerHTML = '<i class="fa-solid fa-info-circle"></i> Số lượng tồn kho hiện tại: <strong>' + stock + '</strong>';
                stockInfo.style.display = 'block';

                // Set max cho input quantity
                quantityInput.max = stock;
            } else {
                stockInfo.style.display = 'none';
                quantityInput.max = '';
            }
        }

        function checkQuantity() {
            const select = document.getElementById('product_id');
            const quantityInput = document.getElementById('quantity');

            if (select.value && quantityInput.value) {
                const selectedOption = select.options[select.selectedIndex];
                const stock = parseInt(selectedOption.getAttribute('data-stock'));
                const quantity = parseInt(quantityInput.value);

                if (quantity > stock) {
                    alert('Số lượng xuất không được vượt quá số lượng tồn kho (' + stock + ')!');
                    quantityInput.value = stock;
                }
            }
        }
    </script>

    <?php
    if (isset($stmt)) $stmt->close();
    $conn->close();
    ?>
</body>

</html>