<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php";


// Xử lý cập nhật trạng thái giao hàng thành công
if (isset($_POST['mark_done'])) {
    // Đảm bảo rằng order_id được truyền và là số nguyên hợp lệ
    $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
    if ($order_id) {
        // Cập nhật trạng thái thành 2 (Giao hàng thành công)
        $update_sql = "UPDATE don_hang SET status = 2 WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $order_id);

        // Kiểm tra xem lệnh đã thực thi thành công hay chưa (tùy chọn)
        // if ($stmt->execute()) {
        //     // Có thể thêm mã để xử lý thành công ở đây (ví dụ: chuyển hướng hoặc thông báo)
        // } else {
        //     // Xử lý lỗi
        // }

        $stmt->execute();
        $stmt->close();
    }
}

// Lấy danh sách đơn hàng
// Chỉ lấy các trường cần thiết, thêm trường 'id' (cần cho hành động) và 'order_id' (mã đơn)
$sql = "SELECT id, order_id, status FROM don_hang ORDER BY created_at DESC";
$result = $conn->query($sql);


?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* CSS cho sidebar và bố cục chính vẫn giữ nguyên */
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
            /* Giảm bớt chiều rộng vì ít cột hơn, nhưng giữ rộng đủ để bảng không bị hẹp */
            width: 800px;
            background: #fff;
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            /* Căn giữa container */
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        /* CSS cho bảng */
        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* Giúp các cột có độ rộng cố định */
        }

        .table th,
        .table td {
            padding: 14px 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        /* Cấu hình độ rộng cho các cột mới */
        .table th:nth-child(1),
        .table td:nth-child(1) {
            width: 15%;
        }

        /* Mã đơn */
        .table th:nth-child(2),
        .table td:nth-child(2) {
            width: 25%;
        }

        /* Trạng thái */
        .table th:nth-child(3),
        .table td:nth-child(3) {
            width: 25%;
        }

        /* Xem chi tiết */
        .table th:nth-child(4),
        .table td:nth-child(4) {
            width: 35%;
        }

        /* Hành động */

        .table th {
            background: #007bff;
            color: white;
        }

        .table tr:hover {
            background: #f9fafc;
        }

        /* CSS cho trạng thái */
        .status {
            padding: 6px 12px;
            border-radius: 6px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
            /* Để status button có padding đẹp hơn */
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

        /* CSS cho nút */
        .btn {
            background: #007bff;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            font-size: 14px;
            white-space: nowrap;
            /* Ngăn nút bị xuống dòng */
        }

        .btn-detail {
            background: #6c757d;
            /* Màu xám cho nút xem chi tiết */
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-detail:hover {
            background: #5a6268;
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
                        <th>Trạng thái</th>
                        <th>Xem chi tiết</th>
                        <th>Hành động</th>
                    </tr>

                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $status_text = '';
                        $status_class = '';

                        // Logic chuyển đổi trạng thái (giữ nguyên theo mã cũ)
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

                            <td><span class="status <?= $status_class ?>"><?= $status_text ?></span></td>

                            <td>
                                <a href="chi_tiet.php?id=<?= $row['id'] ?>" class="btn btn-detail">Xem chi tiết</a>
                            </td>

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