<?php
session_start();
include "Database/connectdb.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}

// Lấy thông tin người dùng đang đăng nhập
$username = $_SESSION['tk'];

// Kiểm tra người dùng trong CSDL
$sql_check = "SELECT * FROM user WHERE Tai_Khoan = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Tài khoản không tồn tại!");
}

$user = $result->fetch_assoc();

// ===========================================
// 1. NHẬN DỮ LIỆU TỪ FORM (ĐÃ THÊM HO_TEN)
// ===========================================
$ho_ten = $_POST['ho_ten'] ?? $user['Ho_Ten']; // <-- THÊM DÒNG NÀY
$email = $_POST['email'] ?? $user['Email'];
$phone = $_POST['phone'] ?? $user['phone'];
$password = $_POST['password'] ?? '';
$avatar = $user['avatar']; // Giữ ảnh cũ nếu không upload mới

// ===========================================
// 2. Xử lý Upload Avatar (Giữ nguyên)
// ===========================================
if (!empty($_FILES['avatar']['name'])) {
    $target_dir = "uploads/avatar/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Đổi logic tên file để đơn giản hơn: uniqid()
    $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . "." . $file_extension;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
        $avatar = $target_file;
    }
}

// ===========================================
// 3. Xây dựng và Thực thi SQL UPDATE (ĐÃ CẬP NHẬT HO_TEN)
// ===========================================
if (!empty($password)) {
    // Lưu ý: Trong thực tế, bạn PHẢI HASH mật khẩu bằng password_hash()
    $hashed_password = $password; // Thay thế bằng password_hash($password, PASSWORD_DEFAULT);

    // Thêm Ho_Ten và Mat_Khau vào câu lệnh SQL
    $sql_update = "UPDATE user SET Ho_Ten=?, Email=?, phone=?, Mat_Khau=?, avatar=? WHERE Tai_Khoan=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssssss", $ho_ten, $email, $phone, $hashed_password, $avatar, $username);
} else {
    // Chỉ cập nhật Ho_Ten, Email, phone, avatar
    $sql_update = "UPDATE user SET Ho_Ten=?, Email=?, phone=?, avatar=? WHERE Tai_Khoan=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssss", $ho_ten, $email, $phone, $avatar, $username);
}

if ($stmt_update->execute()) {
    // 4. CẬP NHẬT SESSION (QUAN TRỌNG ĐỂ CẬP NHẬT SIDEBAR TỨC THÌ)
    $_SESSION['ho_ten'] = $ho_ten; // <-- CẬP NHẬT HO_TEN TRONG SESSION
    $_SESSION['avatar'] = $avatar;

    header("Location: admin.php?success=1");
    exit();
} else {
    echo "Lỗi cập nhật: " . $conn->error;
}

$conn->close();
