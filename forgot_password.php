<?php
session_start();
require_once 'Database/connectdb.php';
require_once 'send_email.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    // Kiểm tra email có tồn tại không
    $sql = "SELECT * FROM user WHERE Email = '$email'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        // Tạo mã token ngẫu nhiên
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Lưu token vào database
        $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES ('$email', '$token', '$expires_at')";
        mysqli_query($conn, $sql);

        // Tạo link reset password
        $reset_link = "http://localhost/Project_nhom18/reset_password.php?token=" . $token;

        // Gửi email
        $subject = "Yêu cầu đặt lại mật khẩu";
        $body = "
            <h2>Đặt lại mật khẩu</h2>
            <p>Bạn đã yêu cầu đặt lại mật khẩu. Vui lòng click vào link dưới đây:</p>
            <p><a href='$reset_link'>$reset_link</a></p>
            <p>Mã xác thực của bạn: <strong>$token</strong></p>
            <p>Link này sẽ hết hạn sau 1 giờ.</p>
            <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
        ";

        if (sendEmail($email, $subject, $body)) {
            $message = "Đã gửi link đặt lại mật khẩu đến email của bạn. Vui lòng kiểm tra!";
        } else {
            $error = "Không thể gửi email. Vui lòng thử lại!";
        }
    } else {
        $error = "Email không tồn tại trong hệ thống!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu</title>
    <style>
        /* CSS MỚI: Dựa trên ảnh mẫu */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            /* Nền xanh đậm */
            font-family: Arial, sans-serif;
            background-color: #172a3a;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            /* Form chính giữa, nền trắng */
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            max-width: 380px;
            width: 90%;
            text-align: center;
        }

        h2 {
            /* Tiêu đề */
            font-size: 1.5rem;
            text-align: center;
            color: #172a3a;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .form-group {
            /* Nhóm input */
            margin-bottom: 30px;
            text-align: left;
        }

        .label-text {
            /* Text mô tả trường input */
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-size: 0.9rem;
        }

        input[type="email"] {
            /* Style cho input */
            width: 100%;
            padding: 8px 0;
            border: none;
            border-bottom: 1px solid #ccc;
            /* Gạch dưới */
            font-size: 1rem;
            color: #333;
            background: transparent;
            transition: border-bottom-color 0.3s;
        }

        input:focus {
            outline: none;
            border-bottom-color: #172a3a;
            /* Thay đổi màu khi focus */
        }

        /* Cần điều chỉnh lại button để khớp màu xanh đậm trong ảnh */
        .submit-btn {
            width: 100%;
            padding: 12px;
            /* Màu nền xanh đậm */
            background-color: #172a3a;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background-color: #0d1e2a;
            transform: translateY(-1px);
        }

        .message {
            /* Thông báo lỗi/thành công */
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95rem;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            /* Link quay lại đăng nhập */
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
            color: #777;
        }

        .back-link a {
            color: #667eea;
            /* Giữ màu link cũ cho tính nhất quán */
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Quên Mật khẩu</h2>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email" class="label-text">Địa chỉ Email</label>
                <input type="email" id="email" name="email" required placeholder="Nhập email đã đăng ký">
            </div>

            <div class="form-group" style="margin-bottom: 40px; opacity: 0; height: 0; overflow: hidden;">
                <label for="new_password" class="label-text">Mật khẩu mới</label>
                <input type="password" id="new_password" placeholder="Nhập mật khẩu mới">
            </div>

            <button type="submit" class="submit-btn">Gửi Email</button>
        </form>

        <div class="back-link">
            — <a href="login.php">Quay lại trang Đăng nhập</a>
        </div>
    </div>
</body>

</html>