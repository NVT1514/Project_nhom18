<?php
include "Database/connectdb.php";
session_start();

// --- DỮ LIỆU VOUCHERS GIẢ LẬP (TỪ DB CỦA BẠN) ---
$vouchers = [];

// SỬA LỖI SQL: Thay thế trang_thai = 'Đang sử dụng' bằng 'Hoạt động' theo ENUM trong DB của bạn
$sql_vouchers = "SELECT * FROM vouchers WHERE trang_thai = 'Hoạt động' LIMIT 4";
$result_vouchers = mysqli_query($conn, $sql_vouchers);

if ($result_vouchers) {
    while ($voucher_item = mysqli_fetch_assoc($result_vouchers)) {
        $vouchers[] = $voucher_item;
    }
}

// Thêm mã Freeship giả định nếu chưa có
$vouchers[] = [
    'ma_voucher' => 'FS500K', // Tên mã Freeship
    'giam_phan_tram' => 0,
    'gia_tri_toi_da' => 0,
    'mo_ta' => 'Miễn phí vận chuyển',
    'dieu_kien' => 'Áp dụng cho đơn từ 500.000đ', // Thay thế 500K bằng 500.000đ
];

// --- CẬP NHẬT: Định dạng tiền tệ (cần thiết cho mô tả) ---
function format_currency($amount)
{
    return number_format($amount, 0, ',', '.') . 'đ';
}

// CẬP NHẬT LOGIC FORMAT VOUCHER THEO GIẢM % & ĐIỀU KIỆN TEXT
function format_voucher_display($voucher)
{
    $ma = htmlspecialchars($voucher['ma_voucher']);
    $phan_tram = $voucher['giam_phan_tram'];
    $dieu_kien = htmlspecialchars($voucher['dieu_kien']);
    $gia_tri = intval($voucher['gia_tri_toi_da']);

    $giam_text = "";
    $ma_text = "Nhập mã **$ma**: ";

    if ($phan_tram > 0) {
        $giam_text = "Giảm **$phan_tram%**";
        if ($gia_tri > 0) {
            $giam_text .= " (Tối đa " . format_currency($gia_tri) . ")";
        }
    } elseif (strpos($ma, 'FS') !== false) {
        $ma_text = ""; // Bỏ "Nhập mã" cho freeship
        $giam_text = "**Miễn phí vận chuyển**";
    }

    return $ma_text . $giam_text . ". " . $dieu_kien;
}
// --- Kết thúc phần Voucher PHP ---


// --- Kiểm tra ID sản phẩm (Giữ nguyên) ---
if (!isset($_GET['id'])) {
    $id = 1; // ID giả định
} else {
    $id = intval($_GET['id']);
}

