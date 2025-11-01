<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('user_session');
    session_start();
}
include "Database/connectdb.php";


// Lấy các đơn hàng có status = 1 (chờ giao) hoặc 2 (đã giao)
$sql = "SELECT * FROM don_hang WHERE user_id = ? AND status IN (1,2) ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Trạng thái đơn hàng</title>
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
        .main-container {
            flex: 1;
            padding: 30px;
            width: 100%;
        }

        .main-container h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .order-container {
            width: 90%;
            margin: 40px auto;
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

        .status.wait {
            background-color: #ffc107;
            /* vàng */
        }

        .status.done {
            background-color: #4caf50;
            /* xanh lá */
        }

        .btn {
            background: #007bff;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <?php include "sidebar_user.php"; ?>

    <div class="main-container">
        <?php
        $breadcrumb_title = "Trạng thái đơn hàng";
        $breadcrumb_items = [
            ["label" => "Trang chủ", "link" => "maincustomer.php"],
            ["label" => $breadcrumb_title]
        ];
        include "breadcrumb.php";
        ?>
        <div class="order-container">
            <h1><i class="fa-solid fa-truck"></i> Trạng thái đơn hàng</h1>

            <?php if ($result->num_rows > 0): ?>
                <table class="table">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Người nhận</th>
                        <th>Địa chỉ</th>
                        <th>Phương thức thanh toán</th>
                        <th>Tổng tiền</th>
                        <th>Ngày đặt</th>
                        <th>Trạng thái</th>
                        <th>Chi tiết</th>
                    </tr>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        if ($row['status'] == 1) {
                            $status_text = 'Chờ giao hàng';
                            $status_class = 'wait';
                        } elseif ($row['status'] == 2) {
                            $status_text = 'Giao hàng thành công';
                            $status_class = 'done';
                        }
                        ?>
                        <tr>
                            <td>#<?= htmlspecialchars($row['order_id']) ?></td>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['address']) ?></td>
                            <td><?= htmlspecialchars($row['payment_method']) ?></td>
                            <td><?= number_format($row['total'], 0, ',', '.') ?>đ</td>
                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                            <td><span class="status <?= $status_class ?>"><?= $status_text ?></span></td>
                            <td><a href="/Du_an_nhom_18/chi_tiet_don_hang.php?id=<?= $row['id'] ?>" class="btn">Xem</a></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p style="text-align:center; font-size:16px; color:#666;">Không có đơn hàng nào.</p>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>