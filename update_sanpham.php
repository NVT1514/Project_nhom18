<?php
include "Database/connectdb.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $so_luong = intval($_POST['so_luong']);

    // ✅ Xác định trạng thái dựa trên số lượng
    if ($so_luong > 10) {
        $trang_thai = "Còn hàng";
    } elseif ($so_luong > 0 && $so_luong <= 10) {
        $trang_thai = "Sắp hết";
    } else {
        $trang_thai = "Hết hàng";
    }

    // ✅ Cập nhật cả số lượng và trạng thái
    $sql = "UPDATE san_pham SET so_luong = ?, trang_thai = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $so_luong, $trang_thai, $id);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Đã cập nhật số lượng và trạng thái ($trang_thai) thành công!"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Lỗi khi cập nhật dữ liệu!"
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        "success" => false,
        "message" => "Phương thức không hợp lệ!"
    ]);
}
