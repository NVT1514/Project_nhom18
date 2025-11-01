<?php
// =======================
// admin_banner.php
// =======================
session_start();



// ✅ Thư mục lưu banner
$upload_dir = __DIR__ . "/uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ✅ File cấu hình banner
$config_file = __DIR__ . "/banner_config.php";

// ✅ Đọc đường dẫn banner hiện tại
$current_banner = "uploads/banner-sanpham.jpg";
if (file_exists($config_file)) {
    include $config_file;
}

// ✅ Xử lý upload banner mới
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['banner'])) {
    $file = $_FILES['banner'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "banner-sanpham." . $ext;
        $target_path = $upload_dir . $filename;

        // Xóa banner cũ nếu có
        if (file_exists($target_path)) {
            unlink($target_path);
        }

        // Lưu ảnh mới
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Cập nhật đường dẫn trong config
            $relative_path = "uploads/" . $filename;
            file_put_contents($config_file, "<?php\n\$current_banner = '$relative_path';\n?>");

            $message = "✅ Cập nhật banner thành công!";
            $current_banner = $relative_path;
        } else {
            $message = "❌ Lỗi: Không thể lưu file!";
        }
    } else {
        $message = "❌ Lỗi upload file!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Banner Sản phẩm</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            max-width: 700px;
            margin: 60px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .preview {
            text-align: center;
            margin-bottom: 20px;
        }

        .preview img {
            width: 100%;
            max-height: 250px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        form {
            text-align: center;
        }

        input[type="file"] {
            display: block;
            margin: 15px auto;
            padding: 8px;
        }

        button {
            background: #007bff;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #0056b3;
        }

        .message {
            text-align: center;
            font-weight: 500;
            margin-top: 15px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #555;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>🖼️ Quản lý Banner Sản phẩm</h2>

        <?php if ($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <div class="preview">
            <h4>Banner hiện tại:</h4>
            <img src="<?= htmlspecialchars($current_banner) ?>" alt="Banner hiện tại">
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="file" name="banner" accept="image/*" required>
            <button type="submit">Tải lên & Cập nhật Banner</button>
        </form>

        <div style="text-align:center;">
            <a href="maincustomer.php" class="back-link">⬅ Quay lại trang quản trị</a>
        </div>
    </div>

</body>

</html>