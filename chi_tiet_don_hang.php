<?php
session_start();
include "Database/connectdb.php";


// ===== Kiểm tra đăng nhập =====
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập để xem chi tiết đơn hàng!'); window.location.href='../login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// ===== Nhận ID đơn hàng =====
$order_id = intval($_GET['id'] ?? 0);
if ($order_id <= 0) {
    echo "<script>alert('Mã đơn hàng không hợp lệ!'); window.history.back();</script>";
    exit();
}

// ===== Lấy thông tin đơn hàng =====
$sql_order = "SELECT * FROM don_hang WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql_order);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.history.back();</script>";
    exit();
}

// ===== Lấy danh sách sản phẩm trong đơn =====
$sql_items = "SELECT * FROM chi_tiet_don_hang WHERE order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result();

function status_text($status)
{
    return match ($status) {
        0 => "<span style='color:#e67e22;'>Chưa thanh toán</span>",
        1 => "<span style='color:#2980b9;'>Chờ giao hàng</span>",
        2 => "<span style='color:#27ae60;'>Đã giao thành công</span>",
        default => "Không xác định"
    };
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f3f6fa;
            margin: 0;
            display: flex;
        }

        /* Container chính */
        .container {
            flex: 1;
            padding: 40px 60px;
            width: calc(100% - 240px);
            background: #fff;
            border-radius: 16px;
            margin: 30px auto;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 26px;
            letter-spacing: 0.3px;
        }

        /* Thông tin đơn hàng */
        .order-info {
            border: 1px solid #e1e4eb;
            border-radius: 10px;
            background: #f9fbff;
            padding: 20px 25px;
            margin-bottom: 30px;
        }

        .order-info p {
            margin: 8px 0;
            color: #444;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Bảng đơn hàng */
        .order-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 25px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .order-table thead th {
            background: linear-gradient(135deg, #00aaff, #007bff);
            color: #fff;
            font-weight: 600;
            text-align: center;
            padding: 14px;
            font-size: 15px;
            white-space: nowrap;
        }

        .order-table tbody tr {
            background: #fff;
            transition: 0.25s;
        }

        .order-table tbody tr:nth-child(even) {
            background: #f8fbff;
        }

        .order-table tbody tr:hover {
            background: #eaf4ff;
        }

        .order-table td {
            padding: 14px 10px;
            text-align: center;
            font-size: 15px;
            color: #333;
            border-bottom: 1px solid #eaeaea;
            vertical-align: middle;
        }

        .order-table img {
            width: 90px;
            height: 90px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #eee;
            transition: 0.2s;
            background: #fafafa;
        }

        .order-table img:hover {
            transform: scale(1.05);
        }

        /* Tổng tiền */
        .total {
            text-align: right;
            font-size: 20px;
            font-weight: 700;
            color: #27ae60;
            margin-top: 20px;
            padding-right: 10px;
        }

        /* Nút quay lại */
        .back-btn {
            display: inline-block;
            background: linear-gradient(135deg, #00aaff, #007bff);
            color: white;
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            transition: 0.3s;
        }

        .back-btn:hover {
            background: linear-gradient(135deg, #0088cc, #0056b3);
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .container {
                padding: 20px;
                width: 100%;
            }

            .order-table thead {
                display: none;
            }

            .order-table,
            .order-table tbody,
            .order-table tr,
            .order-table td {
                display: block;
                width: 100%;
            }

            .order-table tr {
                margin-bottom: 20px;
                border: 1px solid #eee;
                border-radius: 8px;
                padding: 10px;
                background: #fff;
            }

            .order-table td {
                text-align: right;
                position: relative;
                padding-left: 50%;
            }

            .order-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 45%;
                text-align: left;
                font-weight: 600;
                color: #555;
            }
        }
    </style>


</head>

<body>

    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="container">
        <h1>🧾 Chi tiết đơn hàng</h1>

        <div class="order-info">
            <p><strong>Mã đơn hàng:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
            <p><strong>Người nhận:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
            <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['phone']) ?></p>
            <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address']) ?></p>
            <p><strong>Phương thức thanh toán:</strong> <?= strtoupper($order['payment_method']) ?></p>
            <p><strong>Trạng thái:</strong> <?= status_text($order['status']) ?></p>
            <p><strong>Ngày đặt:</strong> <?= date("d/m/Y H:i", strtotime($order['created_at'])) ?></p>
        </div>

        <table class="order-table">
            <thead>
                <tr>
                    <th>Hình ảnh</th>
                    <th>Sản phẩm</th>
                    <th>Size</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th>Tổng</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetch_assoc()):
                    $product_sql = mysqli_query($conn, "SELECT hinh_anh FROM san_pham WHERE id = {$item['product_id']}");
                    $product_img = mysqli_fetch_assoc($product_sql)['hinh_anh'] ?? 'no-image.png';
                ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($product_img) ?>" alt=""></td>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= htmlspecialchars($item['size']) ?></td>
                        <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <p class="total">Tổng cộng: <?= number_format($order['total'], 0, ',', '.') ?>đ</p>

        <div style="text-align:center; margin-top:20px;">
            <a href="trang_thai_don_hang.php" class="back-btn"><i class="fa fa-arrow-left"></i> Quay lại danh sách</a>
        </div>
    </div>


</body>

</html>