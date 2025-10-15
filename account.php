<?php
include "Database/connectdb.php";
include "Database/function.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Lấy thông tin user
$res = mysqli_query($conn, "SELECT * FROM user WHERE id = $user_id");
if (!$res || mysqli_num_rows($res) == 0) die("Không tìm thấy thông tin tài khoản.");
$user = mysqli_fetch_assoc($res);

// Lấy danh sách tài khoản ngân hàng nếu là seller hoặc admin
$bank_accounts = [];
if ($user['role'] === 'seller' || $user['role'] === 'admin') {
    $res_bank = mysqli_query($conn, "SELECT * FROM payment_accounts ORDER BY id DESC");
    if ($res_bank) while ($row = mysqli_fetch_assoc($res_bank)) $bank_accounts[] = $row;
}

// Thêm tài khoản ngân hàng
if (isset($_POST['add_bank_account'])) {
    $bank = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $account = mysqli_real_escape_string($conn, $_POST['account_number']);
    $display = mysqli_real_escape_string($conn, $_POST['display_name']);
    mysqli_query($conn, "INSERT INTO payment_accounts (bank_name, account_number, display_name) VALUES ('$bank','$account','$display')");
    $_SESSION['message'] = "Thêm tài khoản ngân hàng thành công!";
    header("Location: account.php");
    exit();
}

// Xóa tài khoản ngân hàng
if (isset($_GET['delete_bank'])) {
    $id = intval($_GET['delete_bank']);
    mysqli_query($conn, "DELETE FROM payment_accounts WHERE id=$id");
    $_SESSION['message'] = "Xóa tài khoản ngân hàng thành công!";
    header("Location: account.php");
    exit();
}

// Xử lý cập nhật thông tin
if (isset($_POST['update_info'])) {
    $tai_khoan = mysqli_real_escape_string($conn, $_POST['Tai_Khoan']);
    $email = mysqli_real_escape_string($conn, $_POST['Email']);
    mysqli_query($conn, "UPDATE user SET Tai_Khoan='$tai_khoan', Email='$email' WHERE id=$user_id");
    $_SESSION['message'] = "Cập nhật thông tin thành công!";
    header("Location: account.php");
    exit();
}

// Xử lý đổi mật khẩu
if (isset($_POST['change_password'])) {
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    if ($old_pass !== $user['Mat_Khau']) {
        $error = "Mật khẩu cũ không đúng!";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Mật khẩu mới và xác nhận mật khẩu không khớp!";
    } else {
        mysqli_query($conn, "UPDATE user SET Mat_Khau='$new_pass' WHERE id=$user_id");
        $_SESSION['message'] = "Đổi mật khẩu thành công!";
        header("Location: account.php");
        exit();
    }
}

