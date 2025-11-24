<?php
include "Database/connectdb.php";
include "Database/function.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}

// Lấy dữ liệu sản phẩm
$products = get_all_products();
$new_products = lay_san_pham_moi();
$best_selling = lay_san_pham_ban_chay();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Trang khách hàng</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        body {
            background-color: #fff;
            font-family: "Poppins", sans-serif;
        }

        .main-wrapper {
            max-width: 1480px;
            margin: 0 auto;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.03);
        }

        .navbar-menu a ::after,
        .dropdown-toggle::after {
            content: none !important;
            border: none !important;
            display: none !important;
        }

        h2.section-title {
            text-align: center;
            font-weight: 700;
            margin-bottom: 40px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        section {
            padding: 30px 0;
        }

        .section-banner {
            margin-top: 50px;
        }

        /* BANNER */
        .section-banner .swiper {
            height: 500px;
        }

        .section-banner img {
            width: 100%;
            height: 500px;
            object-fit: cover;
        }

        /* CARD CHUNG */
        .voucher-list {
            scroll-behavior: smooth;
            overflow-x: auto;
            scrollbar-width: none;
            /* Ẩn thanh cuộn Firefox */
        }

        .voucher-list::-webkit-scrollbar {
            display: none;
            /* Ẩn thanh cuộn Chrome */
        }

        .voucher-card {
            flex: 0 0 calc(25% - 12px);
            /* 4 thẻ trên 1 hàng */
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            border: 1px solid #e5e5e5;
            transition: all 0.3s ease;
            min-width: 300px;
        }

        .voucher-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .voucher-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .text-uppercase {
            text-transform: uppercase !important;
            color: #253bffff;
        }

        .voucher-left {
            flex: 1;
            padding-right: 10px;
        }

        .voucher-right {
            min-width: 130px;
        }

        .voucher-code {
            font-size: 0.9rem;
        }

        .btn-copy {
            font-size: 0.85rem;
            border-radius: 6px;
            padding: 5px 12px;
        }

        .voucher-card::before,
        .voucher-card::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 10px;
            background: radial-gradient(circle at center, #fff 3px, transparent 3px);
            background-size: 10px 20px;
        }

        .voucher-card::before {
            left: -5px;
        }

        .voucher-card::after {
            right: -5px;
        }

        .voucher-nav button {
            border-radius: 50%;
            width: 42px;
            height: 42px;
            transition: all 0.3s ease;
        }

        .voucher-nav button:hover {
            background-color: #000;
            color: #fff;
        }

        @media (max-width: 992px) {
            .voucher-card {
                flex: 0 0 calc(33.33% - 10px);
            }
        }

        @media (max-width: 768px) {
            .voucher-card {
                flex: 0 0 calc(50% - 10px);
            }
        }

        @media (max-width: 576px) {
            .voucher-card {
                flex: 0 0 calc(90%);
            }
        }

        /* DANH MỤC GIÀY */
        .section-category img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            border-radius: 10px;
        }

        .category-card {
            transition: all 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .category-card h6 {
            margin-top: 10px;
            font-size: 0.95rem;
        }

        .category-card p {
            font-size: 0.8rem;
            color: #6c757d;
        }


        /* SẢN PHẨM */
        .product-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .overlay-icons {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            opacity: 0;
            transition: 0.3s ease;
        }

        .product-card:hover .overlay-icons {
            opacity: 1;
            bottom: 15px;
        }

        .icon-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.9);
            color: #222;
            border-radius: 8px;
            font-size: 1rem;
            transition: 0.3s;
            text-decoration: none;
        }

        .icon-btn:hover {
            background: #0d6efd;
            color: #fff;
        }

        .bg-primary {
            background: linear-gradient(180deg, #263445 0%, #1a6dcc 100%) !important;
        }

        /* DANH MỤC GIÀY - PHONG CÁCH MỚI */
        .section-shoes {
            background: linear-gradient(180deg, #f8f9fa 0%, #fff 100%);
        }

        .shoe-card {
            height: 300px;
            position: relative;
            cursor: pointer;
            transition: transform 0.5s ease, box-shadow 0.3s ease;
        }

        .shoe-card img {
            transition: transform 0.6s ease;
            object-fit: cover;
        }

        .shoe-card:hover img {
            transform: scale(1.1);
        }

        .shoe-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.8) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .shoe-card:hover .shoe-overlay {
            opacity: 1;
        }

        .shoe-category h3 {
            border-left: 5px solid #182dd0ff;
            padding-left: 12px;
        }

        .text-primary {
            color: linear-gradient(180deg, #263445 0%, #1a6dcc 100%) !important;
        }

        .shoe-card {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }


        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        /* SWIPER NÚT */
        .swiper-button-next,
        .swiper-button-prev {
            color: #000;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            top: 50%;
            transform: translateY(-50%);
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 20px;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: #000;
            color: #fff;
        }

        @media (max-width: 768px) {

            .swiper-button-next,
            .swiper-button-prev {
                display: none;
            }
        }

        .btn-view-all {
            display: block;
            width: fit-content;
            margin: 30px auto 0;
            padding: 8px 24px;
            font-weight: 600;
        }

        .btn-outline-primary {
            border-width: 2px;
        }

        /* --- Nút Xem tất cả (đen trắng) --- */
        .btn-outline-primary {
            color: #000 !important;
            /* chữ đen */
            border-color: #000 !important;
            /* viền đen */
            background-color: #fff !important;
            /* nền trắng */
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            color: #fff !important;
            /* chữ trắng khi hover */
            background-color: #000 !important;
            /* nền đen khi hover */
            border-color: #000 !important;
        }


        /* SECTION FEATURE */
        .section-feature {
            background: url('#') center/cover no-repeat;
            position: relative;
            color: #fff;
            padding-top: 0;
            /* ĐÃ CHỈNH SỬA */
        }

        .section-feature::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, #034a9b 0%, #263445 100%);
        }

        .section-feature .container {
            position: relative;
            z-index: 2;
        }

        .nav-tabs .nav-link {
            border-bottom: none;
            color: #fff;
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(180deg, #263445 0%, #1a6dcc 100%);
            color: #fff;
        }

        .out-of-stock {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: #fff;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        /* SECTION MID BANNER */
        .section-mid-banner {
            position: relative;
            overflow: hidden;
            padding-bottom: 0;
            /* ĐÃ CHỈNH SỬA */
        }

        .section-mid-banner .banner-wrapper img {
            filter: brightness(0.75);
            transition: transform 0.8s ease;
        }

        .section-mid-banner .banner-wrapper:hover img {
            transform: scale(1.05);
        }

        .section-mid-banner .banner-text {
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.7);
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include 'header.php'; ?>

        <section class="section-banner">
            <div class="swiper bannerSwiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide"><img src="Img/banner_mau_v4.png" alt=""></div>
                    <div class="swiper-slide"><img src="Img/banner1.jpg" alt=""></div>
                    <div class="swiper-slide"><img src="Img/banner_mau_v5.png" alt=""></div>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </section>

        <?php include 'bar.php'; ?>

        <section class="section-voucher py-5" style="background-color:#f8f9fa;">
            <div class="container">
                <h2 class="section-title text-uppercase fw-bold text-center mb-4">Voucher khuyến mãi</h2>

                <div class="voucher-wrapper position-relative">
                    <div class="voucher-list d-flex flex-nowrap overflow-hidden" id="voucherList">
                        <?php
                        $today = date('Y-m-d');
                        $result = mysqli_query($conn, "SELECT * FROM vouchers WHERE ngay_het_han >= '$today' ORDER BY ngay_het_han ASC");

                        if ($result && mysqli_num_rows($result) > 0):
                            while ($vc = mysqli_fetch_assoc($result)):
                                $ma = htmlspecialchars($vc['ma_voucher']);
                                $mo_ta = htmlspecialchars($vc['mo_ta'] ?: 'Không có mô tả');
                                $giam = intval($vc['giam_phan_tram']);
                                $het_han = htmlspecialchars($vc['ngay_het_han']);
                        ?>
                                <div class="voucher-card me-3">
                                    <div class="voucher-inner">
                                        <div class="voucher-left">
                                            <h6 class="fw-bold text-uppercase mb-1">Voucher</h6>
                                            <h3 class="fw-bold text-primary mb-0"><?= $giam ?>%</h3>
                                            <p class="mb-1 text-muted"><?= $mo_ta ?></p>
                                            <small class="text-secondary">Hết hạn: <?= $het_han ?></small>
                                        </div>
                                        <div class="voucher-right text-end">
                                            <p class="text-muted mb-1">Đơn từ <?= rand(299, 999) ?>K</p>
                                            <div class="voucher-code">Nhập mã: <span class="fw-semibold"><?= $ma ?></span></div>
                                            <button class="btn btn-dark btn-copy mt-2" data-code="<?= $ma ?>">Sao chép</button>
                                            <div class="copy-alert text-success fw-semibold small mt-1" style="display:none;">✅ Đã sao chép!</div>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            endwhile;
                        else:
                            echo "<p class='text-center text-muted'>Hiện chưa có voucher nào khả dụng</p>";
                        endif;
                        ?>
                    </div>

                    <!-- Nút điều hướng -->
                    <div class="voucher-nav text-center mt-4">
                        <button id="prevBtn" class="btn btn-outline-dark me-2">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button id="nextBtn" class="btn btn-outline-dark">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>



        <section class="section-mid-banner">
            <div class="container-fluid p-0">
                <div class="banner-wrapper position-relative">
                    <img src="Img/banner_mau_v3.png" alt="Retro Sports Banner" class="w-100" style="object-fit: cover; height: 480px;">
                    <div class="banner-text position-absolute top-50 start-50 translate-middle text-center text-white">
                        <h1 class="fw-bold display-5">RETRO VIBES - FRESH STYLES</h1>
                        <p class="fs-4">Sống năng động – Mặc phong cách</p>
                        <p class="fw-semibold">Fall Collection 2025</p>
                        <a href="sanpham.php?bo=retro" class="btn btn-light mt-3 px-4 py-2">Xem Bộ Sưu Tập</a>
                    </div>
                </div>
            </div>
        </section>
        <section class="section-feature text-center">
            <div class="container">
                <ul class="nav nav-tabs justify-content-center mb-4" id="productTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" id="new-tab" data-bs-toggle="tab" data-bs-target="#new-products">Hàng mới</button></li>
                    <li class="nav-item"><button class="nav-link" id="best-tab" data-bs-toggle="tab" data-bs-target="#best-products">Bán chạy</button></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="new-products">
                        <div class="swiper mySwiperNew">
                            <div class="swiper-wrapper">
                                <?php foreach ($new_products as $sp): ?>
                                    <div class="swiper-slide">
                                        <div class="card product-card position-relative">
                                            <div class="position-relative">
                                                <img src="uploads/<?= htmlspecialchars($sp['hinh_anh'] ?? 'no-image.png') ?>">
                                                <div class="badge bg-primary position-absolute top-0 end-0 m-2 px-3 py-1">HÀNG MỚI</div>
                                                <div class="overlay-icons d-flex justify-content-center">
                                                    <a href="chitietsanpham.php?id=<?= $sp['id'] ?>" class="icon-btn me-2"><i class="fa-solid fa-cart-shopping"></i></a>
                                                    <a href="#" class="icon-btn" data-id="<?= $sp['id'] ?>"><i class="fa-solid fa-eye"></i></a>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <h5 class="card-title text-truncate"><?= htmlspecialchars($sp['ten_san_pham']) ?></h5>
                                                <p class="text-danger fw-bold"><?= number_format($sp['gia'], 0, ',', '.') ?>đ</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                        <a href="sanpham.php?type=new" class="btn btn-outline-primary btn-view-all">Xem tất cả →</a>
                    </div>

                    <div class="tab-pane fade" id="best-products">
                        <div class="swiper mySwiperBest">
                            <div class="swiper-wrapper">
                                <?php foreach ($best_selling as $sp): ?>
                                    <div class="swiper-slide">
                                        <div class="card product-card position-relative">
                                            <div class="position-relative">
                                                <img src="uploads/<?= htmlspecialchars($sp['hinh_anh'] ?? 'no-image.png') ?>">
                                                <div class="overlay-icons d-flex justify-content-center">
                                                    <a href="chitietsanpham.php?id=<?= $sp['id'] ?>" class="icon-btn me-2"><i class="fa-solid fa-cart-shopping"></i></a>
                                                    <a href="#" class="icon-btn" data-id="<?= $sp['id'] ?>"><i class="fa-solid fa-eye"></i></a>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <h5 class="card-title text-truncate"><?= htmlspecialchars($sp['ten_san_pham']) ?></h5>
                                                <p class="text-danger fw-bold"><?= number_format($sp['gia'], 0, ',', '.') ?>đ</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                        <a href="sanpham.php?type=best" class="btn btn-outline-primary btn-view-all">Xem tất cả →</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-shoes py-5 position-relative">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="section-title text-uppercase fw-bold text-dark">Bộ Sưu Tập Giày</h2>
                    <p class="text-muted">Khám phá phong cách thời thượng và năng động của từng dòng giày nổi bật.</p>
                </div>

                <?php
                $sql_dm = "SELECT * FROM phan_loai_san_pham WHERE loai_chinh = 'Giày' AND trang_thai = 'Đang sử dụng'";
                $result_dm = mysqli_query($conn, $sql_dm);

                if ($result_dm && mysqli_num_rows($result_dm) > 0) {
                    while ($dm = mysqli_fetch_assoc($result_dm)) {
                        $id_dm = $dm['id'];
                        $ten_dm = htmlspecialchars($dm['ten_phan_loai']);

                        echo "<div class='shoe-category mb-5'>";
                        echo "<div class='d-flex justify-content-between align-items-center mb-4'>";
                        echo "<h3 class='fw-bold text-primary text-uppercase'>$ten_dm</h3>";
                        echo "<a href='sanpham.php?phanloai=$id_dm' class='btn btn-outline-primary btn-sm'>Xem tất cả →</a>";
                        echo "</div>";

                        $sql_sp = "SELECT * FROM san_pham WHERE phan_loai_id = $id_dm LIMIT 4";
                        $result_sp = mysqli_query($conn, $sql_sp);

                        if ($result_sp && mysqli_num_rows($result_sp) > 0) {
                            echo '<div class="row g-4">';
                            $i = 0;
                            while ($sp = mysqli_fetch_assoc($result_sp)) {
                                $img = htmlspecialchars($sp['hinh_anh'] ?? 'no-image.png');
                                $name = htmlspecialchars($sp['ten_san_pham']);
                                $price = number_format($sp['gia'], 0, ',', '.');
                                $delay = $i * 100;

                                echo "
                        <div class='col-6 col-md-3'>
                            <div class='shoe-card position-relative overflow-hidden rounded-4' style='animation-delay: {$delay}ms'>
                                <img src='uploads/$img' alt='$name' class='w-100 h-100 object-fit-cover'>
                                <div class='shoe-overlay d-flex flex-column justify-content-center align-items-center text-center p-3'>
                                    <h5 class='fw-bold text-white mb-2'>$name</h5>
                                    <p class='text-light mb-3'>{$price}đ</p>
                                    <a href='chitietsanpham.php?id={$sp['id']}' class='btn btn-light btn-sm px-3'>Xem chi tiết</a>
                                </div>
                            </div>
                        </div>";
                                $i++;
                            }
                            echo '</div>';
                        } else {
                            echo "<p class='text-muted'>Hiện chưa có sản phẩm nào trong danh mục này.</p>";
                        }

                        echo "</div>";
                    }
                } else {
                    echo "<p class='text-muted text-center'>Hiện chưa có danh mục giày nào được thêm.</p>";
                }
                ?>
            </div>
        </section>



        <section class="section-all">
            <div class="container">
                <h2 class="section-title">Tất cả sản phẩm</h2>
                <div class="row g-4 justify-content-center">
                    <?php foreach ($products as $sp): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card product-card position-relative">
                                <div class="position-relative overflow-hidden">
                                    <img src="uploads/<?= htmlspecialchars($sp['hinh_anh'] ?? 'no-image.png') ?>">
                                    <?php if (intval($sp['so_luong'] ?? 1) === 0): ?>
                                        <div class="out-of-stock">Hết hàng</div>
                                    <?php endif; ?>
                                    <div class="overlay-icons d-flex justify-content-center">
                                        <a href="chitietsanpham.php?id=<?= $sp['id'] ?>" class="icon-btn me-2"><i class="fa-solid fa-cart-shopping"></i></a>
                                        <a href="#" class="icon-btn" data-id="<?= $sp['id'] ?>"><i class="fa-solid fa-eye"></i></a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title text-truncate"><?= htmlspecialchars($sp['ten_san_pham']) ?></h5>
                                    <p class="text-danger fw-bold"><?= number_format($sp['gia'], 0, ',', '.') ?>đ</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php include 'footer.php'; ?>
    </div>
    <?php include 'lien_he.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        new Swiper('.bannerSwiper', {
            loop: true,
            autoplay: {
                delay: 4000
            },
            pagination: {
                el: '.swiper-pagination'
            }
        });

        function setupSwiper(cls) {
            return new Swiper(cls, {
                slidesPerView: 5,
                spaceBetween: 20,
                navigation: {
                    nextEl: cls + ' .swiper-button-next',
                    prevEl: cls + ' .swiper-button-prev'
                },
                loop: false,
                breakpoints: {
                    0: {
                        slidesPerView: 1
                    },
                    576: {
                        slidesPerView: 2
                    },
                    768: {
                        slidesPerView: 3
                    },
                    1200: {
                        slidesPerView: 5
                    }
                }
            });
        }
        setupSwiper('.mySwiperNew');
        setupSwiper('.mySwiperBest');

        // Copy mã voucher
        document.querySelectorAll('.btn-copy').forEach(btn => {
            btn.addEventListener('click', () => {
                const code = btn.dataset.code;
                navigator.clipboard.writeText(code).then(() => {
                    btn.classList.add('copied');
                    btn.innerHTML = "✅ Đã sao chép";
                    const alert = btn.nextElementSibling;
                    alert.style.display = 'block';
                    setTimeout(() => {
                        btn.classList.remove('copied');
                        btn.innerHTML = '<i class="fa-solid fa-copy me-1"></i> Sao chép mã';
                        alert.style.display = 'none';
                    }, 2000);
                });
            });
        });

        // Xem nhanh sản phẩm
        document.addEventListener('click', e => {
            const btn = e.target.closest('.icon-btn[data-id]');
            if (!btn) return;
            e.preventDefault();
            fetch(`gioithieusanpham.php?id=${btn.dataset.id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const p = data.data;
                        document.getElementById('quickViewName').textContent = p.ten_san_pham;
                        document.getElementById('quickViewImage').src = p.hinh_anh;
                        document.getElementById('quickViewPrice').textContent = p.gia;
                        document.getElementById('quickViewDesc').textContent = p.mo_ta;
                        new bootstrap.Modal(document.getElementById('quickViewModal')).show();
                    } else alert('Không thể tải thông tin sản phẩm.');
                })
                .catch(() => alert('Lỗi khi kết nối đến server.'));
        });
    </script>

    <script>
        document.querySelectorAll('.btn-copy').forEach(btn => {
            btn.addEventListener('click', () => {
                const code = btn.dataset.code;
                navigator.clipboard.writeText(code).then(() => {
                    const alert = btn.nextElementSibling;
                    alert.style.display = 'block';
                    setTimeout(() => alert.style.display = 'none', 1500);
                });
            });
        });

        const list = document.getElementById('voucherList');
        document.getElementById('nextBtn').addEventListener('click', () => {
            list.scrollBy({
                left: list.offsetWidth,
                behavior: 'smooth'
            });
        });
        document.getElementById('prevBtn').addEventListener('click', () => {
            list.scrollBy({
                left: -list.offsetWidth,
                behavior: 'smooth'
            });
        });
    </script>

    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Giới thiệu sản phẩm</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-5 text-center">
                            <img id="quickViewImage" src="" class="img-fluid rounded shadow-sm" alt="">
                        </div>
                        <div class="col-md-7">
                            <h4 id="quickViewName" class="fw-bold mb-3"></h4>
                            <p id="quickViewPrice" class="text-danger fw-bold fs-5 mb-3"></p>
                            <p id="quickViewDesc" class="text-muted"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>