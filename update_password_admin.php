<?php
// update_password_admin.php
// File này xử lý việc thay đổi mật khẩu cho Admin.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Đảm bảo file connectdb.php tồn tại và đã thiết lập biến $conn
include "Database/connectdb.php";

/**
 * Hàm chuyển hướng quay lại trang admin.php (tab password) kèm thông báo lỗi.
 * @param string $errorMessage Nội dung thông báo lỗi.
 */
function redirectToPasswordError($errorMessage)
{
    // Chuyển hướng quay lại tab 'password' với thông báo lỗi
    header("Location: admin.php?tab=password&error_password=" . urlencode($errorMessage));
    exit();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_SESSION['tk'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ===========================================
    // 1. KIỂM TRA DỮ LIỆU ĐẦU VÀO
    // ===========================================
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        redirectToPasswordError("Vui lòng điền đầy đủ 3 trường mật khẩu.");
    }

    if ($new_password !== $confirm_password) {
        redirectToPasswordError("Mật khẩu mới và xác nhận mật khẩu không khớp.");
    }

    if (strlen($new_password) < 3) {
        // Giữ nguyên logic cũ, nhưng mật khẩu thô của bạn đang là 3 ký tự. 
        // Thay đổi giới hạn thành 3 ký tự (tối thiểu).
        redirectToPasswordError("Mật khẩu mới phải có ít nhất 3 ký tự.");
    }

    // ===========================================
    // 2. LẤY MẬT KHẨU CŨ TỪ CSDL
    // ===========================================
    $sql = "SELECT Mat_Khau FROM user WHERE Tai_Khoan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        redirectToPasswordError("Tài khoản không tồn tại trong hệ thống.");
    }
    $user = $result->fetch_assoc();
    $db_password = $user['Mat_Khau']; // Giá trị mật khẩu thô đang lưu trong CSDL
    $stmt->close();

    // ===========================================
    // 3. XÁC MINH MẬT KHẨU HIỆN TẠI VÀ KIỂM TRA TRÙNG MẬT KHẨU CŨ
    // (***CHỈ SO SÁNH CHUỖI MẬT KHẨU THÔ***)
    // ===========================================

    // So sánh chuỗi mật khẩu hiện tại người dùng nhập với mật khẩu thô trong CSDL
    if ($current_password !== $db_password) {
        redirectToPasswordError("Mật khẩu hiện tại không chính xác.");
    }

    // Kiểm tra mật khẩu mới có trùng với mật khẩu hiện tại không
    if ($new_password === $db_password) {
        redirectToPasswordError("Mật khẩu mới không được trùng với mật khẩu hiện tại.");
    }

    // ===========================================
    // 4. CẬP NHẬT MẬT KHẨU MỚI (Vẫn là mật khẩu thô)
    // ===========================================
    // Mật khẩu mới được lưu dưới dạng thô ($new_password), KHÔNG DÙNG HASH.
    $sql_update = "UPDATE user SET Mat_Khau = ? WHERE Tai_Khoan = ?";
    $stmt_update = $conn->prepare($sql_update);
    // Lưu mật khẩu thô vào CSDL
    $stmt_update->bind_param("ss", $new_password, $username);

    if ($stmt_update->execute()) {
        $stmt_update->close();
        // Chuyển hướng về tab password với thông báo thành công
        header("Location: admin.php?tab=password&success_password=1");
        exit();
    } else {
        $stmt_update->close();
        redirectToPasswordError("Lỗi CSDL khi cập nhật mật khẩu: " . $conn->error);
    }
} else {
    // Nếu không phải là POST request
    header("Location: admin.php?tab=password");
    exit();
}

$conn->close();
