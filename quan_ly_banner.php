<?php
// ==========================================================
// 1. CẤU HÌNH VÀ KẾT NỐI DATABASE
// ==========================================================
$servername = "localhost";
$username = "root"; // Thay bằng username DB của bạn
$password = ""; // Thay bằng password DB của bạn
$dbname = "project_nhom18";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập mã hóa (quan trọng cho tiếng Việt)
$conn->set_charset("utf8mb4");

// Khai báo biến thông báo
$message = "";
$error = "";

// ==========================================================
// 2. LOGIC XỬ LÝ THAO TÁC (THÊM, SỬA, XÓA)
// ==========================================================

// --- XỬ LÝ THÊM BANNER ---
if (isset($_POST['add_banner'])) {
    $tieu_de = $_POST['tieu_de'];
    $lien_ket = $_POST['lien_ket'];
    $vi_tri = $_POST['vi_tri'];
    $thu_tu = (int)$_POST['thu_tu'];
    $trang_thai = $_POST['trang_thai'];
    $hinh_anh = null;

    // Xử lý File Upload (Đây là phần quan trọng nhất)
    if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
        $target_dir = "uploads/banners/"; // Thay bằng thư mục lưu ảnh thực tế của bạn
        $file_name = basename($_FILES["hinh_anh"]["name"]);
        $target_file = $target_dir . time() . "_" . $file_name; // Thêm timestamp để tên file là DUY NHẤT
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Kiểm tra định dạng file (chỉ cho phép JPG, PNG, JPEG, GIF)
        if (!in_array($imageFileType, array("jpg", "png", "jpeg", "gif"))) {
            $error = "Lỗi: Chỉ cho phép file JPG, JPEG, PNG & GIF.";
        } else {
            // Đảm bảo thư mục tồn tại
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES["hinh_anh"]["tmp_name"], $target_file)) {
                $hinh_anh = $target_file;
            } else {
                $error = "Lỗi khi di chuyển file upload.";
            }
        }
    } else {
        $error = "Lỗi: Vui lòng chọn một hình ảnh cho banner.";
    }

    // Nếu không có lỗi, tiến hành INSERT vào DB
    if (empty($error) && $hinh_anh) {
        $stmt = $conn->prepare("INSERT INTO banner (tieu_de, hinh_anh, lien_ket, vi_tri, thu_tu, trang_thai) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssids", $tieu_de, $hinh_anh, $lien_ket, $vi_tri, $thu_tu, $trang_thai);

        if ($stmt->execute()) {
            $message = "Thêm banner thành công!";
        } else {
            $error = "Lỗi khi thêm banner: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --- XỬ LÝ XÓA BANNER ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];

    // 1. Lấy đường dẫn ảnh để xóa file vật lý
    $stmt = $conn->prepare("SELECT hinh_anh FROM banner WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $result_img = $stmt->get_result();
    $banner_data = $result_img->fetch_assoc();
    $stmt->close();

    // 2. Xóa khỏi DB
    $stmt = $conn->prepare("DELETE FROM banner WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);

    if ($stmt->execute()) {
        // 3. Xóa file vật lý (nếu tồn tại)
        if ($banner_data && file_exists($banner_data['hinh_anh'])) {
            unlink($banner_data['hinh_anh']);
        }
        $message = "Xóa banner thành công!";
    } else {
        $error = "Lỗi khi xóa banner: " . $stmt->error;
    }
    $stmt->close();

    // Chuyển hướng để loại bỏ tham số GET (tránh xóa lại khi F5)
    header("Location: quan_ly_banner.php?msg=" . urlencode($message) . "&err=" . urlencode($error));
    exit();
}

// --- LẤY DANH SÁCH BANNER ---
$sql_select = "SELECT id, tieu_de, hinh_anh, lien_ket, vi_tri, thu_tu, trang_thai, ngay_tao FROM banner ORDER BY thu_tu ASC, ngay_tao DESC";
$result = $conn->query($sql_select);

// Lấy thông báo từ Redirect (sau khi xóa)
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
}
if (isset($_GET['err'])) {
    $error = urldecode($_GET['err']);
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản Lý Banner - Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        h1,
        h2 {
            color: #333;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .thumbnail {
            width: 100px;
            height: auto;
            display: block;
        }

        .actions a {
            margin-right: 5px;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 3px;
        }

        .edit {
            background-color: #ffc107;
            color: #333;
        }

        .delete {
            background-color: #dc3545;
            color: white;
        }

        .add-form {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .add-form label,
        .add-form input,
        .add-form select {
            display: block;
            margin-bottom: 10px;
        }

        .add-form input[type="text"],
        .add-form input[type="number"],
        .add-form select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        .add-form button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🖼️ Quản Lý Banner</h1>

        <?php if (!empty($message)): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2>Danh Sách Banner Hiện Có</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Vị trí</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th>Liên kết</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        // Hiển thị ảnh (Kiểm tra xem ảnh có tồn tại không)
                        echo "<td>";
                        if (!empty($row['hinh_anh']) && file_exists($row['hinh_anh'])) {
                            // Lưu ý: Đường dẫn ảnh cần tương đối chính xác từ file quan_ly_banner.php
                            echo "<img src='" . htmlspecialchars($row['hinh_anh']) . "' class='thumbnail' alt='Banner'>";
                        } else {
                            echo "Không có ảnh";
                        }
                        echo "</td>";
                        echo "<td>" . htmlspecialchars($row['tieu_de']) . "</td>";
                        echo "<td>" . $row['vi_tri'] . "</td>";
                        echo "<td>" . $row['thu_tu'] . "</td>";
                        echo "<td>" . $row['trang_thai'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['lien_ket']) . "</td>";
                        echo "<td>" . $row['ngay_tao'] . "</td>";
                        echo "<td class='actions'>";
                        // Thao tác Sửa (Chưa triển khai trang sửa chi tiết)
                        echo "<a href='#' class='edit'>Sửa</a>";
                        // Thao tác Xóa (Sử dụng confirm() trong JS)
                        echo "<a href='quan_ly_banner.php?action=delete&id=" . $row['id'] . "' class='delete' onclick=\"return confirm('Bạn có chắc chắn muốn xóa banner này không?');\">Xóa</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>Chưa có banner nào được thêm.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="add-form">
            <h2>➕ Thêm Banner Mới</h2>
            <form action="quan_ly_banner.php" method="POST" enctype="multipart/form-data">

                <label for="tieu_de">Tiêu đề (Gợi nhớ):</label>
                <input type="text" id="tieu_de" name="tieu_de">

                <label for="hinh_anh">Hình ảnh Banner (Bắt buộc):</label>
                <input type="file" id="hinh_anh" name="hinh_anh" required>

                <label for="lien_ket">Liên kết (URL khi click):</label>
                <input type="text" id="lien_ket" name="lien_ket" placeholder="/product/sale-off">

                <label for="vi_tri">Vị trí hiển thị:</label>
                <select id="vi_tri" name="vi_tri">
                    <option value="Trang chủ Slide">Trang chủ Slide</option>
                    <option value="Dưới Sản phẩm">Dưới Sản phẩm</option>
                    <option value="Sidebar">Sidebar</option>
                </select>

                <label for="thu_tu">Thứ tự ưu tiên (Số nhỏ hiển thị trước):</label>
                <input type="number" id="thu_tu" name="thu_tu" value="0" required>

                <label for="trang_thai">Trạng thái:</label>
                <select id="trang_thai" name="trang_thai">
                    <option value="Hiển thị">Hiển thị</option>
                    <option value="Ẩn">Ẩn</option>
                </select>

                <button type="submit" name="add_banner">Thêm Banner</button>
            </form>
        </div>
    </div>
</body>

</html>

<?php
// Đóng kết nối DB sau khi hoàn thành
$conn->close();
?>