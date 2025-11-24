<?php
session_start();
// Đảm bảo đường dẫn này là chính xác
include "Database/connectdb.php";

// ================== KIỂM TRA ĐĂNG NHẬP ==================
if (!isset($_SESSION['user_id'])) {
    // Thêm điều kiện kiểm tra nếu đang ở trang login để tránh vòng lặp
    if (basename($_SERVER['PHP_SELF']) != 'login.php') {
        echo "<script>alert('Vui lòng đăng nhập để xem giỏ hàng!'); window.location.href='../login.php';</script>";
        exit();
    }
}

$user_id = $_SESSION['user_id'] ?? 0; // Sử dụng 0 nếu chưa đăng nhập (mặc dù đã chặn ở trên)

// ================== XÓA SẢN PHẨM ==================
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $check = mysqli_query($conn, "SELECT * FROM gio_hang WHERE id = $cart_id AND user_id = $user_id LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM gio_hang WHERE id = $cart_id AND user_id = $user_id LIMIT 1");
    }
    // Giữ nguyên các biến trạng thái voucher khi reload
    $redirect_url = "cart.php";
    if (isset($_POST['applied_voucher_code']) && !empty($_POST['applied_voucher_code'])) {
        $redirect_url .= "?voucher=" . urlencode($_POST['applied_voucher_code']);
    }
    header("Location: " . $redirect_url);
    exit();
}

// ================== CẬP NHẬT SỐ LƯỢNG ==================
if (isset($_POST['update_qty'])) {
    foreach ($_POST['quantity'] as $cart_id => $qty) {
        $qty = max(1, intval($qty));
        mysqli_query($conn, "UPDATE gio_hang SET so_luong = $qty WHERE id = $cart_id AND user_id = $user_id");
    }
    // Giữ nguyên các biến trạng thái voucher khi reload
    $redirect_url = "cart.php";
    if (isset($_POST['applied_voucher_code']) && !empty($_POST['applied_voucher_code'])) {
        $redirect_url .= "?voucher=" . urlencode($_POST['applied_voucher_code']);
    }
    header("Location: " . $redirect_url);
    exit();
}

// ================== LẤY SẢN PHẨM TRONG GIỎ & TÍNH TỔNG ==================
$sql = "SELECT gh.id AS cart_id, sp.id AS san_pham_id, sp.ten_san_pham, sp.gia, sp.hinh_anh, gh.so_luong, gh.size
        FROM gio_hang gh
        JOIN san_pham sp ON gh.san_pham_id = sp.id
        WHERE gh.user_id = $user_id";
$result = mysqli_query($conn, $sql);

$raw_total = 0;
$cart_items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subtotal = $row['gia'] * $row['so_luong'];
    $raw_total += $subtotal;
    $cart_items[] = $row;
}
// Đặt lại con trỏ cho lần dùng sau (nếu cần, nhưng ở đây không cần thiết)
// mysqli_data_seek($result, 0); 
$cart_count = count($cart_items);

// ================== ÁP DỤNG MÃ GIẢM GIÁ (SỬ DỤNG BẢNG VOUCHERS) ==================
$giam_phan_tram = 0;      // Tên biến mới (từ database)
$giam_toi_da = 0.00;      // Tên biến mới (từ database)
$min_order_amount_numeric = 0.00; // Tên biến mới cho giá trị số của điều kiện
$discount_rate = 0;
$discount_amount = 0.00; // Tổng tiền giảm cuối cùng
$voucher_code = "";
$voucher_message = "";
$min_order_text = ""; // Chuỗi điều kiện (ví dụ: "Đơn hàng từ 300K")
$raw_total = $raw_total ?? 0.00; // Đảm bảo $raw_total tồn tại

