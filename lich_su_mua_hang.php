<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Đảm bảo đường dẫn này là chính xác
include "Database/connectdb.php";

// ================== KIỂM TRA ĐĂNG NHẬP VÀ QUYỀN (Tăng cường bảo vệ) ==================

// 🛑 SỬA LỖI 1: KHỞI TẠO VÀ ÉP KIỂU $user_id MỘT CÁCH NGHIÊM NGẶT
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$user_role = $_SESSION['role'] ?? '';

// Nếu user_id không hợp lệ (bằng 0 sau khi ép kiểu) HOẶC không phải là user
if ($user_id === 0 || $user_role !== 'user') {
    // Nếu có lỗi, HỦY Session và chuyển hướng đến trang đăng nhập
    session_unset();
    session_destroy();
    echo '<script>alert("Bạn không có quyền truy cập trang này!"); window.location.href = "login.php";</script>';
    exit(); // Dừng thực thi code ngay lập tức
}

// Lọc an toàn cho user_id (Bắt buộc dùng mysqli_real_escape_string khi chèn vào string SQL)
// (Dù đã là số nguyên nhưng vẫn làm để đảm bảo an toàn tối đa cho câu lệnh)
$safe_user_id = mysqli_real_escape_string($conn, $user_id);

// ================== TRUY VẤN ĐƠN HÀNG CỦA USER ==================

// 🛑 SỬA LỖI 2: Đảm bảo CÚ PHÁP TRUY VẤN CHỈ THỰC HIỆN KHI CÓ USER ID HỢP LỆ
$sql = "SELECT * FROM don_hang 
        WHERE user_id = '$safe_user_id' 
        ORDER BY created_at DESC";

$orders = mysqli_query($conn, $sql);

if (!$orders) {
    die("Lỗi truy vấn đơn hàng: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Lịch sử mua hàng</title>
    <link rel="stylesheet" href="../css/lich_su_mua_hang.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f6f8fa;
            margin: 0;
            display: flex;
        }

        /* Container chính */
        .order-history-container {
            flex: 1;
            padding: 30px;
        }

        .order-history-container h2 {
            margin-bottom: 20px;
            color: #333;
        }

        /* Card đơn hàng */
        .order-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 15px 25px;
            margin-bottom: 25px;
            transition: all 0.2s ease-in-out;
        }

        .order-card:hover {
            transform: translateY(-3px);
        }

        /* Header đơn hàng */
        .order-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .order-header p {
            margin: 5px 0;
        }

        .status {
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Màu theo trạng thái */
        .status.đã {
            color: #28a745;
        }

        .status.chờ {
            color: #ffc107;
        }

        .status.hủy {
            color: #dc3545;
        }

        /* Bảng sản phẩm */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .product-table th,
        .product-table td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .product-table th {
            background: #fafafa;
            font-weight: bold;
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .no-order {
            text-align: center;
            color: #777;
            font-size: 1.1em;
            margin-top: 30px;
        }

        /* --- Bảng chi tiết đơn hàng --- */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fafafa;
            border-radius: 10px;
            overflow: hidden;
            font-size: 15px;
        }

        .product-table thead {
            background: #f0f2f5;
        }

        .product-table th,
        .product-table td {
            text-align: center;
            padding: 12px 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .product-table th {
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            font-size: 14px;
        }

        .product-table tbody tr:hover {
            background: #f9f9f9;
        }

        /* --- Ảnh sản phẩm --- */
        .product-img {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        /* --- Tổng tiền --- */
        .total {
            text-align: right;
            font-size: 17px;
            font-weight: 700;
            color: #d63031;
            margin-top: 15px;
        }

        /* --- Khoảng cách giữa đơn hàng --- */
        .order-card+.order-card {
            margin-top: 25px;
        }

        /* --- Responsive --- */
        @media (max-width: 768px) {

            .product-table th,
            .product-table td {
                font-size: 13px;
                padding: 8px;
            }

            .product-img {
                width: 50px;
                height: 50px;
            }

            .total {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <?php include "sidebar_user.php"; ?>
    <div class="order-history-container">
        <?php
        $breadcrumb_title = "Lịch sử mua hàng";
        $breadcrumb_items = [
            ["label" => "Trang chủ", "link" => "maincustomer.php"],
            ["label" => $breadcrumb_title]
        ];
        include "breadcrumb.php";
        ?>
        <h2><i class="fa fa-history"></i> Lịch sử mua hàng</h2>

        <?php if (mysqli_num_rows($orders) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                <div class="order-card">
                    <div class="order-header">
                        <p><strong>Mã đơn hàng:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
                        <p><strong>Ngày đặt:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                        <p><strong>Phương thức thanh toán:</strong> <?= strtoupper(htmlspecialchars($order['payment_method'])) ?>
                        </p>
                        <p>
                            <strong>Trạng thái:</strong>
                            <?php
                            $statusText = '';
                            $class = '';
                            // 0: Chờ thanh toán (VNPAY/QR), 1: Chờ xác nhận (COD), 2: Hoàn thành
                            switch ($order['status']) {
                                case 0:
                                    $statusText = "Chờ thanh toán";
                                    $class = "chờ";
                                    break;
                                case 1:
                                    $statusText = "Chờ xác nhận";
                                    $class = "chờ";
                                    break;
                                case 2:
                                    $statusText = "Hoàn thành";
                                    $class = "đã";
                                    break;
                                default:
                                    $statusText = "Không xác định";
                                    $class = "";
                                    break;
                            }
                            ?>
                            <span class="status <?= $class ?>"><?= $statusText ?></span>
                        </p>
                    </div>

                    <p><strong>Người nhận:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
                    <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                    <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address']) ?></p>

                    <?php
                    // --- Lấy chi tiết sản phẩm + ảnh từ bảng san_pham ---
                    $order_id = intval($order['id']);

                    // Lọc an toàn cho order_id
                    $safe_order_id = mysqli_real_escape_string($conn, $order_id);
                    $sql_items = "
                        SELECT c.*, s.hinh_anh 
                        FROM chi_tiet_don_hang c
                        JOIN san_pham s ON c.product_id = s.id
                        WHERE c.order_id = '$safe_order_id'
                    ";

                    $items = mysqli_query($conn, $sql_items);

                    if (!$items) {
                        die("Lỗi truy vấn chi tiết đơn hàng: " . mysqli_error($conn));
                    }
                    ?>

                    <hr>

                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Ảnh</th>
                                <th>Sản phẩm</th>
                                <th>Kích cỡ</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = mysqli_fetch_assoc($items)): ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($item['hinh_anh'] ?: 'https://cdn-icons-png.flaticon.com/512/679/679720.png') ?>"
                                            alt="Ảnh sản phẩm" class="product-img">
                                    </td>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= htmlspecialchars($item['size']) ?></td>
                                    <td><?= number_format($item['price'], 0, ',', '.') ?> ₫</td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> ₫</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <div class="total">Tổng tiền: <?= number_format($order['total'], 0, ',', '.') ?> ₫</div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-order">Bạn chưa có đơn hàng nào.</p>
        <?php endif; ?>
    </div>

</body>

</html>