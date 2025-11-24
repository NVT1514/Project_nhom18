<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Giả định file connectdb.php tồn tại và đã kết nối CSDL
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

// Cập nhật session (quan trọng cho các file include khác)
$_SESSION['ho_ten'] = $user['Ho_Ten'] ?? $username;
$_SESSION['avatar'] = $user['avatar'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
$_SESSION['role'] = $user['role'] ?? 'user';

$stmt->close();

// Xác định Tab đang hoạt động
$tab = $_GET['tab'] ?? 'view';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* CSS Cơ bản */
        body {
            background-color: #f0f2f5;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }

        .main-content {
            flex: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - 80px);
            /* Giả định có header/footer */
        }

        /* Profile Card Container */
        .profile-card {
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* Header Section */
        .profile-header-card {
            background: #063250ff;
            color: white;
            padding: 30px;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-info h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .profile-info p {
            margin: 2px 0 0 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Tab Navigation */
        .profile-tabs {
            border-bottom: 1px solid #e0e0e0;
            padding: 0 30px;
            display: flex;
        }

        .profile-tabs a {
            padding: 15px 20px;
            text-decoration: none;
            color: #7f8c8d;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: color 0.2s, border-bottom 0.2s;
        }

        .profile-tabs a.active {
            color: #3498db;
            border-bottom: 3px solid #3498db;
        }

        .profile-tabs a:hover {
            color: #2c3e50;
        }

        /* Content Section */
        .profile-content {
            padding: 30px;
        }

        /* Alerts */
        .alert-message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* View Details */
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed #eee;
            font-size: 1rem;
        }

        .detail-row span {
            color: #34495e;
            font-weight: 600;
        }

        .detail-row .value {
            color: #2c3e50;
            font-weight: 500;
        }

        /* Forms */
        .form-section h4 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #34495e;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-actions {
            text-align: right;
            padding-top: 10px;
        }

        .btn-primary {
            background-color: #063250ff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: #156092ff;
        }

        .btn-danger {
            background-color: #e74c3c;
            margin-left: 10px;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar_admin.php'; // Sidebar chính của Admin Panel 
        ?>

        <div class="main-content">
            <div class="profile-card">

                <div class="profile-header-card">
                    <img src="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" alt="Avatar" class="profile-avatar">
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($user['Ho_Ten'] ?? 'Người dùng hệ thống'); ?></h3>
                        <p><i class="fa-solid fa-at"></i> Tài khoản: **<?php echo htmlspecialchars($user['Tai_Khoan']); ?>**</p>
                        <p>
                            <?php
                            if ($user['role'] === 'superadmin') {
                                echo '👑 SuperAdmin | Quản trị tối cao';
                            } elseif ($user['role'] === 'admin') {
                                echo '🛠️ Admin | Quản trị viên';
                            } else {
                                echo '👤 User | Người dùng';
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="profile-tabs">
                    <a href="?tab=view" class="<?php echo ($tab === 'view' || $tab === 'edit') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-circle-user"></i> Thông tin chung
                    </a>
                    <a href="?tab=password" class="<?php echo $tab === 'password' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-lock"></i> Thay đổi mật khẩu
                    </a>
                </div>

                <div class="profile-content">

                    <?php
                    // Xử lý thông báo (giả định các file update_profile_admin.php và update_password_admin.php sẽ chuyển hướng về đây)
                    if (isset($_GET['success_profile'])): ?>
                        <div class="alert-message alert-success">✅ Cập nhật hồ sơ thành công!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error_profile'])): ?>
                        <div class="alert-message alert-error">❌ Lỗi hồ sơ: <?php echo htmlspecialchars($_GET['error_profile']); ?></div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success_password'])): ?>
                        <div class="alert-message alert-success">✅ Đổi mật khẩu thành công!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error_password'])): ?>
                        <div class="alert-message alert-error">❌ Lỗi mật khẩu: <?php echo htmlspecialchars($_GET['error_password']); ?></div>
                    <?php endif; ?>

                    <?php if ($tab === 'view' || $tab === 'edit'): ?>
                        <div id="profile-view-section" style="display: <?php echo ($tab === 'view' || !isset($_GET['edit'])) ? 'block' : 'none'; ?>;">

                            <div class="detail-row">
                                <span><i class="fa-solid fa-user-tag"></i> Tài khoản:</span>
                                <div class="value"><?php echo htmlspecialchars($user['Tai_Khoan'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="detail-row">
                                <span><i class="fa-solid fa-signature"></i> Họ và Tên:</span>
                                <div class="value"><?php echo htmlspecialchars($user['Ho_Ten'] ?? 'N/A'); ?></div>
                            </div>
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
                                    echo ($user['role'] === 'superadmin' || $user['role'] === 'admin')
                                        ? 'Quản trị viên'
                                        : 'Người dùng thông thường';
                                    ?>
                                </div>
                            </div>
                            <div class="detail-row">
                                <span><i class="fa-solid fa-calendar-plus"></i> Ngày tham gia:</span>
                                <div class="value">
                                    <?php
                                    echo isset($user['Ngay_Tao']) && $user['Ngay_Tao']
                                        ? date("d/m/Y", strtotime($user['Ngay_Tao']))
                                        : 'N/A';
                                    ?>
                                </div>
                            </div>

                            <div class="form-actions" style="margin-top: 25px;">
                                <button class="btn-primary" id="startEditBtn">
                                    <i class="fa-solid fa-pen-to-square"></i> Chỉnh sửa thông tin
                                </button>
                            </div>
                        </div>

                        <form id="profile-edit-form" method="POST" enctype="multipart/form-data" action="update_profile_admin.php"
                            style="display: <?php echo ($tab === 'edit' || isset($_GET['error_profile'])) ? 'block' : 'none'; ?>;" class="form-section">

                            <h4>Cập nhật Thông tin cá nhân</h4>

                            <div class="form-group">
                                <label for="avatar">Ảnh đại diện mới</label>
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
                                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn-primary" id="cancelEditBtn">Hủy</button>
                                <button type="submit" class="btn-primary btn-danger">Lưu thay đổi</button>
                            </div>
                        </form>

                    <?php elseif ($tab === 'password'): ?>
                        <form method="POST" action="update_password_admin.php" class="form-section">
                            <h4>Thay đổi mật khẩu tài khoản</h4>
                            <p>Để đảm bảo an toàn, vui lòng nhập mật khẩu hiện tại và mật khẩu mới.</p>

                            <div class="form-group">
                                <label for="current_password">Mật khẩu hiện tại</label>
                                <input type="password" name="current_password" id="current_password" required placeholder="Nhập mật khẩu hiện tại">
                            </div>

                            <div class="form-group">
                                <label for="new_password">Mật khẩu mới</label>
                                <input type="password" name="new_password" id="new_password" required placeholder="Nhập mật khẩu mới">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Xác nhận mật khẩu mới">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary btn-danger"><i class="fa-solid fa-key"></i> Đổi mật khẩu</button>
                            </div>
                        </form>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startEditBtn = document.getElementById('startEditBtn');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const profileView = document.getElementById('profile-view-section');
            const profileEditForm = document.getElementById('profile-edit-form');

            // Hàm chuyển sang chế độ chỉnh sửa
            if (startEditBtn) {
                startEditBtn.addEventListener('click', () => {
                    if (profileView && profileEditForm) {
                        profileView.style.display = 'none';
                        profileEditForm.style.display = 'block';
                        // Thêm tham số 'edit' vào URL (không load lại trang)
                        history.pushState(null, null, '?tab=view&edit=1');
                    }
                });
            }

            // Hàm chuyển về chế độ xem
            if (cancelEditBtn) {
                cancelEditBtn.addEventListener('click', () => {
                    if (profileView && profileEditForm) {
                        profileView.style.display = 'block';
                        profileEditForm.style.display = 'none';
                        // Xóa tham số 'edit' khỏi URL (không load lại trang)
                        history.pushState(null, null, '?tab=view');
                    }
                });
            }
        });
    </script>
</body>

</html>