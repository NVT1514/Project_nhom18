<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "Database/connectdb.php";
include "Database/cart_count_helper.php";

/* -----------------------------------------------------
 LẤY DỮ LIỆU PHÂN LOẠI SẢN PHẨM
 -----------------------------------------------------
 */
// 1. Lấy tất cả danh mục đang hoạt động
$sql_categories = "
    SELECT id, ten_phan_loai, parent_id, loai_chinh 
    FROM phan_loai_san_pham
    WHERE trang_thai = 'Đang sử dụng'
    ORDER BY parent_id ASC, loai_chinh ASC, ten_phan_loai ASC 
";
$result_categories = mysqli_query($conn, $sql_categories);

$menu_cap_1 = []; // Dùng để lưu trữ các mục menu cấp 1 (parent_id = 0)
$menu_cap_2_by_parent = []; // Cấu trúc menu con: Parent ID -> Loại Chính -> Items

while ($row = mysqli_fetch_assoc($result_categories)) {
    $parent_id = $row['parent_id'] ?? 0;

    // ---------------------------------------------
    // Giai đoạn 1: Gom nhóm Menu Cấp 1 (parent_id = 0)
    // ---------------------------------------------
    if (is_null($row['parent_id']) || $row['parent_id'] == 0) {
        $row['parent_id'] = 0;
        // Thêm mục cha (SẢN PHẨM, DENIM, TechUrban) vào danh sách menu chính
        $menu_cap_1[$row['id']] = $row;
        // Khởi tạo mảng con cho menu cấp 1 này
        if (!isset($menu_cap_2_by_parent[$row['id']])) {
            $menu_cap_2_by_parent[$row['id']] = [];
        }
    }

    // ---------------------------------------------
    // Giai đoạn 2: Gom nhóm Menu Cấp 2 (parent_id != 0)
    // ---------------------------------------------
    else {
        $loai_chinh = trim($row['loai_chinh']);

        // Nếu danh mục con này có một Loại Chính cụ thể (ÁO, QUẦN)
        if (!empty($loai_chinh) && $loai_chinh != 'Khác') {

            // Đảm bảo mục cha của nó đã được định nghĩa là Menu Cấp 1
            if (isset($menu_cap_1[$parent_id])) {

                // Gom nhóm các danh mục con vào Loại Chính (ÁO, QUẦN) bên trong Menu Cấp 1
                if (!isset($menu_cap_2_by_parent[$parent_id][$loai_chinh])) {
                    $menu_cap_2_by_parent[$parent_id][$loai_chinh] = [];
                }

                $menu_cap_2_by_parent[$parent_id][$loai_chinh][] = $row;
            }
        }
    }
}

// LẤY $parent_cats phục vụ cho phần HÀNG MỚI (giữ nguyên)
// (Chỉ cần dùng mảng $menu_cap_1 vừa tạo ở trên)
$parent_cats = $menu_cap_1;


/* -----------------------------------------------------
 LẤY DANH SÁCH DANH MỤC CÓ SẢN PHẨM MỚI (TRONG 1 NGÀY)
 -----------------------------------------------------
 */
// Phần này giữ nguyên, dùng $parent_cats ($menu_cap_1) để nhóm.
$sql_new = "
    SELECT DISTINCT T2.id, T2.ten_phan_loai, T2.parent_id
    FROM san_pham T1
    JOIN phan_loai_san_pham T2 ON T1.phan_loai_id = T2.id
    WHERE T1.ngay_tao >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY T2.ten_phan_loai ASC
";
$result_new = mysqli_query($conn, $sql_new);

