<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php";

// --- Thêm voucher ---
if (isset($_POST['add_voucher'])) {
    $ma = trim($_POST['ma_voucher']);
    $mo_ta = trim($_POST['mo_ta']);
    $giam = intval($_POST['giam_phan_tram']);
    $toi_da = !empty($_POST['gia_tri_toi_da']) ? floatval($_POST['gia_tri_toi_da']) : null;
    $dieu_kien = trim($_POST['dieu_kien']);
    $ngay_bd = $_POST['ngay_bat_dau'];
    $ngay_hh = $_POST['ngay_het_han'];
    $trang_thai = $_POST['trang_thai'];

    if (strtotime($ngay_bd) > strtotime($ngay_hh)) {
        header("Location: quan_ly_voucher.php?msg=date_error");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO vouchers (ma_voucher, mo_ta, giam_phan_tram, gia_tri_toi_da, dieu_kien, ngay_bat_dau, ngay_het_han, trang_thai) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssidssss", $ma, $mo_ta, $giam, $toi_da, $dieu_kien, $ngay_bd, $ngay_hh, $trang_thai);
    $stmt->execute();
    $stmt->close();
    header("Location: quan_ly_voucher.php?msg=added");
    exit();
}

// --- Xóa voucher ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM vouchers WHERE id = $id");
    header("Location: quan_ly_voucher.php?msg=deleted");
    exit();
}

