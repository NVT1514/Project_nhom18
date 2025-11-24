<?php
include "Database/connectdb.php";

header('Content-Type: application/json');

// Bắt đầu session và kiểm tra đăng nhập (quan trọng cho bảo mật)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['tk'])) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: Không được phép. Vui lòng đăng nhập lại.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy danh sách ID từ POST
    $ids_string = isset($_POST['ids']) ? $_POST['ids'] : '';

    if (empty($ids_string)) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: Không có sản phẩm nào được chọn.']);
        exit();
    }

    // Tách chuỗi ID thành mảng và đảm bảo chúng là số nguyên
    $ids_array = array_map('intval', explode(',', $ids_string));

    // Loại bỏ các giá trị không hợp lệ (ví dụ: 0)
    $valid_ids = array_filter($ids_array, function ($id) {
        return $id > 0;
    });

    if (empty($valid_ids)) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ID sản phẩm không hợp lệ.']);
        exit();
    }

    // Chuyển mảng ID thành chuỗi an toàn cho mệnh đề IN
    $ids_list = implode(',', $valid_ids);

    // Xây dựng câu truy vấn DELETE
    $sql = "DELETE FROM san_pham WHERE id IN ($ids_list)";

    if (mysqli_query($conn, $sql)) {
        $rows_affected = mysqli_affected_rows($conn);
        echo json_encode(['success' => true, 'message' => "Đã xóa thành công $rows_affected sản phẩm.", 'deleted_ids' => $valid_ids]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi SQL: ' . mysqli_error($conn)]);
    }

    mysqli_close($conn);
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
}

exit();
