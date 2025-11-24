<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php";

// ================== XỬ LÝ NÚT HẾT HÀNG (GIỮ NGUYÊN) ==================
if (isset($_POST['out_of_stock'])) {
    $product_id = intval($_POST['product_id']);
    mysqli_query($conn, "UPDATE san_pham SET so_luong = 0 WHERE id = $product_id");
    header("Location: quan_ly_kho_hang.php");
    exit();
}

// ================== XỬ LÝ NHẬP / XUẤT KHO (GIỮ NGUYÊN) ==================
if (isset($_POST['update_stock'])) {
    $product_id = intval($_POST['product_id']);
    $qty = intval($_POST['quantity']);
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier']);
    $total_price = floatval($_POST['total_price']);
    $action = $_POST['action'];

    if ($action === 'in') {
        $new_price = isset($_POST['new_price']) && $_POST['new_price'] !== '' ? floatval($_POST['new_price']) : null;
        if ($new_price && $new_price > 0) {
            mysqli_query($conn, "UPDATE san_pham SET so_luong = so_luong + $qty, gia = $new_price WHERE id = $product_id");
        } else {
            mysqli_query($conn, "UPDATE san_pham SET so_luong = so_luong + $qty WHERE id = $product_id");
        }

        $gia_moi_sql = $new_price ? $new_price : "NULL";
        mysqli_query($conn, "
            INSERT INTO lich_su_kho (product_id, hanh_dong, so_luong, nha_cung_cap, tong_tien, gia_moi, ngay_thuc_hien)
            VALUES ($product_id, 'Nhập hàng', $qty, '$supplier', $total_price, $gia_moi_sql, NOW())
        ");
    } elseif ($action === 'out') {
        mysqli_query($conn, "UPDATE san_pham SET so_luong = GREATEST(so_luong - $qty, 0) WHERE id = $product_id");
        mysqli_query($conn, "
            INSERT INTO lich_su_kho (product_id, hanh_dong, so_luong, nha_cung_cap, tong_tien, ngay_thuc_hien)
            VALUES ($product_id, 'Xuất hàng', $qty, '$supplier', $total_price, NOW())
        ");
    }

    header("Location: quan_ly_kho_hang.php");
    exit();
}

// ================== TỰ ĐỘNG CẬP NHẬT KHO (GIỮ NGUYÊN) ==================
$processed_orders = mysqli_query($conn, "
    SELECT id FROM don_hang WHERE status = 1 AND (processed_stock IS NULL OR processed_stock = 0)
");

if ($processed_orders && mysqli_num_rows($processed_orders) > 0) {
    while ($order = mysqli_fetch_assoc($processed_orders)) {
        $order_id = intval($order['id']);
        $items = mysqli_query($conn, "SELECT product_id, quantity FROM chi_tiet_don_hang WHERE order_id = $order_id");
        while ($item = mysqli_fetch_assoc($items)) {
            $product_id = intval($item['product_id']);
            $qty = intval($item['quantity']);
            mysqli_query($conn, "
                UPDATE san_pham 
                SET so_luong = GREATEST(so_luong - $qty, 0),
                    so_luong_ban = so_luong_ban + $qty
                WHERE id = $product_id
            ");
        }
        mysqli_query($conn, "UPDATE don_hang SET processed_stock = 1 WHERE id = $order_id");
    }
}

// ================== CẤU HÌNH PHÂN TRANG ==================
$limit = 6; // Số sản phẩm trên mỗi trang (có thể điều chỉnh)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page - 1) * $limit;


// ================== LỌC, TÌM KIẾM & SẮP XẾP ==================
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? '';
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');

// Xây dựng điều kiện WHERE cho truy vấn (tính tổng số trang)
$where_condition = " WHERE 1";

// Lọc trạng thái
if ($filter === 'in_stock') {
    $where_condition .= " AND so_luong > 0";
} elseif ($filter === 'out_of_stock') {
    $where_condition .= " AND so_luong = 0";
}

// Tìm kiếm theo tên
if (!empty($search)) {
    $where_condition .= " AND ten_san_pham LIKE '%$search%'";
}

// 1. TÍNH TỔNG SỐ SẢN PHẨM VÀ TỔNG SỐ TRANG
$count_query = "SELECT COUNT(id) AS total_products FROM san_pham" . $where_condition;
$count_result = mysqli_query($conn, $count_query);
$total_products = mysqli_fetch_assoc($count_result)['total_products'];
$total_pages = ceil($total_products / $limit);

// 2. TRUY VẤN DỮ LIỆU CỦA TRANG HIỆN TẠI
$query = "SELECT *, FORMAT(gia, 0) AS gia_ban_formatted FROM san_pham" . $where_condition;

// Sắp xếp
switch ($sort) {
    case 'best_selling':
        $query .= " ORDER BY so_luong_ban DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY gia ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY gia DESC";
        break;
    case 'name_az':
        $query .= " ORDER BY ten_san_pham ASC";
        break;
    case 'name_za':
        $query .= " ORDER BY ten_san_pham DESC";
        break;
    default:
        $query .= " ORDER BY id DESC"; // Mặc định: mới nhất
}

// Thêm LIMIT và OFFSET cho phân trang
$query .= " LIMIT $limit OFFSET $start";

$products = mysqli_query($conn, $query);
if (!$products) die("Lỗi truy vấn sản phẩm: " . mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý kho hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Tái cấu trúc CSS để phù hợp với giao diện mẫu */
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
        }

        main.main-content {
            flex: 1;
            padding: 20px;
            padding-top: 100px;
            box-sizing: border-box;
        }

        .warehouse-manager {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* ==== TOP BAR ==== */
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
        }

        /* ==== USER DROPDOWN ==== */
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


        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .header-section h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .action-buttons button {
            background: #4a90e2;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-left: 10px;
        }

        .action-buttons button:hover {
            background: #3a7bd2;
        }

        .tabs {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 2px solid #ddd;
        }

        .tab-btn {
            padding: 10px 15px;
            cursor: pointer;
            color: #555;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            border-bottom: 2px solid transparent;
            margin-right: 15px;
        }

        .tab-btn.active {
            color: #4a90e2;
            border-bottom: 2px solid #4a90e2;
        }

        .filter-sort-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-group {
            flex: 1;
            display: flex;
        }

        .search-group input {
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 100%;
            margin-right: 10px;
        }

        .sort-filter-group {
            display: flex;
            gap: 10px;
        }

        .sort-filter-group select,
        .sort-filter-group button {
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        /* Cần thêm CSS cho bảng giống mẫu hơn (ít padding hơn, chỉ có viền dưới) */
        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th,
        .product-table td {
            padding: 10px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .product-table th {
            background-color: #f7f9fc;
            color: #555;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
        }

        .product-table td {
            vertical-align: middle;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-info img {
            border-radius: 4px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-in-stock {
            background-color: #e6f7ff;
            color: #1890ff;
            border: 1px solid #91d5ff;
        }

        .status-out-of-stock {
            background-color: #fff2e8;
            color: #fa541c;
            border: 1px solid #ffbb96;
        }

        /* Thêm style cho cột Giá bán */
        .price-cell {
            font-weight: bold;
            color: #28a745;
            /* Màu xanh lá cây để làm nổi bật giá */
        }

        /* ==== PHÂN TRANG ==== */
        .pagination {
            display: flex;
            justify-content: flex-end;
            /* Căn phải */
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .pagination a,
        .pagination span {
            color: #4a90e2;
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
            border-radius: 4px;
            transition: background-color 0.3s, border-color 0.3s;
            font-size: 0.9rem;
        }

        .pagination a:hover {
            background-color: #f1f3f6;
            border-color: #4a90e2;
        }

        .pagination span.current-page {
            background-color: #4a90e2;
            color: white;
            border: 1px solid #4a90e2;
            font-weight: bold;
        }

        .pagination span.disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        .pagination .info {
            margin-right: auto;
            /* Đẩy thông tin sang bên trái */
            color: #555;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar_admin.php'; // Thanh điều hướng bên trái
        ?>
        <main class="main-content">
            <div class="warehouse-manager">
                <div class="topbar">
                    <div class="search-box">
                        <h1>Quản lý kho</h1>
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
                <div class="header-section">
                    <h2>Cửa hàng chính</h2>
                    <div class="action-buttons">
                        <button onclick="alert('Chức năng chưa được triển khai!')">
                            <i class="fa-solid fa-list-check"></i> Danh sách lô - HSD
                        </button>
                        <button onclick="alert('Chức năng chưa được triển khai!')">
                            <i class="fa-solid fa-file-export"></i> Xuất file
                        </button>
                        <button onclick="alert('Chức năng chưa được triển khai!')">
                            <i class="fa-solid fa-file-import"></i> Nhập file
                        </button>
                    </div>
                </div>

                <div class="tabs">
                    <a href="?filter=all&search=<?= htmlspecialchars($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                        class="tab-btn <?= $filter == 'all' ? 'active' : '' ?>">
                        Tất cả
                    </a>
                    <a href="?filter=in_stock&search=<?= htmlspecialchars($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                        class="tab-btn <?= $filter == 'in_stock' ? 'active' : '' ?>">
                        Còn hàng
                    </a>
                    <a href="?filter=out_of_stock&search=<?= htmlspecialchars($search) ?>&sort=<?= htmlspecialchars($sort) ?>"
                        class="tab-btn <?= $filter == 'out_of_stock' ? 'active' : '' ?>">
                        Hết hàng
                    </a>
                </div>

                <div class="filter-sort-bar">
                    <form method="get" class="search-group" action="quan_ly_kho_hang.php">
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                        <input type="text" name="search"
                            placeholder="🔍 Tìm kiếm theo mã SKU, tên sản phẩm..."
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                            style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                        <button type="submit" style="background:#4a90e2; color:white; border:1px solid #4a90e2; border-radius: 0 6px 6px 0; padding: 10px 15px;">
                            Tìm
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="?filter=<?= htmlspecialchars($filter) ?>&sort=<?= htmlspecialchars($sort) ?>"
                                style="padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; background: #fff; color: #555; text-decoration: none; margin-left: 10px; display: flex; align-items: center; gap: 5px;">
                                <i class="fa-solid fa-times"></i> Bỏ tìm
                            </a>
                        <?php endif; ?>
                    </form>

                    <div class="sort-filter-group">
                        <form method="get" style="display:inline-block;">
                            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            <select name="sort" onchange="this.form.submit()">
                                <option value="">🔽 Sắp xếp theo</option>
                                <option value="best_selling" <?= $sort == 'best_selling' ? 'selected' : '' ?>>🏆 Bán chạy</option>
                                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>💰 Giá tăng dần</option>
                                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>💰 Giá giảm dần</option>
                                <option value="name_az" <?= $sort == 'name_az' ? 'selected' : '' ?>>🔤 Tên A–Z</option>
                                <option value="name_za" <?= $sort == 'name_za' ? 'selected' : '' ?>>🔤 Tên Z–A</option>
                            </select>
                        </form>
                        <button onclick="alert('Bộ lọc khác chưa được triển khai!')" style="background:white; color:#555;">
                            <i class="fa-solid fa-filter"></i> Bộ lọc khác
                        </button>
                    </div>
                </div>

                <table class="product-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Sản phẩm</th>
                            <th>SKU</th>
                            <th>Đơn vị tính</th>
                            <th>Tồn thực tế</th>
                            <th>Tồn khả dụng</th>
                            <th>Tồn đã đặt</th>
                            <th>Giá bán</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($products)): ?>
                            <tr>
                                <td><input type="checkbox"></td>
                                <td>
                                    <div class="product-info">
                                        <?php if ($row['hinh_anh']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($row['hinh_anh']) ?>" width="40" height="40" style="object-fit:cover;">
                                        <?php else: ?>
                                            <span style="color:#aaa;">[Ảnh]</span>
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($row['ten_san_pham']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['sku'] ?? 'Không') ?></td>
                                <td>Sản phẩm</td>
                                <td><span class="status-badge <?= $row['so_luong'] > 0 ? 'status-in-stock' : 'status-out-of-stock' ?>"><?= $row['so_luong'] ?></span></td>
                                <td><?= $row['so_luong'] ?></td>
                                <td><?= $row['so_luong_ban'] ?></td>
                                <td class="price-cell"><?= htmlspecialchars($row['gia_ban_formatted']) ?> VNĐ</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <div class="info">
                        Hiển thị <?= min($start + 1, $total_products) ?> - <?= min($start + $limit, $total_products) ?> trong tổng số <?= $total_products ?> sản phẩm
                    </div>

                    <?php
                    $current_url_params = http_build_query([
                        'filter' => $filter,
                        'sort' => $sort,
                        'search' => $search
                    ]);

                    // Nút Quay lại
                    if ($page > 1) {
                        $prev_page = $page - 1;
                        echo "<a href=\"?page=$prev_page&$current_url_params\"><i class='fa-solid fa-chevron-left'></i></a>";
                    } else {
                        echo "<span class='disabled'><i class='fa-solid fa-chevron-left'></i></span>";
                    }

                    // Hiển thị các số trang
                    $max_links = 5; // Số lượng nút số trang tối đa hiển thị
                    $start_link = max(1, $page - floor($max_links / 2));
                    $end_link = min($total_pages, $start_link + $max_links - 1);

                    if ($end_link - $start_link < $max_links - 1) {
                        $start_link = max(1, $end_link - $max_links + 1);
                    }

                    for ($i = $start_link; $i <= $end_link; $i++) {
                        if ($i == $page) {
                            echo "<span class='current-page'>$i</span>";
                        } else {
                            echo "<a href=\"?page=$i&$current_url_params\">$i</a>";
                        }
                    }

                    // Nút Tiếp theo
                    if ($page < $total_pages) {
                        $next_page = $page + 1;
                        echo "<a href=\"?page=$next_page&$current_url_params\"><i class='fa-solid fa-chevron-right'></i></a>";
                    } else {
                        echo "<span class='disabled'><i class='fa-solid fa-chevron-right'></i></span>";
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Toggle user dropdown menu
        function toggleUserMenu() {
            const userMenu = document.querySelector('.user-menu');
            userMenu.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const userBtn = document.querySelector('.user-menu-btn');
            if (!userMenu.contains(event.target) && !userBtn.contains(event.target)) {
                userMenu.classList.remove('active');
            }
        });

        // Logout function
        function logoutUser() {
            if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                window.location.href = 'login.php';
            }
        }
    </script>
</body>

</html>