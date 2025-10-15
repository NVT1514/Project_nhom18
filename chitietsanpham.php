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

// --- Kiểm tra đăng nhập ---
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['ten_san_pham']) ?></title>
    <link rel="stylesheet" href="../css/chitietsanpham.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-detail-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 50px auto;
            max-width: 1100px;
        }

        .product-gallery img.main-image {
            width: 350px;
            height: 350px;
            border-radius: 10px;
            object-fit: cover;
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

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cart,
        .btn-wishlist,
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

        .btn-wishlist {
            color: red;
            border: 1px solid red;
            background-color: white;
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
        }

        .related-products h2 {
            margin-bottom: 20px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
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
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="product-detail-container">
        <!-- Hình ảnh sản phẩm -->
        <div class="product-gallery">
            <img class="main-image" src="<?= !empty($product['hinh_anh']) ? $product['hinh_anh'] : '../uploads/no-image.png' ?>"
                alt="<?= htmlspecialchars($product['ten_san_pham']) ?>">
            <div class="thumbnails">
                <img src="<?= $product['hinh_anh'] ?>" onclick="changeImage(this)">
                <img src="../uploads/sample1.jpg" onclick="changeImage(this)">
                <img src="../uploads/sample2.jpg" onclick="changeImage(this)">
            </div>
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="product-info">
            <h1 class="product-title"><?= htmlspecialchars($product['ten_san_pham']) ?></h1>
            <p class="product-price"><?= number_format($product['gia'], 0, ',', '.') ?>đ</p>

            <div class="product-options">
                <label>Size:</label>
                <select>
                    <option>S</option>
                    <option>M</option>
                    <option>L</option>
                    <option>XL</option>
                </select>

                <label>Màu sắc:</label>
                <select>
                    <option>Đen</option>
                    <option>Trắng</option>
                    <option>Xám</option>
                </select>
            </div>

            <p class="product-description"><?= nl2br(htmlspecialchars($product['mo_ta'])) ?></p>

            <!-- Nút thêm và mua -->
            <div class="button-group">
                <form method="POST" action="add_to_cart.php" style="display:inline-block;">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn-cart">Thêm vào giỏ</button>
                </form>

                <button class="btn-wishlist"><i class="fa fa-heart"></i> Yêu thích</button>

                <a href="maincustomer.php">
                    <button type="button" class="btn-back">Quay lại</button>
                </a>
            </div>

            <form method="POST" action="add_to_cart.php" style="margin-top: 15px;">
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="redirect" value="cart.php">
                <button type="submit" class="btn-buy">MUA NGAY</button>
            </form>
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
                            <img src="' . $item['hinh_anh'] . '" alt="">
                            <p>' . $item['ten_san_pham'] . '</p>
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
    </script>
</body>

</html>