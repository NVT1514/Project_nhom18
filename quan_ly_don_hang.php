<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
include "Database/connectdb.php";
ob_start();

// Xử lý cập nhật trạng thái giao hàng thành công
if (isset($_POST['mark_done'])) {
    $order_id = intval($_POST['order_id']);
    $update_sql = "UPDATE don_hang SET status = 2 WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();
}

// Lấy danh sách đơn hàng
$sql = "SELECT * FROM don_hang ORDER BY created_at DESC";
$result = $conn->query($sql);


?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
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

        .order-container {
            width: 1300px;
            background: #fff;
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 14px 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #007bff;
            color: white;
        }

        .table tr:hover {
            background: #f9fafc;
        }

        .status {
            padding: 6px 12px;
            border-radius: 6px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
        }

        .status.pending {
            background-color: #ff9800;
        }

        .status.shipping {
            background-color: #03a9f4;
        }

        .status.done {
            background-color: #4caf50;
        }

        .btn {
            background: #007bff;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: #0056b3;
        }

        .no-orders {
            text-align: center;
            color: #777;
            font-size: 16px;
            padding: 20px;
        }
    </style>
</head>

<body>
    <?php include "sidebar_admin.php"; ?>

    <div class="main-content">
        <div class="order-container">
            <h1><i class="fa-solid fa-list"></i> Quản lý đơn hàng</h1>

            <?php if ($result->num_rows > 0): ?>
                <table class="table">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Tên khách hàng</th>
                        <th>Địa chỉ</th>
                        <th>Số điện thoại</th>
                        <th>Tổng tiền</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                        <th>Ngày đặt</th>
                        <th>Hành động</th>
                    </tr>

                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $status_text = '';
                        $status_class = '';

                        if ($row['status'] == 1) {
                            $status_text = 'Chờ giao hàng';
                            $status_class = 'shipping';
                        } elseif ($row['status'] == 2) {
                            $status_text = 'Giao hàng thành công';
                            $status_class = 'done';
                        } else {
                            $status_text = 'Chờ xác nhận';
                            $status_class = 'pending';
                        }
                        ?>
                        <tr>
                            <td>#<?= htmlspecialchars($row['order_id']) ?></td>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['address']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= number_format($row['total'], 0, ',', '.') ?>đ</td>
                            <td><?= strtoupper(htmlspecialchars($row['payment_method'])) ?></td>
                            <td><span class="status <?= $status_class ?>"><?= $status_text ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td>
                                <?php if ($row['status'] == 1): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="mark_done" class="btn">Giao hàng thành công</button>
                                    </form>
                                <?php elseif ($row['status'] == 2): ?>
                                    <span class="status done">Đã giao</span>
                                <?php else: ?>
                                    <span class="status pending">Đang xử lý</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p class="no-orders">Chưa có đơn hàng nào.</p>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>