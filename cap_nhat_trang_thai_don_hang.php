<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Giả định file này chứa kết nối CSDL $conn
include "Database/connectdb.php";
// Giả định file này chứa hàm log_activity
if (file_exists("Database/log_activity.php")) {
    include "Database/log_activity.php";
} else {
    function log_activity($conn, $category, $action) {}
}

// ================== ĐỊNH NGHĨA TRẠNG THÁI (MAPPING) ==================
$statuses = [
    0 => ['text' => 'Chờ xác nhận', 'class' => 'pending', 'label' => 'Chờ xác nhận'],
    4 => ['text' => 'Đang chuẩn bị hàng', 'class' => 'preparing', 'label' => 'Đang chuẩn bị hàng'],
    1 => ['text' => 'Đang giao', 'class' => 'shipping', 'label' => 'Đang giao'],
    2 => ['text' => 'Đã giao', 'class' => 'done', 'label' => 'Đã giao'],
    3 => ['text' => 'Đã hủy', 'class' => 'cancelled', 'label' => 'Đã hủy']
];
ksort($statuses);

// Biến thông báo
$message = '';
$message_type = ''; // 'success' hoặc 'error'


// ================== HÀM KIỂM TRA QUY TẮC CHUYỂN ĐỔI NGHIỆP VỤ (YÊU CẦU 5) ==================
/**
 * Kiểm tra xem việc chuyển từ trạng thái cũ sang trạng thái mới có hợp lệ không.
 * Quy tắc: 0->4, 4->1, 1->2. Hủy: 0->3, 4->3. Các luồng khác bị cấm.
 */
function validate_status_change($old_status, $new_status)
{
    // Không thể chuyển đổi nếu trạng thái cũ là Đã giao (2) hoặc Đã hủy (3)
    if ($old_status == 2 || $old_status == 3) {
        return false;
    }

    // Quy tắc chuyển đổi hợp lệ (từ cũ -> mới)
    $valid_transitions = [
        0 => [4, 3], // Chờ xác nhận -> Đang chuẩn bị (4) HOẶC Đã hủy (3)
        4 => [1, 3], // Đang chuẩn bị -> Đang giao (1) HOẶC Đã hủy (3)
        1 => [2]    // Đang giao -> Đã giao (2)
    ];

    return isset($valid_transitions[$old_status]) && in_array($new_status, $valid_transitions[$old_status]);
}

// ================== HÀM HOÀN TỒN KHO KHI HỦY (YÊU CẦU 2) ==================
function restore_stock($conn, $order_id)
{
    // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
    $conn->begin_transaction();
    try {
        // 1. Lấy chi tiết sản phẩm và số lượng cần hoàn
        $sql_detail = "SELECT product_id, quantity FROM chi_tiet_don_hang WHERE order_id = ?";
        $stmt_detail = $conn->prepare($sql_detail);
        $stmt_detail->bind_param("i", $order_id);
        $stmt_detail->execute();
        $result_detail = $stmt_detail->get_result();

        // 2. Hoàn tồn kho cho từng sản phẩm
        $sql_update_stock = "UPDATE san_pham SET stock = stock + ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update_stock);

        while ($row = $result_detail->fetch_assoc()) {
            $product_id = $row['product_id'];
            $quantity = $row['quantity'];

            // Bind và thực hiện cập nhật tồn kho
            $stmt_update->bind_param("ii", $quantity, $product_id);
            if (!$stmt_update->execute()) {
                throw new Exception("Lỗi cập nhật tồn kho cho sản phẩm #$product_id.");
            }
        }

        $stmt_detail->close();
        $stmt_update->close();

        $conn->commit();
        return true; // Hoàn tồn kho thành công

    } catch (Exception $e) {
        $conn->rollback();
        // Ghi log lỗi hoàn tồn kho nếu cần
        log_activity($conn, "Lỗi Nghiệp vụ", "Đơn hàng #$order_id: Lỗi hoàn tồn kho: " . $e->getMessage());
        return false; // Hoàn tồn kho thất bại
    }
}

