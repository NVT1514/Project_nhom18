<?php
include "Database/connectdb.php";
session_start();

// --- Kiểm tra ID sản phẩm ---
if (!isset($_GET['id'])) {
    die("Không có sản phẩm nào được chọn!");
}

$id = intval($_GET['id']);

// --- Lấy thông tin sản phẩm ---
$sql = "SELECT * FROM san_pham WHERE id = $id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Không tìm thấy sản phẩm!");
}
$product = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['ten_san_pham']) ?></title>
    <link rel="stylesheet" href="css/chitietsanpham.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-detail-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            max-width: 1100px;
            padding-top: 100px;
        }

        .product-gallery {
            position: relative;
        }

        .product-gallery img.main-image {
            width: 350px;
            height: 350px;
            border-radius: 10px;
            object-fit: cover;
        }

        .product-gallery .sold-out {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(255, 0, 0, 0.7);
            color: #fff;
            padding: 5px 12px;
            font-weight: bold;
            border-radius: 5px;
            font-size: 16px;
            z-index: 10;
        }

        .thumbnails img {
            width: 70px;
            height: 70px;
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            object-fit: cover;
        }

        .product-info {
            max-width: 500px;
        }

        .product-title {
            font-size: 28px;
            font-weight: bold;
        }

        .product-price {
            color: #d00;
            font-size: 22px;
            margin: 10px 0;
        }

        .product-options {
            margin: 15px 0;
        }

        .product-options select {
            margin-right: 15px;
            padding: 5px;
        }

        .product-description {
            background: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .quantity-control button {
            width: 30px;
            height: 30px;
            border: none;
            background-color: #ddd;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .quantity-control button:hover {
            background-color: #ccc;
        }

        .quantity-control input {
            width: 60px;
            text-align: center;
            padding: 5px;
            font-size: 16px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cart,
        .btn-back,
        .btn-buy {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-cart {
            background-color: white;
            border: 1px solid black;
        }

        .btn-cart:hover {
            background-color: #007bff;
            color: white;
        }

        .btn-back {
            background-color: green;
            color: white;
        }

        .btn-buy {
            width: 100%;
            background-color: darkblue;
            color: white;
            padding: 15px 0;
            margin-top: 20px;
        }

        .btn-buy:hover {
            background-color: #000080;
        }

        .related-products {
            margin: 50px auto;
            max-width: 1100px;
            width: 100%;
            padding: 0 10px;
        }

        .related-products h2 {
            margin-bottom: 20px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .related-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
        }

        .related-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }

        .related-item p {
            font-weight: 600;
            margin-top: 8px;
        }

        .related-item span {
            color: red;
        }

        .btn-disabled {
            background-color: gray !important;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="product-detail-container">
        <div class="product-gallery">
            <img class="main-image"
                src="<?= !empty($product['hinh_anh']) ? 'uploads/' . htmlspecialchars($product['hinh_anh']) : 'uploads/no-image.png' ?>"
                alt="<?= htmlspecialchars($product['ten_san_pham']) ?>">

            <?php if (isset($product['so_luong']) && $product['so_luong'] == 0): ?>
                <div class="sold-out">HẾT HÀNG</div>
            <?php endif; ?>

            <div class="thumbnails">
                <img src="uploads/<?= htmlspecialchars($product['hinh_anh']) ?>" onclick="changeImage(this)">
            </div>
        </div>

        <div class="product-info">
            <h1 class="product-title"><?= htmlspecialchars($product['ten_san_pham']) ?></h1>
            <p class="product-price"><?= number_format($product['gia'], 0, ',', '.') ?>đ</p>
            <p class="product-description"><?= nl2br(htmlspecialchars($product['mo_ta'])) ?></p>

            <div class="product-options">
                <?php if (isset($product['so_luong']) && $product['so_luong'] > 0): ?>
                    <form method="POST" action="add_to_cart.php" class="product-action-form">
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">

                        <!-- CHỌN SIZE -->
                        <label for="size">Chọn size:</label>
                        <select name="size" id="size" required>
                            <option value="S">S</option>
                            <option value="M" selected>M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                        </select>

                        <!-- CHỌN SỐ LƯỢNG -->
                        <div class="quantity-control">
                            <label for="quantity">Số lượng:</label>
                            <button type="button" onclick="changeQuantity(-1)">−</button>
                            <input type="number" name="quantity" id="quantity" value="1" min="1"
                                max="<?= $product['so_luong'] ?>">
                            <button type="button" onclick="changeQuantity(1)">+</button>
                            <small style="color:#555;">Còn lại: <?= $product['so_luong'] ?></small>
                        </div>

                        <div class="button-group">
                            <button type="submit" name="add_cart" class="btn-cart">
                                <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
                            </button>

                            <button type="submit" name="buy_now" formaction="add_to_cart.php" class="btn-buy">
                                <i class="fa-solid fa-bolt"></i> Mua ngay
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 8px; text-align:center; font-weight:bold;">
                        Sản phẩm hiện tại đã hết hàng
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sản phẩm liên quan -->
    <div class="related-products">
        <h2>Sản phẩm liên quan</h2>
        <div class="product-grid">
            <?php
            $sql_related = "SELECT * FROM san_pham WHERE phan_loai = '" . mysqli_real_escape_string($conn, $product['phan_loai']) . "' AND id != $id LIMIT 4";
            $related = mysqli_query($conn, $sql_related);
            while ($item = mysqli_fetch_assoc($related)) {
                echo '<div class="related-item">
                    <a href="chitietsanpham.php?id=' . $item['id'] . '">
                        <img src="uploads/' . htmlspecialchars($item['hinh_anh']) . '" alt="">
                        <p>' . htmlspecialchars($item['ten_san_pham']) . '</p>
                        <span>' . number_format($item['gia'], 0, ',', '.') . 'đ</span>
                    </a>
                </div>';
            }
            ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function changeImage(img) {
            document.querySelector('.main-image').src = img.src;
        }

        function changeQuantity(change) {
            const input = document.getElementById('quantity');
            const max = parseInt(input.max);
            let current = parseInt(input.value);
            current += change;
            if (current < 1) current = 1;
            if (current > max) current = max;
            input.value = current;
        }
    </script>
</body>

</html>