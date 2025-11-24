<?php
session_start();
// Đảm bảo đường dẫn này là chính xác
include "Database/connectdb.php";

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Lỗi không xác định.', 'new_qty' => 0];

// Kiểm tra phiên đăng nhập
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Vui lòng đăng nhập.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

// Xử lý yêu cầu AJAX cập nhật số lượng
if (isset($_POST['ajax_update_qty']) && isset($_POST['cart_id']) && isset($_POST['qty'])) {

    $cart_id = intval($_POST['cart_id']);
    // Đảm bảo số lượng yêu cầu tối thiểu là 1
    $requested_qty = max(1, intval($_POST['qty']));

    // 1. Truy vấn để lấy product_id và tồn kho tối đa (max_stock)
    $sql_check_stock = "
        SELECT 
            sp.so_luong AS max_stock 
        FROM gio_hang gh
        JOIN san_pham sp ON gh.san_pham_id = sp.id
        WHERE gh.id = ? AND gh.user_id = ?
        LIMIT 1
    ";

    // Sử dụng Prepared Statements để bảo mật SQL Injection
    $stmt = mysqli_prepare($conn, $sql_check_stock);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    mysqli_stmt_execute($stmt);
    $stock_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($stock_result) > 0) {
        $stock_row = mysqli_fetch_assoc($stock_result);
        $max_stock = intval($stock_row['max_stock']);

        $final_qty = $requested_qty;
        $message = 'Cập nhật thành công.';

        // 2. Kiểm tra và giới hạn số lượng theo tồn kho
        if ($requested_qty > $max_stock) {
            $final_qty = $max_stock;

            // Nếu tồn kho bằng 0, thông báo hết hàng nhưng vẫn giữ số lượng 1 để khách có thể xóa
            if ($max_stock === 0) {
                $final_qty = 1;
                $message = 'Sản phẩm đã hết hàng. Vui lòng xóa khỏi giỏ hàng.';
            } else {
                $message = 'Số lượng đã được giới hạn về tồn kho tối đa: ' . $max_stock . '.';
            }
        }

        // 3. Cập nhật số lượng (đã được giới hạn) vào bảng gio_hang
        $sql_update = "UPDATE gio_hang SET so_luong = ? WHERE id = ? AND user_id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "iii", $final_qty, $cart_id, $user_id);

        if (mysqli_stmt_execute($stmt_update)) {
            $response['success'] = true;
            $response['message'] = $message;
            $response['new_qty'] = $final_qty; // Trả về số lượng thực tế đã được lưu vào DB
        } else {
            $response['message'] = 'Lỗi cập nhật CSDL: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt_update);
    } else {
        $response['message'] = 'Sản phẩm không tồn tại trong giỏ hàng hoặc không thuộc về bạn.';
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Yêu cầu không hợp lệ.';
}

echo json_encode($response);
mysqli_close($conn);
