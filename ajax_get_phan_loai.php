<?php
include "Database/connectdb.php";

if (isset($_GET['mode']) && $_GET['mode'] === 'loai' && isset($_GET['loai_chinh'])) {
    $loai_chinh = $_GET['loai_chinh'];

    $sql = "SELECT ten_phan_loai 
            FROM phan_loai_san_pham 
            WHERE loai_chinh = ? AND trang_thai = 'Đang sử dụng'
            ORDER BY ten_phan_loai ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $loai_chinh);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<option value="">-- Chọn loại sản phẩm --</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['ten_phan_loai']) . '">' . htmlspecialchars($row['ten_phan_loai']) . '</option>';
        }
    } else {
        echo '<option value="">Không có loại nào phù hợp</option>';
    }
    exit;
}

echo '<option value="">Dữ liệu không hợp lệ</option>';
