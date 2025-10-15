<?php
header('Content-Type: application/json');
include "Database/connectdb.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $new_role = strtolower(trim($_POST['new_role'] ?? ''));

    if ($identifier === '' || $new_role === '') {
        echo json_encode(["success" => false, "message" => "Vui lòng nhập đủ thông tin."]);
        exit;
    }

    // Tìm user theo ID, Email hoặc Tên đăng nhập
    $sql = "SELECT * FROM user WHERE id = ? OR Email = ? OR Tai_Khoan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $identifier, $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(["success" => false, "message" => "Không tìm thấy tài khoản phù hợp."]);
        exit;
    }

    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // Cập nhật quyền hạn mới
    $update = $conn->prepare("UPDATE user SET role=? WHERE id=?");
    $update->bind_param("si", $new_role, $user_id);

    if ($update->execute()) {
        echo json_encode(["success" => true, "message" => "✅ Cập quyền thành công cho tài khoản <b>" . $user['Tai_Khoan'] . "</b>."]);
    } else {
        echo json_encode(["success" => false, "message" => "❌ Lỗi khi cập quyền."]);
    }
}
