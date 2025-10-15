<?php
include "Database/connectdb.php";
include "Database/function.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}

$products = get_all_products();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Trang khách hàng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        /*MAIN-CONTENT*/
        .content {
            width: 100%;
            max-width: 100vw;
            margin: 0 auto;
            margin-top: 90px;
        }

        .swiper {
            width: 100%;
            max-width: 1400px;
            height: 500px;
            margin: 0 auto;
            border-radius: 28px;
            overflow: hidden;
            background: white;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            padding-top: 0;
        }

        /* Swiper container */
        .swiper-button-next,
        .swiper-button-prev {
            color: #565151ff;
            background: #c4c3c3ff;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.85;
            transition: background 0.2s, color 0.2s;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: #ffffffff;
            color: #000000ff;
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 18px;
            font-weight: bold;
        }

        h2 {
            text-align: center;
            margin: 30px 0 20px;
            font-size: 28px;
            color: #222;
            font-weight: 600;
        }

        /* Lưới sản phẩm */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 24px;
            padding: 20px 40px;
        }

        /* Card sản phẩm */
        .product-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }

        /* Ảnh sản phẩm */
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        /* Tên sản phẩm */
        .product-card h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #111;
        }

        /* Nhóm nút */
        .button-group {
            margin-top: 12px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Nút */
        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .btn-detail {
            background: #007bff;
            color: #fff;
        }

        .btn-detail:hover {
            background: #0056b3;
            transform: scale(1.05);
        }

        .btn-cart {
            background: #28a745;
            color: #fff;
        }

        .btn-cart:hover {
            background: #1e7e34;
            transform: scale(1.05);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <!-- MAIN CONTENT -->
    <div class="content">
        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <img src="../img/banner.jpg" alt="Banner 1">
                </div>
                <div class="swiper-slide">
                    <img src="../img/banner1.jpg" alt="Banner 2">
                </div>
                <div class="swiper-slide">
                    <img src="../img/banner2.jpg" alt="Banner 3">
                </div>
                <!-- Thêm các slide khác nếu muốn -->
            </div>
            <!-- Nút điều hướng -->
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <!-- Dots -->
            <div class="swiper-pagination"></div>
        </div>
    </div>
    <h2>Danh sách sản phẩm</h2>
    <div class="product-grid">
        <?php if (!empty($products)) : ?>
            <?php foreach ($products as $sp): ?>
                <div class="product-card">
                    <?php if (!empty($sp['hinh_anh'])): ?>
                        <img src="<?= $sp['hinh_anh'] ?>" alt="<?= htmlspecialchars($sp['ten_san_pham']) ?>">
                    <?php else: ?>
                        <img src="../uploads/no-image.png" alt="Không có ảnh">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($sp['ten_san_pham']) ?></h3>
                    <p style="color:red; font-size: 18px;font-weight: bold;">Giá: <?= number_format($sp['gia'], 0, ',', '.') ?>đ</p>

                    <div class="button-group">
                        <a href="chitietsanpham.php?id=<?= $sp['id']; ?>">
                            <button class="btn btn-detail">Xem chi tiết sản phẩm </button>
                        </a>
                        <button class="btn btn-cart">Thêm vào giỏ</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: span 5; text-align:center;">Chưa có sản phẩm nào</p>
        <?php endif; ?>
    </div>


    <!-- SCRIPT_BANNER -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        const swiper = new Swiper('.swiper', {
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            autoplay: false
        });
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>