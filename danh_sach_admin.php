<?php
header('Content-Type: application/json; charset=UTF-8');
include "Database/connectdb.php";

if (!$conn) {
    echo json_encode(["success" => false, "message" => "Kết nối CSDL thất bại"]);
    exit();
}

$sql = "SELECT id, Tai_Khoan, Ho_Ten, Email, role, Mat_Khau FROM user WHERE role = 'admin'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $admins = [];

    while ($row = $result->fetch_assoc()) {
        $admins[] = [
            "id" => (int)$row['id'],
            "Tai_Khoan" => htmlspecialchars($row['Tai_Khoan']),
            "Ho_Ten" => htmlspecialchars($row['Ho_Ten']),
            "Email" => htmlspecialchars($row['Email']),
            "role" => htmlspecialchars($row['role']),
            // Ẩn phần lớn mật khẩu khi trả về (chỉ hiển thị 3 ký tự đầu)
            "Mat_Khau" => substr($row['Mat_Khau'], 0, 3) . str_repeat('*', max(strlen($row['Mat_Khau']) - 3, 0))
        ];
    }

    echo json_encode(["success" => true, "admins" => $admins], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["success" => false, "message" => "Không có admin nào được tìm thấy"], JSON_UNESCAPED_UNICODE);
}

$conn->close();
