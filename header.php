<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "Database/connectdb.php";

/* 
-----------------------------------------------------
 LẤY DỮ LIỆU PHÂN LOẠI SẢN PHẨM THEO LOẠI CHÍNH
-----------------------------------------------------
*/
$sql_loai = "
    SELECT loai_chinh, ten_phan_loai
    FROM phan_loai_san_pham
    WHERE trang_thai = 'Đang sử dụng'
    ORDER BY 
        FIELD(loai_chinh, 'Quần','Áo','Giày','Khác'),
        ten_phan_loai ASC
";
$result_loai = mysqli_query($conn, $sql_loai);

$phan_loai_nhom = [];
while ($row = mysqli_fetch_assoc($result_loai)) {
    $phan_loai_nhom[$row['loai_chinh']][] = $row['ten_phan_loai'];
}

/* 
-----------------------------------------------------
 LẤY DANH SÁCH SẢN PHẨM MỚI TRONG 1 NGÀY
-----------------------------------------------------
*/
$sql_new = "
    SELECT DISTINCT phan_loai 
    FROM san_pham 
    WHERE ngay_tao >= DATE_SUB(NOW(), INTERVAL 1 DAY)
";
$result_new = mysqli_query($conn, $sql_new);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clothix.vn</title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <header class="site-header">
        <div class="navbar">
            <!-- Logo -->
            <div class="navbar-logo">
                <a href="maincustomer.php"><img src="../Img/ClothiX.jpg" alt="Clothix.vn" style="height:80px"></a>
            </div>

            <!-- Menu chính -->
            <div class="navbar-menu">
                <!-- Dropdown Sản phẩm -->
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle">Sản phẩm <i class="fa fa-caret-down"></i></a>
                    <div class="dropdown-menu">
                        <?php
                        if (!empty($phan_loai_nhom)) {
                            foreach ($phan_loai_nhom as $loai_chinh => $ds_phan_loai) {
                                echo '<div class="dropdown-column">';
                                echo '<div class="dropdown-title">' . htmlspecialchars($loai_chinh) . '</div>';
                                foreach ($ds_phan_loai as $ten_phan_loai) {
                                    echo '<a href="listsanpham.php?phan_loai=' . urlencode($ten_phan_loai) . '">'
                                        . htmlspecialchars($ten_phan_loai) . '</a>';
                                }
                                echo '</div>';
                            }
                        } else {
                            echo '<span class="no-item">Chưa có phân loại sản phẩm</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Dropdown Hàng mới -->
                <div class="dropdown">
                    <a href="#">Hàng Mới <i class="fa fa-caret-down"></i></a>
                    <div class="dropdown-menu">
                        <div class="dropdown-column">
                            <div class="dropdown-title">Phân loại mới</div>
                            <?php
                            if (mysqli_num_rows($result_new) > 0) {
                                while ($row = mysqli_fetch_assoc($result_new)) {
                                    echo '<a href="listsanpham.php?phan_loai=' . urlencode($row['phan_loai']) . '">'
                                        . htmlspecialchars($row['phan_loai']) . '</a>';
                                }
                            } else {
                                echo '<span class="no-item">Không có sản phẩm mới</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <a href="#">DENIM</a>
                <a href="#">TechUrban</a>
                <span class="outlet">
                    <span class="sale">-50%</span>
                    <span>OUTLET</span>
                </span>
            </div>

            <!-- Icon chức năng -->
            <div class="navbar-icons">
                <i class="fa fa-search icon"></i>
                <?php
                if (isset($_SESSION['tk'])) {
                    echo '<a href="profile.php" title="Hồ sơ cá nhân"><i class="fa fa-user icon"></i></a>';
                } else {
                    echo '<a href="login.php" title="Đăng nhập"><i class="fa fa-user icon"></i></a>';
                }
                ?>
                <a href="location.php"><i class="fa fa-map-marker-alt icon"></i></a>
                <a href="cart.php" style="position:relative;">
                    <i class="fa fa-shopping-cart icon"></i>
                    <span class="cart-badge">0</span>
                </a>
            </div>
        </div>

        <!-- Thanh tìm kiếm -->
        <div id="search-bar-container" class="search-container">
            <div class="search-content-wrapper">
                <div class="search-wrapper">
                    <input type="text" id="searchInput" class="search-bar" placeholder="Tìm kiếm sản phẩm...">
                    <button id="searchButton" class="search-btn"><i class="fa fa-search"></i></button>
                </div>
                <div class="hot-keywords">
                    <strong>Từ khóa nổi bật hôm nay</strong><br>
                    <span>smartjean</span>
                    <span>Áo thun</span>
                    <span>Áo polo</span>
                    <span>Quần short</span>
                    <span>Áo khoác</span>
                    <span>Quần tây</span>
                </div>
                <div id="searchHistory" style="margin-top:8px;"></div>
                <div id="searchResults"></div>
            </div>
        </div>
    </header>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchIcon = document.querySelector('.fa-search.icon');
            const searchBarContainer = document.getElementById('search-bar-container');
            const input = document.getElementById('searchInput');
            const btn = document.getElementById('searchButton');
            const results = document.getElementById('searchResults');
            const historyContainer = document.getElementById('searchHistory');

            let timeout = null;
            let searchHistory = JSON.parse(localStorage.getItem('searchHistory')) || [];

            renderSearchHistory();

            // Bật / tắt thanh tìm kiếm
            searchIcon.addEventListener('click', function() {
                searchBarContainer.classList.toggle('active');
                if (searchBarContainer.classList.contains('active')) {
                    input.focus();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === "Escape") searchBarContainer.classList.remove('active');
            });

            // Hiển thị lịch sử tìm kiếm
            function renderSearchHistory() {
                if (searchHistory.length === 0) {
                    historyContainer.innerHTML = '';
                    return;
                }
                historyContainer.innerHTML = `
            <strong>Lịch sử tìm kiếm:</strong><br>
            ${searchHistory.map(item => `<span class="history-item">${item}</span>`).join('')}
        `;
                document.querySelectorAll('.history-item').forEach(span => {
                    span.addEventListener('click', () => {
                        input.value = span.textContent;
                        timKiemSanPham(true);
                    });
                });
            }

            // Hàm tìm kiếm sản phẩm
            async function timKiemSanPham(saveHistory = false) {
                const tukhoa = input.value.trim();
                if (tukhoa === "") {
                    results.innerHTML = "";
                    return;
                }

                if (saveHistory) {
                    if (!searchHistory.includes(tukhoa)) {
                        searchHistory.unshift(tukhoa);
                        if (searchHistory.length > 5) searchHistory.pop();
                        localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
                        renderSearchHistory();
                    }
                }

                results.innerHTML = "<p style='text-align:center;color:#777;'>Đang tìm kiếm...</p>";

                const formData = new FormData();
                formData.append('tukhoa', tukhoa);

                try {
                    const res = await fetch('ajax_timkiem.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.length === 0) {
                        results.innerHTML = "<p style='text-align:center;color:#777;'>Không tìm thấy sản phẩm nào.</p>";
                    } else {
                        results.innerHTML = data.map(sp => `
                    <div class="search-item" data-id="${sp.id}">
                        <img src="${sp.hinh_anh.startsWith('Img/') ? '../' + sp.hinh_anh : sp.hinh_anh}" ...>
                        <div class="info">
                            <h4>${sp.ten_san_pham}</h4>
                            <p>${sp.gia} đ</p>
                        </div>
                    </div>
                `).join('');

                        document.querySelectorAll('.search-item').forEach(item => {
                            item.addEventListener('click', () => {
                                const id = item.getAttribute('data-id');
                                window.location.href = `chitietsanpham.php?id=${id}`;
                            });
                        });
                    }
                } catch (error) {
                    results.innerHTML = "<p style='text-align:center;color:red;'>Lỗi khi tìm kiếm!</p>";
                }
            }

            // Sự kiện nhập & bấm nút tìm
            input.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => timKiemSanPham(false), 400);
            });

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                timKiemSanPham(true);
            });

            document.addEventListener('click', (e) => {
                if (!results.contains(e.target) && e.target !== input && e.target !== btn) {
                    results.innerHTML = "";
                }
            });
        });
    </script>
</body>

</html>