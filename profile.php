<?php
session_start();
include __DIR__ . "/Database/connectdb.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['tk'];
$success_message = '';
$error_message = '';
$active_tab = 'profile';

// ✅ Lấy ID người dùng từ tài khoản
$user_check_sql = "SELECT id FROM user WHERE Tai_Khoan = '" . mysqli_real_escape_string($conn, $username) . "' LIMIT 1";
$user_check_result = mysqli_query($conn, $user_check_sql);
if ($user_check_result && mysqli_num_rows($user_check_result) > 0) {
    $user_row = mysqli_fetch_assoc($user_check_result);
    $user_id = $user_row['id'];
} else {
    session_destroy();
    header("Location: login.php");
    exit();
}

// --- Hàm lấy thông tin người dùng ---
function fetch_user_data($conn, $user_id)
{
    $user_sql = "SELECT id, Tai_Khoan, Email, role, Ho_Ten, phone, avatar, Mat_Khau 
                 FROM user 
                 WHERE id = $user_id 
                 LIMIT 1";
    $user_result = mysqli_query($conn, $user_sql);
    return mysqli_fetch_assoc($user_result) ?? [];
}

$user_data = fetch_user_data($conn, $user_id);

$tai_khoan = $user_data['Tai_Khoan'] ?? '';
$email = $user_data['Email'] ?? '';
$role = $user_data['role'] ?? 'user';
$ho_ten = $user_data['Ho_Ten'] ?? '';
$phone = $user_data['phone'] ?? '';
$plain_password = $user_data['Mat_Khau'] ?? '';
$avatar = !empty($user_data['avatar']) ? $user_data['avatar'] : 'images/default_avatar.png';

// ✅ Chỉ cho phép role user truy cập
if ($role !== 'user') {
    if (in_array($role, ['admin', 'superadmin'])) {
        header("Location: admin.php");
    } else {
        header("Location: profile.php");
    }
    exit();
}