// --- Sửa voucher ---
if (isset($_POST['edit_voucher'])) {
    $id = intval($_POST['id']);
    $ma = trim($_POST['ma_voucher']);
    $mo_ta = trim($_POST['mo_ta']);
    $giam = intval($_POST['giam_phan_tram']);
    $toi_da = !empty($_POST['gia_tri_toi_da']) ? floatval($_POST['gia_tri_toi_da']) : null;
    $dieu_kien = trim($_POST['dieu_kien']);
    $ngay_bd = $_POST['ngay_bat_dau'];
    $ngay_hh = $_POST['ngay_het_han'];
    $trang_thai = $_POST['trang_thai'];

    if (strtotime($ngay_bd) > strtotime($ngay_hh)) {
        header("Location: quan_ly_voucher.php?msg=date_error&edit_id=" . $id);
        exit();
    }

    $stmt = $conn->prepare("UPDATE vouchers 
        SET ma_voucher=?, mo_ta=?, giam_phan_tram=?, gia_tri_toi_da=?, dieu_kien=?, ngay_bat_dau=?, ngay_het_han=?, trang_thai=? 
        WHERE id=?");
    $stmt->bind_param("ssidssssi", $ma, $mo_ta, $giam, $toi_da, $dieu_kien, $ngay_bd, $ngay_hh, $trang_thai, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: quan_ly_voucher.php?msg=updated");
    exit();
}

$result = $conn->query("SELECT * FROM vouchers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Voucher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --card-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .main-content {
            padding: 40px 20px;
            min-height: 100vh;
        }

        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header Section */
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h2 {
            margin: 0;
            font-weight: 700;
            color: #2c3e50;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h2 i {
            color: var(--primary-color);
        }

        .btn-add-voucher {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .btn-add-voucher:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
            color: white;
        }

        /* Alert Messages */
        .alert-container {
            max-width: 600px;
            margin: 0 auto 25px;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            font-weight: 500;
            box-shadow: var(--card-shadow);
        }

        /* Table Card */
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }

        .table-card:hover {
            box-shadow: var(--hover-shadow);
        }

        .table-responsive {
            border-radius: 15px;
        }

        .table {
            margin: 0;
        }

        .table thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
            color: white;
        }

        .table thead th {
            border: none;
            padding: 18px 12px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f3f5;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 16px 12px;
            vertical-align: middle;
            font-size: 14px;
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        /* Voucher Code Styling */
        .voucher-code {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 1px;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        /* Badge Styling */
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            letter-spacing: 0.3px;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        }

        .badge.bg-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
        }

        .badge.bg-warning {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%) !important;
            color: #fff;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-sm {
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--warning-color) 0%, #ff9800 100%);
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c82333 100%);
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            color: white;
        }

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
            color: white;
            padding: 20px 25px;
            border: none;
        }

        .modal-header .modal-title {
            font-weight: 700;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-body label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .modal-body .form-control,
        .modal-body .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .modal-body .form-control:focus,
        .modal-body .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #e9ecef;
            gap: 10px;
        }

        .modal-footer .btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                text-align: center;
            }

            .page-header h2 {
                font-size: 24px;
            }

            .table thead th {
                font-size: 11px;
                padding: 12px 8px;
            }

            .table tbody td {
                font-size: 12px;
                padding: 12px 8px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-add-voucher {
                width: 100%;
                justify-content: center;
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Loading Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table tbody tr {
            animation: fadeIn 0.3s ease;
        }
    </style>
</head>

<body>
    <?php include 'sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <h2>
                    <i class="fas fa-ticket-alt"></i>
                    Quản lý Voucher
                </h2>
                <button class="btn btn-add-voucher" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus-circle"></i>
                    Thêm Voucher Mới
                </button>
            </div>

            <!-- Alert Messages -->
            <?php
            if (isset($_GET['msg'])) {
                $msg_text = '';
                $msg_type = 'success';
                $icon = '';

                if ($_GET['msg'] == 'added') {
                    $msg_text = "Thêm voucher thành công!";
                    $icon = '<i class="fas fa-check-circle me-2"></i>';
                } elseif ($_GET['msg'] == 'updated') {
                    $msg_text = "Cập nhật voucher thành công!";
                    $icon = '<i class="fas fa-edit me-2"></i>';
                } elseif ($_GET['msg'] == 'deleted') {
                    $msg_text = "Xóa voucher thành công!";
                    $icon = '<i class="fas fa-trash-alt me-2"></i>';
                } elseif ($_GET['msg'] == 'date_error') {
                    $msg_text = "Lỗi: Ngày bắt đầu không thể sau ngày hết hạn!";
                    $msg_type = 'danger';
                    $icon = '<i class="fas fa-exclamation-triangle me-2"></i>';
                }

                if (!empty($msg_text)) {
                    echo '<div class="alert-container">';
                    echo '<div class="alert alert-' . $msg_type . ' alert-dismissible fade show" role="alert">';
                    echo $icon . $msg_text;
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>

            <!-- Table Card -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 150px;">Mã Voucher</th>
                                <th>Mô tả</th>
                                <th style="width: 80px;">Giảm</th>
                                <th style="width: 120px;">Tối đa</th>
                                <th style="width: 150px;">Điều kiện</th>
                                <th style="width: 110px;">Bắt đầu</th>
                                <th style="width: 110px;">Hết hạn</th>
                                <th style="width: 100px;">Trạng thái</th>
                                <th style="width: 140px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center"><strong>#<?= $row['id'] ?></strong></td>
                                        <td>
                                            <span class="voucher-code"><?= htmlspecialchars($row['ma_voucher']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['mo_ta']) ?></td>
                                        <td class="text-center">
                                            <strong class="text-primary"><?= $row['giam_phan_tram'] ?>%</strong>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format($row['gia_tri_toi_da'], 0, ',', '.') ?>đ
                                        </td>
                                        <td><?= htmlspecialchars($row['dieu_kien']) ?></td>
                                        <td class="text-center"><?= date('d/m/Y', strtotime($row['ngay_bat_dau'])) ?></td>
                                        <td class="text-center"><?= date('d/m/Y', strtotime($row['ngay_het_han'])) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $row['trang_thai'] == 'Hoạt động' ? 'success' : ($row['trang_thai'] == 'Hết hạn' ? 'secondary' : 'warning') ?>">
                                                <?= $row['trang_thai'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                                                    <i class="fas fa-edit"></i> Sửa
                                                </button>
                                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Bạn có chắc muốn xóa voucher này?')">
                                                    <i class="fas fa-trash"></i> Xóa
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Modal Sửa -->
                                    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-edit"></i>
                                                            Chỉnh sửa Voucher #<?= $row['id'] ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label>Mã voucher <span class="text-danger">*</span></label>
                                                                <input type="text" name="ma_voucher" class="form-control" value="<?= htmlspecialchars($row['ma_voucher']) ?>" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label>Trạng thái <span class="text-danger">*</span></label>
                                                                <select name="trang_thai" class="form-select">
                                                                    <option <?= $row['trang_thai'] == 'Hoạt động' ? 'selected' : '' ?>>Hoạt động</option>
                                                                    <option <?= $row['trang_thai'] == 'Hết hạn' ? 'selected' : '' ?>>Hết hạn</option>
                                                                    <option <?= $row['trang_thai'] == 'Ẩn' ? 'selected' : '' ?>>Ẩn</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Mô tả</label>
                                                            <textarea name="mo_ta" class="form-control" rows="2"><?= htmlspecialchars($row['mo_ta']) ?></textarea>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label>Giảm (%) <span class="text-danger">*</span></label>
                                                                <input type="number" name="giam_phan_tram" class="form-control" value="<?= $row['giam_phan_tram'] ?>" min="1" max="100" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label>Giá trị tối đa (VNĐ)</label>
                                                                <input type="number" name="gia_tri_toi_da" class="form-control" value="<?= $row['gia_tri_toi_da'] ?>" step="1000">
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Điều kiện áp dụng</label>
                                                            <input type="text" name="dieu_kien" class="form-control" value="<?= htmlspecialchars($row['dieu_kien']) ?>" placeholder="VD: Đơn hàng từ 200.000đ">
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                                                                <input type="date" name="ngay_bat_dau" class="form-control" value="<?= $row['ngay_bat_dau'] ?>" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label>Ngày hết hạn <span class="text-danger">*</span></label>
                                                                <input type="date" name="ngay_het_han" class="form-control" value="<?= $row['ngay_het_han'] ?>" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            <i class="fas fa-times"></i> Hủy
                                                        </button>
                                                        <button type="submit" name="edit_voucher" class="btn btn-warning">
                                                            <i class="fas fa-save"></i> Lưu thay đổi
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <h5>Chưa có voucher nào</h5>
                                            <p>Nhấn "Thêm Voucher Mới" để tạo voucher đầu tiên</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle"></i>
                            Thêm Voucher Mới
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Mã voucher <span class="text-danger">*</span></label>
                                <input type="text" name="ma_voucher" class="form-control" placeholder="VD: SALE50" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Trạng thái <span class="text-danger">*</span></label>
                                <select name="trang_thai" class="form-select">
                                    <option value="Hoạt động" selected>Hoạt động</option>
                                    <option value="Hết hạn">Hết hạn</option>
                                    <option value="Ẩn">Ẩn</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>Mô tả</label>
                            <textarea name="mo_ta" class="form-control" rows="2" placeholder="Mô tả chi tiết về voucher..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Giảm (%) <span class="text-danger">*</span></label>
                                <input type="number" name="giam_phan_tram" class="form-control" min="1" max="100" placeholder="50" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Giá trị tối đa (VNĐ)</label>
                                <input type="number" name="gia_tri_toi_da" class="form-control" step="1000" placeholder="100000">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>Điều kiện áp dụng</label>
                            <input type="text" name="dieu_kien" class="form-control" placeholder="VD: Đơn hàng từ 200.000đ">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                                <input type="date" name="ngay_bat_dau" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Ngày hết hạn <span class="text-danger">*</span></label>
                                <input type="date" name="ngay_het_han" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                        <button type="submit" name="add_voucher" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Thêm voucher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>