// Gom nhóm danh mục MỚI theo Cha (nếu có), hoặc đưa vào 1 nhóm nếu không có Cha
$phan_loai_moi_goc = [];
while ($row = mysqli_fetch_assoc($result_new)) {
    $parent_id = $row['parent_id'] ?? 0;

    // Nếu là danh mục con, ta dùng ID cha để gom nhóm
    if ($parent_id != 0 && isset($parent_cats[$parent_id])) {
        $parent_name = $parent_cats[$parent_id]['ten_phan_loai'];
        if (!isset($phan_loai_moi_goc[$parent_name])) {
            $phan_loai_moi_goc[$parent_name] = [];
        }
        $phan_loai_moi_goc[$parent_name][] = $row;
    } else {
        // Nếu không có parent_id (hoặc parent_id là 0), nhóm vào mục 'Sản phẩm mới'
        if (!isset($phan_loai_moi_goc['Sản phẩm mới'])) {
            $phan_loai_moi_goc['Sản phẩm mới'] = [];
        }
        $phan_loai_moi_goc['Sản phẩm mới'][] = $row;
    }
}
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
            <div class="navbar-logo">
                <a href="maincustomer.php"><img src="../Img/logo.png" alt="Clothix.vn"></a>
            </div>

            <div class="navbar-menu">

                <?php
                // =================================================================
                // 🎯 PHẦN MENU CẤP 1 ĐỘNG: LẶP QUA CÁC MỤC CHA TẠO TỪ ADMIN
                // =================================================================
                foreach ($menu_cap_1 as $cat_id => $cat_info) {
                    $ten_menu_cap_1 = htmlspecialchars($cat_info['ten_phan_loai']);
                    $link_cap_1 = 'listsanpham.php?phan_loai_id=' . $cat_id;
                    $menu_con_groups = $menu_cap_2_by_parent[$cat_id] ?? [];

                    // Kiểm tra xem mục này có cần dropdown không
                    $has_dropdown = !empty($menu_con_groups);

                    echo '<div class="dropdown">';

                    // Mục Cấp 1 (VD: SẢN PHẨM, DENIM, TechUrban)
                    echo '<a href="' . $link_cap_1 . '" class="dropdown-toggle">' . $ten_menu_cap_1;
                    if ($has_dropdown) {
                        echo ' <i class="fa fa-caret-down"></i>';
                    }
                    echo '</a>';

                    // Chỉ hiển thị dropdown nếu có danh mục con
                    if ($has_dropdown) {
                        echo '<div class="dropdown-menu">';

                        // LẶP QUA CÁC NHÓM CON (Dựa trên Loại Chính: ÁO, QUẦN,...)
                        foreach ($menu_con_groups as $group_name => $items) {
                            $max_cols = 4; // Giới hạn số cột
                            if (count($menu_con_groups) > $max_cols) {
                                // Có thể cần điều chỉnh style hoặc giới hạn số cột trong CSS
                            }

                            echo '<div class="dropdown-column">';

                            // Tiêu đề cột là Loại Chính (ÁO, QUẦN)
                            echo '<div class="dropdown-title"> ' . htmlspecialchars($group_name) . '</div>';

                            // Thêm link TẤT CẢ theo Loại Chính (Link đến tất cả sản phẩm thuộc Loại Chính đó)
                            echo '<a href="listsanpham.php?phan_loai_id=' . $cat_id . '&loai_chinh=' . urlencode($group_name) . '" style="font-weight: bold;">Tất cả ' . htmlspecialchars($group_name) . '</a>';

                            // In các danh mục con chi tiết (Áo Khoác, Quần Âu...)
                            foreach ($items as $child_cat) {
                                echo '<a href="listsanpham.php?phan_loai_id=' . $child_cat['id'] . '">'
                                    . htmlspecialchars($child_cat['ten_phan_loai']) . '</a>';
                            }

                            echo '</div>'; // End dropdown-column
                        }

                        echo '</div>'; // End dropdown-menu
                    }
                    echo '</div>'; // End dropdown
                }
                ?>

                <div class="dropdown">
                    <a href="listsanpham.php?new=true">Hàng Mới <i class="fa fa-caret-down"></i></a>
                    <div class="dropdown-menu" style="min-width: 300px;">
                        <?php
                        // Vòng lặp: Lấy ra các Danh mục có sản phẩm mới
                        if (!empty($phan_loai_moi_goc)) {
                            foreach ($phan_loai_moi_goc as $group_name => $items) {
                                echo '<div class="dropdown-column" style="flex: 0 1 100%;">';
                                echo '<div class="dropdown-title">' . htmlspecialchars($group_name) . ' Mới</div>';

                                // Hiển thị các danh mục con có sản phẩm mới
                                foreach ($items as $item) {
                                    echo '<a href="listsanpham.php?phan_loai_id=' . $item['id'] . '">'
                                        . htmlspecialchars($item['ten_phan_loai']) . '</a>';
                                }
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="dropdown-column" style="flex: 0 1 100%;">';
                            echo '<span class="no-item" style="color: #666;">Không có sản phẩm mới trong 24h qua.</span>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>

            </div>

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
                    <span class="cart-badge"><?php echo $cart_count ?? 0; ?></span>
                </a>
            </div>
        </div>

        <div id="search-bar-container" class="search-container">
            <div class="search-content-wrapper">
                <div class="search-main-area">
                    <div class="search-wrapper">
                        <input type="text" id="searchInput" class="search-bar" placeholder="Tìm kiếm sản phẩm...">
                        <button id="searchButton" class="search-btn"><i class="fa fa-search"></i></button>
                    </div>
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
        // Javascript tìm kiếm giữ nguyên
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
                            <img src="${sp.hinh_anh.startsWith('Img/') ? '../' + sp.hinh_anh : sp.hinh_anh}" alt="${sp.ten_san_pham}">
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