/* ===============================================================
   CẬP NHẬT AVATAR
=============================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    $active_tab = 'profile';

    if (isset($_FILES['new_avatar']) && $_FILES['new_avatar']['error'] == 0) {
        $target_dir = "uploads/avatars/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_name = uniqid() . '_' . basename($_FILES['new_avatar']['name']);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
            $error_message = "Chỉ chấp nhận file JPG, JPEG hoặc PNG.";
        } elseif ($_FILES['new_avatar']['size'] > 5 * 1024 * 1024) {
            $error_message = "Kích thước file vượt quá 5MB.";
        } else {
            if (move_uploaded_file($_FILES['new_avatar']['tmp_name'], $target_file)) {
                if ($user_data['avatar'] && $user_data['avatar'] !== 'images/default_avatar.png' && file_exists($user_data['avatar'])) {
                    unlink($user_data['avatar']);
                }

                $new_avatar_path = mysqli_real_escape_string($conn, $target_file);
                $update_sql = "UPDATE user SET avatar = '$new_avatar_path' WHERE id = $user_id";

                if (mysqli_query($conn, $update_sql)) {
                    $success_message = "Cập nhật ảnh đại diện thành công!";
                    $user_data = fetch_user_data($conn, $user_id);
                    $avatar = $user_data['avatar'];
                    $_SESSION['avatar'] = $avatar;
                } else {
                    $error_message = "Lỗi khi cập nhật avatar: " . mysqli_error($conn);
                    unlink($target_file);
                }
            } else {
                $error_message = "Không thể tải file lên máy chủ.";
            }
        }
    } else {
        $error_message = "Vui lòng chọn một file ảnh.";
    }
}

/* ===============================================================
   CẬP NHẬT THÔNG TIN CÁ NHÂN
=============================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_ho_ten = mysqli_real_escape_string($conn, $_POST['ho_ten']);
    $new_email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $update_sql = "UPDATE user SET Ho_Ten = '$new_ho_ten', Email = '$new_email', phone = '$new_phone' WHERE id = $user_id";
    if (mysqli_query($conn, $update_sql)) {
        $success_message = "Cập nhật thông tin cá nhân thành công!";
        $user_data = fetch_user_data($conn, $user_id);
        $ho_ten = $user_data['Ho_Ten'];
        $email = $user_data['Email'];
        $phone = $user_data['phone'];
    } else {
        $error_message = "Lỗi khi cập nhật thông tin: " . mysqli_error($conn);
    }
    $active_tab = 'profile';
}

/* ===============================================================
   ĐỔI MẬT KHẨU
=============================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $user_data = fetch_user_data($conn, $user_id);
    $plain_password = $user_data['Mat_Khau'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Vui lòng điền đầy đủ thông tin.";
    } elseif ($current_password !== $plain_password) {
        $error_message = "Mật khẩu hiện tại không đúng.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Mật khẩu mới và xác nhận không khớp.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    } else {
        $new_plain_password = mysqli_real_escape_string($conn, $new_password);
        $update_pass_sql = "UPDATE user SET Mat_Khau = '$new_plain_password' WHERE id = $user_id";
        if (mysqli_query($conn, $update_pass_sql)) {
            $success_message = "Đổi mật khẩu thành công!";
        } else {
            $error_message = "Lỗi khi đổi mật khẩu: " . mysqli_error($conn);
        }
    }
    $active_tab = 'password';
}

/* ===============================================================
   QUẢN LÝ TÀI KHOẢN NGÂN HÀNG
=============================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $account_number = mysqli_real_escape_string($conn, $_POST['account_number']);
    $display_name = mysqli_real_escape_string($conn, $_POST['display_name']);

    $insert_sql = "INSERT INTO user_bank_accounts (user_id, bank_name, account_number, display_name) 
                   VALUES ('$user_id', '$bank_name', '$account_number', '$display_name')";
    if (mysqli_query($conn, $insert_sql)) {
        $success_message = "Thêm tài khoản ngân hàng thành công!";
        header("Location: profile.php?tab=bank_account");
        exit();
    } else {
        $error_message = "Lỗi khi thêm tài khoản: " . mysqli_error($conn);
    }
}

if (isset($_GET['delete'])) {
    $account_id = (int)$_GET['delete'];
    $delete_sql = "DELETE FROM user_bank_accounts WHERE id = $account_id AND user_id = $user_id LIMIT 1";
    if (mysqli_query($conn, $delete_sql)) {
        $success_message = "Xóa tài khoản ngân hàng thành công!";
        header("Location: profile.php?tab=bank_account");
        exit();
    } else {
        $error_message = "Lỗi khi xóa tài khoản: " . mysqli_error($conn);
    }
}

// Lấy danh sách tài khoản ngân hàng
$list_sql = "SELECT id, bank_name, account_number, display_name 
             FROM user_bank_accounts 
             WHERE user_id = $user_id 
             ORDER BY id DESC";
$result = mysqli_query($conn, $list_sql);

if ($result === false) {
    $error_message = "Lỗi truy vấn danh sách tài khoản: " . mysqli_error($conn);
}

// Giữ trạng thái tab
if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng | <?= htmlspecialchars($tai_khoan) ?></title>
    <style>
        body {
            padding-top: 70px;
        }

        .avatar-container {
            width: 150px;
            height: 150px;
            overflow: hidden;
            border-radius: 50%;
            border: 3px solid #0d6efd;
            margin-bottom: 15px;
            position: relative;
        }

        .avatar-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Tăng kích thước input file ẩn để dễ click */
        .file-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .card-header {
            /* Giữ màu nền chung của card-header */
            background-color: #f7f7f7;
            font-weight: bold;
            font-size: 1.2em;
        }

        /* Đảm bảo tab header được hiển thị đúng khi dùng nav-tabs trong card-header */
        .card-header-tabs .nav-link {
            border-bottom: none;
            /* Điều chỉnh màu chữ mặc định để không bị ảnh hưởng bởi màu nền */
            color: #0d6efd;
        }

        /* Đảm bảo Nav link active hiển thị đúng theo kiểu tab mặc định */
        .card-header-tabs .nav-link.active {
            color: #0d6efd;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }

        /* Điều chỉnh màu sắc cho khối Tài khoản Ngân hàng */
        .bank-account-section .card-header {
            background-color: #198754;
            /* Màu xanh lá cây của success */
            color: white;
        }

        .form-label {
            font-weight: bold;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <?php include 'sidebar_user.php'; ?>
    <div class="container my-5">
        <?php
        $breadcrumb_title = "Hồ sơ người dùng";
        $breadcrumb_items = [
            ["label" => "Trang chủ", "link" => "maincustomer.php"],
            ["label" => $breadcrumb_title]
        ];
        include "breadcrumb.php";
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Hồ sơ người dùng</h2>
        </div>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4 text-center">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="avatar-container mx-auto">
                                <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar người dùng" id="current_avatar">
                                <input type="file" name="new_avatar" id="new_avatar_input" class="file-upload-overlay" accept="image/jpeg, image/png">
                            </div>
                            <h4 class="card-title"><?= htmlspecialchars($ho_ten ?: $tai_khoan) ?></h4>
                            <p class="card-text text-muted">@<?= htmlspecialchars($tai_khoan) ?></p>

                            <button type="submit" name="update_avatar" id="update_avatar_btn" class="btn btn-sm btn-primary mt-2" disabled>Cập nhật Ảnh</button>
                            <small class="d-block text-muted mt-2">Nhấn vào ảnh để thay đổi</small>
                        </form>

                        <hr>
                        <p class="text-start"><strong>Vai trò:</strong> <span class="badge bg-primary"><?= htmlspecialchars($role) ?></span></p>
                        <p class="text-start"><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                        <p class="text-start"><strong>SĐT:</strong> <?= htmlspecialchars($phone) ?: 'Chưa cập nhật' ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link <?= $active_tab == 'profile' ? 'active' : '' ?>" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-pane" type="button" role="tab" aria-controls="profile-pane" aria-selected="<?= $active_tab == 'profile' ? 'true' : 'false' ?>">Thông tin Cá nhân</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link <?= $active_tab == 'password' ? 'active' : '' ?>" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab" aria-controls="password-pane" aria-selected="<?= $active_tab == 'password' ? 'true' : 'false' ?>">Đổi Mật Khẩu</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link <?= $active_tab == 'bank_account' ? 'active' : '' ?>" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank-pane" type="button" role="tab" aria-controls="bank-pane" aria-selected="<?= $active_tab == 'bank_account' ? 'true' : 'false' ?>">Tài khoản Ngân hàng</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="profileTabsContent">

                            <div class="tab-pane fade <?= $active_tab == 'profile' ? 'show active' : '' ?>" id="profile-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                                <form method="post">
                                    <input type="hidden" name="update_profile" value="1">
                                    <div class="mb-3">
                                        <label for="ho_ten" class="form-label">Họ và Tên</label>
                                        <input type="text" name="ho_ten" id="ho_ten" class="form-control" value="<?= htmlspecialchars($ho_ten) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Lưu Thay Đổi Thông tin</button>
                                </form>
                            </div>

                            <div class="tab-pane fade <?= $active_tab == 'password' ? 'show active' : '' ?>" id="password-pane" role="tabpanel" aria-labelledby="password-tab" tabindex="0">
                                <form method="post">
                                    <input type="hidden" name="change_password" value="1">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Mật khẩu mới</label>
                                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning">Đổi Mật Khẩu</button>
                                </form>
                            </div>

                            <div class="tab-pane fade <?= $active_tab == 'bank_account' ? 'show active' : '' ?>" id="bank-pane" role="tabpanel" aria-labelledby="bank-tab" tabindex="0">
                                <div class="bank-account-section">
                                    <div class="card mb-4">
                                        <div class="card-header bg-success text-white">Thêm tài khoản ngân hàng</div>
                                        <div class="card-body">
                                            <form method="post" class="row g-3">
                                                <input type="hidden" name="add_account" value="1">
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
                                                    <input type="text" name="display_name" class="form-control" placeholder="Tên hiển thị (Tên chủ TK)" required>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" name="add_account" class="btn btn-success">Thêm tài khoản</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header">Danh sách tài khoản ngân hàng đã liên kết</div>
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
                                                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                            <tr>
                                                                <td><?= $row['id'] ?></td>
                                                                <td><?= htmlspecialchars($row['bank_name']) ?></td>
                                                                <td><?= htmlspecialchars($row['account_number']) ?></td>
                                                                <td><?= htmlspecialchars($row['display_name']) ?></td>
                                                                <td>
                                                                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa tài khoản này?')">Xóa</a>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="5" class="text-center">Chưa có tài khoản ngân hàng nào được liên kết.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script để xem trước ảnh và kích hoạt nút Cập nhật
        document.getElementById('new_avatar_input').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            const avatarImg = document.getElementById('current_avatar');
            const updateBtn = document.getElementById('update_avatar_btn');

            if (file) {
                reader.onload = function(e) {
                    avatarImg.src = e.target.result;
                    updateBtn.disabled = false; // Kích hoạt nút sau khi chọn file
                }
                reader.readAsDataURL(file);
            } else {
                updateBtn.disabled = true; // Vô hiệu hóa nếu không có file
            }
        });

        // Kích hoạt tab sau khi load trang dựa trên URL hoặc PHP
        const triggerTab = document.querySelector(`#profileTabs button[data-bs-target="#<?= $active_tab ?>-pane"]`);
        if (triggerTab) {
            new bootstrap.Tab(triggerTab).show();
        }
    </script>

</body>

</html>