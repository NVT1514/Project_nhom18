<?php
include "Database/connectdb.php";
session_start();

if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['tk'];
$cart_query = "SELECT c.*, s.ten_san_pham, s.hinh_anh, s.gia
               FROM cart c
               JOIN san_pham s ON c.product_id = s.id
               WHERE c.user_id = '$user_id'";
$cart_items = mysqli_query($conn, $cart_query);
$total = 0;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thanh toán đơn hàng</title>
    <link rel="stylesheet" href="css/checkout.css">
    <style>
        .checkout-container {
            display: flex;
            justify-content: space-between;
            gap: 40px;
            padding: 40px;
            max-width: 1200px;
            margin: auto;
        }

        .checkout-left,
        .checkout-right {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .checkout-left {
            flex: 1.2;
        }

        .checkout-right {
            flex: 0.8;
        }

        .checkout-left input,
        select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .cart-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .cart-item img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            margin-right: 15px;
        }

        .item-info {
            flex: 1;
        }

        .item-price {
            font-weight: bold;
        }

        .voucher input {
            width: 70%;
            padding: 10px;
        }

        .voucher button {
            padding: 10px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 6px;
        }

        .btn-place-order {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="checkout-container">
        <!-- Thông tin người nhận -->
        <div class="checkout-left">
            <h2>Thông tin đơn hàng</h2>
            <form action="place_order.php" method="POST" id="checkoutForm">
                <input type="text" name="fullname" placeholder="Họ và tên" required>
                <input type="text" name="phone" placeholder="Số điện thoại" required>
                <input type="text" name="address" placeholder="Địa chỉ" required>

                <div class="address-select">
                    <select name="province" required>
                        <option value="">Chọn tỉnh/thành phố</option>
                        <option>Hà Nội</option>
                        <option>TP Hồ Chí Minh</option>
                        <option>Đà Nẵng</option>
                    </select>

                    <select name="district" required>
                        <option value="">Chọn quận/huyện</option>
                    </select>

                    <select name="ward" required>
                        <option value="">Chọn phường/xã</option>
                    </select>
                </div>

                <h3>Phương thức thanh toán</h3>
                <label><input type="radio" name="payment" value="cod" checked> Thanh toán khi nhận hàng</label><br>
                <label><input type="radio" name="payment" value="bank"> Chuyển khoản ngân hàng</label>

                <button type="submit" class="btn-place-order">Đặt hàng</button>
            </form>
        </div>

        <!-- Giỏ hàng -->
        <div class="checkout-right">
            <h2>🛒 Giỏ hàng của bạn</h2>
            <?php while ($item = mysqli_fetch_assoc($cart_items)):
                $subtotal = $item['gia'] * $item['quantity'];
                $total += $subtotal;
            ?>
                <div class="cart-item">
                    <img src="<?= $item['hinh_anh'] ?>" alt="">
                    <div class="item-info">
                        <p><?= htmlspecialchars($item['ten_san_pham']) ?></p>
                        <small><?= number_format($item['gia'], 0, ',', '.') ?>đ x <?= $item['quantity'] ?></small>
                    </div>
                    <div class="item-price"><?= number_format($subtotal, 0, ',', '.') ?>đ</div>
                </div>
            <?php endwhile; ?>

            <div class="voucher">
                <input type="text" placeholder="Nhập mã giảm giá">
                <button>Áp dụng</button>
            </div>

            <div class="total-section">
                <p>Tạm tính: <span><?= number_format($total, 0, ',', '.') ?>đ</span></p>
                <p>Phí vận chuyển: <span>0đ</span></p>
                <h3>Tổng cộng: <span><?= number_format($total, 0, ',', '.') ?>đ</span></h3>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>