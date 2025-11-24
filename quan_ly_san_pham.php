<?php
include "Database/connectdb.php";

// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}

// --- LOGIC PHÂN TRANG (PAGINATION) ---
$limit = 5; // Số sản phẩm hiển thị trên mỗi trang (có thể thay đổi)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit; // Vị trí bắt đầu lấy dữ liệu

// Lấy giá trị tìm kiếm và bộ lọc
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Chuẩn bị cho việc ngăn chặn SQL Injection
// CHÚ Ý: Đã dùng Prepared Statements ở dưới nên việc này là dự phòng/thừa.
$search_safe = mysqli_real_escape_string($conn, $search);

// --- 1. Xây dựng Điều kiện WHERE ---
$where_condition = " WHERE 1";
$params = [];
$types = "";

// Thêm điều kiện tìm kiếm
if ($search !== '') {
    // Để an toàn hơn, chúng ta sẽ thêm dấu % vào biến sau khi escape
    $searchTerm = "%" . $search_safe . "%";
    // Tìm theo tên sản phẩm (LIKE) hoặc ID (=)
    $where_condition .= " AND (ten_san_pham LIKE ? OR id = ?)";
    $params[] = $searchTerm;
    $params[] = $search_safe;
    $types .= "ss";
}

// Thêm điều kiện lọc tồn kho
switch ($filter) {
    case 'con':
        $where_condition .= " AND so_luong > ?";
        $params[] = 10;
        $types .= "i";
        break;
    case 'saphet':
        $where_condition .= " AND so_luong > ? AND so_luong <= ?";
        $params[] = 0;
        $params[] = 10;
        $types .= "ii";
        break;
    case 'hethang':
        $where_condition .= " AND so_luong = ?";
        $params[] = 0;
        $types .= "i";
        break;
}

