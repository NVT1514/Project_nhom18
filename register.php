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
            // CẢNH BÁO BẢO MẬT: Trong môi trường thực tế, BẠN PHẢI sử dụng password_hash()
            $matkhau_de_luu = $matkhau; // LƯU TRỰC TIẾP MẬT KHẨU THÔ (Giữ nguyên logic của bạn)

            $ngay_tao = date('Y-m-d H:i:s');
            $role = 'user';
            $ho_ten_default = $taikhoan;

            // Sử dụng 6 cột: Tai_Khoan, Mat_Khau, Email, role, Ngay_Tao, Ho_Ten
            $sql_insert = "INSERT INTO user (Tai_Khoan, Mat_Khau, Email, role, Ngay_Tao, Ho_Ten) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);

            // BIND PARAMETER VỚI $matkhau_de_luu 
            $stmt_insert->bind_param("ssssss", $taikhoan, $matkhau_de_luu, $email, $role, $ngay_tao, $ho_ten_default);

            if ($stmt_insert->execute()) {
                // 1. Lưu thông báo vào session
                $_SESSION['success_message'] = "Đăng ký tài khoản **$taikhoan** thành công! Vui lòng đăng nhập.";

                // 2. Chuyển hướng NGAY LẬP TỨC
                header("Location: login.php");
                session_write_close(); // Rất quan trọng để đảm bảo session được lưu
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
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Tài Khoản | CLOTHIX</title>
    <style>
        /* Biến màu sắc đồng bộ với trang Đăng nhập CLOTHIX */
        :root {
            --color-brand-blue: #092C4C;
            /* Xanh Navy đậm */
            --color-text-dark: #333333;
            /* Màu chữ chính */
            --color-text-light: #f0f0f0;
            /* Màu chữ trên nền tối */
            --color-white: #ffffff;
            --color-link: #FF9F1C;
            /* Màu cam nổi bật cho link/focus */
            --color-bg-light: #f7f7f7;
            /* Nền form đăng nhập */
            --color-success-bg: #d4edda;
            --color-success-text: #155724;
            --color-error-bg: #f8d7da;
            --color-error-text: #721c24;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background-color: var(--color-brand-blue);
            /* Nền tổng thể màu brand */
        }

        .register-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 800px;
            max-width: 90%;
            height: 600px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        /* Phần Trái - Nội dung thương hiệu/Quảng cáo (Xanh Navy đậm) */
        .register-content {
            background-color: var(--color-brand-blue);
            color: var(--color-white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            position: relative;
        }

        .register-content h1 {
            font-size: 2.2rem;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .brand-image {
            max-width: 80%;
            height: auto;
            border-radius: 8px;
            margin-top: 30px;
        }

        /* Ẩn các hiệu ứng không cần thiết */
        .stars,
        .rocket-icon {
            display: none;
        }

        /* Phần Phải - Form (Trắng/Xám nhạt) */
        .register-form-wrapper {
            background-color: var(--color-bg-light);
            /* Nền sáng */
            color: var(--color-text-dark);
            /* Chữ tối */
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .top-links {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .top-links a {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            margin-left: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        /* Đồng bộ màu nút */
        .top-links .signin {
            color: var(--color-text-dark);
            background-color: transparent;
            border: 1px solid var(--color-text-dark);
        }

        .top-links .signup {
            background-color: var(--color-brand-blue);
            /* Nút Đăng ký màu brand */
            color: var(--color-white);
        }

        .top-links .signup:hover {
            background-color: #0d3a66;
        }

        .register-form-wrapper h2 {
            color: var(--color-text-dark);
            text-align: center;
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: var(--color-text-dark);
            /* Label màu tối trên nền sáng */
        }

        /* Đồng bộ kiểu input */
        .input-group input {
            width: 100%;
            padding: 10px 0;
            border: none;
            border-bottom: 1px solid #cccccc;
            /* Viền xám nhạt */
            background-color: transparent;
            color: var(--color-text-dark);
            font-size: 1rem;
            outline: none;
            box-sizing: border-box;
        }

        .input-group input:focus {
            border-bottom: 2px solid var(--color-brand-blue);
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: var(--color-brand-blue);
            /* Nút Đăng ký màu brand */
            color: var(--color-white);
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .btn-register:hover {
            background-color: #0d3a66;
        }

        .login-link {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            color: var(--color-text-dark);
        }

        .login-link a {
            color: var(--color-brand-blue);
            /* Link màu brand */
            text-decoration: none;
            transition: color 0.3s;
        }

        .login-link a:hover {
            color: var(--color-link);
        }

        /* Giữ nguyên Alert Styling */
        .message-box {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.95rem;
            text-align: left;
            border: 1px solid transparent;
            animation: fadein 0.5s;
        }

        .message-error {
            background-color: var(--color-error-bg);
            color: var(--color-error-text);
            border-color: #f5c6cb;
        }

        .message-success {
            background-color: var(--color-success-bg);
            color: var(--color-success-text);
            border-color: #c3e6cb;
        }

        .fade-out {
            transition: opacity 1s ease-out;
            opacity: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .register-container {
                grid-template-columns: 1fr;
                height: auto;
                width: 95%;
            }

            .register-content {
                display: none;
            }

            .register-form-wrapper {
                border-radius: 15px;
                padding: 40px 30px;
            }

            .top-links {
                position: static;
                text-align: right;
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-content">
            <h1>
                Tham gia CLOTHIX - Nơi định hình phong cách cá nhân
            </h1>
            <img src="path/to/product-image.jpg" alt="Phong cách thời trang CLOTHIX" class="brand-image" style="display:none">

            <p style="margin-top: 20px; font-size: 1.1rem; opacity: 0.8;">
                Đăng ký ngay để nhận ưu đãi đầu tiên!
            </p>
        </div>

        <div class="register-form-wrapper">
            <div class="top-links">
                <a href="login.php" class="signin">Đăng nhập</a>
                <a href="#" class="signup">Đăng ký</a>
            </div>

            <h2>Đăng Ký Tài Khoản</h2>

            <?php if (!empty($success)): ?>
                <div id="alertBox" class="message-box message-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div id="alertBox" class="message-box message-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php">

                <div class="input-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username"
                        value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                        placeholder="Tên đăng nhập" required autocomplete="username">
                </div>

                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                        value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                        placeholder="Email" required autocomplete="email">
                </div>

                <div class="input-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Mật khẩu" required autocomplete="new-password">
                </div>

                <div class="input-group">
                    <label for="confirm_password">Nhập lại mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn-register">Đăng Ký</button>
            </form>

            <div class="login-link">
                Bạn đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
            </div>

        </div>
    </div>

    <script>
        // Script để hộp thông báo lỗi/thành công tự động biến mất
        document.addEventListener("DOMContentLoaded", function() {
            const alertBox = document.getElementById("alertBox");
            if (alertBox && alertBox.classList.contains('message-success')) {
                setTimeout(() => {
                    alertBox.classList.add("fade-out");
                    setTimeout(() => alertBox.style.display = "none", 1000);
                }, 3000);
            }
        });
    </script>
</body>

</html>