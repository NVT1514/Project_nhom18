<?php
include "Database/connectdb.php";

$product_id = intval($_GET['product_id']);

// Lấy đầy đủ thông tin lịch sử, bao gồm giá mới nếu có
$query = "
    SELECT 
        hanh_dong, 
        so_luong, 
        nha_cung_cap, 
        tong_tien, 
        gia_moi,
        ngay_thuc_hien
    FROM lich_su_kho 
    WHERE product_id = $product_id 
    ORDER BY ngay_thuc_hien DESC
";

$result = mysqli_query($conn, $query);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Định dạng ngày tháng
    $row['ngay_thuc_hien'] = date("d/m/Y H:i", strtotime($row['ngay_thuc_hien']));

    // Đảm bảo giá trị số không bị null hoặc rỗng
    $tong_tien = isset($row['tong_tien']) && $row['tong_tien'] !== null ? floatval($row['tong_tien']) : 0;
    $gia_moi   = isset($row['gia_moi']) && $row['gia_moi'] !== null ? floatval($row['gia_moi']) : 0;

    // Định dạng hiển thị: chỉ hiện "đ" nếu có giá trị > 0
    $row['tong_tien'] = $tong_tien > 0 ? number_format($tong_tien, 0, ',', '.') . " đ" : '';
    $row['gia_moi']   = $gia_moi > 0 ? number_format($gia_moi, 0, ',', '.') . " đ" : '';

    // Thêm vào danh sách
    $data[] = [
        'ngay_thuc_hien' => $row['ngay_thuc_hien'],
        'hanh_dong'      => $row['hanh_dong'],
        'so_luong'       => $row['so_luong'],
        'nha_cung_cap'   => $row['nha_cung_cap'] ?: '',
        'tong_tien'      => $row['tong_tien'],
        'gia_moi'        => $row['gia_moi']
    ];
}

// Xuất ra JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