// Hàm hỗ trợ để lấy tham chiếu cho call_user_func_array
function ref_values($arr)
{
    $refs = array();
    foreach ($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs;
}

// --- 2. Tính Tổng số lượng sản phẩm (Total Rows) ---
$count_sql = "SELECT COUNT(*) AS total FROM san_pham" . $where_condition;

// Sử dụng Prepared Statement cho COUNT để đảm bảo an toàn
$stmt_count = $conn->prepare($count_sql);
$count_params = [];
$count_types = "";

// Lấy các tham số và kiểu dữ liệu cho COUNT (chỉ cần lấy phần của WHERE)
if (!empty($params)) {
    // Chỉ lấy các tham số và kiểu dữ liệu trước khi thêm LIMIT/OFFSET
    $count_params = array_slice($params, 0, count($params) - (count($params) > 0 && substr($types, -2) === 'ii' ? 2 : 0));
    $count_types = substr($types, 0, strlen($types) - (count($params) > 0 && substr($types, -2) === 'ii' ? 2 : 0));
}

if (!empty($count_types)) {
    $bind_params = array_merge([$count_types], $count_params);
    call_user_func_array([$stmt_count, 'bind_param'], ref_values($bind_params));
}
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Đảm bảo trang hiện tại nằm trong phạm vi hợp lệ
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// --- 3. Lấy Dữ liệu Sản phẩm cho trang hiện tại ---
$sql = "SELECT * FROM san_pham" . $where_condition . " ORDER BY id DESC LIMIT ? OFFSET ?";
$types .= "ii"; // Thêm 2 kiểu cho LIMIT và OFFSET
$params[] = $limit;
$params[] = $offset;

// Sử dụng Prepared Statement cho truy vấn chính
$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], ref_values($bind_params));
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Kho hàng - Danh sách sản phẩm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">

    <style>
        /* CSS CŨ CỦA BẠN - GIỮ NGUYÊN */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f4f6f9;
            /* Màu nền nhẹ hơn, hiện đại hơn */
            color: #000;
            display: flex;
            padding-left: 250px;
            transition: padding-left 0.3s ease;
        }

        .container {
            width: 100%;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            padding-top: 100px;
            min-height: 100vh;
        }

        .container-fluid {
            max-width: 1250px;
            margin: 0 auto;
        }

        /* ==== TOP BAR (GIỮ NGUYÊN/CHỈNH SỬA NHẸ) ==== */
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
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            /* Đổ bóng nhẹ nhàng hơn */
            margin-bottom: 0;
            z-index: 100;
        }

        .search-box h1 {
            font-size: 1.6rem;
            color: #2c3e50;
            margin: 0;
            font-weight: 600;
        }

        .user-box {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-box .fa-bell {
            font-size: 1.2rem;
            color: #7f8c8d;
            cursor: pointer;
            transition: color 0.2s;
        }

        .user-box .fa-bell:hover {
            color: #3498db;
        }

        /* ==== USER DROPDOWN (GIỮ NGUYÊN CSS CŨ CHO ĐỒNG BỘ) ==== */
        /* ... CSS user-menu, user-menu-btn, dropdown-menu ... */
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

        /* ==== KẾT THÚC TOP BAR CSS CŨ ==== */


        /* ==== THANH CHỨC NĂNG MỚI (SEARCH/FILTER/BUTTONS) ==== */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.05);
        }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        /* Thay đổi CSS cho Form tìm kiếm để nhóm input và nút lại */
        .search-form-group {
            display: flex;
            gap: 0;
            /* Loại bỏ khoảng cách giữa input và button */
        }

        .search-form-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            min-width: 200px;
            /* Giảm độ rộng tối thiểu để dành chỗ cho nút */
            border-right: none;
        }

        .search-button,
        .clear-button {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            /* Bằng chiều cao của input */
            margin-left: -1px;
            /* Trừ đi 1px border để liền mạch */
        }

        .search-button {
            background-color: #0a74e6;
            color: #fff;
            border-color: #0a74e6;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        .search-button:hover {
            background-color: #0d55a1;
            border-color: #0d55a1;
        }

        .clear-button {
            background-color: #e74c3c;
            color: #fff;
            border-color: #e74c3c;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
            margin-left: -1px;
            /* Kéo nút lùi lại để liền với input */

        }

        .clear-button:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }

        /* Cập nhật lại form-control chung */
        .form-control,
        .form-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
            height: 40px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .form-select {
            width: 180px;
            cursor: pointer;
            background-color: #fff;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%236c757d%22%20d%3D%22M287%20173.5c-4.4%204.4-10.9%207-17.5%207h-241c-6.6%200-13.1-2.6-17.5-7-9.7-9.7-9.7-25.5%200-35.2l120.5-120.5c9.7-9.7%2025.5-9.7%2035.2%200l120.5%20120.5c9.6%209.7%209.6%2025.5-.1%2035.2z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 10px;
        }

        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* ==== BUTTONS MODERN (Giữ nguyên) ==== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 15px;
            text-transform: capitalize;
            height: 40px;
            /* Đồng bộ chiều cao */
            box-sizing: border-box;
        }

        .btn i {
            margin-right: 5px;
        }

        .btn-success {
            background-color: #0a74e6;
            color: #fff;
        }

        .btn-success:hover {
            background-color: #0d55a1;
            transform: translateY(-1px);
        }

        .btn-delete-multi {
            background-color: #e74c3c;
            color: #fff;
            opacity: 0.6;
            pointer-events: none;
        }

        .btn-delete-multi.active {
            opacity: 1;
            pointer-events: auto;
        }

        .btn-delete-multi.active:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 6px 8px;
            font-size: 13px;
            border-radius: 4px;
        }

        /* ==== DATA TABLE MODERN (Giữ nguyên) ==== */
        .table-container {
            background: #fff;
            border-radius: 10px;
            padding: 0;
            /* Giảm padding tổng thể */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            /* Đổ bóng nổi bật hơn */
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            /* Thay đổi để bo góc ô */
            border-spacing: 0;
            margin: 0;
        }

        .data-table th,
        .data-table td {
            padding: 15px 12px;
            border: none;
            text-align: center;
            vertical-align: middle;
            font-size: 14px;
            border-bottom: 1px solid #e9ecef;
            /* Chỉ giữ đường viền dưới */
        }

        .data-table thead th {
            background-color: #2c3e50;
            /* Màu nền tối hơn cho header */
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
        }

        .data-table thead tr th:first-child {
            border-top-left-radius: 10px;
        }

        .data-table thead tr th:last-child {
            border-top-right-radius: 10px;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Loại bỏ border dưới cùng */

        .data-table tbody tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        .data-table tbody tr:hover {
            background-color: #f7f9fa;
        }

        .data-table img {
            width: 50px;
            /* Nhỏ hơn một chút */
            height: 50px;
            border-radius: 6px;
            border: 1px solid #eee;
            padding: 2px;
            background: #fff;
            display: block;
            margin: 0 auto;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 5px;
        }

        .action-buttons .btn-primary {
            background-color: #3498db;
        }

        .action-buttons .btn-warning {
            background-color: #f39c12;
        }

        .action-buttons .btn-danger {
            background-color: #e74c3c;
        }

        .action-buttons .btn:hover {
            opacity: 0.85;
        }

        /* Tùy chỉnh input số lượng trong bảng */
        .quantity-input {
            width: 60px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }


        /* ==== STATUS BADGES MODERN (Giữ nguyên) ==== */
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            /* Lớn hơn */
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
        }

        .status-con {
            background-color: #e8f5e9;
            /* Nền xanh lá nhạt */
            color: #27ae60;
            /* Chữ xanh lá đậm */
        }

        .status-saphet {
            background-color: #fff9e6;
            /* Nền vàng nhạt */
            color: #f39c12;
            /* Chữ vàng/cam đậm */
        }

        .status-het {
            background-color: #fbecec;
            /* Nền đỏ nhạt */
            color: #e74c3c;
            /* Chữ đỏ đậm */
        }

        /* Responsive Adjustments (Giữ nguyên hoặc cải thiện) */
        @media (max-width: 992px) {
            body {
                padding-left: 0;
            }

            .topbar {
                left: 0;
            }

            .main-content {
                padding: 15px;
                padding-top: 80px;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group,
            .action-group {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }

            .form-select,
            .btn {
                width: 100% !important;
                max-width: none;
            }

            /* Điều chỉnh cho nhóm tìm kiếm */
            .search-form-group {
                width: 100%;
            }

            .search-form-group .form-control,
            .search-button,
            .clear-button {
                width: auto;
                flex-grow: 1;
            }

            .search-form-group .form-control {
                min-width: unset;
                flex-grow: 2;
            }
        }

        /* ==== PHÂN TRANG (PAGINATION) MODERN (Giữ nguyên) ==== */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 15px 0;
        }

        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .pagination-item {
            margin: 0 4px;
        }

        .pagination-link {
            display: block;
            padding: 8px 12px;
            text-decoration: none;
            color: #3498db;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 14px;
            min-width: 30px;
            text-align: center;
        }

        .pagination-link:hover:not(.active) {
            background-color: #f0f4f7;
            border-color: #3498db;
        }

        .pagination-item.active .pagination-link {
            background-color: #0a74e6;
            color: #fff;
            border-color: #0a74e6;
            font-weight: 600;
        }

        .pagination-info {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 500;
        }
    </style>

