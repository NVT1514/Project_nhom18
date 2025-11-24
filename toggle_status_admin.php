<?php
session_start();

// Kiểm tra quyền superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: quanliadmin.php");
    exit();
}

include "Database/connectdb.php";

if (isset($_GET['id']) && isset($_GET['action'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];

    // Kiểm tra không được tự thay đổi trạng thái của chính mình
    if ($user_id == $_SESSION['user_id']) {
        echo "<script>
            alert('Bạn không thể thay đổi trạng thái của chính mình!');
            window.location.href = 'quanliadmin.php';
        </script>";
        exit();
    }

    // Kiểm tra không được thay đổi trạng thái superadmin
    $check_sql = "SELECT role FROM user WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $user['role'] === 'superadmin') {
        echo "<script>
            alert('Không thể thay đổi trạng thái của tài khoản Superadmin!');
            window.location.href = 'quanliadmin.php';
        </script>";
        exit();
    }

    // Thay đổi trạng thái
    if ($action === 'activate') {
        $new_status = 1;
        $message = 'Kích hoạt tài khoản thành công!';
    } else {
        $new_status = 0;
        $message = 'Ngừng hoạt động tài khoản thành công!';
    }

    $sql = "UPDATE user SET trang_thai = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $new_status, $user_id);

    if ($stmt->execute()) {
        echo "<script>
            alert('$message');
            window.location.href = 'quanlinguoidung_admin.php';
        </script>";
    } else {
        echo "<script>
            alert('Có lỗi xảy ra. Vui lòng thử lại!');
            window.location.href = 'quanlinguoidung_admin.php';
        </script>";
    }

    $stmt->close();
} else {
    header("Location: quanlinguoidung_admin.php");
}

$conn->close();