// Lấy mã giảm giá từ form submit (apply_voucher, place_order) hoặc từ URL (sau khi update/remove)
$voucher_code_input = '';
if (isset($_POST['apply_voucher'])) {
    $voucher_code_input = isset($_POST['voucher_input_hidden'])
        ? strtoupper(trim($_POST['voucher_input_hidden']))
        : (isset($_POST['voucher']) ? strtoupper(trim($_POST['voucher'])) : '');
} elseif (isset($_POST['place_order'])) {
    // Lấy từ input hidden
    $voucher_code_input = isset($_POST['applied_voucher_code']) ? mysqli_real_escape_string($conn, $_POST['applied_voucher_code']) : '';
} elseif (isset($_GET['voucher'])) {
    $voucher_code_input = strtoupper(trim($_GET['voucher'])); // Lấy từ URL sau khi update/remove
}

if (!empty($voucher_code_input)) {
    $voucher_code = $voucher_code_input;
    $current_date = date('Y-m-d H:i:s');
    $safe_voucher_code = mysqli_real_escape_string($conn, $voucher_code);

    // Truy vấn voucher
    // Dòng 91 (Code Đã Sửa: Thay tên cột và đảm bảo trạng thái)
    $sql_voucher = "SELECT * FROM vouchers 
                 WHERE ma_voucher = '$safe_voucher_code' 
                 AND ngay_het_han >= '$current_date' 
                 AND trang_thai = 'Hoạt động' 
                 LIMIT 1";

    // Dòng 92
    $voucher_result = mysqli_query($conn, $sql_voucher);
    $voucher_data = mysqli_fetch_assoc($voucher_result);

    // 2. Xử lý Voucher Data
    if ($voucher_data) {
        // Ánh xạ tên cột mới
        $giam_phan_tram = (float)$voucher_data['giam_phan_tram'];
        $giam_toi_da = (float)$voucher_data['gia_tri_toi_da'];
        $min_order_text = $voucher_data['dieu_kien'];

        // Cần trích xuất giá trị số từ $min_order_text (ví dụ: 'Đơn hàng từ 300K' => 300000)
        // DÙNG REGEX HOẶC CỘT SỐ RIÊNG LÀ GIẢI PHÁP TỐT NHẤT. Tạm thời dùng 0 nếu bạn chưa có cột số
        // >>> GIẢ ĐỊNH TẠM THỜI: $min_order_amount_numeric đã được gán giá trị số <<<
        $min_order_amount_numeric = 0; // Thay thế bằng logic trích xuất số của bạn

        // 3. Kiểm tra điều kiện đơn hàng tối thiểu (DÒNG 109)
        if ($raw_total >= $min_order_amount_numeric) {

            // Logic Tính toán Giảm giá
            // DÒNG 111 (Code đã sửa)
            if ($giam_phan_tram > 0) { // Giảm theo %

                // DÒNG 112
                $discount_rate = $giam_phan_tram / 100;

                // DÒNG 113
                $discount_amount = $raw_total * $discount_rate;

                // DÒNG 114: Kiểm tra giới hạn giảm tối đa ($giam_toi_da)
                if ($giam_toi_da > 0 && $discount_amount > $giam_toi_da) {
                    // DÒNG 115
                    $discount_amount = $giam_toi_da;
                    // DÒNG 116
                    $voucher_message = "Áp dụng mã **" . $voucher_code . "** thành công! Giảm tối đa " . number_format($giam_toi_da) . "đ.";
                } else {
                    // DÒNG 118
                    $voucher_message = "Áp dụng mã **" . $voucher_code . "** thành công! Giảm " . $giam_phan_tram . "%.";
                }

                // DÒNG 121 (Code đã sửa): Giảm cố định (hoặc Freeship)
            } elseif ($giam_phan_tram === 0 && $giam_toi_da > 0) {
                // DÒNG 121
                $discount_amount = $giam_toi_da;
                // DÒNG 122
                $voucher_rate = round(($discount_amount / $raw_total) * 100, 2);
                // DÒNG 123
                $voucher_message = "Áp dụng mã **" . $voucher_code . "** thành công! Giảm trực tiếp " . number_format($discount_amount) . "đ.";
            }

            // Bỏ qua khối kiểm tra $max_discount dư thừa ở cuối

        } else {
            // Đơn hàng không đủ điều kiện tối thiểu
            $voucher_code = ""; // Đặt lại để không hiển thị trong input
            $voucher_message = "⚠️ Mã **" . $voucher_code_input . "** không áp dụng! Đơn hàng phải từ " . number_format($min_order_amount_numeric, 0, ',', '.') . "đ.";
            $discount_amount = 0.00;
        }
    } else {
        // Mã không hợp lệ hoặc hết hạn
        $voucher_code = ""; // Đặt lại để không hiển thị trong input
        $voucher_message = "Mã **" . $voucher_code_input . "** không hợp lệ hoặc đã hết hạn.";
    }
}

