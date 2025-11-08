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

    $stmt = $conn->prepare("INSERT INTO vouchers (ma_voucher, mo_ta, giam_phan_tram, gia_tri_toi_da, dieu_kien, ngay_bat_dau, ngay_het_han, trang_thai) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssidssss", $ma, $mo_ta, $giam, $toi_da, $dieu_kien, $ngay_bd, $ngay_hh, $trang_thai);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_voucher.php?msg=added");
    exit();
}

// --- Xóa voucher ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM vouchers WHERE id = $id");
    header("Location: admin_voucher.php?msg=deleted");
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

    $stmt = $conn->prepare("UPDATE vouchers 
        SET ma_voucher=?, mo_ta=?, giam_phan_tram=?, gia_tri_toi_da=?, dieu_kien=?, ngay_bat_dau=?, ngay_het_han=?, trang_thai=? 
        WHERE id=?");
    $stmt->bind_param("ssidssssi", $ma, $mo_ta, $giam, $toi_da, $dieu_kien, $ngay_bd, $ngay_hh, $trang_thai, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_voucher.php?msg=updated");
    exit();
}

$result = $conn->query("SELECT * FROM vouchers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Voucher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        .main-content {
            padding: 30px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .table-responsive {
            width: 90%;
            max-width: 1100px;
            /* Giới hạn độ rộng bảng */
        }

        .table thead {
            background-color: #007bff;
            color: white;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #fff;
        }

        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-delete {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-delete:hover {
            background-color: #bb2d3b;
        }

        h2 {
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
        }

        .alert {
            width: 70%;
            margin: 0 auto;
            text-align: center;
        }

        .d-flex.justify-content-between {
            width: 90%;
            max-width: 1100px;
            margin: 10px auto;
        }
    </style>
</head>

<body>
    <?php include 'sidebar_admin.php'; ?>

    <div class="main-content">
        <h2 class="mb-4">🧾 Quản lý Voucher</h2>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success text-center">
                <?php
                if ($_GET['msg'] == 'added') echo "✅ Thêm voucher thành công!";
                elseif ($_GET['msg'] == 'updated') echo "✏️ Cập nhật voucher thành công!";
                elseif ($_GET['msg'] == 'deleted') echo "🗑️ Xóa voucher thành công!";
                ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between mb-3">
            <h5>Danh sách voucher</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">➕ Thêm voucher</button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle text-center">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Mã Voucher</th>
                        <th>Mô tả</th>
                        <th>Giảm (%)</th>
                        <th>Tối đa (VNĐ)</th>
                        <th>Điều kiện</th>
                        <th>Bắt đầu</th>
                        <th>Hết hạn</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><strong><?= htmlspecialchars($row['ma_voucher']) ?></strong></td>
                            <td><?= htmlspecialchars($row['mo_ta']) ?></td>
                            <td><?= $row['giam_phan_tram'] ?>%</td>
                            <td><?= number_format($row['gia_tri_toi_da'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($row['dieu_kien']) ?></td>
                            <td><?= $row['ngay_bat_dau'] ?></td>
                            <td><?= $row['ngay_het_han'] ?></td>
                            <td>
                                <span class="badge bg-<?= $row['trang_thai'] == 'Hoạt động' ? 'success' : ($row['trang_thai'] == 'Hết hạn' ? 'secondary' : 'warning') ?>">
                                    <?= $row['trang_thai'] ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Sửa</button>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Xóa voucher này?')">Xóa</a>
                            </td>
                        </tr>

                        <!-- Modal sửa -->
                        <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">✏️ Sửa voucher #<?= $row['id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <div class="mb-2">
                                                <label>Mã voucher</label>
                                                <input type="text" name="ma_voucher" class="form-control" value="<?= htmlspecialchars($row['ma_voucher']) ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Mô tả</label>
                                                <textarea name="mo_ta" class="form-control"><?= htmlspecialchars($row['mo_ta']) ?></textarea>
                                            </div>
                                            <div class="mb-2">
                                                <label>Giảm (%)</label>
                                                <input type="number" name="giam_phan_tram" class="form-control" value="<?= $row['giam_phan_tram'] ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Giá trị tối đa (VNĐ)</label>
                                                <input type="number" name="gia_tri_toi_da" class="form-control" value="<?= $row['gia_tri_toi_da'] ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label>Điều kiện</label>
                                                <input type="text" name="dieu_kien" class="form-control" value="<?= htmlspecialchars($row['dieu_kien']) ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label>Ngày bắt đầu</label>
                                                <input type="date" name="ngay_bat_dau" class="form-control" value="<?= $row['ngay_bat_dau'] ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Ngày hết hạn</label>
                                                <input type="date" name="ngay_het_han" class="form-control" value="<?= $row['ngay_het_han'] ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Trạng thái</label>
                                                <select name="trang_thai" class="form-select">
                                                    <option <?= $row['trang_thai'] == 'Hoạt động' ? 'selected' : '' ?>>Hoạt động</option>
                                                    <option <?= $row['trang_thai'] == 'Hết hạn' ? 'selected' : '' ?>>Hết hạn</option>
                                                    <option <?= $row['trang_thai'] == 'Ẩn' ? 'selected' : '' ?>>Ẩn</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                            <button type="submit" name="edit_voucher" class="btn btn-warning">Lưu thay đổi</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal thêm -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">➕ Thêm voucher mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Mã voucher</label>
                            <input type="text" name="ma_voucher" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>Mô tả</label>
                            <textarea name="mo_ta" class="form-control"></textarea>
                        </div>
                        <div class="mb-2">
                            <label>Giảm (%)</label>
                            <input type="number" name="giam_phan_tram" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>Giá trị tối đa (VNĐ)</label>
                            <input type="number" name="gia_tri_toi_da" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label>Điều kiện</label>
                            <input type="text" name="dieu_kien" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label>Ngày bắt đầu</label>
                            <input type="date" name="ngay_bat_dau" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>Ngày hết hạn</label>
                            <input type="date" name="ngay_het_han" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>Trạng thái</label>
                            <select name="trang_thai" class="form-select">
                                <option value="Hoạt động">Hoạt động</option>
                                <option value="Hết hạn">Hết hạn</option>
                                <option value="Ẩn">Ẩn</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="add_voucher" class="btn btn-primary">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>