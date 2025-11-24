<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Thiết lập múi giờ cho PHP để các hàm DateTime hoạt động chính xác
date_default_timezone_set('Asia/Ho_Chi_Minh');

include "Database/connectdb.php";

// ================= KIỂM TRA QUYỀN ADMIN =================
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'superadmin')) {
    echo "<script>alert('Bạn không có quyền truy cập trang này!'); window.location.href='login.php';</script>";
    exit();
}

// =================== THIẾT LẬP LỌC & TÌM KIẾM ===================
$search_keyword = $_GET['search'] ?? '';
$filter_module = $_GET['module'] ?? '';
$filter_time = $_GET['time'] ?? ''; // '7days', '30days', 'all'

$query = "SELECT ten_tai_khoan, module, hanh_dong_chi_tiet, ngay_gio 
          FROM nhat_ky_hoat_dong 
          WHERE 1";
$params = [];
$types = '';

// Lọc theo thời gian (sử dụng NOW() của MySQL, không cần Prepared Statement)
if ($filter_time === '7days') {
    $query .= " AND ngay_gio >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filter_time === '30days') {
    $query .= " AND ngay_gio >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

// Lọc theo module
if (!empty($filter_module)) {
    $query .= " AND module = ?";
    $params[] = $filter_module;
    $types .= 's';
}

// Tìm kiếm theo nội dung
if (!empty($search_keyword)) {
    // Để tìm kiếm an toàn, sử dụng % trong PHP và bind_param
    $query .= " AND hanh_dong_chi_tiet LIKE ?";
    $params[] = "%" . $search_keyword . "%";
    $types .= 's';
}

$query .= " ORDER BY ngay_gio DESC";

$stmt = $conn->prepare($query);

// ✅ KIỂM TRA LỖI PREPARE (Bảo vệ thêm)
if ($stmt === false) {
    die("Lỗi SQL Prepare: " . $conn->error);
}

if (!empty($types)) {
    // Cần hàm hỗ trợ ref_values nếu bạn chưa có nó
    if (!function_exists('ref_values')) {
        function ref_values($arr)
        {
            $refs = array();
            foreach ($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
    }
    // Thực hiện bind_param an toàn với số lượng tham số động
    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], ref_values($bind_params));
}

$stmt->execute();
$logs = $stmt->get_result();

// Lấy danh sách các module duy nhất để tạo dropdown lọc
$module_query = "SELECT DISTINCT module FROM nhat_ky_hoat_dong ORDER BY module ASC";
$modules_result = mysqli_query($conn, $module_query);
$available_modules = [];
while ($row = mysqli_fetch_assoc($modules_result)) {
    $available_modules[] = $row['module'];
}

mysqli_close($conn);

// Hàm định dạng thời gian
function format_time_ago($datetime)
{
    $time = new DateTime($datetime);
    $now = new DateTime();
    $interval = $time->diff($now);

    if ($interval->y > 0) return $interval->format('%y năm trước');
    if ($interval->m > 0) return $interval->format('%m tháng trước');
    if ($interval->d > 0) return $interval->format('%d ngày trước');
    if ($interval->h > 0) return $interval->format('%h giờ trước');
    if ($interval->i > 0) return $interval->format('%i phút trước');
    return 'Vừa xong';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Nhật ký Hoạt động</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ... CSS giữ nguyên ... */
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6fa;
            margin: 0;
            display: flex;
        }

        .container {
            display: flex;
            width: 100%;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .log-container {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 1.8rem;
        }

        /* ==== FILTER BAR ==== */
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar input[type="text"] {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            max-width: 300px;
        }

        .filter-bar select,
        .filter-bar button {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
        }

        .filter-bar button {
            background: #3498db;
            color: white;
            border-color: #3498db;
            transition: background 0.2s;
        }

        .filter-bar button:hover {
            background: #2980b9;
        }

        /* ==== LOG LIST ==== */
        .log-table {
            width: 100%;
            border-collapse: collapse;
        }

        .log-table th {
            background: #f9f9f9;
            color: #777;
            padding: 12px 15px;
            text-align: left;
            font-size: 0.95rem;
            border-bottom: 2px solid #eee;
        }

        .log-table td {
            padding: 15px;
            border-bottom: 1px solid #f3f3f3;
            font-size: 0.9rem;
            vertical-align: top;
        }

        .log-table tr:hover {
            background: #fcfcfc;
        }

        .log-user {
            font-weight: 600;
            color: #2c3e50;
        }

        .log-module {
            font-weight: 500;
            color: #9b59b6;
        }

        .log-time {
            color: #95a5a6;
            white-space: nowrap;
        }

        .log-action-detail {
            margin-top: 5px;
            color: #34495e;
            line-height: 1.4;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include "sidebar_admin.php"; ?>
        <div class="main-content">
            <h1><i class="fa-solid fa-list-check"></i> Chi tiết Nhật ký Hoạt động</h1>

            <form method="get" class="filter-bar">
                <input type="text" name="search" placeholder="Tìm kiếm nội dung thao tác..."
                    value="<?= htmlspecialchars($search_keyword) ?>">

                <select name="module">
                    <option value="">-- Module --</option>
                    <?php foreach ($available_modules as $module): ?>
                        <option value="<?= htmlspecialchars($module) ?>"
                            <?= $filter_module === $module ? 'selected' : '' ?>>
                            <?= htmlspecialchars($module) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="time">
                    <option value="all" <?= $filter_time === 'all' ? 'selected' : '' ?>>-- Mọi lúc --</option>
                    <option value="7days" <?= $filter_time === '7days' ? 'selected' : '' ?>>7 ngày qua</option>
                    <option value="30days" <?= $filter_time === '30days' ? 'selected' : '' ?>>30 ngày qua</option>
                </select>

                <button type="submit">Lọc</button>
                <a href="nhat_ky_hoat_dong.php" style="text-decoration:none;">
                    <button type="button" style="background:#e74c3c;border-color:#e74c3c;">
                        <i class="fa-solid fa-undo"></i> Reset
                    </button>
                </a>
            </form>

            <div class="log-container">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Thời gian</th>
                            <th style="width: 20%;">Người dùng</th>
                            <th style="width: 15%;">Module</th>
                            <th style="width: 50%;">Thao tác chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs && mysqli_num_rows($logs) > 0): ?>
                            <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                                <tr>
                                    <td class="log-time" title="<?= date('d/m/Y H:i:s', strtotime($log['ngay_gio'])) ?>">
                                        <?= format_time_ago($log['ngay_gio']); ?>
                                    </td>
                                    <td class="log-user">
                                        <?= htmlspecialchars($log['ten_tai_khoan']); ?>
                                    </td>
                                    <td class="log-module">
                                        <?= htmlspecialchars($log['module']); ?>
                                    </td>
                                    <td>
                                        <div class="log-action-detail">
                                            <?= htmlspecialchars($log['hanh_dong_chi_tiet']); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; color:#95a5a6;">Không tìm thấy hoạt động nào phù hợp với bộ lọc.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>