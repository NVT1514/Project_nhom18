<?php
session_start();
include "Database/connectdb.php";

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Xử lý thêm sản phẩm
if (isset($_POST['add_to_cart'])) {
    $id = intval($_POST['id']);
    $qty = max(1, intval($_POST['quantity'] ?? 1));

    // Lấy sản phẩm từ DB để lưu thông tin chi tiết
    $sql = "SELECT * FROM san_pham WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    if ($product = mysqli_fetch_assoc($result)) {
        // Nếu sản phẩm đã có trong giỏ thì tăng số lượng
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] += $qty;
        } else {
            $_SESSION['cart'][$id] = [
                'id' => $product['id'],
                'ten_san_pham' => $product['ten_san_pham'],
                'gia' => $product['gia'],
                'hinh_anh' => $product['hinh_anh'],
                'mo_ta' => $product['mo_ta'],
                'quantity' => $qty
            ];
        }
    }
    header("Location: cart.php");
    exit();
}

// Xóa sản phẩm khỏi giỏ
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][intval($_GET['remove'])]);
    header("Location: cart.php");
    exit();
}

// Cập nhật số lượng
if (isset($_POST['update_qty'])) {
    foreach ($_POST['quantity'] as $id => $qty) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] = max(1, intval($qty));
        }
    }
}

// Áp dụng mã giảm giá
$discount = 0;
$voucher_code = "";
if (isset($_POST['apply_voucher'])) {
    $voucher_code = strtoupper(trim($_POST['voucher']));
    $discount = match ($voucher_code) {
        "OCT20" => 0.2,
        "OCT70" => 0.7,
        default => 0
    };
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng & Thanh toán</title>
    <link rel="stylesheet" href="../css/cart_new.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fa;
            margin: 0;
        }

        .main-container {
            display: flex;
            gap: 40px;
            max-width: 1200px;
            margin: 80px auto;
            padding: 20px;
        }

        .cart-container,
        .checkout-container {
            flex: 1;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .cart-container h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: #333;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table th,
        .cart-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-info img {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            object-fit: cover;
        }

        .qty-box {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .qty-box input {
            width: 40px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .remove {
            color: red;
            font-size: 16px;
            text-decoration: none;
        }

        .checkout-btn {
            background: #0033cc;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        .checkout-container h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 16px;
        }

        .checkout-container input,
        .checkout-container select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .voucher-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .voucher-box input {
            flex: 1;
        }

        .voucher-box button {
            background: #0033cc;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            cursor: pointer;
        }

        .payment-method {
            margin-top: 20px;
        }

        .payment-method label {
            display: block;
            margin: 8px 0;
        }

        .place-order-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <!-- CỘT TRÁI - GIỎ HÀNG -->
        <div class="cart-container">
            <h1><i class="fa-solid fa-cart-shopping"></i> Giỏ hàng</h1>

            <?php
            $total = 0;
            if (!empty($_SESSION['cart'])):
            ?>
                <form method="post">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $item):
                                $subtotal = $item['gia'] * $item['quantity'];
                                $total += $subtotal;
                            ?>
                                <tr>
                                    <td class="product-info">
                                        <img src="<?= $item['hinh_anh'] ?>" alt="<?= $item['ten_san_pham'] ?>">
                                        <?= htmlspecialchars($item['ten_san_pham']) ?>
                                    </td>
                                    <td><?= number_format($item['gia'], 0, ',', '.') ?>đ</td>
                                    <td>
                                        <div class="qty-box">
                                            <input type="number" name="quantity[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" min="1">
                                        </div>
                                    </td>
                                    <td><?= number_format($subtotal, 0, ',', '.') ?>đ</td>
                                    <td><a href="?remove=<?= $item['id'] ?>" class="remove"><i class="fa fa-times"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="text-align:right; margin-top:10px;">
                        <p><strong>Tổng cộng: <?= number_format($total, 0, ',', '.') ?>đ</strong></p>
                    </div>
                </form>
            <?php else: ?>
                <p>Giỏ hàng trống!</p>
            <?php endif; ?>
        </div>

        <!-- CỘT PHẢI - THÔNG TIN NHẬN HÀNG -->
        <div class="checkout-container">
            <h2>📦 Thông tin nhận hàng</h2>
            <form action="place_order.php" method="POST">
                <input type="text" name="fullname" placeholder="Họ và tên" required>
                <input type="text" name="phone" placeholder="Số điện thoại" required>
                <input type="text" name="address" placeholder="Địa chỉ nhận hàng" required>
                <select name="province" required>
                    <option value="">Chọn tỉnh/thành phố</option>
                    <option>Hà Nội</option>
                    <option>TP Hồ Chí Minh</option>
                    <option>Đà Nẵng</option>
                </select>

                <div class="voucher-box">
                    <input type="text" name="voucher" placeholder="Nhập mã giảm giá">
                    <button type="submit" name="apply_voucher">Áp dụng</button>
                </div>

                <div class="payment-method">
                    <h3>💳 Phương thức thanh toán</h3>
                    <label><input type="radio" name="payment" value="cod" checked> COD (Khi nhận hàng)</label>
                    <label><input type="radio" name="payment" value="vnpay"> VNPAY</label>
                    <label><input type="radio" name="payment" value="momo"> MoMo</label>
                </div>

                <button type="submit" class="place-order-btn">Thanh toán</button>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>