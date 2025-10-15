<?php
header('Content-Type: application/json; charset=utf-8');
include "Database/connectdb.php";

$tukhoa = isset($_POST['tukhoa']) ? trim($_POST['tukhoa']) : '';

if ($tukhoa === '') {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT 
        id, 
        ten_san_pham, 
        gia, 
        hinh_anh
    FROM san_pham
    WHERE 
        ten_san_pham LIKE CONCAT('%', ?, '%')
        OR phan_loai LIKE CONCAT('%', ?, '%')
        OR mo_ta LIKE CONCAT('%', ?, '%')
    ORDER BY ngay_tao DESC
    LIMIT 10
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $tukhoa, $tukhoa, $tukhoa);
$stmt->execute();
$result = $stmt->get_result();

$ketqua = [];
while ($row = $result->fetch_assoc()) {
    $ketqua[] = [
        "id" => $row['id'],
        "ten_san_pham" => $row['ten_san_pham'],
        "gia" => number_format($row['gia'], 0, ',', '.') . "đ",
        "hinh_anh" => $row['hinh_anh'] ?: 'Img/no_image.png'
    ];
}

echo json_encode($ketqua, JSON_UNESCAPED_UNICODE);
