<?php
// PHP logic (Giữ nguyên)
include("Database/connectdb.php");
include "Database/function.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = "";
$alert_type = ""; // success | danger | warning

// 🚀 LOGIC MỚI: XỬ LÝ THÔNG BÁO TỪ ĐĂNG KÝ THÀNH CÔNG
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $alert_type = "success";
    // Xóa session để thông báo không xuất hiện lại sau khi refresh
    unset($_SESSION['success_message']);
}
// ----------------------------------------------------

if (isset($_POST['dang_nhap'])) {
    $taikhoandangnhap = $_POST['username'];
    $matkhaudangnhap = $_POST['password'];

    if (check_exist_account($taikhoandangnhap)) {
        // Lưu ý: Trong môi trường thực tế, bạn nên sử dụng password_verify()
        // để kiểm tra mật khẩu đã được hash (băm) trong cơ sở dữ liệu.
        if (check_dang_nhap($taikhoandangnhap, $matkhaudangnhap)) {
            $a = lay_tai_khoan($taikhoandangnhap, $matkhaudangnhap);

            // Set Session
            $_SESSION['user_id'] = $a['id'];
            $_SESSION['tk'] = $taikhoandangnhap;
            $_SESSION['role'] = $a['role'];

            // 🛑 SỬA LỖI CỐT LÕI: Buộc PHP ghi dữ liệu Session và đóng file/lock session
            session_write_close();

            if ($a['role'] === 'admin' || $a['role'] === 'superadmin') {
                header("Location: thong_ke.php");
            } else {
                header("Location: maincustomer.php");
            }
            exit;
        } else {
            $message = "❌ Sai mật khẩu. Vui lòng thử lại!";
            $alert_type = "danger";
        }
    } else {
        $message = "⚠️ Tên tài khoản chưa được đăng ký. Hãy tạo tài khoản mới!";
        $alert_type = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | CLOTHIX</title>
    <style>
        :root {
            --color-brand-blue: #092C4C;
            /* Xanh Navy đậm, lấy từ banner CLOTHIX */
            --color-text-dark: #333333;
            /* Màu chữ chính */
            --color-text-light: #f0f0f0;
            /* Màu chữ trên nền tối */
            --color-white: #ffffff;
            --color-link: #FF9F1C;
            /* Giữ màu cam nổi bật cho link/focus */
            --color-bg-light: #f7f7f7;
            /* Nền form đăng nhập */
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background-color: var(--color-brand-blue);
            /* Thay nền tổng thể bằng màu brand */
        }

        .login-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 800px;
            max-width: 90%;
            height: 550px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        /* Phần Trái - Nội dung thương hiệu/Quảng cáo */
        .login-content {
            background-color: var(--color-brand-blue);
            /* Nền tối */
            color: var(--color-white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            position: relative;
        }

        .login-content h1 {
            font-size: 2.2rem;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        /* Thay thế icon tên lửa bằng hình ảnh quần áo hoặc logo */
        .brand-image {
            max-width: 80%;
            height: auto;
            border-radius: 8px;
            margin-top: 30px;
        }

        /* Ẩn các hiệu ứng ngôi sao không cần thiết */
        .stars,
        .rocket-icon {
            display: none;
        }


        /* Phần Phải - Form Đăng nhập */
        .login-form-wrapper {
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

        /* Chuyển các nút Sign In/Sign Up lên góc phải */
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

        .login-form-wrapper h2 {
            color: var(--color-text-dark);
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: var(--color-text-dark);
        }

        .form-group input {
            width: 100%;
            padding: 10px 0;
            border: none;
            border-bottom: 1px solid #cccccc;
            background-color: transparent;
            color: var(--color-text-dark);
            font-size: 1rem;
            outline: none;
            transition: border-bottom-color 0.3s;
        }

        .form-group input:focus {
            border-bottom: 2px solid var(--color-brand-blue);
            /* Viền focus màu brand */
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: var(--color-brand-blue);
            /* Nút Đăng nhập màu brand */
            color: var(--color-white);
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .btn-login:hover {
            background-color: #0d3a66;
        }

        .link-group {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
        }

        .link-group a {
            color: var(--color-brand-blue);
            /* Link Quên mật khẩu màu brand */
            text-decoration: none;
            transition: color 0.3s;
        }

        .link-group a:hover {
            color: var(--color-link);
        }

        /* Giữ nguyên Alert Box Styling */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            text-align: center;
            /* Cần thêm màu chữ cho Alert trên nền sáng */
            font-weight: 500;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .fade-out {
            transition: opacity 1s ease-out;
            opacity: 0;
        }


        /* Responsive (Tùy chọn) */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                height: auto;
                width: 95%;
            }

            .login-content {
                display: none;
            }

            .login-form-wrapper {
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
    <div class="login-container">
        <div class="login-content">
            <h1>
                Tham gia CLOTHIX - Nơi định hình phong cách cá nhân
            </h1>
            <img src="path/to/product-image.jpg" alt="Phong cách thời trang CLOTHIX" class="brand-image" style="display:none">

            <p style="margin-top: 20px; font-size: 1.1rem; opacity: 0.8;">
                Khám phá bộ sưu tập Thu Đông 2025 mới nhất.
            </p>
        </div>

        <div class="login-form-wrapper">
            <div class="top-links">
                <a href="#" class="signin">Đăng nhập</a>
                <a href="register.php" class="signup">Đăng ký</a>
            </div>

            <h2>Đăng nhập</h2>

            <?php if (!empty($message)) : ?>
                <div id="alertBox" class="alert alert-<?php echo $alert_type; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" name="dang_nhap" class="btn-login">Đăng nhập</button>
            </form>

            <div class="link-group">
                <p><a href="forgot_password.php">Quên mật khẩu?</a></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const alertBox = document.getElementById("alertBox");
            if (alertBox) {
                // Chỉ set timeout cho alertBox nếu nó tồn tại
                setTimeout(() => {
                    alertBox.classList.add("fade-out");
                    setTimeout(() => alertBox.style.display = "none", 1000);
                }, 3000);
            }
        });
    </script>
</body>

</html>