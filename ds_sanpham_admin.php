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

// Lấy giá trị tìm kiếm và bộ lọc
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Câu truy vấn cơ bản
$sql = "SELECT * FROM san_pham WHERE 1";

// Thêm điều kiện tìm kiếm
if ($search !== '') {
    $sql .= " AND (ten_san_pham LIKE '%$search%' OR id LIKE '%$search%')";
}

// Thêm điều kiện lọc tồn kho
switch ($filter) {
    case 'con':
        $sql .= " AND so_luong > 10";
        break;
    case 'saphet':
        $sql .= " AND so_luong > 0 AND so_luong <= 10";
        break;
    case 'hethang':
        $sql .= " AND so_luong = 0";
        break;
}

// Sắp xếp mới nhất lên đầu
$sql .= " ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Kho hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">

    <style>
        /* General Body and Layout */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f8f9fb;
            /* Light background for main content area */
            color: #000;
            display: flex;
            /* Adjust based on your sidebar. Assuming the sidebar is still included. */
            padding-left: 250px;
            transition: padding-left 0.3s ease;
        }

        .container {
            width: 100%;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            min-height: 100vh;
        }

        .container-fluid {
            max-width: 1250px;
            margin: 0 auto;
        }

        /* Header */
        h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 28px;
            color: #1F2A38;
        }

        /* Top Bar/Search/Filter Area */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            width: 100%;
            /* Make the form take full width */
        }

        /* Input/Select/Button Styling */
        .form-control,
        .form-select {
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            /* Include padding and border in the element's total width and height */
        }

        .form-control {
            flex: 1;
            max-width: 400px;
            min-width: 200px;
            /* Ensures it doesn't get too small */
        }

        .form-select {
            width: 180px;
            cursor: pointer;
            background-color: #fff;
            appearance: none;
            /* Remove default select styling for better control */
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%236c757d%22%20d%3D%22M287%20173.5c-4.4%204.4-10.9%207-17.5%207h-241c-6.6%200-13.1-2.6-17.5-7-9.7-9.7-9.7-25.5%200-35.2l120.5-120.5c9.7-9.7%2025.5-9.7%2035.2%200l120.5%20120.5c9.6%209.7%209.6%2025.5-.1%2035.2z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 10px top 50%;
            background-size: 10px;
        }


        /* General Button Style */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s;
            font-size: 16px;
        }

        /* Specific Button Colors */
        .btn-success {
            background-color: #28a745;
            color: #fff;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-primary {
            background-color: #007bff;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #0069d9;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-sm {
            padding: 6px 8px;
            font-size: 14px;
            border-radius: 4px;
        }


        /* Table Container */
        .table-container {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.15);
            overflow-x: auto;
            /* Makes the table scrollable on small screens */
        }

        /* Table Styling */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            /* Removes spaces between cell borders */
            margin: 0;
            /* Remove default margins */
        }

        .data-table th,
        .data-table td {
            padding: 12px 10px;
            border: 1px solid #dee2e6;
            text-align: center;
            vertical-align: middle;
            font-size: 15px;
        }

        .data-table thead th {
            background-color: #343a40;
            /* Dark header background */
            color: #fff;
            /* White header text */
            border-color: #343a40;
            font-weight: 600;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
            /* Striped table rows */
        }

        .data-table tbody tr:hover {
            background-color: #e9ecef;
            /* Hover effect */
        }

        /* Table Specific Elements */
        .data-table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 3px;
            background: #fff;
            display: block;
            /* Important for alignment */
            margin: 0 auto;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 5px;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 14px;
            white-space: nowrap;
            /* Prevent wrapping */
        }

        .status-con {
            background-color: #d4edda;
            color: #155724;
        }

        .status-saphet {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-het {
            background-color: #f8d7da;
            color: #721c24;
        }


        /* Responsive Adjustments */
        @media (max-width: 992px) {
            body {
                padding-left: 0;
            }

            .main-content {
                padding: 15px;
            }

            .top-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .form-group {
                flex-direction: column;
                gap: 15px;
                /* Increase gap for vertical stacking */
            }

            .form-control,
            .form-select,
            .btn-success {
                width: 100% !important;
                max-width: none;
            }
        }
    </style>

</head>

<body>
    <div class="container">
        <?php include "sidebar_admin.php"; ?>

        <div class="main-content">
            <div class="container-fluid">
                <h2><i class="fa fa-"></i>Danh sách sản phẩm</h2>

                <div class="top-bar">
                    <form class="form-group" method="get">
                        <input type="text" name="search" class="form-control"
                            placeholder="Tìm theo tên hoặc mã sản phẩm..."
                            value="<?= htmlspecialchars($search) ?>">

                        <select name="filter" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="con" <?= $filter === 'con' ? 'selected' : '' ?>>Còn hàng</option>
                            <option value="saphet" <?= $filter === 'saphet' ? 'selected' : '' ?>>Sắp hết (&lt;=10)</option>
                            <option value="hethang" <?= $filter === 'hethang' ? 'selected' : '' ?>>Hết hàng</option>
                        </select>

                        <a href="themsanpham.php" class="btn btn-success">
                            <i class="fa fa-plus"></i> Thêm sản phẩm
                        </a>
                    </form>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
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
                                        <td><?= $row['id'] ?></td>
                                        <td><?= htmlspecialchars($row['ten_san_pham']) ?></td>
                                        <td><?= htmlspecialchars($row['phan_loai']) ?></td>
                                        <td><?= htmlspecialchars($row['loai_chinh']) ?></td>
                                        <td><?= number_format($row['gia'], 0, ',', '.') ?>đ</td>
                                        <td>
                                            <?php
                                            $hinh_anh = $row['hinh_anh'];
                                            if (!empty($hinh_anh)) {
                                                if (!preg_match('/^http/', $hinh_anh) && !str_contains($hinh_anh, 'uploads/')) {
                                                    $hinh_anh = "../uploads/" . $hinh_anh;
                                                }
                                                echo '<img src="' . $hinh_anh . '" alt="Ảnh sản phẩm">';
                                            } else {
                                                echo '<img src="../uploads/no-image.png" alt="Không có ảnh">';
                                            }
                                            ?>
                                        </td>
                                        <td><?= $row['so_luong'] ?></td>
                                        <td><span class="status-badge <?= $badge_class ?>"><?= $trang_thai ?></span></td>
                                        <td><?= $row['ngay_tao'] ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary btn-sm" onclick="updateProduct(<?= $row['id'] ?>)">
                                                    <i class="fa fa-save"></i>
                                                </button>
                                                <a href="sua_sanpham.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="xoa_sanpham.php?id=<?= $row['id'] ?>"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">Không có sản phẩm nào phù hợp</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateProduct(id) {
            const so_luong = document.getElementById("so_luong_" + id)?.value;
            const formData = new FormData();
            formData.append("id", id);
            formData.append("so_luong", so_luong);

            fetch("update_sanpham.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                })
                .catch(err => console.error("Lỗi:", err));
        }
    </script>
</body>

</html>