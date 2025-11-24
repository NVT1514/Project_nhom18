<?php
include "Database/connectdb.php";
include "Database/function.php";

// Lấy id sản phẩm từ URL
if (!isset($_GET['id'])) {
    die("Không có ID sản phẩm.");
}
$id = (int)$_GET['id'];

// Lấy thông tin sản phẩm từ DB
// LƯU Ý: Nếu sau này bạn cần cả phan_loai_id, hãy JOIN với bảng phan_loai_san_pham
$sql = "SELECT * FROM san_pham WHERE id = $id";
$result = mysqli_query($conn, $sql);
$san_pham = mysqli_fetch_assoc($result);

if (!$san_pham) {
    die("Không tìm thấy sản phẩm.");
}

// Xử lý khi người dùng cập nhật
if (isset($_POST['cap_nhat'])) {
    // 1. Lấy dữ liệu và áp dụng bảo mật
    // Đã xóa ký tự lạ xung quanh dấu =
    $ten_san_pham  = mysqli_real_escape_string($conn, $_POST['ten_san_pham']);
    $gia           = mysqli_real_escape_string($conn, $_POST['gia']);
    $mo_ta         = mysqli_real_escape_string($conn, $_POST['mo_ta']);
    $phan_loai     = mysqli_real_escape_string($conn, $_POST['phan_loai']);

    // SỬA LỖI: Đổi tên biến từ nhom_san_pham thành loai_chinh để khớp với form và DB
    $loai_chinh = mysqli_real_escape_string($conn, $_POST['loai_chinh']);

    // Nếu người dùng có upload ảnh mới
    if (!empty($_FILES['hinh_anh']['name'])) {
        $hinh_anh = $_FILES['hinh_anh']['name'];
        // Đã xóa ký tự lạ
        $target   = "../uploads/" . basename($hinh_anh);
        move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target);
    } else {
        $hinh_anh = $san_pham['hinh_anh']; // Giữ nguyên ảnh cũ
    }

    $hinh_anh_safe = mysqli_real_escape_string($conn, $hinh_anh);

    // Update DB
    // Đã xóa ký tự lạ
    $sql_update = "UPDATE san_pham 
                        SET ten_san_pham='$ten_san_pham',
                            gia='$gia',
                            mo_ta='$mo_ta',
                            hinh_anh='$hinh_anh_safe',
                            phan_loai='$phan_loai',
                            -- SỬA LỖI TẠI ĐÂY: Thay nhom_san_pham bằng loai_chinh
                            loai_chinh='$loai_chinh'
                        WHERE id=$id";

    if (mysqli_query($conn, $sql_update)) {
        echo "<script>alert('Cập nhật sản phẩm thành công!'); window.location.href='quan_ly_san_pham.php';</script>";
        exit;
    } else {
        // Nếu vẫn còn lỗi, nó sẽ hiển thị lỗi cụ thể
        echo "<div class='alert alert-danger text-center'>Lỗi: " . mysqli_error($conn) . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Sửa sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">
    <div class="container">
        <h2 class="mb-4">Sửa sản phẩm</h2>
        <form action="" method="post" enctype="multipart/form-data" class="border rounded p-4 shadow-sm bg-light">

            <div class="mb-3">
                <label for="ten_san_pham" class="form-label">Tên sản phẩm</label>
                <input type="text" class="form-control" id="ten_san_pham" name="ten_san_pham"
                    value="<?= htmlspecialchars($san_pham['ten_san_pham'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label for="gia" class="form-label">Giá</label>
                <input type="number" step="0.01" class="form-control" id="gia" name="gia"
                    value="<?= htmlspecialchars($san_pham['gia'] ?? 0) ?>" required>
            </div>

            <div class="mb-3">
                <label for="mo_ta" class="form-label">Mô tả</label>
                <textarea class="form-control" id="mo_ta" name="mo_ta" rows="3"><?= htmlspecialchars($san_pham['mo_ta'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Hình ảnh hiện tại</label><br>
                <?php if (!empty($san_pham['hinh_anh'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($san_pham['hinh_anh']) ?>" alt="" style="max-width:120px; margin-bottom:10px;">
                <?php else: ?>
                    <p>Chưa có ảnh</p>
                <?php endif; ?>
                <input type="file" class="form-control mt-2" id="hinh_anh" name="hinh_anh">
            </div>

            <div class="mb-3">
                <label for="loai_chinh" class="form-label">Nhóm sản phẩm</label>
                <select class="form-select" id="loai_chinh" name="loai_chinh" required>
                    <option value="Áo" <?= ($san_pham['loai_chinh'] ?? '') == 'Áo' ? 'selected' : '' ?>>Áo</option>
                    <option value="Quần" <?= ($san_pham['loai_chinh'] ?? '') == 'Quần' ? 'selected' : '' ?>>Quần</option>
                    <option value="Giày" <?= ($san_pham['loai_chinh'] ?? '') == 'Giày' ? 'selected' : '' ?>>Giày</option>
                    <option value="Khác" <?= ($san_pham['loai_chinh'] ?? '') == 'Khác' ? 'selected' : '' ?>>Khác</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="phan_loai" class="form-label">Loại sản phẩm (Tên danh mục con)</label>
                <input type="text" class="form-control" id="phan_loai" name="phan_loai"
                    value="<?= htmlspecialchars($san_pham['phan_loai'] ?? '') ?>" required>
            </div>

            <button type="submit" class="btn btn-success" name="cap_nhat">Lưu thay đổi</button>
            <a href="quan_ly_san_pham.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</body>

</html>