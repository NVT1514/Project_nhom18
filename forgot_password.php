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
    <title>Đặt lại Mật khẩu | CLOTHIX</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS ĐÃ ĐƯỢC CHỈNH SỬA ĐỂ KHỚP VỚI GIAO DIỆN CLOTHIX */
        :root {
            --color-brand-blue: #092C4C;
            /* Xanh Navy đậm */
            --color-text-dark: #333333;
            --color-white: #ffffff;
            --color-bg-light: #f7f7f7;
            /* Nền form đăng nhập */
            --color-button-blue: #092C4C;
            /* Màu xanh nút Đặt lại, có thể dùng brand blue */
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--color-brand-blue);
            /* Nền tối đồng bộ với trang login */
            color: var(--color-text-dark);
        }

        .form-container {
            background: var(--color-white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            font-weight: 600;
            color: var(--color-brand-blue);
            /* Tiêu đề màu Brand Blue */
            margin-bottom: 30px;
            font-size: 1.5rem;
        }

        /* Đồng bộ kiểu input với trang Đăng nhập */
        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: 400;
            font-size: 0.9em;
            color: var(--color-text-dark);
            margin-top: 20px;
        }

        input {
            width: 100%;
            padding: 10px 0;
            margin: 0 0 15px 0;
            border: none;
            border-bottom: 1px solid #cccccc;
            /* Chỉ có border dưới */
            background-color: transparent;
            color: var(--color-text-dark);
            font-size: 1rem;
            outline: none;
            box-sizing: border-box;
            transition: border-bottom-color 0.3s;
        }

        input:focus {
            border-bottom: 2px solid var(--color-brand-blue);
            /* Viền focus màu Brand Blue */
            box-shadow: none;
            /* Bỏ box-shadow mặc định của Poppins */
        }

        /* Nút Xác nhận và Đặt lại */
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: var(--color-button-blue);
            /* Giữ màu xanh sáng cho nút hành động chính */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 25px;
            transition: background 0.3s ease;
        }

        .submit-btn:hover {
            background: #0d3a66;
        }

        /* Liên kết Quay lại trang Đăng nhập */
        .back-link {
            display: block;
            margin-top: 25px;
            font-size: 0.95rem;
        }

        .back-link a {
            color: var(--color-brand-blue);
            /* Màu Brand Blue cho link */
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: #0d3a66;
            /* Màu đậm hơn khi hover */
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Quên Mật khẩu</h2>

        <form method="POST">
            <label for="email">Địa chỉ Email</label>
            <input type="email" id="email" name="email" placeholder="Nhập email đã đăng ký" required>

            <label for="new_password">Mật khẩu mới</label>
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