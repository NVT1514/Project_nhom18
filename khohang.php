<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php";

// ================== XỬ LÝ NÚT HẾT HÀNG ==================
if (isset($_POST['out_of_stock'])) {
    $product_id = intval($_POST['product_id']);
    mysqli_query($conn, "UPDATE san_pham SET so_luong = 0 WHERE id = $product_id");
    header("Location: khohang.php");
    exit();
}

// ================== XỬ LÝ NHẬP / XUẤT KHO ==================
if (isset($_POST['update_stock'])) {
    $product_id = intval($_POST['product_id']);
    $qty = intval($_POST['quantity']);
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier']);
    $total_price = floatval($_POST['total_price']);
    $action = $_POST['action'];

    if ($action === 'in') {
        $new_price = isset($_POST['new_price']) ? floatval($_POST['new_price']) : null;
        if ($new_price && $new_price > 0) {
            mysqli_query($conn, "UPDATE san_pham SET so_luong = so_luong + $qty, gia = $new_price WHERE id = $product_id");
        } else {
            mysqli_query($conn, "UPDATE san_pham SET so_luong = so_luong + $qty WHERE id = $product_id");
        }

        $gia_moi_sql = $new_price ? $new_price : "NULL";
        mysqli_query($conn, "
            INSERT INTO lich_su_kho (product_id, hanh_dong, so_luong, nha_cung_cap, tong_tien, gia_moi, ngay_thuc_hien)
            VALUES ($product_id, 'Nhập hàng', $qty, '$supplier', $total_price, $gia_moi_sql, NOW())
        ");
    } elseif ($action === 'out') {
        mysqli_query($conn, "UPDATE san_pham SET so_luong = GREATEST(so_luong - $qty, 0) WHERE id = $product_id");
        mysqli_query($conn, "
            INSERT INTO lich_su_kho (product_id, hanh_dong, so_luong, nha_cung_cap, tong_tien, ngay_thuc_hien)
            VALUES ($product_id, 'Xuất hàng', $qty, '$supplier', $total_price, NOW())
        ");
    }

    header("Location: khohang.php");
    exit();
}

// ================== TỰ ĐỘNG CẬP NHẬT KHO ==================
$processed_orders = mysqli_query($conn, "
    SELECT id FROM don_hang WHERE status = 1 AND (processed_stock IS NULL OR processed_stock = 0)
");

if ($processed_orders && mysqli_num_rows($processed_orders) > 0) {
    while ($order = mysqli_fetch_assoc($processed_orders)) {
        $order_id = intval($order['id']);
        $items = mysqli_query($conn, "SELECT product_id, quantity FROM chi_tiet_don_hang WHERE order_id = $order_id");
        while ($item = mysqli_fetch_assoc($items)) {
            $product_id = intval($item['product_id']);
            $qty = intval($item['quantity']);
            mysqli_query($conn, "
                UPDATE san_pham 
                SET so_luong = GREATEST(so_luong - $qty, 0),
                    so_luong_ban = so_luong_ban + $qty
                WHERE id = $product_id
            ");
        }
        mysqli_query($conn, "UPDATE don_hang SET processed_stock = 1 WHERE id = $order_id");
    }
}

// ================== LỌC, TÌM KIẾM & SẮP XẾP ==================
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? '';
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');

$query = "SELECT * FROM san_pham WHERE 1";

// Lọc trạng thái
if ($filter === 'out') {
    $query .= " AND so_luong = 0";
}

// Tìm kiếm theo tên
if (!empty($search)) {
    $query .= " AND ten_san_pham LIKE '%$search%'";
}

// Sắp xếp
switch ($sort) {
    case 'best_selling':
        $query .= " ORDER BY so_luong_ban DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY gia ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY gia DESC";
        break;
    case 'name_az':
        $query .= " ORDER BY ten_san_pham ASC";
        break;
    case 'name_za':
        $query .= " ORDER BY ten_san_pham DESC";
        break;
    default:
        $query .= " ORDER BY id DESC";
}

$products = mysqli_query($conn, $query);
if (!$products) die("Lỗi truy vấn sản phẩm: " . mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý kho hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding-top: 80px;
        }

        .container {
            display: flex;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        main.main-content {
            flex: 1;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.6s ease;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #4a90e2;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr.highlight {
            background-color: #ffe0e0;
        }

        /* Tùy chỉnh chung cho nút */
        button {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            /* Bo góc nhẹ */
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            /* Thêm hiệu ứng hover */
            margin: 3px;
            /* Khoảng cách giữa các nút */
            white-space: nowrap;
            /* Tránh bị xuống dòng */
        }

        button:hover {
            transform: translateY(-1px);
            /* Hiệu ứng nhấc lên khi hover */
        }

        .stock-btn {
            /* Nút Nhập */
            background: #4a90e2;
            /* Xanh dương */
            color: white;
        }

        .stock-btn:hover {
            background: #3a7bd2;
        }

        .out-of-stock {
            /* Nút Xuất */
            background: #ff7f50;
            /* Cam/Đỏ nhạt */
            color: white;
        }

        .out-of-stock:hover {
            background: #e56a40;
        }

        .view-history {
            /* Nút Lịch sử */
            background: #28a745;
            /* Xanh lá cây */
            color: white;
            /* Giữ nguyên màu xanh lá */
        }

        .view-history:hover {
            background: #1f8a3a;
        }

        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
        }

        /* Overlay & Popup */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            animation: fadeInOverlay 0.4s ease forwards;
            z-index: 999;
        }

        .popup {
            background: white;
            padding: 25px;
            border-radius: 12px;
            width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            transform: scale(0.8);
            animation: scaleUp 0.4s ease forwards;
        }

        .popup input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .popup .btn-group {
            text-align: right;
        }

        @keyframes fadeInOverlay {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes scaleUp {
            from {
                transform: scale(0.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar_admin.php'; ?>
        <main class="main-content">
            <div class="form-container">
                <h1> Quản lý kho hàng</h1>

                <!-- THANH TÌM KIẾM + BỘ LỌC -->
                <div class="sort-group" style="margin-bottom:15px;text-align:center;">
                    <form method="get" style="display:inline-block;margin-right:10px;">
                        <input type="text" name="search" placeholder="🔍 Tìm sản phẩm..."
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                            style="padding:8px 12px;border:1px solid #ccc;border-radius:6px;width:250px;">
                        <button type="submit" style="padding:8px 15px;background:#4a90e2;color:white;border:none;border-radius:6px;cursor:pointer;">
                            <i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm
                        </button>
                    </form>

                    <form method="get" style="display:inline-block;margin-right:10px;">
                        <select name="filter" onchange="this.form.submit()" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;">
                            <option value="all" <?= ($_GET['filter'] ?? '') == 'all' ? 'selected' : '' ?>>-- Tất cả sản phẩm --</option>
                            <option value="out" <?= ($_GET['filter'] ?? '') == 'out' ? 'selected' : '' ?>>❌ Hết hàng</option>
                        </select>
                    </form>

                    <form method="get" style="display:inline-block;">
                        <select name="sort" onchange="this.form.submit()" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;">
                            <option value="">🔽 Sắp xếp theo</option>
                            <option value="best_selling" <?= ($_GET['sort'] ?? '') == 'best_selling' ? 'selected' : '' ?>>🏆 Bán chạy</option>
                            <option value="price_asc" <?= ($_GET['sort'] ?? '') == 'price_asc' ? 'selected' : '' ?>>💰 Giá tăng dần</option>
                            <option value="price_desc" <?= ($_GET['sort'] ?? '') == 'price_desc' ? 'selected' : '' ?>>💰 Giá giảm dần</option>
                            <option value="name_az" <?= ($_GET['sort'] ?? '') == 'name_az' ? 'selected' : '' ?>>🔤 Tên A–Z</option>
                            <option value="name_za" <?= ($_GET['sort'] ?? '') == 'name_za' ? 'selected' : '' ?>>🔤 Tên Z–A</option>
                        </select>
                    </form>
                </div>

                <table>
                    <tr>
                        <th>ID</th>
                        <th>Tên sản phẩm</th>
                        <th>Hình ảnh</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Đã bán</th>
                        <th>Trạng thái</th>
                        <th>Ngày cập nhật gần nhất</th>
                        <th>Hành động</th>
                    </tr>
                    <?php while ($row = mysqli_fetch_assoc($products)):
                        $history = mysqli_query($conn, "SELECT ngay_thuc_hien FROM lich_su_kho WHERE product_id={$row['id']} ORDER BY ngay_thuc_hien DESC LIMIT 1");
                        $last_update = ($history && mysqli_num_rows($history) > 0) ? mysqli_fetch_assoc($history)['ngay_thuc_hien'] : null;
                    ?>
                        <tr class="<?= $row['so_luong'] == 0 ? 'highlight' : '' ?>">
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['ten_san_pham']) ?></td>
                            <td>
                                <?php if ($row['hinh_anh']): ?>
                                    <img src="../uploads/<?= htmlspecialchars($row['hinh_anh']) ?>" width="70" height="70" style="border-radius:8px;object-fit:cover;">
                                <?php else: ?>
                                    <span style="color:#aaa;">(Không có ảnh)</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($row['gia'], 0, ',', '.') ?> đ</td>
                            <td><?= $row['so_luong'] ?></td>
                            <td><?= $row['so_luong_ban'] ?></td>
                            <td><?= $row['so_luong'] > 0 ? "<span style='color:green;font-weight:bold;'>Còn hàng</span>" : "<span style='color:red;font-weight:bold;'>Hết hàng</span>" ?></td>
                            <td><?= $last_update ? date("d/m/Y H:i", strtotime($last_update)) : "–" ?></td>
                            <td>
                                <div class="action-group">
                                    <button class="stock-btn" onclick="openPopup('in', <?= $row['id'] ?>, '<?= htmlspecialchars($row['ten_san_pham']) ?>')">📥 Nhập</button>
                                    <button class="out-of-stock" onclick="openPopup('out', <?= $row['id'] ?>, '<?= htmlspecialchars($row['ten_san_pham']) ?>')">📤 Xuất</button>
                                    <button class="view-history" onclick="showHistory(<?= $row['id'] ?>, '<?= htmlspecialchars($row['ten_san_pham']) ?>')">📜 Lịch sử</button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </main>
    </div>

    <!-- POPUP NHẬP/XUẤT -->
    <div class="overlay" id="overlay">
        <div class="popup" id="popup">
            <form method="post">
                <h3 id="popup-title">Nhập/Xuất hàng</h3>
                <input type="hidden" name="product_id" id="popup-product-id">
                <input type="hidden" name="action" id="popup-action">
                <label>Số lượng:</label>
                <input type="number" name="quantity" min="1" required>

                <div id="supplier-section">
                    <label>Tên nhà cung cấp:</label>
                    <input type="text" name="supplier" required>
                    <label>Tổng tiền:</label>
                    <input type="number" name="total_price" min="0" step="0.01" required>
                    <label id="price-label" style="display:none;">Giá mới (đ):</label>
                    <input type="number" name="new_price" id="new_price" step="0.01" style="display:none;">
                </div>
                <div class="btn-group">
                    <button type="button" class="out-of-stock" onclick="closePopup()">Hủy</button>
                    <button type="submit" name="update_stock" class="stock-btn">Xác nhận</button>
                </div>
            </form>
        </div>
    </div>

    <!-- POPUP LỊCH SỬ -->
    <div class="overlay" id="historyOverlay">
        <div class="popup" id="historyPopup">
            <h3 id="historyTitle">Lịch sử thay đổi</h3>
            <table id="historyTable" border="1" style="width:100%;border-collapse:collapse;margin-top:10px;">
                <tr>
                    <th>Ngày</th>
                    <th>Hành động</th>
                    <th>Số lượng</th>
                    <th>Nhà cung cấp</th>
                    <th>Tổng tiền</th>
                    <th>Giá mới</th>
                </tr>
            </table>
            <div style="text-align:right;margin-top:10px;">
                <button class="out-of-stock" onclick="closeHistory()">Đóng</button>
            </div>
        </div>
    </div>

    <script>
        function openPopup(type, id, name) {
            const overlay = document.getElementById("overlay");
            const actionField = document.getElementById("popup-action");
            const title = document.getElementById("popup-title");
            const priceLabel = document.getElementById("price-label");
            const newPriceInput = document.getElementById("new_price");

            overlay.style.display = "flex";
            document.getElementById("popup-product-id").value = id;
            actionField.value = type;
            title.innerText = (type === 'in' ? "📥 Nhập hàng - " : "📤 Xuất hàng - ") + name;

            if (type === 'in') {
                priceLabel.style.display = "block";
                newPriceInput.style.display = "block";
            } else {
                priceLabel.style.display = "none";
                newPriceInput.style.display = "none";
            }
        }

        function closePopup() {
            document.getElementById("overlay").style.display = "none";
        }

        async function showHistory(productId, productName) {
            const overlay = document.getElementById("historyOverlay");
            const table = document.getElementById("historyTable");
            document.getElementById("historyTitle").innerText = "📜 Lịch sử sản phẩm: " + productName;
            overlay.style.display = "flex";

            table.innerHTML = `
                <tr><th>Ngày</th><th>Hành động</th><th>Số lượng</th><th>Nhà cung cấp</th><th>Tổng tiền</th><th>Giá mới</th></tr>
            `;

            const res = await fetch(`xem_lichsu.php?product_id=${productId}`);
            const data = await res.json();

            if (data.length === 0) {
                const row = table.insertRow();
                const cell = row.insertCell();
                cell.colSpan = 6;
                cell.innerHTML = "<i>Chưa có lịch sử</i>";
                cell.style.textAlign = "center";
                return;
            }

            data.forEach(item => {
                const row = table.insertRow();
                row.insertCell().innerText = item.ngay_thuc_hien;
                row.insertCell().innerText = item.hanh_dong;
                row.insertCell().innerText = item.so_luong;
                row.insertCell().innerText = item.nha_cung_cap;
                row.insertCell().innerHTML = item.tong_tien || "";
                row.insertCell().innerHTML = item.gia_moi || "";
            });
        }

        function closeHistory() {
            document.getElementById("historyOverlay").style.display = "none";
        }
    </script>
</body>

</html>