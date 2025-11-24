<?php
// File: Database/log_activity.php

/**
 * Ghi lại hoạt động của Admin vào bảng nhat_ky_hoat_dong.
 * * @param mysqli $conn Kết nối cơ sở dữ liệu.
 * @param string $module Tên module (ví dụ: Quản lý sản phẩm).
 * @param string $action Chi tiết hành động (ví dụ: đã thêm sản phẩm mới: Áo thun).
 */
function log_activity($conn, $module, $action)
{
    // Lấy thông tin người dùng đang đăng nhập
    $user_id = $_SESSION['user_id'] ?? 0;
    $username = $_SESSION['tk'] ?? 'Guest';

    if ($user_id == 0 || $username == 'Guest') {
        // Không ghi log nếu không xác định được tài khoản
        return;
    }

    $sql = "INSERT INTO nhat_ky_hoat_dong (user_id, ten_tai_khoan, module, hanh_dong_chi_tiet, ngay_gio)
            VALUES (?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);

    // Gán tham số: i (integer), s (string), s (string), s (string)
    $stmt->bind_param("isss", $user_id, $username, $module, $action);

    $stmt->execute();
    $stmt->close();
}