// Tính tổng sau giảm giá
$final_total = $raw_total - $discount_amount;
$final_total = max(0, $final_total); // Đảm bảo tổng không âm

// ================== XỬ LÝ THANH TOÁN ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Lấy lại các giá trị từ form
    $payment_method = $_POST['payment'];
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    // Lấy mã giảm giá đã áp dụng (từ input hidden trong form checkout)
    $applied_voucher_code = mysqli_real_escape_string($conn, $_POST['applied_voucher_code']);

    if ($cart_count > 0) {
        // Cần tính lại chính xác tổng tiền và giảm giá (giống như logic trên)
        // để đảm bảo tính toàn vẹn (tránh trường hợp người dùng sửa đổi giá trị hidden)
        // **Để đơn giản, chúng ta sẽ sử dụng lại $final_total và $discount_amount đã tính ở trên.**
        // Trong môi trường production, bạn nên chạy lại logic voucher ở đây!
        $total_to_save = $final_total;
        $discount_amount_to_save = $discount_amount;

        // Tạo mã đơn hàng
        $order_code = 'DH' . time();

        // Thêm vào bảng đơn hàng (sử dụng $total_to_save)
        // status: 1 (Đã đặt hàng - COD) hoặc 0 (Chờ thanh toán - VNPAY/QR)
        $status = ($payment_method === 'cod') ? 1 : 0;

        $sql_insert_order = "
        INSERT INTO don_hang (user_id, order_id, fullname, phone, address, total, payment_method, status, created_at)
        VALUES ($user_id, '$order_code', '$fullname', '$phone', '$address', $total_to_save, '$payment_method', $status, NOW())
        ";

        if (mysqli_query($conn, $sql_insert_order)) {
            $new_order_id = mysqli_insert_id($conn);

            // Thêm chi tiết từng sản phẩm
            foreach ($cart_items as $item) {
                $product_id = $item['san_pham_id'];
                $product_name = mysqli_real_escape_string($conn, $item['ten_san_pham']);
                $price = $item['gia'];
                $quantity = $item['so_luong'];
                $size = mysqli_real_escape_string($conn, $item['size']);

                $sql_detail = "
                    INSERT INTO chi_tiet_don_hang (order_id, product_id, product_name, price, quantity, size)
                    VALUES ($new_order_id, $product_id, '$product_name', $price, $quantity, '$size')
                ";
                mysqli_query($conn, $sql_detail);
            }

            // Xóa giỏ hàng sau khi tạo đơn
            mysqli_query($conn, "DELETE FROM gio_hang WHERE user_id = $user_id");

            // Chuyển hướng
            if ($payment_method === 'cod') {
                echo "<script>alert('Đặt hàng thành công! Đơn hàng sẽ sớm được xử lý.'); window.location.href='trang_thai_don_hang.php';</script>";
                exit;
            } else {
                // VNPAY/QR - Chuyển sang trang thanh toán VNPAY (hoặc hiển thị modal QR)
                // Lưu ý: Nếu muốn dùng VNPAY thật, cần tạo file vnpay_payment.php
                echo "<script>alert('Tạo đơn hàng thành công, chuyển sang cổng thanh toán.'); window.location.href='trang_thai_don_hang.php';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Lỗi khi tạo đơn hàng: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Giỏ hàng trống!');</script>";
    }
}

// LẤY TÀI KHOẢN NHẬN THANH TOÁN
$acc_result = mysqli_query($conn, "SELECT * FROM payment_accounts ORDER BY id DESC LIMIT 1");
$account_data = mysqli_fetch_assoc($acc_result);
$bank = $account_data['bank_name'] ?? 'VCB';
$account = $account_data['account_number'] ?? '0123456789';
$display_name = $account_data['display_name'] ?? 'Ngân hàng nhận tiền';

// Lấy danh sách các voucher đang hoạt động để hiển thị (tùy chọn)
$active_vouchers = [];
$sql_vouchers_display = "SELECT 
                            ma_voucher, 
                            giam_phan_tram, 
                            gia_tri_toi_da, 
                            dieu_kien, 
                            ngay_het_han 
                         FROM 
                            vouchers 
                         WHERE 
                            ngay_het_han >= CURRENT_DATE() 
                            AND trang_thai = 'Hoạt động' 
                         ORDER BY 
                            giam_phan_tram DESC 
                         LIMIT 3"; // Giả định sắp xếp theo % giảm

$result_vouchers_display = mysqli_query($conn, $sql_vouchers_display);
while ($row = mysqli_fetch_assoc($result_vouchers_display)) {
    $active_vouchers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thanh toán đơn hàng</title>
    <link rel="stylesheet" href="css/cart.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <div id="qrBox"></div>
        </div>
    </div>

    <div class="main-container">
        <div class="checkout-left-col">

            <div class="section-box">
                <h2><i class="fa-solid fa-cart-shopping"></i> Chi tiết giỏ hàng</h2>
                <?php if ($cart_count > 0): ?>
                    <form method="POST" id="updateQtyForm" action="cart.php?voucher=<?= urlencode($voucher_code) ?>">
                        <input type="hidden" name="update_qty" value="1">
                        <input type="hidden" name="applied_voucher_code" value="<?= htmlspecialchars($voucher_code) ?>">

                        <div class="cart-items-container">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item-detail-card">

                                    <a href="?remove=<?= $item['cart_id'] ?>&voucher=<?= urlencode($voucher_code) ?>"
                                        class="remove-item-btn"
                                        onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?');"
                                        title="Xóa sản phẩm"><i class="fa-solid fa-xmark"></i></a>

                                    <div class="product-info-left">
                                        <img src="uploads/<?= htmlspecialchars($item['hinh_anh']) ?>" alt="<?= htmlspecialchars($item['ten_san_pham']) ?>" class="product-thumb">
                                        <div class="product-details-content">
                                            <span class="product-name-title"><?= htmlspecialchars($item['ten_san_pham']) ?></span>
                                            <span class="product-size">Size: <?= htmlspecialchars($item['size']) ?></span>
                                            <span class="product-price-unit"><?= number_format($item['gia'], 0, ',', '.') ?>đ / SP</span>
                                        </div>
                                    </div>

                                    <div class="product-actions-right">
                                        <div class="quantity-control-new">
                                            <label>Số lượng:</label>
                                            <input type="number"
                                                name="quantity[<?= $item['cart_id'] ?>]"
                                                value="<?= $item['so_luong'] ?>"
                                                min="1"
                                                class="qty-input-field"
                                                onchange="updateCartItem(<?= $item['cart_id'] ?>, this.value, <?= $item['gia'] ?>)">
                                        </div>
                                        <div class="item-subtotal">
                                            <label>Thành tiền:</label>
                                            <span class="subtotal-amount" id="subtotal-<?= $item['cart_id'] ?>">
                                                <?= number_format($item['gia'] * $item['so_luong'], 0, ',', '.') ?>đ
                                            </span>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="update-btn-row">
                        </div>
                    </form>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 50px 0;">Giỏ hàng của bạn đang trống! Hãy thêm sản phẩm nhé.</p>
                <?php endif; ?>
            </div>

            <form id="checkoutForm" method="POST" onsubmit="return handlePayment(event)">
                <input type="hidden" name="applied_voucher_code" id="appliedVoucherCode" value="<?= htmlspecialchars($voucher_code) ?>">
                <input type="hidden" name="place_order" value="1">
                <input type="hidden" id="finalTotalValue" value="<?= $final_total ?>">

                <div class="section-box">
                    <h2><i class="fa-solid fa-address-card"></i> Thông tin nhận hàng</h2>
                    <input type="text" name="fullname" placeholder="Họ và tên" required>
                    <input type="text" name="phone" placeholder="Số điện thoại" required>
                    <input type="text" name="address" placeholder="Địa chỉ nhận hàng (chi tiết: Số nhà, Tên đường, Tỉnh/Thành)" required>
                </div>

                <div class="section-box">
                    <h2><i class="fa-solid fa-truck-fast"></i> Phương thức vận chuyển</h2>
                    <div class="shipping-method">
                        <label>
                            <input type="radio" name="shipping" value="freeship" checked>
                            <span class="shipping-info">
                                <strong>Freeship đơn hàng</strong><br>
                                <span style="font-size: 14px; color: #2ecc71;">Miễn phí</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="section-box">
                    <h2><i class="fa-solid fa-wallet"></i> Hình thức thanh toán</h2>
                    <div class="payment-method">
                        <label>
                            <input type="radio" name="payment" value="cod" checked>
                            <i class="fa-solid fa-money-bill-wave"></i> Thanh toán khi nhận hàng (COD)
                        </label>
                        <label>
                            <input type="radio" name="payment" value="vnpay">
                            <i class="fa-solid fa-qrcode"></i> Thanh toán qua QR (VNPAY/Momo)
                        </label>
                    </div>
                </div>

                <?php if ($cart_count > 0): ?>
                    <button type="submit" class="place-order-btn" id="submitBtn">HOÀN TẤT ĐẶT HÀNG</button>
                <?php else: ?>
                    <button type="button" class="place-order-btn" disabled>Giỏ hàng trống</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="cart-right-col">
            <div class="section-box cart-summary">
                <h2>Tóm tắt đơn hàng <span>(<?= $cart_count ?> sản phẩm)</span></h2>

                <?php if ($cart_count > 0): ?>
                    <div class="cart-item-list">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <img src="uploads/<?= htmlspecialchars($item['hinh_anh']) ?>" alt="<?= htmlspecialchars($item['ten_san_pham']) ?>">
                                <div class="item-details">
                                    <div class="name"><?= htmlspecialchars($item['ten_san_pham']) ?></div>
                                    <div class="qty">x<?= $item['so_luong'] ?> | Size: <?= htmlspecialchars($item['size']) ?></div>
                                </div>
                                <div class="item-price"><?= number_format($item['gia'] * $item['so_luong'], 0, ',', '.') ?>đ</div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="voucher-box">
                        <form method="POST" id="voucherForm">
                            <input type="hidden" name="apply_voucher" value="1">
                            <input type="hidden" name="voucher_input_hidden" id="voucherInputHidden" value="<?= htmlspecialchars($voucher_code) ?>">

                            <h3><i class="fa-solid fa-tags"></i> Ưu Đãi Dành Cho Bạn</h3>

                            <div class="voucher-input-group">
                                <input type="text" name="voucher" id="voucherInput" placeholder="Nhập mã giảm giá" value="<?= htmlspecialchars($voucher_code) ?>">
                                <button type="button" onclick="applyVoucher('input')">Áp dụng Voucher</button>
                            </div>
                        </form>

                        <?php if (!empty($voucher_message)): ?>
                            <div class="voucher-message <?= ($discount_amount > 0) ? 'success' : 'error' ?>">
                                <?= $voucher_message ?>
                            </div>
                        <?php endif; ?>

                        <div class="voucher-list" id="voucherList">
                            <?php foreach ($active_vouchers as $v):
                                $is_percentage = ($v['giam_phan_tram'] > 0);
                                $ma_voucher = htmlspecialchars($v['ma_voucher']);
                                $min_order = number_format((float)($v['min_order_amount'] ?? 0), 0, ',', '.');
                                $gia_tri_toi_da = (float)($v['gia_tri_toi_da'] ?? 0);
                                $is_selected = ($voucher_code == $ma_voucher) ? 'selected' : '';

                                // Tinh chỉnh text hiển thị chính (dòng 1)
                                if ($is_percentage) {
                                    $phan_tram = (int)$v['giam_phan_tram'];
                                    $display_text = "Giảm {$phan_tram}%";
                                    if ($gia_tri_toi_da > 0) {
                                        $display_text .= " (Tối đa " . number_format($gia_tri_toi_da, 0, ',', '.') . "đ)";
                                    }
                                } else {
                                    $display_text = "Giảm " . number_format($v['discount_value'], 0, ',', '.') . "đ"; // Dùng discount_value cho voucher fixed
                                }
                            ?>
                                <div class="voucher-card <?= $is_selected ?>" onclick="applyVoucher('<?= $ma_voucher ?>')">
                                    <strong><?= $ma_voucher ?></strong>
                                    <p><?= $display_text ?></p>
                                    <p>Đơn từ <?= $min_order ?>đ</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>


                    <div class="total-summary">
                        <div class="total-row">
                            <span>Tạm tính:</span>
                            <span><?= number_format($raw_total, 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="total-row">
                            <span>Giảm giá (<?= $voucher_code ?: 'Chưa áp dụng' ?>):</span>
                            <span style="color: #27ae60;">- <?= number_format($discount_amount, 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="total-row">
                            <span>Phí vận chuyển:</span>
                            <span>0đ</span>
                        </div>
                        <hr>
                        <div class="total-row final-total-row">
                            <span>TỔNG CỘNG:</span>
                            <span><?= number_format($final_total, 0, ',', '.') ?>đ</span>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 50px 0;">Giỏ hàng trống!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <script>
        // Lấy giá trị tổng cuối cùng từ PHP (đã được format là số nguyên)
        const finalTotal = parseInt(document.getElementById('finalTotalValue').value);

        // Hàm định dạng số có dấu phẩy
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Hàm áp dụng voucher
        function applyVoucher(source) {
            let code = '';
            if (source === 'input') {
                code = document.getElementById('voucherInput').value;
            } else {
                code = source;
            }
            // Đặt giá trị vào input hidden và input hiển thị
            document.getElementById('voucherInputHidden').value = code.toUpperCase().trim();
            document.getElementById('voucherInput').value = code.toUpperCase().trim();

            // Cập nhật giá trị trong form checkout
            document.getElementById('appliedVoucherCode').value = code.toUpperCase().trim();

            // Submit form voucher
            document.getElementById('voucherForm').submit();
        }


        // Hàm xử lý thanh toán
        function handlePayment(event) {
            const form = document.getElementById('checkoutForm');
            const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
            const totalForQR = finalTotal > 0 ? finalTotal : 1000; // Đặt tối thiểu 1000đ nếu tổng bằng 0

            // 1. Kiểm tra giỏ hàng trống (cũng đã chặn bằng nút)
            if (<?= $cart_count ?> === 0) {
                alert('Giỏ hàng trống, không thể đặt hàng!');
                return false;
            }

            // 2. Kiểm tra thông tin nhận hàng
            if (!form.checkValidity()) {
                // Trigger HTML5 validation messages
                form.reportValidity();
                return false;
            }

            // 3. Xử lý VNPAY/QR
            if (paymentMethod === 'vnpay') {
                event.preventDefault();

                const formData = new FormData(form);
                const name = formData.get('fullname');
                const phone = formData.get('phone');

                // Tạo mã đơn hàng tạm thời cho nội dung chuyển khoản
                const orderId = "DH" + Date.now().toString().slice(-6);

                // Tạo link QR
                const qrUrl = `https://img.vietqr.io/image/<?= $bank ?>-<?= $account ?>-compact.png?amount=${totalForQR}&addInfo=Don%20hang%20${orderId}`;

                const qrHTML = `
                    <div class="qr-wrapper">
                        <h2>Thanh toán đơn hàng</h2>
                        <p class="amount">Tổng tiền: ${numberWithCommas(totalForQR)}đ</p>
                        <p>Người nhận: ${name}<br>SĐT: ${phone}</p>
                        <p><b>Tài khoản nhận:</b> <?= $display_name ?> (<?= $account ?>)</p>
                        <p><b>Nội dung CK:</b> Don hang ${orderId}</p>
                        <img src="${qrUrl}" class="qr-img" alt="QR thanh toán">
                        <p style="color: #e53935; font-weight: 600;">⚠️ Lưu ý: Nội dung chuyển khoản phải ghi đúng: Don hang ${orderId}</p>
                        <button onclick="submitCheckoutForm()">Đã chuyển khoản (Tạo đơn)</button>
                    </div>`;

                document.getElementById('qrBox').innerHTML = qrHTML;
                document.getElementById('qrModal').style.display = 'flex';
                return false;
            }

            // 4. Xử lý COD (submit form bình thường)
            return true;
        }

        function submitCheckoutForm() {
            // Khi nhấn "Đã chuyển khoản (Tạo đơn)", ta cho form checkout submit thật
            // Form sẽ được xử lý lại bằng PHP để tạo đơn hàng.
            document.getElementById('checkoutForm').submit();
            closeModal();
        }

        function closeModal() {
            document.getElementById('qrModal').style.display = 'none';
        }

        // Đóng modal khi click ra ngoài
        window.onclick = function(e) {
            const modal = document.getElementById('qrModal');
            if (e.target === modal) {
                closeModal();
            }
        }

        // Loại bỏ hàm scrollVoucher đã cũ.
    </script>
    <script>
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        // Hàm cập nhật số lượng và tính lại tổng tiền
        function updateCartItem(cartId, qty, price) {
            qty = parseInt(qty);
            if (qty < 1) qty = 1;

            const subtotalEl = document.querySelector(`#subtotal-${cartId}`);

            // 1. Chuẩn bị dữ liệu gửi đi (POST data)
            const formData = new FormData();
            formData.append('ajax_update_qty', 1);
            formData.append('cart_id', cartId);
            formData.append('qty', qty); // Gửi số lượng khách muốn mua

            // 2. Gửi yêu cầu AJAX
            fetch('cart_update_ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    // 3. Xử lý phản hồi JSON
                    if (data.success) {
                        const actualQty = data.new_qty; // Lấy số lượng thực tế đã được giới hạn từ PHP
                        const newSubtotal = actualQty * price;

                        // A. Cập nhật input number trong giỏ hàng
                        const qtyInput = document.querySelector(`input[name="quantity[${cartId}]"]`);
                        qtyInput.value = actualQty;

                        // B. Cập nhật thành tiền
                        subtotalEl.textContent = numberWithCommas(newSubtotal) + "đ";

                        // C. Hiển thị thông báo nếu số lượng bị giới hạn
                        if (actualQty !== qty) {
                            alert(`⚠️ ${data.message}`);
                        }

                        // D. Cập nhật lại tổng tiền tạm tính
                        recalculateTotal();

                    } else {
                        // Hiển thị lỗi từ PHP (ví dụ: lỗi đăng nhập, lỗi CSDL)
                        alert(`Lỗi: ${data.message}`);
                    }
                })
                .catch(err => {
                    // Lỗi kết nối mạng
                    console.error(err);
                    alert('Lỗi kết nối máy chủ! Vui lòng thử lại.');
                });
        }

        function recalculateTotal() {
            let total = 0;
            document.querySelectorAll('.subtotal-amount').forEach(el => {
                const val = parseInt(el.textContent.replace(/\D/g, '')) || 0;
                total += val;
            });

            // Tạm tính
            document.querySelector('.total-summary .total-row span:last-child').textContent = numberWithCommas(total) + "đ";

            // Giảm giá (tạm tính lại, vẫn dựa trên voucher hiện có)
            let discount = <?= (int)$discount_amount ?>;
            let finalTotal = Math.max(0, total - discount);
            document.querySelector('.final-total-row span:last-child').textContent = numberWithCommas(finalTotal) + "đ";
        }
    </script>


    <?php include 'footer.php'; ?>
</body>

</html>