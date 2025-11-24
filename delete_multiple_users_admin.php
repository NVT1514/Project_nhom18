<?php
session_start();
// Đảm bảo đường dẫn file kết nối CSDL là chính xác
include "Database/connectdb.php";

// Kiểm tra quyền hạn
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'superadmin' && $_SESSION['role'] !== 'admin')) {
    // Tùy chỉnh URL chuyển hướng nếu người dùng không có quyền
    header('Location: index.php');
    exit();
}

// Kiểm tra nếu có dữ liệu POST (danh sách user_ids[])
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_ids']) && is_array($_POST['user_ids'])) {

    $user_ids = $_POST['user_ids'];
    $count = count($user_ids);

    if ($count > 0) {

        // 1. Tạo chuỗi placeholders (?, ?, ...) tương ứng với số lượng ID
        $placeholders = implode(',', array_fill(0, $count, '?'));

        // 2. Chuẩn bị câu SQL DELETE
        $sql = "DELETE FROM user WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);

        // 3. Gắn tham số (tất cả đều là kiểu integer 'i')
        $types = str_repeat('i', $count);

        // Sử dụng spread operator (...) để truyền mảng ID vào bind_param
        $stmt->bind_param($types, ...$user_ids);

        // 4. Thực thi
        if ($stmt->execute()) {
            // Thiết lập thông báo thành công (tùy chọn)
            $_SESSION['success_message'] = "Đã xóa thành công {$count} người dùng.";
        } else {
            $_SESSION['error_message'] = "Lỗi khi xóa người dùng: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Không có người dùng nào được chọn để xóa.";
    }
} else {
    $_SESSION['error_message'] = "Yêu cầu xóa không hợp lệ.";
}

$conn->close();

// Chuyển hướng người dùng trở lại trang quản lý
header("Location: quanlinguoidung_admin.php");
exit();
