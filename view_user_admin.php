<?php
session_start();
include "Database/connectdb.php";

// Kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['tk']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: login.php");
    exit();
}

// Lấy ID người dùng từ GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user_management.php");
    exit();
}

$user_id = intval($_GET['id']);

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT id, Tai_Khoan, Mat_Khau, Ho_Ten, Email, avatar, phone, role, Ngay_Tao FROM user WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: user_management.php");
    exit();
}

$user = $result->fetch_assoc();

// Lấy thông tin tài khoản ngân hàng của người dùng (nếu có)
$bank_stmt = $conn->prepare("SELECT bank_name, account_number, display_name, created_at FROM user_bank_accounts WHERE user_id = ?");
$bank_stmt->bind_param("i", $user_id);
$bank_stmt->execute();
$bank_result = $bank_stmt->get_result();

$stmt->close();
$bank_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết người dùng</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f6f7fb;
        }

        .user-detail-container {
            background: #fff;
            border-radius: 10px;
            padding: 30px 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            max-width: 750px;
            margin: 0 auto;
        }

        .user-detail-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .user-info label {
            font-weight: 600;
            color: #444;
        }

        .user-info p {
            margin: 0;
            background-color: #f9f9f9;
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .btn-back {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 25px;
        }

        .btn-back:hover {
            background-color: #2e7dc1;
        }

        .avatar-preview {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            margin: 10px auto 25px;
            display: block;
            object-fit: cover;
            border: 2px solid #ccc;
        }

        .bank-info {
            margin-top: 35px;
            border-top: 2px dashed #ddd;
            padding-top: 20px;
        }

        .bank-info h3 {
            margin-bottom: 15px;
            text-align: center;
            color: #333;
        }

        .bank-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bank-table th,
        .bank-table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: center;
        }

        .bank-table th {
            background-color: #f1f1f1;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar_admin.php'; ?>

        <div class="main-content">
            <div class="user-detail-container">
                <h2>Thông Tin Chi Tiết Người Dùng</h2>

                <!-- Avatar -->
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="avatar-preview">
                <?php else: ?>
                    <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="Avatar mặc định" class="avatar-preview">
                <?php endif; ?>

                <!-- Thông tin chính -->
                <div class="user-info">
                    <div>
                        <label>ID người dùng:</label>
                        <p><?php echo htmlspecialchars($user['id']); ?></p>
                    </div>

                    <div>
                        <label>Tài khoản đăng nhập:</label>
                        <p><?php echo htmlspecialchars($user['Tai_Khoan']); ?></p>
                    </div>

                    <div>
                        <label>Họ tên:</label>
                        <p><?php echo htmlspecialchars($user['Ho_Ten'] ?? 'Chưa cập nhật'); ?></p>
                    </div>

                    <div>
                        <label>Email:</label>
                        <p><?php echo htmlspecialchars($user['Email']); ?></p>
                    </div>

                    <div>
                        <label>Số điện thoại:</label>
                        <p><?php echo htmlspecialchars($user['phone'] ?? 'Chưa cập nhật'); ?></p>
                    </div>

                    <div>
                        <label>Quyền hạn:</label>
                        <p><?php echo htmlspecialchars($user['role']); ?></p>
                    </div>

                    <div>
                        <label>Mật khẩu:</label>
                        <p><?php echo htmlspecialchars($user['Mat_Khau']); ?></p>
                    </div>

                    <div>
                        <label>Ngày tạo tài khoản:</label>
                        <p><?php echo htmlspecialchars($user['Ngay_Tao']); ?></p>
                    </div>
                </div>

                <!-- Thông tin tài khoản ngân hàng -->
                <div class="bank-info">
                    <h3><i class="fa-solid fa-building-columns"></i> Tài Khoản Ngân Hàng</h3>
                    <?php if ($bank_result->num_rows > 0): ?>
                        <table class="bank-table">
                            <tr>
                                <th>Ngân hàng</th>
                                <th>Số tài khoản</th>
                                <th>Tên hiển thị</th>
                                <th>Ngày thêm</th>
                            </tr>
                            <?php while ($bank = $bank_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bank['bank_name']); ?></td>
                                    <td><?php echo htmlspecialchars($bank['account_number']); ?></td>
                                    <td><?php echo htmlspecialchars($bank['display_name'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($bank['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p style="text-align:center;color:#777;">Người dùng chưa liên kết tài khoản ngân hàng nào.</p>
                    <?php endif; ?>
                </div>

                <a href="quanlinguoidung_admin.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</a>
            </div>
        </div>
    </div>
</body>

</html>