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

$inventory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$inventory_id) {
    header("Location: kiem_ke.php");
    exit();
}

// Lấy thông tin phiếu kiểm kê
$inventory_sql = "SELECT * FROM phieu_kiem_ke WHERE id = ?";
$stmt_inventory = $conn->prepare($inventory_sql);
$stmt_inventory->bind_param("i", $inventory_id);
$stmt_inventory->execute();
$inventory = $stmt_inventory->get_result()->fetch_assoc();
$stmt_inventory->close();

if (!$inventory) {
    header("Location: kiem_ke.php");
    exit();
}

// ================== XỬ LÝ CẬP NHẬT SỐ LƯỢNG THỰC TẾ ==================
if (isset($_POST['update_actual'])) {
    $detail_id = filter_var($_POST['detail_id'], FILTER_SANITIZE_NUMBER_INT);
    $actual_quantity = filter_var($_POST['actual_quantity'], FILTER_SANITIZE_NUMBER_INT);

    if ($detail_id && $actual_quantity >= 0) {
        // Lấy số lượng hệ thống
        $get_system_sql = "SELECT system_quantity FROM chi_tiet_kiem_ke WHERE id = ?";
        $stmt_get = $conn->prepare($get_system_sql);
        $stmt_get->bind_param("i", $detail_id);
        $stmt_get->execute();
        $system_qty = $stmt_get->get_result()->fetch_assoc()['system_quantity'];
        $stmt_get->close();

        $difference = $actual_quantity - $system_qty;

        // Cập nhật
        $update_sql = "UPDATE chi_tiet_kiem_ke SET actual_quantity = ?, difference = ? WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("iii", $actual_quantity, $difference, $detail_id);
        $stmt_update->execute();
        $stmt_update->close();

        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $inventory_id);
        exit();
    }
}

// ================== XỬ LÝ HOÀN THÀNH KIỂM KÊ ==================
if (isset($_POST['complete_inventory'])) {
    // Cập nhật số lượng sản phẩm theo số lượng thực tế
    $details_sql = "SELECT product_id, actual_quantity FROM chi_tiet_kiem_ke WHERE inventory_id = ?";
    $stmt_details = $conn->prepare($details_sql);
    $stmt_details->bind_param("i", $inventory_id);
    $stmt_details->execute();
    $details_result = $stmt_details->get_result();

    $update_product_sql = "UPDATE san_pham SET so_luong = ? WHERE id = ?";
    $stmt_update_product = $conn->prepare($update_product_sql);

    while ($detail = $details_result->fetch_assoc()) {
        $stmt_update_product->bind_param("ii", $detail['actual_quantity'], $detail['product_id']);
        $stmt_update_product->execute();
    }

    $stmt_update_product->close();
    $stmt_details->close();

    // Cập nhật trạng thái phiếu kiểm kê
    $complete_sql = "UPDATE phieu_kiem_ke SET status = 'completed', completed_at = NOW() WHERE id = ?";
    $stmt_complete = $conn->prepare($complete_sql);
    $stmt_complete->bind_param("i", $inventory_id);
    $stmt_complete->execute();
    $stmt_complete->close();

    log_activity($conn, "Quản lý Kho", "đã hoàn thành kiểm kê: " . $inventory['title']);

    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $inventory_id);
    exit();
}

// ================== HỦY PHIẾU KIỂM KÊ ==================
if (isset($_POST['cancel_inventory'])) {
    $cancel_sql = "UPDATE phieu_kiem_ke SET status = 'cancelled' WHERE id = ?";
    $stmt_cancel = $conn->prepare($cancel_sql);
    $stmt_cancel->bind_param("i", $inventory_id);
    $stmt_cancel->execute();
    $stmt_cancel->close();

    log_activity($conn, "Quản lý Kho", "đã hủy phiếu kiểm kê: " . $inventory['title']);

    header("Location: kiem_ke.php");
    exit();
}

// Lấy chi tiết kiểm kê
$details_sql = "SELECT ctkk.*, sp.ten_san_pham, sp.hinh_anh 
                FROM chi_tiet_kiem_ke ctkk 
                LEFT JOIN san_pham sp ON ctkk.product_id = sp.id 
                WHERE ctkk.inventory_id = ? 
                ORDER BY sp.ten_san_pham ASC";
$stmt_details = $conn->prepare($details_sql);
$stmt_details->bind_param("i", $inventory_id);
$stmt_details->execute();
$details_result = $stmt_details->get_result();

// Định nghĩa trạng thái
$statuses = [
    'pending' => ['text' => 'Đang kiểm kê', 'class' => 'status-pending'],
    'completed' => ['text' => 'Hoàn thành', 'class' => 'status-completed'],
    'cancelled' => ['text' => 'Đã hủy', 'class' => 'status-cancelled']
];

