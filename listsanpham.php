<?php
include "Database/connectdb.php";

// Lấy phân loại sản phẩm (nếu có)
$phan_loai = isset($_GET['phan_loai']) ? $_GET['phan_loai'] : '';

// Truy vấn sản phẩm
if ($phan_loai != '') {
    $sql = "SELECT * FROM san_pham WHERE phan_loai = '$phan_loai'";
} else {
    $sql = "SELECT * FROM san_pham";
}
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}

// =============================
// 🖼️ LẤY BANNER HIỆN TẠI
// =============================
$banner_path = "uploads/banner-sanpham.jpg"; // banner mặc định
if (file_exists("banner_config.php")) {
    include "banner_config.php";
    if (isset($current_banner) && file_exists($current_banner)) {
        $banner_path = $current_banner;
    }
}
if (!file_exists($banner_path)) {
    $banner_path = "uploads/no-banner.png"; // fallback nếu không tồn tại
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Sản phẩm</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        /* ===== Banner ===== */
        .banner-container {
            position: relative;
            width: 100%;
            height: 300px;
            margin-top: 80px;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.15);
            background: #eee;
        }

        .banner-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(80%);
            transition: transform 0.6s ease;
        }

        .banner-container:hover img {
            transform: scale(1.05);
        }

        .banner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #fff;
        }

        .banner-overlay h1 {
            font-size: 34px;
            margin: 0;
            font-weight: 800;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.4);
        }

        .banner-overlay p {
            font-size: 18px;
            margin-top: 10px;
            text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.4);
        }

        /* ===== Danh sách sản phẩm ===== */
        h2 {
            text-align: center;
            margin-top: 100px;
            color: #333;
        }

        .product-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 20px;
            padding: 40px 80px;
        }

        .product-card {
            position: relative;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .product-card img {
            width: 100%;
            height: 230px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }

        .product-info {
            padding: 12px 14px 16px;
            text-align: left;
        }

        .product-info h3 {
            font-size: 16px;
            font-weight: 700;
            color: #222;
            margin: 8px 0;
            height: 42px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-info p {
            font-size: 17px;
            color: #e60000;
            font-weight: bold;
            margin: 4px 0 10px;
        }

        .out-of-stock {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(198, 20, 20, 0.85);
            color: #fff;
            font-size: 26px;
            font-weight: 900;
            padding: 12px 20px;
            width: 100%;
            text-align: center;
            pointer-events: none;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <!-- Banner -->
    <div class="banner-container">
        <img src="<?php echo $banner_path; ?>" alt="Banner sản phẩm">
        <div class="banner-overlay">
            <h1>Chào mừng đến với cửa hàng</h1>
            <p>Khám phá những sản phẩm mới nhất ngay hôm nay!</p>
        </div>
    </div>

    <!-- Danh sách sản phẩm -->
    <h2>Danh sách sản phẩm <?php echo $phan_loai ? "($phan_loai)" : ""; ?></h2>
    <div class="product-list">
        <?php while ($row = mysqli_fetch_assoc($result)) {
            $img = !empty($row['hinh_anh']) ? "uploads/" . htmlspecialchars($row['hinh_anh']) : "uploads/no-image.png";
            $ten = !empty($row['ten_san_pham']) ? htmlspecialchars($row['ten_san_pham']) : "Sản phẩm chưa đặt tên";
            $gia = isset($row['gia']) ? number_format($row['gia'], 0, ',', '.') : "0";
        ?>
            <div class="product-card" onclick="window.location.href='chitietsanpham.php?id=<?php echo $row['id']; ?>'">
                <img src="<?php echo $img; ?>" alt="<?php echo $ten; ?>">
                <?php if (isset($row['hang_ton']) && $row['hang_ton'] <= 0): ?>
                    <div class="out-of-stock">Hết hàng</div>
                <?php endif; ?>
                <div class="product-info">
                    <h3><?php echo $ten; ?></h3>
                    <hr>
                    <p><?php echo $gia; ?> đ</p>
                </div>
            </div>
        <?php } ?>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>