// Xử lý nhập/xuất kho
if (isset($_POST['confirm_action'])) {
    $product_id = intval($_POST['product_id']);
    $qty = intval($_POST['qty']);
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier']);
    $date = $_POST['date'];
    $total_price = floatval($_POST['total_price']);
    $action = $_POST['action_type']; // import / export

    $res = mysqli_query($conn, "SELECT * FROM san_pham WHERE id=$product_id");
    $product = mysqli_fetch_assoc($res);

    if (!$product) {
        $_SESSION['message'] = "Sản phẩm không tồn tại!";
        header("Location: account.php");
        exit();
    }

    if ($action === 'import') {
        $new_qty = $product['so_luong'] + $qty;
        $new_hang_ton = $product['hang_ton'] + $qty;
        mysqli_query($conn, "UPDATE san_pham SET so_luong=$new_qty, hang_ton=$new_hang_ton WHERE id=$product_id");
        mysqli_query($conn, "INSERT INTO kho_log (product_id, type, qty, supplier, date, total_price) 
            VALUES ($product_id,'import',$qty,'$supplier','$date',$total_price)");
        $_SESSION['message'] = "Nhập kho thành công!";
    } elseif ($action === 'export') {
        if ($product['so_luong'] >= $qty) {
            $new_qty = $product['so_luong'] - $qty;
            $new_hang_ton = max(0, $product['hang_ton'] - $qty);
            $new_so_luong_ban = $product['so_luong_ban'] + $qty;
            mysqli_query($conn, "UPDATE san_pham SET so_luong=$new_qty, hang_ton=$new_hang_ton, so_luong_ban=$new_so_luong_ban WHERE id=$product_id");
            mysqli_query($conn, "INSERT INTO kho_log (product_id, type, qty, supplier, date, total_price) 
                VALUES ($product_id,'export',$qty,'$supplier','$date',$total_price)");
            $_SESSION['message'] = "Xuất kho thành công!";
        } else {
            $_SESSION['message'] = "Số lượng trong kho không đủ!";
        }
    }
    header("Location: account.php");
    exit();
}

// Lịch sử đơn hàng customer
$order_history = [];
if ($user['role'] === 'customer') {
    $res_orders = mysqli_query($conn, "SELECT * FROM don_hang WHERE user_id = $user_id ORDER BY created_at DESC");
    if ($res_orders) while ($row = mysqli_fetch_assoc($res_orders)) $order_history[] = $row;
}

// Lấy dữ liệu kho
$stock_items = $empty_items = [];
if ($user['role'] !== 'customer') {
    $res_stock = mysqli_query($conn, "SELECT * FROM san_pham WHERE hang_ton > 0 ORDER BY id DESC");
    if ($res_stock) while ($row = mysqli_fetch_assoc($res_stock)) $stock_items[] = $row;

    $res_empty = mysqli_query($conn, "SELECT * FROM san_pham WHERE hang_ton = 0 ORDER BY id DESC");
    if ($res_empty) while ($row = mysqli_fetch_assoc($res_empty)) $empty_items[] = $row;
}

// Báo cáo kho
$range = $_GET['range'] ?? 'today';
$start_date = $end_date = date('Y-m-d');
switch ($range) {
    case 'yesterday':
        $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case '7days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case '6months':
        $start_date = date('Y-m-d', strtotime('-6 months'));
        break;
    case 'lastyear':
        $start_date = date('Y-01-01', strtotime('-1 year'));
        $end_date = date('Y-12-31', strtotime('-1 year'));
        break;
}

$report = [];
if ($user['role'] !== 'customer') {
    $res_report = mysqli_query($conn, "SELECT type, SUM(qty) as total_qty, SUM(total_price) as total_price 
        FROM kho_log 
        WHERE date BETWEEN '$start_date' AND '$end_date'
        GROUP BY type");
    if ($res_report) while ($row = mysqli_fetch_assoc($res_report)) $report[$row['type']] = $row;
}
if (isset($_POST['update_stock'])) {
    $order_id = intval($_POST['order_id']);

    // Lấy các sản phẩm trong đơn
    $items = mysqli_query($conn, "
        SELECT product_id, quantity 
        FROM chi_tiet_don_hang 
        WHERE order_id=$order_id
    ");

    while ($item = mysqli_fetch_assoc($items)) {
        $product_id = intval($item['product_id']);
        $qty = intval($item['quantity']);

        $prod = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT hang_ton, so_luong_ban 
            FROM san_pham 
            WHERE id=$product_id
        "));

        $new_hang_ton = max(0, intval($prod['hang_ton']) - $qty);
        $new_so_luong_ban = intval($prod['so_luong_ban']) + $qty;

        mysqli_query($conn, "
            UPDATE san_pham 
            SET hang_ton=$new_hang_ton, so_luong_ban=$new_so_luong_ban 
            WHERE id=$product_id
        ");
    }

    // Đánh dấu đơn này đã xử lý kho
    mysqli_query($conn, "UPDATE don_hang SET processed_stock=1 WHERE id=$order_id");

    echo "<script>alert('Đã cập nhật kho'); window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
    exit();
}

// ================== XỬ LÝ TỰ ĐỘNG KHO CHO CÁC ĐƠN ĐÃ DUYỆT ==================
$processed_orders = mysqli_query($conn, "
    SELECT id 
    FROM don_hang 
    WHERE status=1 AND (processed_stock IS NULL OR processed_stock=0)
");

if ($processed_orders && mysqli_num_rows($processed_orders) > 0) {
    while ($order = mysqli_fetch_assoc($processed_orders)) {
        $order_id = intval($order['id']);
        $items = mysqli_query($conn, "
            SELECT product_id, quantity 
            FROM chi_tiet_don_hang 
            WHERE order_id=$order_id
        ");
        while ($item = mysqli_fetch_assoc($items)) {
            $product_id = intval($item['product_id']);
            $qty = intval($item['quantity']);

            $prod = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT hang_ton, so_luong_ban 
                FROM san_pham 
                WHERE id=$product_id
            "));
            $new_hang_ton = max(0, intval($prod['hang_ton']) - $qty);
            $new_so_luong_ban = intval($prod['so_luong_ban']) + $qty;

            mysqli_query($conn, "
                UPDATE san_pham 
                SET hang_ton=$new_hang_ton, so_luong_ban=$new_so_luong_ban 
                WHERE id=$product_id
            ");
        }
        mysqli_query($conn, "UPDATE don_hang SET processed_stock=1 WHERE id=$order_id");
    }
}



// ================== XỬ LÝ DUYỆT ĐƠN ==================
if (isset($_GET['approve'])) {
    $order_id = intval($_GET['approve']);
    mysqli_query($conn, "UPDATE don_hang SET status = 1 WHERE id = $order_id");

    // Redirect giữ ở mục quản lý đơn hàng
    header("Location: account.php?active=orders");
    exit();
}

// Lấy danh sách đơn hàng cùng chi tiết sản phẩm và ảnh
$sql = "
    SELECT dh.id AS order_id, dh.order_id AS order_code, dh.fullname, dh.phone, dh.address, dh.total, dh.payment_method, dh.status, dh.created_at,
           ctdh.product_name, ctdh.price, ctdh.quantity, ctdh.size, sp.hinh_anh
    FROM don_hang dh
    LEFT JOIN chi_tiet_don_hang ctdh ON dh.id = ctdh.order_id
    LEFT JOIN san_pham sp ON ctdh.product_id = sp.id
    ORDER BY dh.created_at DESC, ctdh.id ASC
";
$result = mysqli_query($conn, $sql);
if (!$result) die("Lỗi truy vấn: " . mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tài khoản</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding-top: 80px;
        }

        .container-main {
            display: flex;
            min-height: calc(100vh - 60px);
        }

        .sidebar {
            width: 300px;
            background: #2c3e50;
            color: #fff;
            display: flex;
            flex-direction: column;
            padding-top: 30px;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #34495e;
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 22px;
        }

        .sidebar-header p {
            margin: 0;
            font-size: 18px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 20px 0 0 0;
            flex: 1;
        }

        .sidebar ul li {
            padding: 20px;
            cursor: pointer;
            border-bottom: 1px solid #34495e;
            font-size: 18px;
        }

        .sidebar ul li:hover,
        .sidebar ul li.active {
            background: #34495e;
        }

        .sidebar ul li.logout {
            background: #e74c3c;
            text-align: center;
            font-weight: bold;
            margin-top: auto;
        }

        .content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #f5f5f5;
        }

        input,
        button,
        select {
            width: 100%;
            padding: 12px;
            margin: 5px 0 20px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            cursor: pointer;
        }

        button.update {
            background: #3498db;
            color: #fff;
        }

        button.change {
            background: #f39c12;
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 16px;
        }

        table,
        th,
        td {
            border: 1px solid #ccc;
        }

        th,
        td {
            padding: 10px;
            text-align: center;
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 16px;
        }

        .alert-success {
            background: #2ecc71;
            color: #fff;
        }

        .alert-danger {
            background: #e74c3c;
            color: #fff;
        }

        .toggle-details {
            width: 150px;
        }
    </style>
    /* Quản lý đơn hàng - Seller/Admin */
    #orders_manage h3 {
    font-size: 24px;
    color: #5c7cfa;
    margin-bottom: 20px;
    }

    .order-header {
    background: #fff;
    border-radius: 8px;
    padding: 15px 20px;
    margin: 15px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: pointer;
    }

    .order-header div {
    font-size: 16px;
    color: #333;
    flex:1;
    }

    .details-btn, .approve-btn {
    padding: 6px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    }

    .details-btn {
    background: #5c7cfa;
    color: #fff;
    margin-left: 10px;
    }

    .approve-btn {
    background: #ff8c00;
    color: #fff;
    margin-left: 10px;
    }

    .detail-section {
    display: none;
    background: #fff;
    border-radius: 8px;
    padding: 15px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    }

    .detail-section table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    }

    .detail-section table th, .detail-section table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
    font-size: 14px;
    }

    .detail-section table th {
    background: #f0f4ff;
    font-weight: bold;
    }

    .detail-section img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    }

    .status-approved {
    color: green;
    font-weight: bold;
    }

    .status-pending {
    color: red;
    font-weight: bold;
    }

    @media screen and (max-width: 768px){
    .order-header {
    flex-direction: column;
    align-items: flex-start;
    }
    .details-btn, .approve-btn {
    margin: 10px 0 0 0;
    }
    .detail-section table th, .detail-section table td {
    font-size: 12px;
    padding: 6px;
    }
    }
    .details-btn{
    width: 150px;
    }
    </style>

    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container-main">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><?= htmlspecialchars($user['Tai_Khoan']) ?></h3>
                <p><?= ucfirst($user['role']) ?></p>
            </div>
            <ul>
                <?php if ($user['role'] === 'customer'): ?>
                    <li class="active" onclick="showSection('info')">Thông tin tài khoản</li>
                    <li onclick="showSection('password')">Đổi mật khẩu</li>
                    <li onclick="showSection('orders')">Lịch sử mua hàng</li>
                <?php else: ?>
                    <li class="active" onclick="showSection('info')">Thông tin tài khoản</li>
                    <li onclick="showSection('bank_accounts')">Tài khoản ngân hàng</li>
                    <li onclick="showSection('password')">Đổi mật khẩu</li>
                    <li onclick="showSection('orders_manage')">Quản lý đơn hàng</li>
                    <li onclick="showSection('inventory_stock')">Hàng tồn</li>
                    <li onclick="showSection('inventory_empty')">Hết hàng</li>
                    <li onclick="showSection('report')">Báo cáo kho</li>
                <?php endif; ?>
            </ul>
            <ul>
                <li class="logout" onclick="window.location.href='logout.php'">Đăng xuất</li>
            </ul>
        </div>

        <div class="content">
            <?php if (!empty($_SESSION['message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error; ?></div>
            <?php endif; ?>

            <!-- Thông tin tài khoản -->
            <div id="info">
                <h3>Thông tin tài khoản</h3>
                <form method="post">
                    <label>Tài khoản</label>
                    <input type="text" name="Tai_Khoan" value="<?= htmlspecialchars($user['Tai_Khoan']) ?>" required>
                    <label>Email</label>
                    <input type="email" name="Email" value="<?= htmlspecialchars($user['Email']) ?>" required>
                    <button type="submit" name="update_info" class="update">Cập nhật</button>
                </form>
            </div>

            <!-- Đổi mật khẩu -->
            <div id="password" style="display:none;">
                <h3>Đổi mật khẩu</h3>
                <form method="post">
                    <label>Mật khẩu cũ</label>
                    <input type="password" name="old_pass" required>
                    <label>Mật khẩu mới</label>
                    <input type="password" name="new_pass" required>
                    <label>Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm_pass" required>
                    <button type="submit" name="change_password" class="change">Đổi mật khẩu</button>
                </form>
            </div>

            <!-- Lịch sử mua hàng -->
            <?php if ($user['role'] === 'customer'): ?>
                <div id="orders" style="display:none;">
                    <h3 style="font-size:24px;color:#5c7cfa;margin-bottom:20px;">📦 Lịch sử đơn hàng</h3>
                    <?php
                    $sql = "
        SELECT dh.id AS order_id, dh.order_id AS order_code, dh.fullname, dh.phone, dh.address, dh.total, 
               dh.payment_method, dh.created_at, dh.status,
               ctdh.product_name, ctdh.price, ctdh.quantity, ctdh.size, sp.hinh_anh
        FROM don_hang dh
        LEFT JOIN chi_tiet_don_hang ctdh ON dh.id = ctdh.order_id
        LEFT JOIN san_pham sp ON ctdh.product_id = sp.id
        WHERE dh.user_id = $user_id
        ORDER BY dh.created_at DESC, ctdh.id ASC
    ";
                    $result = mysqli_query($conn, $sql);

                    if (!$result) {
                        echo "<p style='color:red;'>Lỗi truy vấn: " . mysqli_error($conn) . "</p>";
                    } elseif (mysqli_num_rows($result) === 0) {
                        echo "<p>Bạn chưa có đơn hàng nào.</p>";
                    } else {
                        $current_order = null;
                        while ($row = mysqli_fetch_assoc($result)) {
                            if ($current_order !== $row['order_id']) {
                                if ($current_order !== null) {
                                    echo "</tbody></table></div>"; // đóng bảng + div chi tiết cũ
                                }
                                $current_order = $row['order_id'];

                                // Trạng thái đơn hàng
                                if (strtoupper($row['payment_method']) === 'COD') {
                                    $status_text = '<span style="color:green;font-weight:bold;">Thành công</span>';
                                } else {
                                    $status_text = $row['status'] == 1
                                        ? '<span style="color:green;font-weight:bold;">Thành công</span>'
                                        : '<span style="color:red;font-weight:bold;">Chờ xác nhận</span>';
                                }

                                // Header đơn hàng
                                echo "<div class='order-header' 
                        style='background:#fff;border-radius:8px;padding:15px 20px;margin:20px 0;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);cursor:pointer;'>
                        <span>Đơn hàng: <b>{$row['order_code']}</b> | Tổng: "
                                    . number_format($row['total'], 0, ',', '.') .
                                    "đ | Phương thức: {$row['payment_method']} | Ngày: {$row['created_at']} | Trạng thái: {$status_text}</span>
                        <button class='toggle-details' data-order='{$row['order_id']}' 
                            style='padding:6px 14px;border:none;border-radius:6px;background:#5c7cfa;color:#fff;cursor:pointer;'>
                            Xem chi tiết
                        </button>


                    </div>";

                                // Chi tiết đơn hàng
                                echo "<div class='order-details' id='details-{$row['order_id']}' 
                        style='display:none;background:#fff;border-radius:8px;padding:15px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);margin-bottom:20px;'>
                        <p><b>Người nhận:</b> " . htmlspecialchars($row['fullname']) . "</p>
                        <p><b>Số điện thoại:</b> " . htmlspecialchars($row['phone']) . "</p>
                        <p><b>Địa chỉ:</b> " . htmlspecialchars($row['address']) . "</p>
                        <table style='width:100%;border-collapse:collapse;margin-top:15px;'>
                            <thead style='background:#f0f4ff;'>
                                <tr>
                                    <th>Ảnh</th>
                                    <th>Sản phẩm</th>
                                    <th>Size</th>
                                    <th>Giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>";
                            }

                            $subtotal = $row['price'] * $row['quantity'];
                            echo "<tr style='border-bottom:1px solid #eee;text-align:center;'>
                    <td><img src='" . str_replace("../", "", htmlspecialchars($row['hinh_anh'])) . "' width='60' height='60' style='object-fit:cover;border-radius:6px;'></td>

                    <td>" . htmlspecialchars($row['product_name']) . "</td>
                    <td>" . htmlspecialchars($row['size']) . "</td>
                    <td>" . number_format($row['price'], 0, ',', '.') . "đ</td>
                    <td>" . intval($row['quantity']) . "</td>
                    <td>" . number_format($subtotal, 0, ',', '.') . "đ</td>
                </tr>";
                        }
                        if ($current_order !== null) echo "</tbody></table></div>";
                    }
                    ?>
                </div>
                <script>
                    const buttons = document.querySelectorAll('.toggle-details');
                    buttons.forEach(btn => {
                        btn.addEventListener('click', () => {
                            const orderId = btn.getAttribute('data-order');
                            const detailsDiv = document.getElementById('details-' + orderId);
                            if (detailsDiv.style.display === 'none' || detailsDiv.style.display === '') {
                                detailsDiv.style.display = 'block';
                                btn.textContent = 'Ẩn chi tiết';
                            } else {
                                detailsDiv.style.display = 'none';

                                btn.textContent = 'Xem chi tiết';
                            }
                        });
                    });
                </script>
            <?php endif; ?>


            <!-- Seller/Admin -->
            <?php if ($user['role'] !== 'customer'): ?>

                <!-- Tài khoản ngân hàng -->
                <div id="bank_accounts" style="display:none;">
                    <h3>Tài khoản ngân hàng</h3>
                    <form method="post">
                        <label>Ngân hàng</label>
                        <select name="bank_name" required>
                            <option value="" disabled selected>Chọn ngân hàng</option>
                            <option>VietcomBank</option>
                            <option>MbBank</option>
                            <option>ViettinBank</option>
                            <option>Momo</option>
                            <option>VNPay</option>
                            <option>AgriBank</option>
                            <option>TpBank</option>
                            <option>Sacombank</option>
                        </select>
                        <label>Số tài khoản</label>
                        <input type="text" name="account_number" required>
                        <label>Tên hiển thị</label>
                        <input type="text" name="display_name">
                        <button type="submit" name="add_bank_account" class="update">Thêm tài khoản</button>
                    </form>

                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ngân hàng</th>
                                <th>Số tài khoản</th>
                                <th>Tên hiển thị</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bank_accounts as $acc): ?>
                                <tr>
                                    <td><?= $acc['id'] ?></td>
                                    <td><?= htmlspecialchars($acc['bank_name']) ?></td>
                                    <td><?= htmlspecialchars($acc['account_number']) ?></td>
                                    <td><?= htmlspecialchars($acc['display_name']) ?></td>
                                    <td>
                                        <a href="?delete_bank=<?= $acc['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Quản lý đơn hàng -->
                <div id="orders_manage" style="display:none;">
                    <h3>Quản lý đơn hàng</h3>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                        <button type="submit" name="update_stock" class="approve-btn" style="background:#ff8c00;">Cập nhật kho</button>
                    </form>


                    <?php
                    $current_order = null;
                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($current_order !== $row['order_id']) {
                            if ($current_order !== null) {
                                echo "</tbody></table></div>"; // đóng bảng cũ
                            }
                            $current_order = $row['order_id'];

                            // Trạng thái
                            $status_text = ($row['status'] == 1 || strtoupper($row['payment_method']) === 'COD')
                                ? '<span class="status-approved">Thành công</span>'
                                : '<span class="status-pending">Chờ xác nhận</span>';

                            // Header đơn hàng + nút xem chi tiết
                            echo "<div class='order-header'>
                <div>Đơn hàng: <b>{$row['order_code']}</b> | Tổng: " . number_format($row['total'], 0, ',', '.') . "đ | Phương thức: {$row['payment_method']} | Ngày: {$row['created_at']} | Trạng thái: $status_text</div>
                <button class='details-btn' onclick='toggleDetails({$row['order_id']})'>Xem chi tiết</button>
              </div>";

                            // Div chi tiết
                            echo "<div id='detail-{$row['order_id']}' class='detail-section'>
                <p><b>Người nhận:</b> " . htmlspecialchars($row['fullname']) . "</p>
                <p><b>SĐT:</b> " . htmlspecialchars($row['phone']) . "</p>
                <p><b>Địa chỉ:</b> " . htmlspecialchars($row['address']) . "</p>
                <table>
                    <thead>
                        <tr><th>Ảnh</th><th>Sản phẩm</th><th>Size</th><th>Giá</th><th>Số lượng</th><th>Thành tiền</th></tr>
                    </thead>
                    <tbody>";
                        }

                        $subtotal = $row['price'] * $row['quantity'];
                        echo "<tr>
            <td><img src='" . str_replace("../", "", htmlspecialchars($row['hinh_anh'])) . "' width='60' height='60' style='object-fit:cover;border-radius:6px;'></td>
            <td>" . htmlspecialchars($row['product_name']) . "</td>
            <td>" . htmlspecialchars($row['size']) . "</td>
            <td>" . number_format($row['price'], 0, ',', '.') . "đ</td>
            <td>{$row['quantity']}</td>
            <td>" . number_format($subtotal, 0, ',', '.') . "đ</td>
          </tr>";

                        // Nút duyệt chỉ hiển thị nếu thanh toán ngân hàng và chưa duyệt
                        if ($row['status'] == 0 && strtoupper($row['payment_method']) !== 'COD') {
                            echo "<a href='?approve={$row['order_id']}' class='approve-btn'>Xác nhận</a>";
                        }
                    }
                    if ($current_order !== null) {
                        echo "</tbody></table></div>";
                    }
                    ?>

                    <script>
                        function toggleDetails(orderId) {
                            const section = document.getElementById('detail-' + orderId);
                            if (section.style.display === 'none' || section.style.display === '') {
                                section.style.display = 'block';
                            } else {
                                section.style.display = 'none';
                            }
                        }
                    </script>
                </div>

                <!-- Hàng tồn -->
                <div id="inventory_stock" style="display:none;">
                    <h3>Hàng tồn</h3>
                    <?php if (!empty($stock_items)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Mã SP</th>
                                    <th>Tên SP</th>
                                    <th>Loại</th>
                                    <th>Nhóm SP</th>
                                    <th>Ngày tạo</th>
                                    <th>Mô tả</th>
                                    <th>Hình ảnh</th>
                                    <th>Tổng nhập</th>
                                    <th>Hàng tồn</th>
                                    <th>Đã bán</th>
                                    <th>Giá</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stock_items as $item): ?>
                                    <tr>
                                        <td><?= $item['id'] ?></td>
                                        <td><?= htmlspecialchars($item['id']) ?></td>
                                        <td><?= htmlspecialchars($item['ten_san_pham']) ?></td>
                                        <td><?= htmlspecialchars($item['phan_loai']) ?></td>
                                        <td><?= htmlspecialchars($item['nhom_san_pham']) ?></td>
                                        <td><?= $item['ngay_tao'] ?></td>
                                        <td><?= htmlspecialchars($item['mo_ta']) ?></td>
                                        <td>
                                            <?php if (!empty($item['hinh_anh'])): ?>
                                                <img src="uploads/<?= basename($item['hinh_anh']) ?>" width="50" alt="SP">
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $item['so_luong'] ?></td>
                                        <td><?= $item['hang_ton'] ?></td>
                                        <td><?= $item['so_luong_ban'] ?></td>
                                        <td><?= number_format($item['gia'], 0, ',', '.') ?>đ</td>
                                        <td>
                                            <button onclick="showForm('import', <?= $item['id'] ?>)">Nhập hàng</button>
                                            <button onclick="showForm('export', <?= $item['id'] ?>)">Xuất hàng</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Chưa có sản phẩm trong kho.</p>
                    <?php endif; ?>
                </div>

                <!-- Hết hàng -->
                <div id="inventory_empty" style="display:none;">
                    <h3>Hết hàng</h3>
                    <?php if (!empty($empty_items)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Mã SP</th>
                                    <th>Tên SP</th>
                                    <th>Loại</th>
                                    <th>Nhóm SP</th>
                                    <th>Ngày tạo</th>
                                    <th>Mô tả</th>
                                    <th>Hình ảnh</th>
                                    <th>Tổng nhập</th>
                                    <th>Hàng tồn</th>
                                    <th>Đã bán</th>
                                    <th>Giá</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($empty_items as $item): ?>
                                    <tr>
                                        <td><?= $item['id'] ?></td>
                                        <td><?= htmlspecialchars($item['id']) ?></td>
                                        <td><?= htmlspecialchars($item['ten_san_pham']) ?></td>
                                        <td><?= htmlspecialchars($item['phan_loai']) ?></td>
                                        <td><?= htmlspecialchars($item['nhom_san_pham']) ?></td>
                                        <td><?= $item['ngay_tao'] ?></td>
                                        <td><?= htmlspecialchars($item['mo_ta']) ?></td>
                                        <td>
                                            <?php if (!empty($item['hinh_anh'])): ?>
                                                <img src="uploads/<?= basename($item['hinh_anh']) ?>" width="50" alt="SP">
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $item['so_luong'] ?></td>
                                        <td><?= $item['hang_ton'] ?></td>
                                        <td><?= $item['so_luong_ban'] ?></td>
                                        <td><?= number_format($item['gia'], 0, ',', '.') ?>đ</td>
                                        <td>
                                            <button onclick="showForm('import', <?= $item['id'] ?>)">Nhập hàng</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Chưa có sản phẩm hết hàng.</p>
                    <?php endif; ?>
                </div>

                <!-- Báo cáo kho -->
                <div id="report" style="display:none;">
                    <h3>Báo cáo kho</h3>
                    <form method="get">
                        <label>Lọc theo thời gian:</label>
                        <select name="range" onchange="this.form.submit()">
                            <option value="today" <?= $range == 'today' ? 'selected' : '' ?>>Hôm nay</option>
                            <option value="yesterday" <?= $range == 'yesterday' ? 'selected' : '' ?>>Hôm qua</option>
                            <option value="7days" <?= $range == '7days' ? 'selected' : '' ?>>7 ngày</option>
                            <option value="30days" <?= $range == '30days' ? 'selected' : '' ?>>30 ngày</option>
                            <option value="6months" <?= $range == '6months' ? 'selected' : '' ?>>6 tháng</option>
                            <option value="lastyear" <?= $range == 'lastyear' ? 'selected' : '' ?>>Năm trước</option>
                        </select>
                    </form>
                    <table>
                        <thead>
                            <tr>
                                <th>Loại</th>
                                <th>Số lượng</th>
                                <th>Tổng tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Nhập kho</td>
                                <td><?= $report['import']['total_qty'] ?? 0 ?></td>
                                <td><?= number_format($report['import']['total_price'] ?? 0, 0, ',', '.') ?>đ</td>
                            </tr>
                            <tr>
                                <td>Xuất kho</td>
                                <td><?= $report['export']['total_qty'] ?? 0 ?></td>
                                <td><?= number_format($report['export']['total_price'] ?? 0, 0, ',', '.') ?>đ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <!-- Form nhập/xuất kho -->
    <div id="warehouseForm" style="display:none; position:fixed; top:20%; left:30%; width:40%; background:#fff; padding:20px; border:1px solid #ccc; z-index:999;">
        <h3 id="formTitle">Nhập/Xuất kho</h3>
        <form method="post">
            <input type="hidden" name="product_id" id="product_id">
            <input type="hidden" name="action_type" id="action_type">
            <label>Số lượng</label>
            <input type="number" name="qty" min="1" required>
            <label>Nhà cung cấp</label>
            <input type="text" name="supplier" required>
            <label>Ngày</label>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
            <label>Tổng tiền</label>
            <input type="number" name="total_price" required>
            <button type="submit" name="confirm_action">Xác nhận</button>
            <button type="button" onclick="closeForm()">Hủy</button>
        </form>
    </div>

    <script>
        function showSection(id) {
            const sections = ['info', 'password', 'orders', 'bank_accounts', 'orders_manage', 'inventory_stock', 'inventory_empty', 'report'];
            sections.forEach(s => {
                const el = document.getElementById(s);
                if (el) el.style.display = (s === id) ? 'block' : 'none';
            });

            document.querySelectorAll('.sidebar ul li').forEach(li => li.classList.remove('active'));
            const li = document.querySelector(`.sidebar ul li[onclick="showSection('${id}')"]`);
            if (li) li.classList.add('active');
        }

        function showForm(action, productId) {
            document.getElementById('warehouseForm').style.display = 'block';
            document.getElementById('product_id').value = productId;
            document.getElementById('action_type').value = action;
            document.getElementById('formTitle').innerText = (action === 'import' ? 'Nhập kho' : 'Xuất kho');
        }

        function closeForm() {
            document.getElementById('warehouseForm').style.display = 'none';
        }
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>