// --- Lấy thông tin sản phẩm (Giữ nguyên) ---
$sql = "SELECT * FROM san_pham WHERE id = $id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    $sql_fallback = "SELECT * FROM san_pham ORDER BY id ASC LIMIT 1";
    $result_fallback = mysqli_query($conn, $sql_fallback);
    if (!$result_fallback || mysqli_num_rows($result_fallback) == 0) {
        // Thiết lập dữ liệu giả nếu không có sản phẩm nào
        $product = [
            'id' => 0,
            'ten_san_pham' => 'Giày Nike Shox Hiệu Năng Cao - "Triple Black" Huyền Bí (Giả lập)',
            'gia' => 875000,
            'hinh_anh' => 'giay_nike_shox.jpg',
            'phan_loai' => 'Giày thể thao',
            'so_luong' => 10,
            'mo_ta' => 'Đây là mô tả chi tiết của sản phẩm Giày Nike Shox giả lập. Sản phẩm có thiết kế Triple Black mạnh mẽ, chất liệu da tổng hợp bền bỉ và đế Shox đặc trưng mang lại độ đàn hồi vượt trội.'
        ];
    } else {
        $product = mysqli_fetch_assoc($result_fallback);
        $id = $product['id'];
    }
} else {
    $product = mysqli_fetch_assoc($result);
}
// Giả định các kích cỡ sẵn có (cho phần giao diện mới)
$available_sizes = ['S (US 7)', 'M (US 8)', 'L (US 9)', 'XL (US 10)'];
$default_size = 'M (US 8)';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['ten_san_pham']) ?></title>
    <link rel="stylesheet" href="css/chitietsanpham.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div id="toast-notification" class="toast-notification">
        <div class="toast-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-check"></i>
                Thêm vào giỏ hàng thành công
            </div>
            <button class="toast-close-btn" onclick="hideToast()">&times;</button>
        </div>

        <div class="toast-body">
            <div class="toast-product-detail">
                <img id="toast-image" src="" alt="Product Image" class="toast-product-image">
                <div class="toast-product-info">
                    <h4 id="toast-name"></h4>
                    <p id="toast-variant"></p>
                    <p id="toast-price" class="toast-product-price"></p>
                </div>
            </div>
            <a href="cart.php" class="toast-view-cart-btn">Xem giỏ hàng</a>
        </div>
    </div>
    <div class="main-product-area">

        <div class="product-gallery-block">
            <div class="product-gallery">
                <img class="main-image"
                    src="<?= !empty($product['hinh_anh']) ? 'uploads/' . htmlspecialchars($product['hinh_anh']) : 'uploads/no-image.png' ?>"
                    alt="<?= htmlspecialchars($product['ten_san_pham']) ?>">

                <?php if (isset($product['so_luong']) && $product['so_luong'] == 0): ?>
                    <div class="sold-out">HẾT HÀNG</div>
                <?php endif; ?>

                <div class="thumbnails" style="display: flex;">
                    <img src="uploads/<?= htmlspecialchars($product['hinh_anh']) ?>" class="active" onclick="changeImage(this)">
                    <img src="uploads/sample_thumb2.jpg" onclick="changeImage(this)">
                    <img src="uploads/sample_thumb3.jpg" onclick="changeImage(this)">
                </div>
            </div>
        </div>

        <div class="product-info-block">
            <h1 class="product-title"><?= htmlspecialchars($product['ten_san_pham']) ?></h1>
            <p class="product-price"><?= number_format($product['gia'], 0, ',', '.') ?>đ</p>

            <?php if (!empty($vouchers)): ?>
                <div class="product-promotions">
                    <p style="font-size: 16px; font-weight: 700; color: #d00; margin-top: 0;"><i class="fa-solid fa-gift"></i> NHẬN VOUCHER ƯU ĐÃI</p>
                    <div class="voucher-list">
                        <?php foreach ($vouchers as $voucher): ?>
                            <p>
                                <?= format_voucher_display($voucher) ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="btn-promotions">
                    <label for="voucher">Mã giảm giá</label>
                    <div class="voucher-buttons-area">
                        <?php
                        $added_voucher_types = [];
                        foreach ($vouchers as $voucher):
                            $ma = htmlspecialchars($voucher['ma_voucher']);
                            $phan_tram = $voucher['giam_phan_tram'];

                            $button_text = "";
                            $type = $phan_tram > 0 ? "GIAM_{$phan_tram}" : "FREESHIP";

                            // Chỉ thêm nút nếu loại voucher này chưa được thêm (tránh trùng lặp nút FREESHIP/GIẢM %)
                            if (!in_array($type, $added_voucher_types)) {
                                if ($phan_tram > 0) {
                                    // Nút Voucher giảm %
                                    $button_text = "VOUCHER GIẢM {$phan_tram}%";
                                } elseif (strpos($ma, 'FS') !== false) {
                                    // Nút Freeship
                                    $button_text = "VOUCHER FREESHIP";
                                }

                                if (!empty($button_text)) {
                                    echo '<button type="button" class="voucher-button" onclick="copyVoucher(\'' . $ma . '\')">
                                    ' . $button_text . '
                                </button>';
                                    $added_voucher_types[] = $type;
                                }
                            }
                        endforeach;
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="product-options">
                <?php if (isset($product['so_luong']) && $product['so_luong'] > 0): ?>
                    <form method="POST" action="add_to_cart.php" class="product-action-form">
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">

                        <label for="size">Kích cỡ</label>
                        <div class="size-options" id="size-options">
                            <input type="hidden" name="size" id="selected-size" value="<?= htmlspecialchars($default_size) ?>">
                            <?php foreach ($available_sizes as $size): ?>
                                <button type="button" class="size-button <?= ($size == $default_size) ? 'active' : '' ?>"
                                    data-size="<?= htmlspecialchars($size) ?>"
                                    onclick="selectSize(this)">
                                    <?= htmlspecialchars($size) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>


                        <label for="quantity">Số lượng</label>
                        <div class="quantity-control-wrapper">
                            <div class="quantity-control">
                                <button type="button" onclick="changeQuantity(-1)">−</button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1"
                                    max="<?= $product['so_luong'] ?>">
                                <button type="button" onclick="changeQuantity(1)">+</button>
                            </div>
                            <small>(Còn lại: <?= $product['so_luong'] ?>)</small>
                        </div>


                        <div class="button-group">
                            <button type="submit" name="add_cart" class="btn-cart">
                                <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
                            </button>

                            <button type="submit" name="buy_now" id="buy-now-btn" class="btn-buy">
                                <i class="fa-solid fa-bolt"></i> Mua ngay
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="sold-out-message">
                        Sản phẩm hiện tại đã hết hàng
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-assurances">
                <div class="assurance-item">
                    <i class="fa-solid fa-truck"></i>
                    <p>Freeship đơn hàng trên <?= number_format(500000, 0, ',', '.') ?>đ</p>
                </div>
                <div class="assurance-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <p>Đổi trả 15 ngày</p>
                </div>
                <div class="assurance-item">
                    <i class="fa-solid fa-credit-card"></i>
                    <p>Thanh toán COD/Thẻ</p>
                </div>
            </div>

        </div>
    </div>

    <div class="product-description-section">
        <div class="description-tabs">
            <button class="tab-button active" onclick="showTab('description-content')">MÔ TẢ SẢN PHẨM</button>
            <button class="tab-button" onclick="showTab('shipping-policy')">CHÍNH SÁCH VẬN CHUYỂN</button>
        </div>

        <div id="description-content" class="tab-pane active">
            <div class="summary-details">
                <div class="summary-item"><strong>Phân loại:</strong> <?= htmlspecialchars($product['phan_loai']) ?></div>
                <div class="summary-item"><strong>Thương hiệu:</strong> Nike Shox</div>
                <div class="summary-item"><strong>Chất liệu:</strong> Da tổng hợp, Đế cao su</div>
                <div class="summary-item"><strong>Trạng thái:</strong> <?= $product['so_luong'] > 0 ? 'Còn hàng' : 'Hết hàng' ?></div>
            </div>

            <div class="description-text">
                <h2>Chi tiết sản phẩm</h2>
                <p><?= nl2br(htmlspecialchars($product['mo_ta'])) ?></p>
            </div>
        </div>

        <div id="shipping-policy" class="tab-pane" style="display:none;">
            <h2 style="color: #001F5D;">Chính sách Vận chuyển & Đổi trả</h2>
            <p>1. **Vận chuyển:** Miễn phí vận chuyển cho đơn hàng trên 500.000đ. Thời gian giao hàng dự kiến 3-5 ngày làm việc.</p>
            <p>2. **Đổi trả:** Áp dụng đổi trả trong vòng 15 ngày kể từ ngày nhận hàng với sản phẩm còn nguyên tem mác, chưa qua sử dụng. Vui lòng xem chi tiết tại trang Chính sách.</p>
        </div>
    </div>

    <div class="related-products">
        <h2>Sản phẩm cùng loại</h2>

        <button class="scroll-button left" onclick="scrollProducts(-320)">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button class="scroll-button right" onclick="scrollProducts(320)">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
        <div class="product-grid">
            <?php
            // Lấy 4 sản phẩm cùng loại, loại trừ sản phẩm hiện tại (có thể lặp lại nếu DB ít)
            $sql_related = "SELECT * FROM san_pham WHERE phan_loai = '" . mysqli_real_escape_string($conn, $product['phan_loai']) . "' AND id != $id LIMIT 4";
            $related = mysqli_query($conn, $sql_related);

            // Nếu không đủ, lấy 4 sản phẩm bất kỳ
            if (mysqli_num_rows($related) < 4) {
                $sql_related = "SELECT * FROM san_pham WHERE id != $id LIMIT 4";
                $related = mysqli_query($conn, $sql_related);
            }

            while ($item = mysqli_fetch_assoc($related)) {
                $raw_path = htmlspecialchars($item['hinh_anh']);
                $clean_image_path = str_replace('../', '', $raw_path);

                if (strpos($clean_image_path, 'uploads/') === false) {
                    // Nếu DB chỉ lưu tên file, thêm tiền tố 'uploads/'
                    $clean_image_path = 'uploads/' . $clean_image_path;
                }
                // 💡 KẾT THÚC SỬA CODE 💡

                echo '<div class="related-item">
                    <a href="chitietsanpham.php?id=' . $item['id'] . '">
                        <img src="' . $clean_image_path . '" alt="' . htmlspecialchars($item['ten_san_pham']) . '">
                        <div class="related-item-info">
                            <p>' . htmlspecialchars($item['ten_san_pham']) . '</p>
                            <span>' . number_format($item['gia'], 0, ',', '.') . 'đ</span>
                        </div>
                    </a>
                </div>';
            }
            ?>
        </div>
    </div>

    <?php include 'footer.php'; // Đã thêm file giả định 
    ?>

    <script>
        // Hàm chọn Kích cỡ mới
        function selectSize(button) {
            document.querySelectorAll('.size-button').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            document.getElementById('selected-size').value = button.getAttribute('data-size');
        }

        // Hàm thay đổi ảnh (Giữ nguyên)
        function changeImage(img) {
            document.querySelectorAll('.thumbnails img').forEach(i => i.classList.remove('active'));
            document.querySelector('.main-image').src = img.src;
            img.classList.add('active');
        }

        // Hàm thay đổi Số lượng (Giữ nguyên)
        function changeQuantity(change) {
            const input = document.getElementById('quantity');
            const max = parseInt(input.max);
            let current = parseInt(input.value);
            current += change;
            if (current < 1) current = 1;
            if (current > max) current = max;
            input.value = current;
        }

        // HÀM SAO CHÉP VOUCHER (BỎ ALERT)
        function copyVoucher(voucherCode) {
            const tempInput = document.createElement('input');
            tempInput.value = voucherCode;
            document.body.appendChild(tempInput);

            tempInput.select();
            document.execCommand('copy');

            document.body.removeChild(tempInput);

            // THAY THẾ alert BẰNG TOAST
            showToast(`✅ Đã sao chép mã voucher: ${voucherCode}! Vui lòng dán mã này ở trang thanh toán.`, 'success');
        }

        // Hàm chuyển tab (Giữ nguyên)
        function showTab(tabId) {
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.style.display = 'none';
                pane.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });

            const activePane = document.getElementById(tabId);
            activePane.style.display = 'block';
            activePane.classList.add('active');
            document.querySelector(`.tab-button[onclick="showTab('${tabId}')"]`).classList.add('active');
        }

        // Hàm cuộn sản phẩm (Giữ nguyên)
        function scrollProducts(distance) {
            const grid = document.querySelector('.related-products .product-grid');
            grid.scrollLeft += distance;
        }


        document.addEventListener('DOMContentLoaded', () => {
            showTab('description-content');
        });

        // BẮT ĐẦU THÊM CHỨC NĂNG CHO NÚT MUA NGAY

        const buyNowBtn = document.getElementById('buy-now-btn');
        const form = document.querySelector('.product-action-form');
        const defaultAction = form.getAttribute('action'); // Lấy action mặc định là add_to_cart.php

        if (buyNowBtn && form) {
            buyNowBtn.addEventListener('click', function(event) {
                // Đảm bảo form gửi dữ liệu đến add_to_cart.php trước
                form.action = defaultAction;

                // 1. Thêm một trường ẩn để báo cho add_to_cart.php biết cần chuyển hướng
                let redirectInput = document.createElement('input');
                redirectInput.type = 'hidden';
                redirectInput.name = 'redirect_to_cart';
                redirectInput.value = 'true';
                form.appendChild(redirectInput);

                // Tự động submit form. Sau đó, file add_to_cart.php sẽ xử lý logic chuyển hướng.
            });
        }

        // --- LOGIC HIỂN THỊ TOAST (ĐÃ CẬP NHẬT) ---
        function showToast(productData) {
            const toast = document.getElementById('toast-notification');

            // 1. Cập nhật nội dung sản phẩm
            document.getElementById('toast-image').src = productData.image;
            document.getElementById('toast-name').textContent = productData.name;
            document.getElementById('toast-variant').textContent = `${productData.size}`;
            document.getElementById('toast-price').textContent = productData.price;

            // 2. Hiển thị Toast
            toast.classList.add('show');

            // 3. Tự động ẩn sau 5 giây
            setTimeout(() => {
                hideToast();
            }, 5000);
        }

        function hideToast() {
            document.getElementById('toast-notification').classList.remove('show');
        }


        // Hàm sao chép voucher (Giữ nguyên logic gọi Toast mới)
        function copyVoucher(voucherCode) {
            // ... (logic sao chép voucher, bạn có thể bỏ qua nếu đã làm xong) ...

            const tempInput = document.createElement('input');
            tempInput.value = voucherCode;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            // Bạn có thể dùng alert tạm thời cho voucher hoặc thiết kế một Toast đơn giản khác
            alert(`✅ Đã sao chép mã voucher: ${voucherCode}! Vui lòng dán mã này ở trang thanh toán.`);
        }

        // Hàm để kiểm tra URL sau khi thêm giỏ hàng (ĐÃ CẬP NHẬT)
        document.addEventListener('DOMContentLoaded', () => {
            showTab('description-content');

            const urlParams = new URLSearchParams(window.location.search);

            // Kiểm tra tham số từ URL
            if (urlParams.has('add_to_cart_success')) {
                // Lấy thông tin từ tham số URL được gửi từ add_to_cart.php
                const productData = {
                    name: decodeURIComponent(urlParams.get('product_name') || 'Sản phẩm'),
                    size: decodeURIComponent(urlParams.get('product_size') || 'M'),
                    price: decodeURIComponent(urlParams.get('product_price') || '0đ'),
                    image: urlParams.get('product_image') ? `uploads/${decodeURIComponent(urlParams.get('product_image'))}` : 'uploads/no-image.png'
                };

                showToast(productData);

                // Xóa tham số khỏi URL để thông báo không hiện lại khi refresh
                const newUrl = window.location.pathname + window.location.hash;
                history.replaceState(null, '', newUrl);
            }
        });
    </script>
</body>

</html>