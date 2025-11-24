<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. KIỂM TRA PHÂN QUYỀN
// Chỉ Superadmin mới được tạo Admin mới
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    // Nếu không phải Superadmin, chuyển hướng hoặc hiển thị thông báo lỗi
    header("Location: index.php"); // Hoặc trang không có quyền truy cập
    exit();
}

include "Database/connectdb.php";

$error = '';
$success = '';

// Khởi tạo các biến để giữ giá trị form
$tai_khoan = '';
$ho_ten = '';
$email = '';
$role = 'admin'; // Giá trị mặc định

// 2. XỬ LÝ KHI FORM ĐƯỢC GỬI (POST request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy và làm sạch dữ liệu đầu vào
    $tai_khoan = trim($_POST['Tai_Khoan'] ?? '');
    $ho_ten = trim($_POST['Ho_Ten'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    $mat_khau = $_POST['Mat_Khau'] ?? ''; // Lấy mật khẩu thô
    $role = $_POST['role'] ?? 'admin';

    // Kiểm tra dữ liệu bắt buộc
    if (empty($tai_khoan) || empty($mat_khau) || empty($email) || empty($ho_ten)) {
        $error = "Vui lòng điền đầy đủ tất cả các trường bắt buộc.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Địa chỉ Email không hợp lệ.";
    } else {
        // =======================================================
        // ❌ CẢNH BÁO: KHÔNG SỬ DỤNG password_hash(), LƯU MẬT KHẨU THÔ
        // LƯU Ý: Đây là mã theo yêu cầu của bạn, nhưng có rủi ro bảo mật cao!
        $plain_password = $mat_khau; // LƯU MẬT KHẨU THÔ
        // =======================================================

        // 3. KIỂM TRA TÀI KHOẢN HOẶC EMAIL ĐÃ TỒN TẠI CHƯA
        $sql_check = "SELECT id FROM user WHERE Tai_Khoan = ? OR Email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $tai_khoan, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = "Tên tài khoản hoặc Email đã được sử dụng. Vui lòng chọn thông tin khác.";
        } else {
            // 4. THỰC HIỆN TRUY VẤN THÊM ADMIN
            $sql_insert = "INSERT INTO user (Tai_Khoan, Ho_Ten, Email, Mat_Khau, role) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);

            // Tham số: sssss (string, string, string, string, string)
            $stmt_insert->bind_param("sssss", $tai_khoan, $ho_ten, $email, $plain_password, $role);

            if ($stmt_insert->execute()) {
                $success = "Tạo tài khoản Admin **" . htmlspecialchars($tai_khoan) . "** thành công! (Mật khẩu được lưu dưới dạng văn bản thuần)";
                // Xóa các biến để reset form sau khi thành công (trừ $success)
                $tai_khoan = '';
                $ho_ten = '';
                $email = '';
                $role = 'admin';
            } else {
                $error = "Lỗi khi tạo tài khoản Admin: " . $conn->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Tài Khoản Admin Mới</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .btn-submit {
            background-color: #16a361ff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-submit:hover {
            background-color: #13653f;
        }

        .alert-error {
            padding: 10px;
            margin-bottom: 15px;
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }

        .alert-success {
            padding: 10px;
            margin-bottom: 15px;
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar_admin.php'; ?>
        <div class="main-content">
            <div class="form-container">
                <h2>➕ Tạo Tài Khoản Admin Mới</h2>
                <hr>

                <?php if ($error): ?>
                    <div class="alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">

                    <div class="form-group">
                        <label for="Tai_Khoan">Tên đăng nhập (*):</label>
                        <input type="text" id="Tai_Khoan" name="Tai_Khoan"
                            value="<?php echo htmlspecialchars($tai_khoan); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="Ho_Ten">Họ Tên (*):</label>
                        <input type="text" id="Ho_Ten" name="Ho_Ten"
                            value="<?php echo htmlspecialchars($ho_ten); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="Email">Email (*):</label>
                        <input type="email" id="Email" name="Email"
                            value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="Mat_Khau">Mật khẩu (*):</label>
                        <input type="password" id="Mat_Khau" name="Mat_Khau" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Quyền hạn (*):</label>
                        <select id="role" name="role" required>
                            <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                                <option value="superadmin" <?php echo ($role == 'superadmin') ? 'selected' : ''; ?>>Superadmin</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit"><i class="fa-solid fa-plus-circle"></i> Tạo Admin</button>
                    <a href="quanlinguoidung_admin.php" style="margin-left: 10px;">Quay lại danh sách</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>