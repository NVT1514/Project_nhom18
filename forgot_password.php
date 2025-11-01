<?php
// PHẦN LOGIC PHP GIỮ NGUYÊN CHỨC NĂNG CŨ
include("Database/connectdb.php");

if (isset($_POST['submit'])) {
    // ⚠️ LƯU Ý: Cần sử dụng Prepared Statements để tránh lỗi SQL Injection trong code thực tế.
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);

    if (!empty($email) && !empty($new_password)) {
        // Kiểm tra email có tồn tại không
        $sql = "SELECT * FROM user WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            // Cập nhật mật khẩu mới (Mật khẩu đã được hash)
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = "UPDATE user SET mat_khau = '$hashed_password' WHERE email = '$email'";

            // Xử lý kết quả cập nhật
            if (mysqli_query($conn, $update)) {
                echo "<script>alert('✅ Đặt lại mật khẩu thành công! Vui lòng đăng nhập lại.'); window.location='login.php';</script>";
            } else {
                echo "<script>alert('❌ Lỗi khi cập nhật mật khẩu: " . mysqli_error($conn) . "');</script>";
            }
        } else {
            echo "<script>alert('❌ Email không tồn tại trong hệ thống');</script>";
        }
    } else {
        echo "<script>alert('Vui lòng nhập đầy đủ thông tin');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang='vi'>

<head>
    <meta charset='UTF-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại Mật khẩu</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* CSS NÂNG CẤP GIAO DIỆN */
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --background-color: #f0f2f5;
            --card-background: #ffffff;
            --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: var(--background-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }

        .form-container {
            background: var(--card-background);
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 25px;
        }

        /* Ẩn label và dùng placeholder để form gọn hơn, hoặc dùng label phía trên */
        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: 400;
            font-size: 0.9em;
            color: #555;
            margin-top: 15px;
            /* Thêm khoảng cách giữa các trường */
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 0 0 15px 0;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            box-sizing: border-box;
            /* Đảm bảo padding không làm hỏng width */
            transition: border-color 0.3s;
            font-size: 1em;
        }

        input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1em;
            margin-top: 10px;
            transition: background 0.3s ease, transform 0.1s ease;
        }

        .submit-btn:hover {
            background: #0056b3;
        }

        .submit-btn:active {
            transform: scale(0.99);
        }

        .back-link {
            display: block;
            margin-top: 25px;
            font-size: 0.9em;
        }

        .back-link a {
            color: var(--secondary-color);
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Quên Mật khẩu</h2>
        <form method="POST">
            <label for="email">Địa chỉ Email:</label>
            <input type="email" id="email" name="email" placeholder="Nhập email đã đăng ký" required>

            <label for="new_password">Mật khẩu mới:</label>
            <input type="password" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới" required>

            <button type="submit" name="submit" class="submit-btn">
                Xác nhận và Đặt lại
            </button>
        </form>

        <div class="back-link">
            <a href="login.php">← Quay lại trang Đăng nhập</a>
        </div>
    </div>
</body>

</html>