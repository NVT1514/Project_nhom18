<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php"; // Giả định file kết nối DB của bạn

// 1. Lấy Order ID từ URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Kiểm tra nếu không có ID hợp lệ
if ($order_id === 0) {
    die("Lỗi: Không tìm thấy Mã đơn hàng.");
}

// 2. Truy vấn thông tin đơn hàng chính
$order_detail = null;
$items = [];

// Truy vấn thông tin đơn hàng
$sql_order = "SELECT order_id, fullname, address, phone, total, payment_method, created_at, status 
              FROM don_hang 
              WHERE id = ?";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();

if ($result_order->num_rows > 0) {
    $order_detail = $result_order->fetch_assoc();
} else {
    // Nếu không tìm thấy đơn hàng, dừng và báo lỗi
    die("Lỗi: Không tìm thấy đơn hàng với ID: " . $order_id);
}
$stmt_order->close();


// 3. Truy vấn chi tiết các sản phẩm trong đơn hàng (Giả định có bảng 'chi_tiet_don_hang')
$sql_items = "SELECT ctdh.product_name, ctdh.price, ctdh.quantity, (ctdh.price * ctdh.quantity) as subtotal
              FROM chi_tiet_don_hang ctdh
              WHERE ctdh.order_id = ?"; // Giả định 'order_id' trong bảng chi tiết là ID của đơn hàng chính
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

if ($result_items->num_rows > 0) {
    while ($row = $result_items->fetch_assoc()) {
        $items[] = $row;
    }
}
$stmt_items->close();

// Hàm hiển thị trạng thái
function get_status_display($status)
{
    if ($status == 1) return ['text' => 'Chờ giao hàng', 'class' => 'shipping'];
    if ($status == 2) return ['text' => 'Giao hàng thành công', 'class' => 'done'];
    return ['text' => 'Chờ xác nhận', 'class' => 'pending'];
}

$status_info = get_status_display($order_detail['status']);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi Tiết Đơn Hàng #<?= htmlspecialchars($order_detail['order_id']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f6f8fa;
            margin: 0;
            display: flex;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .detail-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .section-heading {
            color: #007bff;
            margin-top: 25px;
            margin-bottom: 15px;
            font-size: 1.3em;
            font-weight: 600;
        }

        /* Thông tin chung */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            /* Chia 2 cột */
            gap: 15px 30px;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            flex-shrink: 0;
            margin-right: 15px;
        }

        .info-value {
            color: #333;
            text-align: right;
            font-weight: 400;
        }

        /* Bảng sản phẩm */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .table tr:nth-child(even) {
            background-color: #fefefe;
        }

        .table td:nth-child(3),
        .table td:nth-child(4) {
            text-align: right;
            /* Căn phải cho số tiền/số lượng */
        }

        /* Tổng tiền cuối cùng */
        .total-summary {
            text-align: right;
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: 700;
            color: #333;
            padding-top: 15px;
            border-top: 2px solid #007bff;
        }

        .total-summary span {
            color: #dc3545;
            margin-left: 20px;
        }

        .status {
            padding: 6px 12px;
            border-radius: 6px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
        }

        .status.pending {
            background-color: #ff9800;
            /* Chờ xác nhận */
        }

        .status.shipping {
            background-color: #03a9f4;
            /* Chờ giao hàng */
        }

        .status.done {
            background-color: #4caf50;
            /* Giao hàng thành công */
        }
    </style>
</head>

<body>
    <?php include "sidebar_admin.php"; ?>

    <div class="main-content">
        <div class="detail-container">
            <h1><i class="fa-solid fa-file-invoice"></i> Chi Tiết Đơn Hàng #<?= htmlspecialchars($order_detail['order_id']) ?></h1>

            <div class="section-heading"><i class="fa-solid fa-circle-info"></i> Thông tin chung</div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Mã đơn:</span>
                    <span class="info-value">#<?= htmlspecialchars($order_detail['order_id']) ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Ngày đặt:</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($order_detail['created_at'])) ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Tên khách hàng:</span>
                    <span class="info-value"><?= htmlspecialchars($order_detail['fullname']) ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Số điện thoại:</span>
                    <span class="info-value"><?= htmlspecialchars($order_detail['phone']) ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Địa chỉ:</span>
                    <span class="info-value" style="max-width: 60%;"><?= htmlspecialchars($order_detail['address']) ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Phương thức thanh toán:</span>
                    <span class="info-value"><?= strtoupper(htmlspecialchars($order_detail['payment_method'])) ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Trạng thái đơn hàng:</span>
                    <span class="status <?= $status_info['class'] ?>"><?= $status_info['text'] ?></span>
                </div>
            </div>

            <div class="section-heading"><i class="fa-solid fa-box"></i> Chi tiết sản phẩm</div>

            <?php if (!empty($items)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th style="width: 15%;">Số lượng</th>
                            <th style="width: 20%; text-align: right;">Đơn giá</th>
                            <th style="width: 20%; text-align: right;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><?= number_format($item['quantity']) ?></td>
                                <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                                <td><?= number_format($item['subtotal'], 0, ',', '.') ?>đ</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="total-summary">
                    Tổng tiền đơn hàng: <span><?= number_format($order_detail['total'], 0, ',', '.') ?>đ</span>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #777;">Không có sản phẩm nào trong đơn hàng này.</p>
            <?php endif; ?>

            <p style="text-align: center; margin-top: 30px;">
                <a href="quan_ly_don_hang.php" class="btn" style="background: #6c757d; text-decoration: none; padding: 10px 20px; border-radius: 8px; color: white;">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại
                </a>
            </p>
        </div>
    </div>

</body>

</html>