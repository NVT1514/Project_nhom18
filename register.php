<?php
session_start();
// Đảm bảo đường dẫn tới file kết nối Database là chính xác
include "Database/connectdb.php";

$error = '';
$success = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form và loại bỏ khoảng trắng thừa
    $taikhoan = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matkhau = $_POST['password'] ?? ''; // Mật khẩu thô
    $xacnhan_matkhau = $_POST['confirm_password'] ?? '';

    // 1. Kiểm tra tính hợp lệ cơ bản
    if (empty($taikhoan) || empty($matkhau) || empty($email) || empty($xacnhan_matkhau)) {
        $error = 'Vui lòng điền đầy đủ các trường bắt buộc.';
    } elseif ($matkhau !== $xacnhan_matkhau) {
        $error = 'Mật khẩu nhập lại không khớp.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ Email không hợp lệ.';
    } elseif (strlen($matkhau) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } else {
        // 2. Kiểm tra tài khoản đã tồn tại chưa
        $sql_check = "SELECT Tai_Khoan FROM user WHERE Tai_Khoan = ? OR Email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ss", $taikhoan, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = 'Tên đăng nhập hoặc Email đã được sử dụng.';
        } else {
            // 3. LƯU MẬT KHẨU VÀO CSDL 

            // XÓA BỎ HÀM password_hash()
            $matkhau_de_luu = $matkhau; // LƯU TRỰC TIẾP MẬT KHẨU THÔ

            $ngay_tao = date('Y-m-d H:i:s');
            $role = 'user';
            $ho_ten_default = $taikhoan;

            // Sử dụng 6 cột: Tai_Khoan, Mat_Khau, Email, role, Ngay_Tao, Ho_Ten
            $sql_insert = "INSERT INTO user (Tai_Khoan, Mat_Khau, Email, role, Ngay_Tao, Ho_Ten) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);

            // BIND PARAMETER VỚI $matkhau_de_luu 
            $stmt_insert->bind_param("ssssss", $taikhoan, $matkhau_de_luu, $email, $role, $ngay_tao, $ho_ten_default);

            if ($stmt_insert->execute()) {
                // Đăng ký thành công, chuyển hướng về trang Đăng nhập
                $_SESSION['register_success'] = true;
                header("Location: login.php");
                exit();
            } else {
                $error = 'Có lỗi xảy ra trong quá trình đăng ký: ' . $conn->error;
            }

            $stmt_insert->close();
        }
        $stmt_check->close();
    }
    // Giữ lại dữ liệu form khi có lỗi để người dùng không phải nhập lại
    $form_data = $_POST;
} else {
    // Lấy dữ liệu form nếu có lỗi từ lần submit trước (hoặc để trống nếu lần đầu truy cập)
    $form_data = $_SESSION['form_data'] ?? [];
    unset($_SESSION['form_data']);
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Tài Khoản</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* CSS Đồng bộ */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            /* Ảnh nền */
            background: #f0f2f5;
            background-image: url('nenden.jpg');
            background-size: cover;
            background-position: center;
        }

        .page-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.1);
            /* Lớp phủ mờ */
        }

        .register-container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .register-container h2 {
            margin-bottom: 25px;
            font-size: 1.8rem;
            color: #1877f2;
        }

        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .input-group label {
            display: none;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .input-group input:focus {
            border-color: #1877f2;
            outline: none;
            box-shadow: 0 0 0 1px #1877f2;
        }

        .btn-register {
            width: 100%;
            background-color: #1877f2;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .btn-register:hover {
            background-color: #156ad4;
        }

        .link-login {
            display: block;
            margin-top: 20px;
            font-size: 0.95rem;
            color: #555;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            text-align: center;
        }

        .link-login a {
            color: #42b72a;
            text-decoration: none;
            font-weight: 600;
        }

        .message-error {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: 500;
            font-size: 0.95rem;
            text-align: left;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="page-container">
        <div class="register-container">
            <h2>Đăng Ký Tài Khoản</h2>

            <?php if ($error): ?>
                <div class="message-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php">

                <div class="input-group">
                    <label for="username">Tên đăng nhập:</label>
                    <input type="text" id="username" name="username"
                        value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                        placeholder="Tên đăng nhập" required>
                </div>

                <div class="input-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email"
                        value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                        placeholder="Email" required>
                </div>

                <div class="input-group">
                    <label for="password">Mật khẩu:</label>
                    <input type="password" id="password" name="password" placeholder="Mật khẩu" required>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Nhập lại mật khẩu:</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                </div>

                <button type="submit" class="btn-register">Đăng Ký</button>
            </form>

            <div class="login-link">
                Nếu đã có tài khoản, hãy
                <a href="login.php">Đăng nhập ngay</a>
            </div>
        </div>
    </div>
</body>

</html>