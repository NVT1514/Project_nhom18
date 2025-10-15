<?php
include "Database/connectdb.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Kiểm tra có tồn tại tài khoản admin này không
    $check = $conn->prepare("SELECT * FROM nguoi_dung WHERE id = ? AND vai_tro = 'admin'");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Xóa tài khoản admin
        $delete = $conn->prepare("DELETE FROM nguoi_dung WHERE id = ?");
        $delete->bind_param("i", $id);
        if ($delete->execute()) {
            echo json_encode(["success" => true, "message" => "Xóa tài khoản admin thành công!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Không thể xóa tài khoản admin."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Không tìm thấy tài khoản admin này."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Yêu cầu không hợp lệ."]);
}
