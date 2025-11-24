<?php
// update_profile_admin.php
// File này CHỈ xử lý cập nhật Họ Tên, Email, Số điện thoại và Avatar.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php"; // Đảm bảo kết nối CSDL

// Hàm chuyển hướng với lỗi
function redirectToError($errorMessage)
{
    // Chuyển hướng quay lại tab view của admin.php và mang theo thông báo lỗi
    header("Location: admin.php?tab=view&error_profile=" . urlencode($errorMessage));
    exit();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['tk'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ===========================================
    // 1. NHẬN VÀ KIỂM TRA DỮ LIỆU TỪ FORM
    // ===========================================
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Lấy avatar cũ từ session
    $avatar_url = $_SESSION['avatar'] ?? 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

    // Kiểm tra dữ liệu bắt buộc
    if (empty($ho_ten) || empty($email)) {
        redirectToError("Họ Tên và Email không được để trống.");
    }

    // Kiểm tra định dạng Email đơn giản
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectToError("Định dạng Email không hợp lệ.");
    }

    // ===========================================
    // 2. Xử lý Upload Avatar
    // ===========================================
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/avatars/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $imageFileType = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));

        // Kiểm tra loại file và kích thước
        if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            redirectToError("Chỉ chấp nhận file ảnh JPG, JPEG, PNG & GIF.");
        }
        if ($_FILES["avatar"]["size"] > 2000000) { // Max 2MB
            redirectToError("Kích thước ảnh quá lớn, tối đa 2MB.");
        }

        // Tạo tên file duy nhất: [tên_tài_khoản]_[thời_gian].[ext]
        $new_file_name = $username . '_' . time() . '.' . $imageFileType;
        $target_file = $target_dir . $new_file_name;

        // Di chuyển file
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            $avatar_url = $target_file; // Cập nhật URL mới
        } else {
            redirectToError("Lỗi khi upload ảnh đại diện, vui lòng thử lại.");
        }
    }

    // ===========================================
    // 3. Xây dựng và Thực thi SQL UPDATE
    // ===========================================
    // Lưu ý: Đã loại bỏ trường 'Mat_Khau' trong file này
    $sql_update = "UPDATE user SET Ho_Ten=?, Email=?, phone=?, avatar=? WHERE Tai_Khoan=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssss", $ho_ten, $email, $phone, $avatar_url, $username);

    if ($stmt_update->execute()) {
        // CẬP NHẬT SESSION sau khi lưu thành công
        $_SESSION['ho_ten'] = $ho_ten;
        $_SESSION['avatar'] = $avatar_url;

        $stmt_update->close();
        // Chuyển hướng về tab view với thông báo thành công
        header("Location: admin.php?tab=view&success_profile=1");
        exit();
    } else {
        $error_message = $conn->error;
        $stmt_update->close();
        redirectToError("Lỗi CSDL khi cập nhật: " . $error_message);
    }
} else {
    // Nếu không phải là POST request
    header("Location: admin.php?tab=view");
    exit();
}
// Đóng kết nối
// $conn->close();
