<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('user_session');
    session_start();
}
include "Database/connectdb.php";


// ✅ Lấy danh sách đơn hàng của người dùng (bảng orders)
$sql = "SELECT * FROM don_hang WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Lỗi prepare SQL: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
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

        <?php if ($orders->num_rows > 0): ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <p><strong>Mã đơn hàng:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
                        <p><strong>Ngày đặt:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                        <p><strong>Phương thức thanh toán:</strong> <?= strtoupper(htmlspecialchars($order['payment_method'])) ?></p>
                        <p>
                            <strong>Trạng thái:</strong>
                            <?php
                            $statusText = '';
                            switch ($order['status']) {
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
                    $sql_items = "
                SELECT c.*, s.hinh_anh 
                FROM chi_tiet_don_hang c
                JOIN san_pham s ON c.product_id = s.id
                WHERE c.order_id = ?
            ";
                    $stmt_items = $conn->prepare($sql_items);
                    if (!$stmt_items) {
                        die("Lỗi prepare SQL (chi_tiet_don_hang): " . $conn->error);
                    }
                    $stmt_items->bind_param("i", $order_id);
                    $stmt_items->execute();
                    $items = $stmt_items->get_result();
                    ?>

                    <hr>

                    <table>
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
                            <?php while ($item = $items->fetch_assoc()): ?>
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