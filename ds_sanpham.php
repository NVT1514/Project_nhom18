<?php
include "Database/connectdb.php";

// Lấy danh sách sản phẩm
$sql = "SELECT * FROM san_pham ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

// Phân quyền truy cập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 100px;
        }

        .container {
            max-width: 1400px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 30px;
        }

        .table img {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 3px;
            background: #fff;
        }

        .alert {
            max-width: 600px;
            margin: 10px auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'header.php'; ?>
        <h2>Danh sách sản phẩm</h2>
        <a href="themsanpham.php" class="btn btn-success mb-3">+ Thêm sản phẩm</a>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Tên sản phẩm</th>
                    <th>Giá</th>
                    <th>Ảnh</th>
                    <th>Phân loại</th>
                    <th>Loại sản phẩm</th>
                    <th>Ngày tạo</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['ten_san_pham']) ?></td>
                            <td><?= number_format($row['gia'], 0, ',', '.') ?>đ</td>
                            <td>
                                <?php
                                $hinh_anh = $row['hinh_anh'];
                                if (!empty($hinh_anh)) {
                                    // Nếu không phải link http và không chứa "uploads/" thì thêm đường dẫn
                                    if (!preg_match('/^http/', $hinh_anh) && !str_contains($hinh_anh, 'uploads/')) {
                                        $hinh_anh = "../uploads/" . $hinh_anh;
                                    }
                                    echo '<img src="' . $hinh_anh . '" alt="Ảnh" width="60">';
                                } else {
                                    echo '<img src="../uploads/no-image.png" alt="Không có ảnh" width="60">';
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($row['phan_loai']) ?></td>
                            <td><?= htmlspecialchars($row['loai_chinh']) ?></td>
                            <td><?= $row['ngay_tao'] ?></td>
                            <td>
                                <a href="sua_sanpham.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Sửa</a>
                                <a href="xoa_sanpham.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">Xóa</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Chưa có sản phẩm nào</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>