// ================== XỬ LÝ CẬP NHẬT TRẠNG THÁI (TỪ DROPDOWN) - (YÊU CẦU 3, 4, 5, 6, 7) ==================
if (isset($_POST['update_status'])) {
    $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
    $new_status = filter_var($_POST['new_status'], FILTER_SANITIZE_NUMBER_INT);

    // Kiểm tra tính hợp lệ cơ bản
    if (!$order_id || !isset($statuses[$new_status])) {
        $message = 'Dữ liệu cập nhật không hợp lệ.';
        $message_type = 'error';
    } else {
        // 1. Lấy trạng thái cũ
        $sql_get_old = "SELECT status FROM don_hang WHERE id = ?";
        $stmt_old = $conn->prepare($sql_get_old);
        $stmt_old->bind_param("i", $order_id);
        $stmt_old->execute();
        $old_status = $stmt_old->get_result()->fetch_assoc()['status'] ?? -1;
        $stmt_old->close();

        // 2. Kiểm tra ràng buộc nghiệp vụ (Yêu cầu 5)
        if (!validate_status_change($old_status, $new_status)) {
            // Yêu cầu 5X.1: Hiển thị thông báo lỗi
            $old_name = $statuses[$old_status]['text'] ?? 'Không xác định';
            $new_name = $statuses[$new_status]['text'] ?? 'Không xác định';
            $message = "5X.1: Không thể chuyển trạng thái từ **$old_name** sang **$new_name**. Vi phạm quy tắc nghiệp vụ.";
            $message_type = 'error';
        }
        // 3. Xử lý Hoàn tồn kho nếu chuyển sang Đã hủy (Yêu cầu 2)
        else if ($new_status == 3 && $old_status != 3) {
            if (restore_stock($conn, $order_id)) {
                // Tiếp tục cập nhật trạng thái nếu hoàn tồn kho thành công
                goto update_db;
            } else {
                $message = 'Lỗi nghiệp vụ: Không thể hoàn tồn kho. Vui lòng kiểm tra log hệ thống.';
                $message_type = 'error';
            }
        }
        // 4. Cập nhật vào CSDL (Yêu cầu 6)
        else {
            update_db:
            $update_sql = "UPDATE don_hang SET status = ? WHERE id = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("ii", $new_status, $order_id);

            if ($stmt_update->execute()) {
                $status_name = $statuses[$new_status]['text'] ?? 'Không xác định';
                $log_action = "đã cập nhật trạng thái đơn hàng #$order_id thành: **$status_name**";
                log_activity($conn, "Quản lý Đơn hàng", $log_action);

                // Yêu cầu 7: Hiển thị thông báo thành công
                $message = "Cập nhật trạng thái thành công.";
                $message_type = 'success';
            } else {
                $message = "Lỗi CSDL: Không thể cập nhật trạng thái.";
                $message_type = 'error';
            }
            $stmt_update->close();
        }
    }
    // Không cần header redirect nếu muốn hiển thị thông báo ngay trên trang hiện tại
    // Sau khi xử lý POST, reload trang với thông báo qua session/GET hoặc in trực tiếp.
    // Dùng session để hiển thị thông báo sau khi reload (tùy chọn)
    $_SESSION['msg'] = ['text' => $message, 'type' => $message_type];
    header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit();
}

// Lấy thông báo từ session và xóa
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg']['text'];
    $message_type = $_SESSION['msg']['type'];
    unset($_SESSION['msg']);
}

// ... (Giữ nguyên các logic Lấy Thống kê, Xử lý Bộ lọc, Phân trang) ...

// --- Xử lý Bộ lọc Ngày/Tháng và Khởi tạo biến cho Prepared Statement ---
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

// Hàm để lấy giá trị cho trường input ngày
function get_date_value($key)
{
    return isset($_GET[$key]) ? htmlspecialchars($_GET[$key]) : '';
}

// ... (Lấy Thống kê Tổng quan) ...
$stats_sql = "
    SELECT
        COUNT(CASE WHEN status IN (0, 4) THEN 1 END) AS processing_count, 
        COUNT(CASE WHEN status = 1 THEN 1 END) AS shipping_count,
        COUNT(CASE WHEN status = 2 THEN 1 END) AS done_count,
        COUNT(CASE WHEN status = 3 THEN 1 END) AS cancelled_count
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

// ... (Xử lý Phân trang và Truy vấn đơn hàng) ...
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(id) AS total FROM don_hang WHERE 1=1 {$filter_sql}";
$stmt_count = $conn->prepare($count_sql);
if ($filter_sql) {
    $stmt_count->bind_param($param_types, ...$params);
}
$stmt_count->execute();
$total_orders = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();
$total_pages = ceil($total_orders / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
} else if ($page < 1) {
    $page = 1;
    $offset = 0;
}


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
        /* Thêm CSS cho thông báo */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Giữ lại các CSS cũ và CSS phân trang đã thêm trước đó */
        /* ... (CSS cũ và mới) ... */
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

        .table th {
            background: #007bff;
            color: white;
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
                            <button onclick="logoutUser()">
                                <i class="fa-solid fa-sign-out-alt"></i>
                                <span>Đăng xuất</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert <?= $message_type ?>">
                    <i class="fa-solid fa-circle-info"></i> <?= $message ?>
                </div>
            <?php endif; ?>

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
                        $status_info = $statuses[$current_status] ?? $statuses[0];
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
                                <?php if ($current_status == 2 || $current_status == 3): ?>
                                    <span class="status <?= $status_class ?>"><?= $status_info['label'] ?></span>
                                <?php else: ?>
                                    <form method="POST" action="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET) ?>" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="update_status" value="1">

                                        <select name="new_status" class="action-select" onchange="this.form.submit()" style="padding: 8px 10px; border-radius: 5px; border: 1px solid #ccc; background-color: #f9f9f9; cursor: pointer; font-size: 14px;">
                                            <?php foreach ($statuses as $key => $status): ?>
                                                <?php
                                                // Loại bỏ các trạng thái không hợp lệ trong dropdown để hạn chế lỗi
                                                // 1. Chỉ cho phép chuyển đến trạng thái tiếp theo hợp lệ.
                                                if ($key == $current_status) {
                                                    // Luôn hiển thị trạng thái hiện tại
                                                } else if (!validate_status_change($current_status, $key)) {
                                                    // Ẩn các trạng thái không thể chuyển đến
                                                    continue;
                                                }
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
        // ... (Giữ nguyên Script JS) ...
        function toggleUserMenu() {
            const userMenu = document.querySelector('.user-menu');
            userMenu.classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const userBtn = document.querySelector('.user-menu-btn');
            if (userMenu && userBtn && !userMenu.contains(event.target) && !userBtn.contains(event.target)) {
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