<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Include kết nối DB
include __DIR__ . "/Database/connectdb.php";

// Lấy thông tin người dùng từ bảng user
$user_sql = "SELECT tai_khoan, email, role FROM user WHERE id = $user_id LIMIT 1";
$user_result = mysqli_query($conn, $user_sql);
$user_data = mysqli_fetch_assoc($user_result);

$tai_khoan = $user_data['tai_khoan'] ?? '';
$email = $user_data['email'] ?? '';
$role = $user_data['role'] ?? 'user';

// Chỉ user được truy cập
if ($role !== 'user') {
    echo "<script>alert('Chỉ user mới được truy cập!'); window.location.href='login.php';</script>";
    exit();
}

// ...existing code...
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng</title>
    <style>
        body {
            padding-top: 70px;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <?php include 'header.php'; ?>
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Thông tin người dùng</h2>
            <a href="logout.php" class="btn btn-danger">Đăng xuất</a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <p><strong>Tài khoản:</strong> <?= htmlspecialchars($tai_khoan) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                <p><strong>Vai trò:</strong> <?= htmlspecialchars($role) ?></p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Thêm tài khoản ngân hàng</div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <div class="col-md-4">
                        <select name="bank_name" class="form-select" required>
                            <option value="" disabled selected>Chọn ngân hàng</option>
                            <option>VietcomBank</option>
                            <option>MbBank</option>
                            <option>ViettinBank</option>
                            <option>Momo</option>
                            <option>VNPay</option>
                            <option>AgriBank</option>
                            <option>TpBank</option>
                            <option>Sacombank</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="account_number" class="form-control" placeholder="Số tài khoản" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="display_name" class="form-control" placeholder="Tên hiển thị">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_account" class="btn btn-primary">Thêm tài khoản</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Danh sách tài khoản ngân hàng</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Ngân hàng</th>
                            <th>Số tài khoản</th>
                            <th>Tên hiển thị</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['bank_name']) ?></td>
                                <td><?= htmlspecialchars($row['account_number']) ?></td>
                                <td><?= htmlspecialchars($row['display_name']) ?></td>
                                <td>
                                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'footer.php'; ?>
</body>

</html>