$status_info = $statuses[$inventory['status']] ?? $statuses['pending'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Kiểm Kê</title>
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
            max-width: 1400px;
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

        /* Header info */
        .inventory-header {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .inventory-header h2 {
            margin: 0 0 10px 0;
        }

        .inventory-meta {
            display: flex;
            gap: 30px;
            font-size: 14px;
            opacity: 0.95;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
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

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: space-between;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        .btn-back:hover {
            background: #5a6268;
        }

        .btn-complete {
            background: #28a745;
            color: white;
        }

        .btn-complete:hover {
            background: #218838;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background: #c82333;
        }

        /* Table */
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
            position: sticky;
            top: 0;
        }

        .table tr:hover {
            background: #f9fafc;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }

        .quantity-input {
            width: 80px;
            padding: 6px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            text-align: center;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #17a2b8;
        }

        .btn-update {
            background: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-update:hover {
            background: #0056b3;
        }

        .diff-positive {
            color: #28a745;
            font-weight: bold;
        }

        .diff-negative {
            color: #dc3545;
            font-weight: bold;
        }

        .diff-zero {
            color: #6c757d;
        }

        .summary-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .summary-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .summary-item h4 {
            margin: 0 0 10px 0;
            color: #6c757d;
            font-size: 14px;
        }

        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }

        .disabled-row {
            opacity: 0.6;
            background: #f8f9fa;
        }
    </style>
</head>

<body>
    <?php include "sidebar_admin.php"; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="search-box">
                <h1>Chi tiết Kiểm Kê</h1>
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
            <!-- Header thông tin -->
            <div class="inventory-header">
                <h2><?= htmlspecialchars($inventory['title']) ?></h2>
                <div class="inventory-meta">
                    <span><i class="fa-solid fa-user"></i> Người tạo: <?= htmlspecialchars($inventory['created_by']) ?></span>
                    <span><i class="fa-solid fa-calendar"></i> Ngày tạo: <?= date('d/m/Y H:i', strtotime($inventory['created_at'])) ?></span>
                    <span>Trạng thái: <span class="status-badge <?= $status_info['class'] ?>"><?= $status_info['text'] ?></span></span>
                </div>
                <?php if ($inventory['note']): ?>
                    <p style="margin: 10px 0 0 0; opacity: 0.9;">
                        <i class="fa-solid fa-note-sticky"></i> <?= htmlspecialchars($inventory['note']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Action buttons -->
            <div class="action-buttons">
                <a href="kiem_ke.php" class="btn btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại
                </a>

                <?php if ($inventory['status'] == 'pending'): ?>
                    <div style="display: flex; gap: 10px;">
                        <form method="POST" onsubmit="return confirm('Xác nhận hoàn thành kiểm kê? Số lượng sản phẩm trong kho sẽ được cập nhật theo số lượng thực tế.');">
                            <button type="submit" name="complete_inventory" class="btn btn-complete">
                                <i class="fa-solid fa-check-circle"></i> Hoàn thành kiểm kê
                            </button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy phiếu kiểm kê này?');">
                            <button type="submit" name="cancel_inventory" class="btn btn-cancel">
                                <i class="fa-solid fa-times-circle"></i> Hủy phiếu
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bảng chi tiết -->
            <table class="table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>SL Hệ thống</th>
                        <th>SL Thực tế</th>
                        <th>Chênh lệch</th>
                        <?php if ($inventory['status'] == 'pending'): ?>
                            <th>Hành động</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stt = 1;
                    $total_system = 0;
                    $total_actual = 0;
                    $total_diff = 0;

                    while ($detail = $details_result->fetch_assoc()):
                        $total_system += $detail['system_quantity'];
                        $total_actual += $detail['actual_quantity'];
                        $total_diff += $detail['difference'];

                        $diff_class = 'diff-zero';
                        if ($detail['difference'] > 0) {
                            $diff_class = 'diff-positive';
                            $diff_icon = '<i class="fa-solid fa-arrow-up"></i>';
                        } elseif ($detail['difference'] < 0) {
                            $diff_class = 'diff-negative';
                            $diff_icon = '<i class="fa-solid fa-arrow-down"></i>';
                        } else {
                            $diff_icon = '<i class="fa-solid fa-equals"></i>';
                        }
                    ?>
                        <tr <?= $inventory['status'] != 'pending' ? 'class="disabled-row"' : '' ?>>
                            <td><?= $stt++ ?></td>
                            <td>
                                <?php if ($detail['hinh_anh']): ?>
                                    <img src="<?= htmlspecialchars($detail['hinh_anh']) ?>" class="product-img" alt="Product">
                                <?php else: ?>
                                    <div class="product-img" style="background: #e9ecef; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-image" style="color: #6c757d;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($detail['ten_san_pham'] ?? 'N/A') ?></strong></td>
                            <td><?= $detail['system_quantity'] ?></td>
                            <td><?= $detail['actual_quantity'] ?></td>
                            <td class="<?= $diff_class ?>">
                                <?= $diff_icon ?> <?= abs($detail['difference']) ?>
                            </td>
                            <?php if ($inventory['status'] == 'pending'): ?>
                                <td>
                                    <form method="POST" style="display: inline-flex; gap: 5px; align-items: center;">
                                        <input type="hidden" name="detail_id" value="<?= $detail['id'] ?>">
                                        <input type="number" name="actual_quantity" class="quantity-input"
                                            value="<?= $detail['actual_quantity'] ?>" min="0" required>
                                        <button type="submit" name="update_actual" class="btn-update">
                                            <i class="fa-solid fa-save"></i>
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Tổng kết -->
            <div class="summary-box">
                <h3 style="margin-top: 0;">Tổng kết</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <h4>Tổng SL Hệ thống</h4>
                        <div class="value"><?= $total_system ?></div>
                    </div>
                    <div class="summary-item">
                        <h4>Tổng SL Thực tế</h4>
                        <div class="value"><?= $total_actual ?></div>
                    </div>
                    <div class="summary-item">
                        <h4>Tổng Chênh lệch</h4>
                        <div class="value <?= $total_diff > 0 ? 'diff-positive' : ($total_diff < 0 ? 'diff-negative' : 'diff-zero') ?>">
                            <?php
                            if ($total_diff > 0) echo '+';
                            echo $total_diff;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
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
    </script>

    <?php
    $stmt_details->close();
    $conn->close();
    ?>
</body>

</html>