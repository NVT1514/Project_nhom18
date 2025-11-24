<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Đảm bảo đường dẫn này đúng với cấu trúc thư mục của bạn
include "Database/connectdb.php";

if (isset($_GET['id'])) {
    $user_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    if ($user_id > 0) {
        // Chuẩn bị câu lệnh SQL: chỉ xóa tài khoản có role là 'user'
        $sql_delete = "DELETE FROM user WHERE id = ? AND role = 'user'";

        $stmt_delete = $conn->prepare($sql_delete);

        if (!$stmt_delete) {
            $_SESSION['message'] = "Lỗi prepare SQL: " . $conn->error;
            $_SESSION['msg_type'] = "danger";
            // Không thoát ngay, tiếp tục đóng kết nối
        } else {
            $stmt_delete->bind_param("i", $user_id);

            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    $_SESSION['message'] = "Đã xóa tài khoản người dùng ID **" . $user_id . "** thành công!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['message'] = "Không tìm thấy người dùng có ID **" . $user_id . "** hoặc người dùng đó không phải là khách hàng (role='user').";
                    $_SESSION['msg_type'] = "warning";
                }
            } else {
                $_SESSION['message'] = "Lỗi khi thực thi xóa: " . $stmt_delete->error;
                $_SESSION['msg_type'] = "danger";
            }

            $stmt_delete->close();
        }
    } else {
        $_SESSION['message'] = "ID người dùng không hợp lệ.";
        $_SESSION['msg_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Không có ID người dùng được cung cấp.";
    $_SESSION['msg_type'] = "warning";
}

$conn->close();

// Chuyển hướng về trang quản lý người dùng
header("Location: quanliuser.php");
exit();
