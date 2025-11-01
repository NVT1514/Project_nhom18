<?php
include("Database/connectdb.php");
include "Database/function.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = "";
$alert_type = ""; // success | danger | warning

if (isset($_POST['dang_nhap'])) {
    $taikhoandangnhap = $_POST['username'];
    $matkhaudangnhap = $_POST['password'];

    if (check_exist_account($taikhoandangnhap)) {
        if (check_dang_nhap($taikhoandangnhap, $matkhaudangnhap)) {
            $a = lay_tai_khoan($taikhoandangnhap, $matkhaudangnhap);
            $_SESSION['user_id'] = $a['id'];
            $_SESSION['tk'] = $taikhoandangnhap;
            $_SESSION['role'] = $a['role'];

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
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: url('../img/background.jpg') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .fade-out {
            transition: opacity 1s ease-out;
            opacity: 0;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h3 class="text-center mb-4">Đăng nhập</h3>

        <?php if (!empty($message)) : ?>
            <div id="alertBox" class="alert alert-<?php echo $alert_type; ?> text-center" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Tên đăng nhập</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="d-grid">
                <button type="submit" name="dang_nhap" class="btn btn-primary">Đăng nhập</button>
            </div>
        </form>

        <div class="text-center mt-3">
            <p><a href="forgot_password.php" class="link-primary">Quên mật khẩu?</a></p>
            <p>Chưa có tài khoản? <a href="register.php" class="link-success">Đăng kí ngay</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 🕓 Tự động ẩn thông báo sau 3 giây
        document.addEventListener("DOMContentLoaded", function() {
            const alertBox = document.getElementById("alertBox");
            if (alertBox) {
                setTimeout(() => {
                    alertBox.classList.add("fade-out");
                    setTimeout(() => alertBox.style.display = "none", 1000);
                }, 3000);
            }
        });
    </script>
</body>

</html>