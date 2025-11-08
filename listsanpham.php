<?php
include "Database/connectdb.php";

// 1. NHẬN CÁC THAM SỐ TỪ URL
// ⚠️ QUAN TRỌNG: Đã đổi 'category_id' thành 'phan_loai_id' để khớp với header.php
$phan_loai_id = isset($_GET['phan_loai_id']) ? (int)$_GET['phan_loai_id'] : 0;
// Thêm tham số 'loai_chinh' để lọc khi người dùng click vào 'Tất cả ÁO'/'Tất cả QUẦN'
$loai_chinh_url = isset($_GET['loai_chinh']) ? $_GET['loai_chinh'] : '';

$category_name = "";
$title_suffix = "";

// 2. XÂY DỰNG TRUY VẤN CƠ SỞ
$sql = "SELECT sp.*, pl.ten_phan_loai, pl.parent_id, pl.loai_chinh
        FROM san_pham sp
        LEFT JOIN phan_loai_san_pham pl ON sp.phan_loai_id = pl.id 
        WHERE 1=1"; // Bắt đầu bằng điều kiện luôn đúng

$types = "";
$params = [];


// 3. LỌC: LỌC THEO ID DANH MỤC HOẶC DANH MỤC CHA
if ($phan_loai_id > 0) {
    // 3a. Lấy thông tin danh mục đang lọc
    $sql_cat_info = "SELECT id, ten_phan_loai, parent_id, loai_chinh FROM phan_loai_san_pham WHERE id = ?";
    $stmt_cat_info = $conn->prepare($sql_cat_info);
    $stmt_cat_info->bind_param("i", $phan_loai_id);
    $stmt_cat_info->execute();
    $cat_info_result = $stmt_cat_info->get_result();
    $cat_info = $cat_info_result->fetch_assoc();
    $stmt_cat_info->close();

    if ($cat_info) {
        $category_name = $cat_info['ten_phan_loai'];
        // Thiết lập tiêu đề hiển thị
        $title_suffix = htmlspecialchars($category_name);

        // 3b. Xử lý danh mục ĐA CẤP (Cấp 1)
        // Nếu parent_id là NULL hoặc 0, đây là danh mục cha (Cấp 1), cần lấy sản phẩm của các con
        if (is_null($cat_info['parent_id']) || $cat_info['parent_id'] == 0) {

            // Lấy tất cả danh mục con (cấp 2/3) của nó
            $sql_child_ids = "SELECT id FROM phan_loai_san_pham WHERE parent_id = ?";
            $stmt_child_ids = $conn->prepare($sql_child_ids);
            $stmt_child_ids->bind_param("i", $phan_loai_id);
            $stmt_child_ids->execute();
            $child_ids_result = $stmt_child_ids->get_result();

            $valid_ids = [$phan_loai_id]; // Bao gồm cả ID cha (phòng trường hợp sản phẩm gán thẳng vào Cấp 1)
            while ($row = $child_ids_result->fetch_assoc()) {
                $valid_ids[] = $row['id'];
            }
            $stmt_child_ids->close();

            // Lọc bằng danh sách ID đã thu thập
            $placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
            $sql .= " AND sp.phan_loai_id IN ($placeholders)";

            // Thêm các ID vào params
            foreach ($valid_ids as $id) {
                $types .= "i";
                $params[] = $id;
            }
        } else {
            // Đây là danh mục cấp 2/3, chỉ lọc theo ID này
            $sql .= " AND sp.phan_loai_id = ?";
            $types .= "i";
            $params[] = $phan_loai_id;
        }
    }
}
// 4. LỌC THEO LOẠI CHÍNH (ÁP DỤNG KHI KHÔNG CÓ phan_loai_id, VÍ DỤ: Click "SẢN PHẨM" -> "ÁO" (Tất cả Áo))
else if (!empty($loai_chinh_url) && $loai_chinh_url != 'Khác') {
    $sql .= " AND pl.loai_chinh = ?";
    $types .= "s";
    $params[] = $loai_chinh_url;
    $title_suffix = "Tất cả " . htmlspecialchars($loai_chinh_url);
}


// 5. HOÀN THIỆN VÀ THỰC THI TRUY VẤN
$sql .= " ORDER BY sp.ngay_tao DESC";

$stmt = $conn->prepare($sql);

if (!empty($types)) {
    // Bind các tham số nếu có
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();


// =============================
// 🖼️ LẤY BANNER HIỆN TẠI (Giữ nguyên)
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<head>
    <meta charset="UTF-8">
    <title>Sản phẩm <?php echo $title_suffix ? " - " . htmlspecialchars($title_suffix) : ""; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        /* ===== Banner (giữ nguyên) ===== */
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

        /* ===== Danh sách sản phẩm (giữ nguyên) ===== */
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

        .navbar-menu a ::after,
        .dropdown-toggle::after {
            content: none !important;
            border: none !important;
            display: none !important;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="banner-container">
        <img src="<?php echo htmlspecialchars($banner_path); ?>" alt="Banner sản phẩm">
        <div class="banner-overlay">
            <h1>Chào mừng đến với cửa hàng</h1>
            <p>Khám phá những sản phẩm mới nhất ngay hôm nay!</p>
        </div>
    </div>

    <h2>
        <i class="fa-solid fa-tags"></i> Danh sách sản phẩm
        <?php
        // Hiển thị tên phân loại đã được tính toán
        // Nếu không có lọc, hiển thị "Nổi bật" (hoặc bạn có thể tự thay đổi)
        echo $title_suffix ? "(" . htmlspecialchars($title_suffix) . ")" : "Nổi bật";
        ?>
    </h2>
    <div class="product-list">
        <?php
        if ($result->num_rows > 0) {
            // Đảm bảo lặp qua tất cả sản phẩm
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()) {
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
        <?php
            }
        } else {
            echo '<p style="text-align: center; grid-column: 1 / -1; padding: 50px;">Không tìm thấy sản phẩm nào trong danh mục này.</p>';
        }
        ?>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script>
</body>

</html>