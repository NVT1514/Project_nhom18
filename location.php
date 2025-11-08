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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        .location-container {
            margin: 100px auto 20px;
            max-width: 1400px;
            padding: 0 24px 40px;
            background: linear-gradient(180deg, #f9fafc 0%, #ffffff 60%);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            animation: fadeInUp 1s ease forwards;
        }

        .navbar-menu a ::after,
        .dropdown-toggle::after {
            content: none !important;
            border: none !important;
            display: none !important;
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
            border-radius: 30px;
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
            background: white;
            color: black;
            padding: 12px 28px;
            border-color: black;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }

        .hero-btn:hover {
            transform: translateY(-3px);
            background: black;
            color: white;
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

        /* ===== STORE FILTER SECTION (NEW) ===== */
        .store-filter-section {
            padding: 40px 0;
            background-color: #ffffff;
            border-bottom: 1px solid #eee;
        }

        .filter-title {
            font-size: 24px;
            font-weight: 600;
            color: #1a2750;
            text-align: center;
            margin-bottom: 25px;
        }

        .filter-controls {
            display: flex;
            gap: 15px;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .filter-dropdown,
        .filter-search {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 16px;
            flex: 1;
        }

        .filter-btn {
            background: linear-gradient(90deg, #1a2750, #3a4a8a);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn:hover {
            background: linear-gradient(90deg, #3a4a8a, #1a2750);
            transform: translateY(-1px);
        }

        /* ===== GALLERY SECTION (NEW) ===== */
        .store-gallery-section {
            padding: 30px 0;
            background: linear-gradient(180deg, #034a9b 0%, #263445 100%);
        }

        .store-gallery-section h2 {
            color: white;
        }

        .store-gallery-section p {
            color: white;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .gallery-item {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .gallery-item:hover {
            transform: scale(1.03);
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            min-height: 250px;
            object-fit: cover;
            display: block;
        }

        .gallery-btn {
            margin-top: 30px;
            padding: 10px 30px;
        }

        /* ===== FAQ SECTION (NEW) ===== */
        .location-faq-section {
            padding: 60px 0 80px;
            background-color: #ffffff;
        }

        .location-faq-section .container {
            max-width: 900px;
        }

        .accordion-button {
            background-color: #f0f4ff !important;
            color: #1a2750 !important;
            font-weight: 600;
            padding: 15px 20px;
        }

        .accordion-button:focus {
            box-shadow: none;
            border-color: #a3a3a3;
        }

        .accordion-body {
            background-color: #fff;
            font-size: 15px;
            line-height: 1.6;
        }

        .accordion-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        /* ===== STORE SECTION (EXISTING) ===== */
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
            margin-bottom: 15px;
            font-weight: bold;
        }

        .section-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 40px;
        }

        /* ===== STORE SECTION (Đã chỉnh sửa) ===== */
        /* ... (Giữ nguyên .store-location-section và .section-title, .section-subtitle) ... */

        .store-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            /* Giảm gap để có không gian */
            padding: 0 15px;
            /* Thêm padding nếu cần */
        }

        .store-card {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            /* Quan trọng: Đảm bảo các thẻ cao bằng nhau nếu dùng Flexbox hoặc Grid*/
            display: flex;
            /* Bật Flexbox để căn chỉnh nội dung bên trong */
            flex-direction: column;
        }

        .store-image-container {
            height: 250px;
            /* Đặt chiều cao cố định cho khu vực hình ảnh */
            overflow: hidden;
            position: relative;
        }

        .store-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* Quan trọng: Hình ảnh sẽ được cắt để vừa vặn, không bị méo */
            display: block;
            transition: transform 0.3s;
        }

        .store-card:hover .store-img {
            transform: scale(1.05);
        }

        .store-info {
            padding: 15px;
            flex-grow: 1;
            /* Quan trọng: Đẩy footer xuống dưới cùng */
        }

        /* ... (Phần CSS responsive và animation giữ nguyên) ... */

        /* ===== RESPONSIVE (NEW) ===== */
        @media (max-width: 768px) {
            .filter-controls {
                flex-direction: column;
            }

            .filter-dropdown,
            .filter-search {
                width: 100%;
            }
        }

        /* ===== RESPONSIVE (EXISTING) ===== */
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
                <img src="../img/logo.png" alt="Logo" />
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
            <div class="benefit-box">
                <i class="fa-solid fa-shield-halved"></i>
                <h3>An toàn và Yên tâm</h3>
                <p>Đảm bảo nguồn gốc, chính sách đổi trả rõ ràng, hỗ trợ hậu mãi chu đáo.</p>
            </div>
        </div>
    </section>

    <section class="store-gallery-section">
        <div class="container">
            <h2 class="section-title">Không Gian Mua Sắm Hiện Đại</h2>
            <p class="section-subtitle">Tận hưởng trải nghiệm thời trang tinh tế và thoải mái.</p>

            <div class="gallery-grid">
                <div class="gallery-item"><img src="uploads/gallery_1.jpg" alt="Phòng thử đồ Clothix" class="gallery-img"></div>
                <div class="gallery-item"><img src="uploads/gallery_2.jpg" alt="Khu vực trưng bày denim" class="gallery-img"></div>
                <div class="gallery-item"><img src="uploads/gallery_3.jpg" alt="Thiết kế nội thất cửa hàng" class="gallery-img"></div>
                <div class="gallery-item"><img src="uploads/gallery_4.png" alt="Mặt tiền cửa hàng" class="gallery-img"></div>
            </div>
            <div class="text-center">
                <a href="#" class="hero-btn gallery-btn">Xem thêm hình ảnh</a>
            </div>
        </div>
    </section>
    <div class="store-filter-section">
        <div class="container">
            <h3 class="filter-title">Tìm cửa hàng Clothix gần bạn nhất</h3>
            <div class="filter-controls">
                <select id="city-filter" class="form-select filter-dropdown" aria-label="Lọc theo Tỉnh/Thành phố">
                    <option selected>— Chọn Tỉnh/Thành phố —</option>
                    <option value="Hà Nội">Hà Nội</option>
                    <option value="TP.HCM">TP. Hồ Chí Minh</option>
                    <option value="Đà Nẵng">Đà Nẵng</option>
                </select>
                <input type="text" id="search-store" class="form-control filter-search" placeholder="Nhập tên đường/quận..." aria-label="Tìm kiếm tên cửa hàng">
                <button id="search-btn" class="btn-primary filter-btn">Tìm kiếm</button>
            </div>
        </div>
    </div>
    <section id="store-section" class="store-location-section">
        <div class="container">
            <h2 class="section-title">Các Địa Chỉ Cửa Hàng Clothix</h2>
            <p class="section-subtitle">Tìm kiếm cửa hàng Clothix gần bạn nhất trên toàn quốc.</p>

            <div class="store-grid">
                <div class="store-card" data-city="Hà Nội" data-address="Tôn Đức Thắng">
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

                <div class="store-card" data-city="TP.HCM" data-address="Crescent Mall">
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

                <div class="store-card" data-city="Đà Nẵng" data-address="Lê Duẩn">
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

    <section class="location-faq-section">
        <div class="container">
            <h2 class="section-title">Giải Đáp Thắc Mắc Về Cửa Hàng</h2>

            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Tôi có thể đổi/trả hàng đã mua online tại cửa hàng không?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <strong>Có.</strong> Bạn hoàn toàn có thể mang sản phẩm đã mua qua website đến bất kỳ cửa hàng Clothix nào để thực hiện đổi/trả trong vòng 15 ngày, miễn là sản phẩm còn nguyên tem mác và hóa đơn mua hàng.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Các cửa hàng có chương trình ưu đãi dành cho thành viên không?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Tuyệt đối có! Khách hàng thành viên sẽ được tích điểm, nhận ưu đãi giảm giá độc quyền và quà tặng sinh nhật khi mua sắm trực tiếp tại hệ thống cửa hàng Clothix.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Thời gian mở cửa của các cửa hàng có đồng nhất không?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Thời gian mở cửa có thể khác nhau tùy vào vị trí (cửa hàng Flagship hay tại Trung tâm thương mại). Vui lòng kiểm tra kỹ giờ mở cửa cụ thể tại từng địa chỉ bên trên.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bắt đầu code JavaScript cho chức năng lọc/tìm kiếm
        document.addEventListener('DOMContentLoaded', function() {
            const cityFilter = document.getElementById('city-filter');
            const searchInput = document.getElementById('search-store');
            const searchButton = document.getElementById('search-btn');
            const storeCards = document.querySelectorAll('.store-card');

            function filterStores() {
                const selectedCity = cityFilter.value.toLowerCase();
                const searchText = searchInput.value.toLowerCase().trim();

                storeCards.forEach(card => {
                    const cardCity = card.getAttribute('data-city').toLowerCase();
                    const cardAddress = card.querySelector('.store-address').textContent.toLowerCase();
                    const cardName = card.querySelector('.store-name').textContent.toLowerCase();

                    const matchCity = selectedCity === '— chọn tỉnh/thành phố —' || cardCity === selectedCity;
                    const matchSearch = cardAddress.includes(searchText) || cardName.includes(searchText);

                    if (matchCity && matchSearch) {
                        card.style.display = 'block'; // Hiển thị
                    } else {
                        card.style.display = 'none'; // Ẩn
                    }
                });
            }

            // Gắn sự kiện cho Dropdown và Button
            cityFilter.addEventListener('change', filterStores);
            searchButton.addEventListener('click', filterStores);

            // Tùy chọn: Lọc khi người dùng gõ (input event)
            searchInput.addEventListener('input', filterStores);
        });
    </script>
</body>
<?php include 'lien_he.php'; ?>

</html>