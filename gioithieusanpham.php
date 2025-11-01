<?php
include "Database/connectdb.php";

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID sản phẩm']);
    exit;
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM san_pham WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy sản phẩm']);
    exit;
}

$sp = $result->fetch_assoc();

echo json_encode([
    'status' => 'success',
    'data' => [
        'ten_san_pham' => $sp['ten_san_pham'],
        'hinh_anh' => 'uploads/' . $sp['hinh_anh'],
        'gia' => number_format($sp['gia'], 0, ',', '.') . 'đ',
        'mo_ta' => $sp['mo_ta'] ?? 'Chưa có mô tả chi tiết cho sản phẩm này.'
    ]
]);