</head>

<body>
    <div class="container">
        <?php include "sidebar_admin.php"; ?>

        <div class="main-content">
            <div class="container-fluid">
                <div class="topbar">
                    <div class="search-box">
                        <h1>Danh sách sản phẩm</h1>
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
                <div class="toolbar">
                    <form class="filter-group" method="get">
                        <div class="search-form-group">
                            <input type="text" name="search" id="search-input" class="form-control"
                                placeholder="Tìm theo tên hoặc mã sản phẩm..."
                                value="<?= htmlspecialchars($search) ?>">

                            <?php if (!empty($search)): ?>
                                <button type="button" class="clear-button" title="Bỏ tìm kiếm"
                                    onclick="clearSearchAndSubmit('<?= htmlspecialchars($filter) ?>')">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            <?php else: ?>
                                <button type="submit" class="search-button" title="Tìm kiếm">
                                    <i class="fa-solid fa-search"></i>
                                </button>
                            <?php endif; ?>
                        </div>

                        <select name="filter" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="con" <?= $filter === 'con' ? 'selected' : '' ?>>Còn hàng (&gt;10)</option>
                            <option value="saphet" <?= $filter === 'saphet' ? 'selected' : '' ?>>Sắp hết (&lt;=10)</option>
                            <option value="hethang" <?= $filter === 'hethang' ? 'selected' : '' ?>>Hết hàng</option>
                        </select>
                    </form>

                    <div class="action-group">
                        <a href="themsanpham.php" class="btn btn-success">
                            <i class="fa fa-plus"></i> Thêm sản phẩm
                        </a>

                        <button type="button" class="btn btn-delete-multi" id="btn-delete-multi" onclick="deleteSelectedProducts()">
                            <i class="fa-solid fa-trash"></i> Xóa nhanh (<span id="selected-count">0</span>)
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 30px;"><input type="checkbox" id="select-all"></th>
                                <th>ID</th>
                                <th>Tên sản phẩm</th>
                                <th>Phân loại</th>
                                <th>Loại chính</th>
                                <th>Giá</th>
                                <th>Ảnh</th>
                                <th>Số lượng tồn</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <?php
                                    $so_luong = (int)$row['so_luong'];
                                    if ($so_luong == 0) {
                                        $trang_thai = "Hết hàng";
                                        $badge_class = "status-het";
                                    } elseif ($so_luong <= 10) {
                                        $trang_thai = "Sắp hết";
                                        $badge_class = "status-saphet";
                                    } else {
                                        $trang_thai = "Còn hàng";
                                        $badge_class = "status-con";
                                    }
                                    ?>
                                    <tr id="row-<?= $row['id'] ?>">
                                        <td><input type="checkbox" name="product_id[]" class="product-checkbox" value="<?= $row['id'] ?>"></td>

                                        <td><?= $row['id'] ?></td>
                                        <td style="text-align: left; max-width: 250px;"><?= htmlspecialchars($row['ten_san_pham']) ?></td>
                                        <td><?= htmlspecialchars($row['phan_loai']) ?></td>
                                        <td><?= htmlspecialchars($row['loai_chinh']) ?></td>
                                        <td><?= number_format($row['gia'], 0, ',', '.') ?>đ</td>
                                        <td>
                                            <?php
                                            $hinh_anh = $row['hinh_anh'];
                                            if (!empty($hinh_anh)) {
                                                // Điều chỉnh đường dẫn ảnh (giữ nguyên logic cũ của bạn)
                                                if (!preg_match('/^http/', $hinh_anh) && !str_contains($hinh_anh, 'uploads/')) {
                                                    $hinh_anh = "../uploads/" . $hinh_anh;
                                                }
                                                echo '<img src="' . htmlspecialchars($hinh_anh) . '" alt="Ảnh sản phẩm">';
                                            } else {
                                                echo '<img src="../uploads/no-image.png" alt="Không có ảnh">';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <input type="number" id="so_luong_<?= $row['id'] ?>" class="quantity-input" value="<?= $row['so_luong'] ?>" min="0">
                                        </td>
                                        <td><span class="status-badge <?= $badge_class ?>"><?= $trang_thai ?></span></td>
                                        <td style="font-size: 13px;"><?= date("Y-m-d H:i", strtotime($row['ngay_tao'])) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary btn-sm" title="Lưu số lượng" onclick="updateProduct(<?= $row['id'] ?>)">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <a href="sua_sanpham.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Chỉnh sửa">
                                                    <i class="fa fa-pen"></i>
                                                </a>
                                                <a href="xoa_sanpham.php?id=<?= $row['id'] ?>"
                                                    class="btn btn-danger btn-sm" title="Xóa"
                                                    onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center" style="padding: 30px; color: #7f8c8d;">Không có sản phẩm nào phù hợp</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="pagination-container">
                <ul class="pagination">
                    <?php if ($total_pages > 1): ?>
                        <?php
                        // Hàm tạo URL phân trang, giữ lại search và filter hiện tại
                        function getPaginationUrl($page, $search, $filter)
                        {
                            $url = '?page=' . $page;
                            if (!empty($search)) {
                                $url .= '&search=' . urlencode($search);
                            }
                            if ($filter !== 'all') {
                                $url .= '&filter=' . urlencode($filter);
                            }
                            return $url;
                        }

                        // Tính toán các trang cần hiển thị (ví dụ: hiển thị 5 trang xung quanh trang hiện tại)
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        // Điều chỉnh nếu ở gần đầu hoặc cuối
                        if ($start_page <= 2) $end_page = min($total_pages, 5);
                        if ($end_page >= $total_pages - 1) $start_page = max(1, $total_pages - 4);
                        $start_page = max(1, $start_page); // Đảm bảo không nhỏ hơn 1
                        $end_page = min($total_pages, $end_page); // Đảm bảo không lớn hơn tổng số trang

                        // Nút Previous
                        if ($page > 1) {
                            echo '<li class="pagination-item"><a class="pagination-link" href="' . getPaginationUrl($page - 1, $search, $filter) . '" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></a></li>';
                        } else {
                            echo '<li class="pagination-item"><span class="pagination-link" style="opacity: 0.5; cursor: default;"><i class="fa-solid fa-chevron-left"></i></span></li>';
                        }

                        // Hiển thị nút trang 1 nếu nó không nằm trong phạm vi hiển thị
                        if ($start_page > 1) {
                            echo '<li class="pagination-item"><a class="pagination-link" href="' . getPaginationUrl(1, $search, $filter) . '">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="pagination-item"><span class="pagination-link" style="border: none; cursor: default;">...</span></li>';
                            }
                        }

                        // Hiển thị các trang chính
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active_class = ($i == $page) ? 'active' : '';
                            echo '<li class="pagination-item ' . $active_class . '"><a class="pagination-link" href="' . getPaginationUrl($i, $search, $filter) . '">' . $i . '</a></li>';
                        }

                        // Hiển thị nút trang cuối nếu nó không nằm trong phạm vi hiển thị
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="pagination-item"><span class="pagination-link" style="border: none; cursor: default;">...</span></li>';
                            }
                            echo '<li class="pagination-item"><a class="pagination-link" href="' . getPaginationUrl($total_pages, $search, $filter) . '">' . $total_pages . '</a></li>';
                        }


                        // Nút Next
                        if ($page < $total_pages) {
                            echo '<li class="pagination-item"><a class="pagination-link" href="' . getPaginationUrl($page + 1, $search, $filter) . '" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></a></li>';
                        } else {
                            echo '<li class="pagination-item"><span class="pagination-link" style="opacity: 0.5; cursor: default;"><i class="fa-solid fa-chevron-right"></i></span></li>';
                        }
                        ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Hàm để chuyển hướng đến trang với search rỗng (bỏ tìm kiếm)
        function clearSearchAndSubmit(filter) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('search'); // Xóa tham số search
            currentUrl.searchParams.delete('page'); // Về trang 1
            if (filter !== 'all') {
                currentUrl.searchParams.set('filter', filter);
            }
            window.location.href = currentUrl.toString();
        }

        // Script để bật tắt dropdown menu (GIỮ NGUYÊN)
        function toggleUserMenu() {
            document.querySelector('.user-menu').classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            if (userMenu && !userMenu.contains(event.target)) {
                userMenu.classList.remove('active');
            }
        });

        // Script LOGOUT (GIỮ NGUYÊN)
        function logoutUser() {
            window.location.href = "logout.php";
        }

        // --- LOGIC CHECKBOX VÀ XÓA NHANH (GIỮ NGUYÊN) ---
        const selectAllCheckbox = document.getElementById('select-all');
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        const deleteMultiButton = document.getElementById('btn-delete-multi');
        const selectedCountSpan = document.getElementById('selected-count');

        // Cập nhật trạng thái nút Xóa Nhanh
        function updateDeleteButtonState() {
            const selectedCount = document.querySelectorAll('.product-checkbox:checked').length;
            selectedCountSpan.textContent = selectedCount;
            if (selectedCount > 0) {
                deleteMultiButton.classList.add('active');
            } else {
                deleteMultiButton.classList.remove('active');
            }
        }

        // Bắt sự kiện chọn tất cả
        selectAllCheckbox.addEventListener('change', function() {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButtonState();
        });

        // Bắt sự kiện chọn từng sản phẩm
        productCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateDeleteButtonState();
                // Nếu một checkbox bị bỏ chọn, thì bỏ chọn "chọn tất cả"
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    // Nếu tất cả đều được chọn, thì chọn "chọn tất cả"
                    const allSelected = Array.from(productCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allSelected;
                }
            });
        });

        // Hàm Xóa Sản phẩm đã chọn
        function deleteSelectedProducts() {
            const selectedIds = Array.from(productCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            if (selectedIds.length === 0) {
                alert('Vui lòng chọn ít nhất một sản phẩm để xóa.');
                return;
            }

            if (confirm(`Bạn có chắc chắn muốn xóa ${selectedIds.length} sản phẩm đã chọn?`)) {
                // Sử dụng AJAX để gửi request xóa hàng loạt (Cần tạo file delete_multi.php)
                window.location.href = `xoa_sanpham_multi.php?ids=${selectedIds.join(',')}`;
            }
        }

        // Hàm cập nhật số lượng (Cần tạo file update_quantity.php)
        function updateProduct(id) {
            const quantityInput = document.getElementById(`so_luong_${id}`);
            const newQuantity = quantityInput.value;

            // Ở đây bạn sẽ thêm logic AJAX để gửi ID và số lượng mới đến server 
            // và xử lý cập nhật trạng thái nếu cần.
            // Hiện tại chỉ là một cảnh báo đơn giản.
            alert(`Đã gửi yêu cầu cập nhật ID ${id} với số lượng mới là: ${newQuantity}`);
        }

        // Khởi tạo trạng thái nút xóa khi trang tải xong
        updateDeleteButtonState();
    </script>
</body>

</html>