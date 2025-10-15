<?php
include("Database/connectdb.php");
include "Database/function.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = ''; // Biến PHP để lưu thông báo lỗi
$success = ''; // Biến PHP để lưu thông báo thành công (nếu có)
$taikhoandangnhap = ''; // Biến để giữ lại tên đăng nhập khi có lỗi

// Xử lý thông báo đăng ký thành công (từ register.php)
if (isset($_SESSION['register_success'])) {
    $success = 'Đăng ký tài khoản thành công! Vui lòng đăng nhập.';
    unset($_SESSION['register_success']);
}

if (isset($_POST['dang_nhap'])) {
    $taikhoandangnhap = trim($_POST['username'] ?? '');
    $matkhaudangnhap = $_POST['password'] ?? '';


    if (check_exist_account($taikhoandangnhap)) {
        if (check_dang_nhap($taikhoandangnhap, $matkhaudangnhap)) {
            // Đăng nhập thành công
            $a = lay_tai_khoan($taikhoandangnhap, $matkhaudangnhap);

            // Lưu vào session
            $_SESSION['tk'] = $taikhoandangnhap;
            $_SESSION['role'] = $a['role'];

            // Phân quyền điều hướng
            if ($a['role'] === 'admin' || $a['role'] === 'superadmin') {
                header('Location: admin.php'); // Chuyển đến dashboard admin
            } else {
                header('Location: maincustomer.php'); // Người dùng bình thường
            }
            exit;
        } else {
            // SỬA LỖI HIỂN THỊ: Gán thông báo lỗi vào biến $error
            $error = 'Bạn đã sai tên tài khoản hoặc mật khẩu.';
        }
    } else {
        // SỬA LỖI HIỂN THỊ: Gán thông báo lỗi vào biến $error
        $error = 'Tên tài khoản chưa được đăng kí.';
    }
}
// Giữ lại tên đăng nhập khi có lỗi
$input_username = $taikhoandangnhap;
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('../img/background.jpg');
            background-size: cover;
            background-position: center center;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 400px;
            padding: 30px 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        h2 {
            text-align: center;
            color: #1877f2;
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: bold;
        }

        label {
            display: none;
        }

        /* Đồng bộ input field */
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin: 8px 0 15px 0;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #fff;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #1877f2;
            outline: none;
            box-shadow: 0 0 0 1px #1877f2;
        }

        /* Đồng bộ nút Đăng nhập */
        input[type="submit"] {
            width: 100%;
            padding: 14px;
            background: #1877f2;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: background 0.2s;
            margin-top: 10px;
        }

        input[type="submit"]:hover {
            background: #156ad4;
        }

        /* Đồng bộ link Đăng ký */
        .register-link {
            margin-top: 20px;
            text-align: center;
            font-size: 0.95rem;
            color: #555;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .register-link a {
            color: #42b72a;
            font-weight: 600;
            text-decoration: none;
        }

        /* Styles cho thông báo LỖI và THÀNH CÔNG */
        .message-error,
        .message-success {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: 500;
            font-size: 0.95rem;
            text-align: left;
        }

        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Đăng nhập</h2>

        <?php if ($success): ?>
            <div class="message-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <label for="username">Tên đăng nhập:</label>
            <input type="text" id="username" name="username"
                value="<?php echo htmlspecialchars($input_username); ?>"
                placeholder="Tên đăng nhập" required>

            <label for="password">Mật khẩu:</label>
            <input type="password" id="password" name="password" placeholder="Mật khẩu" required>

            <input type="submit" value="Đăng nhập" name="dang_nhap">
        </form>
        <div class="register-link">
            Nếu chưa có tài khoản, hãy
            <a href="register.php">Đăng kí ngay</a>
        </div>
    </div>
</body>

</html>