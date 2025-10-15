<?php
include "Database/connectdb.php"; // Kết nối CSDL

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Lấy thông tin sản phẩm để xóa ảnh (nếu có)
    $sql_select = "SELECT hinh_anh FROM san_pham WHERE id = $id";
    $result = mysqli_query($conn, $sql_select);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $hinh_anh = $row['hinh_anh'];

        // Nếu ảnh nằm trong thư mục uploads thì xóa file
        if (!empty($hinh_anh) && file_exists("../uploads/" . $hinh_anh)) {
            unlink("../uploads/" . $hinh_anh);
        }

        // Xóa sản phẩm khỏi DB
        $sql_delete = "DELETE FROM san_pham WHERE id = $id";
        if (mysqli_query($conn, $sql_delete)) {
            header("Location: ds_sanpham.php?msg=deleted");
            exit();
        } else {
            echo "Lỗi khi xóa: " . mysqli_error($conn);
        }
    } else {
        echo "Sản phẩm không tồn tại!";
    }
} else {
    echo "Thiếu ID sản phẩm!";
}
