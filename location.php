<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống cửa hàng Clothix</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        .location-container {
            margin: 100px auto 60px;
            max-width: 1400px;
            padding: 0 24px 40px;
            background: linear-gradient(180deg, #f9fafc 0%, #ffffff 60%);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            animation: fadeInUp 1s ease forwards;
        }

        /* ===== BREADCRUMB ===== */
        .breadcrumb {
            margin-bottom: 30px;
            font-size: 16px;
            color: #000000ff;
        }

        .breadcrumb a {
            text-decoration: none;
            color: #777;
            transition: color 0.2s;
        }

        .breadcrumb a:hover {
            color: #2196f3;
        }

        /* ===== HERO SECTION ===== */
        .location-hero {
            display: flex;
            gap: 24px;
            margin-bottom: 32px;
            opacity: 0;
            animation: fadeInUp 1s ease forwards;
            animation-delay: 0.2s;
        }

        .location-hero-img,
        .location-hero-img2 {
            flex: 1;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .location-hero-img img,
        .location-hero-img2 img {
            width: 85%;
            height: 320px;
            object-fit: cover;
            border-radius: 15px;
            transition: transform 1s ease-out, filter 0.5s ease;
        }

        .location-hero-img:hover img,
        .location-hero-img2:hover img {
            transform: scale(1.1) rotate(-1deg);
            filter: brightness(1.05);
        }

        .location-hero-center {
            flex: 1.2;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            min-height: 320px;
        }

        .location-hero-center img {
            width: 150px;
            margin-bottom: 18px;
        }

        .location-hero-center h2 {
            font-size: 32px;
            color: #1a2750;
            margin: 0;
            padding-bottom: 8px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .location-hero-center h4 {
            font-size: 16px;
            color: #a3a3a3ff;
            margin-top: 30px;
            font-weight: 600;
        }

        .location-hero-center hr {
            width: 60px;
            border: 1px solid #e3e3e3;
            margin: 18px 0;
        }

        .location-hero-center p {
            font-size: 18px;
            font-weight: 600;
            color: #444;
            text-align: center;
            line-height: 1.4;
        }

        .hero-btn {
            margin-top: 20px;
            display: inline-block;
            background: linear-gradient(90deg, #1a2750, #3a4a8a);
            color: white;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }

        .hero-btn:hover {
            transform: translateY(-3px);
            background: linear-gradient(90deg, #3a4a8a, #1a2750);
        }

        /* ===== BENEFITS BAR ===== */
        .location-benefits {
            display: flex;
            gap: 18px;
            margin-top: 24px;
            flex-wrap: wrap;
            opacity: 0;
            animation: fadeInUp 1s ease forwards;
            animation-delay: 0.4s;
        }

        .benefit-box {
            flex: 1 1 220px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .benefit-box:hover {
            transform: translateY(-4px);
            background: #f0f4ff;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .benefit-box i {
            font-size: 28px;
            color: #1a2750;
        }

        .new-label {
            background: #222;
            color: #fff;
            font-size: 12px;
            border-radius: 6px;
            padding: 2px 8px;
            margin-left: 8px;
            font-weight: bold;
        }

        /* ===== BENEFIT SECTION ===== */
        section.location-benefits {
            max-width: 1200px;
            margin: 80px auto;
            text-align: center;
            flex-direction: column;
            gap: 20px;
        }

        section.location-benefits h2 {
            font-size: 2rem;
            margin-bottom: 40px;
            font-weight: 600;
            color: #1a2750;
        }

        .benefit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 50px;
            padding: 0 20px;
        }

        .benefit-grid .benefit-box {
            justify-content: center;
            text-align: center;
            padding: 30px 20px;
        }

        .benefit-grid .benefit-box i {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .benefit-grid .benefit-box h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        /* ===== STORE SECTION ===== */
        .store-location-section {
            padding: 60px 0;
            background-color: #f8f8f8;
            opacity: 0;
            animation: fadeInUp 1s ease forwards;
            animation-delay: 0.6s;
        }

        .store-location-section .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .section-title {
            text-align: center;
            font-size: 30px;
            color: #1a2750;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .section-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 40px;
        }

        .store-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 70px;
        }

        .store-card {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .store-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .store-image-container {
            height: 200px;
            overflow: hidden;
        }

        .store-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease, filter 0.4s ease;
        }

        .store-card:hover .store-img {
            transform: scale(1.05);
            filter: brightness(1.05);
        }

        .store-info {
            padding: 20px;
        }

        .store-name {
            font-size: 18px;
            color: #021d49;
            font-weight: bold;
            margin: 0 0 10px;
        }

        .store-address,
        .store-hours {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
        }

        .store-address i,
        .store-hours i {
            color: #999;
            margin-right: 8px;
        }

        .btn-map {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            padding: 10px 14px;
            color: #fff;
            font-weight: 600;
            background: linear-gradient(90deg, #1a2750, #3a4a8a);
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s;
        }

        .btn-map:hover {
            background: linear-gradient(90deg, #3a4a8a, #1a2750);
            transform: translateY(-2px);
        }

        .btn-map i {
            transition: transform 0.3s;
        }

        .btn-map:hover i {
            transform: translateX(5px);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1100px) {
            .location-hero {
                flex-direction: column;
            }

            .location-hero-img img,
            .location-hero-img2 img {
                height: 220px;
            }

            .location-hero-center {
                min-height: 220px;
            }
        }

        @media (max-width: 700px) {
            .location-benefits {
                flex-direction: column;
            }
        }

        /* ===== ANIMATION ===== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="location-container">
        <nav class="breadcrumb">
            <a href="maincustomer.php">Trang chủ</a> / Hệ thống cửa hàng
        </nav>

        <div class="location-hero">
            <div class="location-hero-img">
                <img src="../img/Editorial.png" alt="Cửa hàng 1">
            </div>

            <div class="location-hero-center">
                <img src="../img/Clothix.jpg" alt="Logo" />
                <h2>HỆ THỐNG</h2>
                <h2 style="color:#1a2750;">CỬA HÀNG CLOTHIX</h2>
                <h4>Trải nghiệm thời trang hiện đại – Tinh tế trong từng chi tiết</h4>
                <hr>
                <p>Đa Dạng Sản Phẩm<br>Mua Sắm Tiện Lợi</p>
                <a href="#store-section" class="hero-btn">Khám phá ngay</a>
            </div>

            <div class="location-hero-img2">
                <img src="../img/Minimalist.png" alt="Cửa hàng 2">
            </div>
        </div>

        <div class="location-benefits">
            <div class="benefit-box"><i class="fa fa-rotate-left"></i> Đổi trả trong 15 ngày</div>
            <div class="benefit-box"><i class="fa fa-clock"></i> Bảo hành trong 30 ngày</div>
            <div class="benefit-box"><i class="fa fa-tag"></i> Hàng mới mỗi ngày <span class="new-label">NEW</span></div>
            <div class="benefit-box"><i class="fa fa-phone"></i> Hotline - 028 7306 6060</div>
        </div>
    </div>

    <!-- LỢI ÍCH -->
    <section class="location-benefits">
        <h2>Tại sao chọn mua tại cửa hàng Clothix?</h2>
        <div class="benefit-grid">
            <div class="benefit-box">
                <i class="fa-solid fa-shirt"></i>
                <h3>Trải nghiệm sản phẩm thật</h3>
                <p>Chạm, thử và cảm nhận chất liệu cao cấp trước khi mua hàng.</p>
            </div>
            <div class="benefit-box">
                <i class="fa-solid fa-user-tie"></i>
                <h3>Tư vấn phong cách chuyên nghiệp</h3>
                <p>Đội ngũ stylist hỗ trợ phối đồ theo phong cách và dáng người bạn.</p>
            </div>
            <div class="benefit-box">
                <i class="fa-solid fa-star"></i>
                <h3>Ưu đãi thành viên</h3>
                <p>Tích điểm, nhận quà và giảm giá độc quyền khi mua tại cửa hàng.</p>
            </div>
        </div>
    </section>

    <!-- ĐỊA CHỈ CỬA HÀNG -->
    <section id="store-section" class="store-location-section">
        <div class="container">
            <h2 class="section-title">Các Địa Chỉ Cửa Hàng Clothix</h2>
            <p class="section-subtitle">Tìm kiếm cửa hàng Clothix gần bạn nhất trên toàn quốc.</p>

            <div class="store-grid">
                <div class="store-card">
                    <div class="store-image-container">
                        <img src="uploads/location_HN.jpg" alt="Cửa hàng Clothix Hà Nội" class="store-img">
                    </div>
                    <div class="store-info">
                        <h3 class="store-name">Clothix Flagship Store - Hà Nội</h3>
                        <p class="store-address"><i class="fas fa-map-marker-alt"></i> Số 54, Đường Tôn Đức Thắng, Quận Đống Đa, Hà Nội.</p>
                        <p class="store-hours"><i class="far fa-clock"></i> Mở cửa: 9:00 - 22:00 (Thứ Hai - Chủ Nhật)</p>
                        <a href="https://maps.app.goo.gl/..." target="_blank" class="btn-map">Xem bản đồ <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="store-card">
                    <div class="store-image-container">
                        <img src="uploads/location_HCM.jpg" alt="Cửa hàng Clothix TP.HCM" class="store-img">
                    </div>
                    <div class="store-info">
                        <h3 class="store-name">Clothix Crescent Mall - TP.HCM</h3>
                        <p class="store-address"><i class="fas fa-map-marker-alt"></i> Tầng L2, Crescent Mall, 101 Tôn Dật Tiên, Quận 7, TP.HCM.</p>
                        <p class="store-hours"><i class="far fa-clock"></i> Mở cửa: 9:30 - 21:30 (Thứ Hai - Chủ Nhật)</p>
                        <a href="https://maps.app.goo.gl/..." target="_blank" class="btn-map">Xem bản đồ <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="store-card">
                    <div class="store-image-container">
                        <img src="uploads/location_DN.jpg" alt="Cửa hàng Clothix Đà Nẵng" class="store-img">
                    </div>
                    <div class="store-info">
                        <h3 class="store-name">Clothix Store - Đà Nẵng</h3>
                        <p class="store-address"><i class="fas fa-map-marker-alt"></i> 156, Đường Lê Duẩn, Quận Thanh Khê, Đà Nẵng.</p>
                        <p class="store-hours"><i class="far fa-clock"></i> Mở cửa: 9:00 - 22:00 (Thứ Hai - Chủ Nhật)</p>
                        <a href="https://maps.app.goo.gl/..." target="_blank" class="btn-map">Xem bản đồ <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>

</html>