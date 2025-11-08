<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['tk'];

// Lấy thông tin người dùng
$sql = "SELECT * FROM user WHERE Tai_Khoan = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Không tìm thấy thông tin tài khoản!");
}

$user = $result->fetch_assoc();

// Cập nhật session
$_SESSION['ho_ten'] = $user['Ho_Ten'] ?? $username;
$_SESSION['avatar'] = $user['avatar'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
$_SESSION['role'] = $user['role'] ?? 'user';

$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ người dùng</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: "Segoe UI", sans-serif;
        }

        .main-content {
            flex: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            background-color: #f4f6f9;
        }

        .profile-container {
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 750px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            transition: 0.3s ease;
        }

        .profile-container:hover {
            transform: translateY(-3px);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 25px;
        }

        .profile-header img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #3498db;
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.3);
        }

        .profile-header h2 {
            margin: 0;
            font-size: 1.7rem;
            color: #2c3e50;
            font-weight: 700;
        }

        .profile-header p {
            color: #7f8c8d;
            font-size: 1rem;
            margin-top: 5px;
        }

        .profile-details {
            margin-top: 25px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row span {
            color: #34495e;
            font-weight: 600;
        }

        .detail-row .value {
            color: #2c3e50;
            font-weight: 500;
        }

        .profile-actions {
            margin-top: 30px;
            text-align: right;
        }

        .btn-edit {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-edit:hover {
            background-color: #2980b9;
        }

        .edit-form {
            margin-top: 30px;
            display: none;
            background: #fefefe;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #ddd;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .edit-form h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #34495e;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 4px rgba(52, 152, 219, 0.4);
        }

        .form-actions {
            text-align: right;
            margin-top: 20px;
        }

        .btn-save {
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-save:hover {
            background-color: #219150;
        }

        .btn-cancel {
            background-color: #bdc3c7;
            color: #2c3e50;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            margin-right: 10px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-cancel:hover {
            background-color: #95a5a6;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 500;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar_admin.php'; ?>
        <div class="main-content">
            <div class="profile-container">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert-success">✅ Cập nhật hồ sơ thành công!</div>
                <?php endif; ?>

                <div class="profile-header">
                    <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>"
                        alt="Avatar">
                    <div>
                        <h2><?php echo htmlspecialchars($user['Ho_Ten'] ?? 'Người dùng hệ thống'); ?></h2>
                        <p>
                            <?php
                            if ($user['role'] === 'superadmin') {
                                echo '👑 SuperAdmin - Toàn quyền hệ thống';
                            } elseif ($user['role'] === 'admin') {
                                echo '🛠️ Admin - Quản trị viên hệ thống';
                            } else {
                                echo '👤 Người dùng thông thường';
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="detail-row">
                        <span><i class="fa-solid fa-envelope"></i> Email:</span>
                        <div class="value"><?php echo htmlspecialchars($user['Email'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-phone"></i> Số điện thoại:</span>
                        <div class="value"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-user-shield"></i> Quyền hạn:</span>
                        <div class="value">
                            <?php
                            if ($user['role'] === 'superadmin') {
                                echo 'SuperAdmin (Toàn quyền hệ thống)';
                            } elseif ($user['role'] === 'admin') {
                                echo 'Admin (Quản lý hệ thống)';
                            } else {
                                echo 'User (Người dùng thông thường)';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-calendar"></i> Ngày tham gia:</span>
                        <div class="value">
                            <?php
                            echo isset($user['Ngay_Tao']) && $user['Ngay_Tao']
                                ? date("d/m/Y", strtotime($user['Ngay_Tao']))
                                : 'Không xác định';
                            ?>
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <button class="btn-edit" id="editBtn"><i class="fa-solid fa-pen-to-square"></i> Chỉnh sửa hồ sơ</button>
                </div>

                <form class="edit-form" id="editForm" method="POST" enctype="multipart/form-data" action="update_profile_admin.php">
                    <h3>Cập nhật thông tin hồ sơ</h3>

                    <div class="form-group">
                        <label for="avatar">Ảnh đại diện</label>
                        <input type="file" name="avatar" id="avatar" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label for="ho_ten">Họ Tên</label>
                        <input type="text" name="ho_ten" id="ho_ten" value="<?php echo htmlspecialchars($user['Ho_Ten'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Mật khẩu mới</label>
                        <input type="password" name="password" id="password" placeholder="Nhập mật khẩu mới (nếu đổi)">
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancel" id="cancelBtn">Hủy</button>
                        <button type="submit" class="btn-save">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const editBtn = document.getElementById('editBtn');
        const editForm = document.getElementById('editForm');
        const cancelBtn = document.getElementById('cancelBtn');

        editBtn.addEventListener('click', () => {
            editForm.style.display = 'block';
            editBtn.style.display = 'none';
        });

        cancelBtn.addEventListener('click', () => {
            editForm.style.display = 'none';
            editBtn.style.display = 'inline-block';
        });
    </script>
</body>

</html>