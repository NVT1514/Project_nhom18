<?php
// ==========================================================
// 1. CẤU HÌNH KẾT NỐI DATABASE VÀ KHỞI TẠO DỮ LIỆU
// ==========================================================
$servername = "localhost";
$username = "root";
$password = ""; // Thay thế bằng mật khẩu của bạn
$dbname = "project_nhom18";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Lấy ID người dùng hiện tại (Mô phỏng: Dùng ID 1 của Admin Hệ thống)
// THỰC TẾ: Bạn phải lấy ID từ SESSION/COOKIE sau khi đăng nhập.
$current_user_id = 1;

// Mảng chứa các thông báo lỗi/thành công
$message = [];
$message_type = ''; // 'success' hoặc 'error'

// --- Lấy dữ liệu cho Dropdown (NCC và Sản phẩm) ---

// 1. Lấy danh sách Nhà Cung Cấp
$ncc_list = [];
$sql_ncc = "SELECT id, ten_ncc FROM nha_cung_cap ORDER BY ten_ncc ASC";
$result_ncc = $conn->query($sql_ncc);
if ($result_ncc && $result_ncc->num_rows > 0) {
    while ($row = $result_ncc->fetch_assoc()) {
        $ncc_list[] = $row;
    }
}

// 2. Lấy danh sách Sản phẩm (chỉ lấy tên và ID, giá nhập sẽ được fetch sau bằng AJAX nếu cần, nhưng ở đây dùng giá bán làm giá nhập mặc định)
$product_list = [];
$sql_product = "SELECT id, ten_san_pham, gia FROM san_pham WHERE trang_thai IN ('Còn hàng', 'Hết hàng') ORDER BY ten_san_pham ASC";
$result_product = $conn->query($sql_product);
if ($result_product && $result_product->num_rows > 0) {
    while ($row = $result_product->fetch_assoc()) {
        $product_list[] = [
            'id' => $row['id'],
            'ten_san_pham' => $row['ten_san_pham'],
            'gia_ban_default' => $row['gia'] // Dùng giá bán làm giá nhập mặc định
        ];
    }
}
$products_json = json_encode($product_list);


// ==========================================================
// 2. XỬ LÝ LƯU ĐƠN NHẬP KHO (POST)
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $ncc_id = $_POST['nha_cung_cap'] ?? null;
    $ngay_nhap_du_kien = $_POST['ngay_nhap_du_kien'] ?? date('Y-m-d H:i:s');
    $ghi_chu = $_POST['ghi_chu'] ?? '';
    $tong_tien_nhap = $_POST['tong_tien_nhap'] ?? 0;

    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $gia_nhap_arr = $_POST['gia_nhap'] ?? [];

    // Kiểm tra dữ liệu cần thiết
    if (empty($ncc_id) || empty($product_ids)) {
        $message_type = 'error';
        $message[] = 'Vui lòng chọn Nhà Cung Cấp và thêm ít nhất một sản phẩm.';
    } else {
        // Bắt đầu Transaction
        $conn->begin_transaction();

        try {
            // 2.1. Tạo Mã Đơn Nhập (Tự động)
            // Lấy số thứ tự đơn nhập trong ngày
            $date_prefix = date('Ymd');
            $sql_max_id = "SELECT COUNT(id) as count FROM phieu_nhap_kho WHERE DATE(ngay_nhap) = CURDATE()";
            $result_max_id = $conn->query($sql_max_id);
            $row_max_id = $result_max_id->fetch_assoc();
            $next_stt = $row_max_id['count'] + 1;
            $ma_don_nhap = "PNK-{$date_prefix}-" . str_pad($next_stt, 3, '0', STR_PAD_LEFT);

            // 2.2. INSERT vào bảng phieu_nhap_kho (HEADER)
            $sql_header = "
                INSERT INTO phieu_nhap_kho (ma_don_nhap, ngay_nhap, user_id, ncc_id, tong_tien_nhap, trang_thai_don, trang_thai_nhap, ghi_chu) 
                VALUES (?, ?, ?, ?, ?, 'Đang giao dịch', 'Chờ nhập', ?)
            ";
            $stmt_header = $conn->prepare($sql_header);

            // Chuyển đổi định dạng ngày tháng đầu vào (nếu cần)
            $ngay_nhap_db = date('Y-m-d H:i:s', strtotime($ngay_nhap_du_kien));

            // Bind parameters: s, s, i, i, d, s
            $stmt_header->bind_param(
                "ssiids",
                $ma_don_nhap,
                $ngay_nhap_db,
                $current_user_id,
                $ncc_id,
                $tong_tien_nhap,
                $ghi_chu
            );
            $stmt_header->execute();
            $phieu_nhap_id = $conn->insert_id;
            $stmt_header->close();

            // 2.3. INSERT vào bảng chi_tiet_phieu_nhap (DETAILS)
            $total_details_amount = 0;

            $sql_detail = "INSERT INTO chi_tiet_phieu_nhap (phieu_nhap_id, product_id, so_luong_nhap, gia_nhap) VALUES (?, ?, ?, ?)";
            $stmt_detail = $conn->prepare($sql_detail);

            for ($i = 0; $i < count($product_ids); $i++) {
                $product_id = intval($product_ids[$i]);
                $quantity = intval($quantities[$i]);
                $gia_nhap = floatval(str_replace(',', '', $gia_nhap_arr[$i])); // Xóa dấu phẩy nếu có

                if ($product_id > 0 && $quantity > 0 && $gia_nhap >= 0) {
                    $stmt_detail->bind_param("iiid", $phieu_nhap_id, $product_id, $quantity, $gia_nhap);
                    $stmt_detail->execute();

                    // Tính lại tổng tiền từ detail để đảm bảo tính toán chính xác
                    $total_details_amount += ($quantity * $gia_nhap);
                }
            }
            $stmt_detail->close();

            // 2.4. Cập nhật lại tổng tiền chính xác (trường hợp JS tính sai hoặc cố tình gửi sai)
            $sql_update_total = "UPDATE phieu_nhap_kho SET tong_tien_nhap = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update_total);
            $stmt_update->bind_param("di", $total_details_amount, $phieu_nhap_id);
            $stmt_update->execute();
            $stmt_update->close();

            // Commit transaction nếu mọi thứ thành công
            $conn->commit();
            $message_type = 'success';
            $message[] = "Tạo đơn nhập kho **{$ma_don_nhap}** thành công! Đang chuyển hướng...";

            // Chuyển hướng về trang danh sách đơn nhập sau 3 giây
            header("Refresh: 3; URL=nhap_kho.php");
        } catch (Exception $e) {
            // Rollback transaction nếu có lỗi
            $conn->rollback();
            $message_type = 'error';
            $message[] = "Lỗi khi tạo đơn nhập: " . $e->getMessage();
            $message[] = "Vui lòng kiểm tra lại dữ liệu và kết nối database.";
        }
    }
}

// Đóng kết nối (sẽ thực hiện sau khi xử lý POST)
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Đơn Nhập Kho Mới | Quản Trị</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .lucide {
            width: 1.25rem;
            height: 1.25rem;
        }

        /* Tùy chỉnh input number loại bỏ mũi tên */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="bg-gray-50 p-4 md:p-8">

    <div class="max-w-4xl mx-auto bg-white p-6 md:p-10 shadow-xl rounded-xl">
        <div class="flex items-center justify-between border-b pb-4 mb-6">
            <h1 class="text-3xl font-extrabold text-gray-800 flex items-center">
                <i data-lucide="scan-barcode" class="lucide mr-3 w-7 h-7 text-indigo-600"></i>
                Tạo Đơn Nhập Kho Mới
            </h1>
            <a href="nhap_kho.php" class="text-indigo-600 hover:text-indigo-700 font-semibold flex items-center transition">
                <i data-lucide="list-checks" class="lucide mr-1 w-5 h-5"></i>
                Danh Sách Đơn Nhập
            </a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="p-4 mb-6 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <ul class="list-disc list-inside">
                    <?php foreach ($message as $msg): ?>
                        <li><?php echo $msg; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="tao_don_nhap.php">

            <div class="mb-8 border border-gray-200 p-6 rounded-lg bg-gray-50">
                <h2 class="text-xl font-bold text-gray-700 mb-4">Thông Tin Đơn Nhập</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="nha_cung_cap" class="block text-sm font-medium text-gray-700 required">Nhà Cung Cấp (*)</label>
                        <select id="nha_cung_cap" name="nha_cung_cap" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                            <option value="">Chọn Nhà Cung Cấp</option>
                            <?php foreach ($ncc_list as $ncc): ?>
                                <option value="<?php echo $ncc['id']; ?>"><?php echo htmlspecialchars($ncc['ten_ncc']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="ngay_nhap_du_kien" class="block text-sm font-medium text-gray-700">Ngày Nhập Dự Kiến (*)</label>
                        <input type="datetime-local" id="ngay_nhap_du_kien" name="ngay_nhap_du_kien"
                            value="<?php echo date('Y-m-d\TH:i'); ?>"
                            required class="mt-1 block w-full pl-3 pr-4 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                    </div>

                    <div class="md:col-span-1">
                        <label for="ghi_chu" class="block text-sm font-medium text-gray-700">Ghi Chú</label>
                        <textarea id="ghi_chu" name="ghi_chu" rows="1" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm resize-none"></textarea>
                    </div>
                </div>
            </div>

            <h2 class="text-xl font-bold text-gray-700 mb-4 flex items-center justify-between">
                Danh sách Sản phẩm Nhập
                <button type="button" onclick="addProductRow()" class="flex items-center bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-600 transition duration-200">
                    <i data-lucide="plus-circle" class="lucide mr-1 w-4 h-4"></i>
                    Thêm Sản Phẩm
                </button>
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider w-2/5">Sản Phẩm (*)</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider w-1/6">SL Nhập (*)</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider w-1/6">Giá Nhập (VNĐ) (*)</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider w-1/6">Thành Tiền (VNĐ)</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider w-1/12"></th>
                        </tr>
                    </thead>
                    <tbody id="product-rows-container" class="bg-white divide-y divide-gray-200">
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-4 pt-4 border-t border-gray-200">
                <span class="text-lg font-bold text-gray-700 mr-4">TỔNG TIỀN NHẬP:</span>
                <span id="tong-tien-nhap-display" class="text-2xl font-extrabold text-green-600">0 VNĐ</span>
                <input type="hidden" name="tong_tien_nhap" id="tong-tien-nhap-input" value="0">
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="flex items-center bg-green-600 text-white px-6 py-3 rounded-xl text-lg font-bold hover:bg-green-700 transition duration-200 shadow-lg shadow-green-200/50">
                    <i data-lucide="save" class="lucide mr-2 w-5 h-5"></i>
                    Lưu & Tạo Phiếu Nhập
                </button>
            </div>

        </form>
    </div>

    <script>
        // Khởi tạo icon Lucide
        lucide.createIcons();

        // Dữ liệu sản phẩm từ PHP
        const productData = <?php echo $products_json; ?>;
        const container = document.getElementById('product-rows-container');
        let rowCount = 0; // Để tạo ID/Name duy nhất cho các trường

        // --- HÀM TIỆN ÍCH ---

        // Định dạng tiền tệ
        function formatCurrency(amount) {
            if (amount === null || isNaN(amount)) return '0 VNĐ';
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND',
                minimumFractionDigits: 0
            }).format(amount);
        }

        // Chuyển từ chuỗi tiền tệ (vd: 1.000.000) sang số (1000000)
        function parseCurrency(currencyString) {
            // Loại bỏ tất cả các ký tự không phải số (trừ dấu chấm/phẩy)
            let cleanString = currencyString.replace(/[^\d,\.]/g, '');
            // Xử lý định dạng VN (1.000.000,00) -> Loại bỏ dấu chấm, đổi dấu phẩy thành chấm
            cleanString = cleanString.replace(/\./g, ''); // Loại bỏ dấu chấm ngăn cách hàng nghìn
            cleanString = cleanString.replace(/,/g, '.'); // Đổi dấu phẩy thành dấu chấm (cho phần thập phân nếu có, nhưng ở đây dùng 0)

            return parseFloat(cleanString) || 0;
        }

        // --- HÀM LOGIC CỦA BẢNG SẢN PHẨM ---

        function updateRowTotal(rowId) {
            const quantityInput = document.getElementById(`quantity-${rowId}`);
            const priceInput = document.getElementById(`price-${rowId}`);
            const totalDisplay = document.getElementById(`total-${rowId}`);

            const quantity = parseInt(quantityInput.value) || 0;
            // Lấy giá trị đã nhập trong input (có thể là chuỗi đã được format)
            const priceString = priceInput.value;
            // Chuyển giá trị từ chuỗi sang số
            const price = parseCurrency(priceString);

            const total = quantity * price;

            // Cập nhật hiển thị thành tiền (dùng định dạng tiền tệ)
            totalDisplay.textContent = formatCurrency(total);

            updateGrandTotal();
        }

        function updateGrandTotal() {
            let grandTotal = 0;
            const totalDisplays = container.querySelectorAll('[id^="total-"]');

            totalDisplays.forEach(display => {
                // Lấy giá trị tiền tệ đã được format và chuyển ngược lại thành số
                const rowTotalString = display.textContent;
                grandTotal += parseCurrency(rowTotalString);
            });

            // Cập nhật hiển thị tổng tiền
            document.getElementById('tong-tien-nhap-display').textContent = formatCurrency(grandTotal);
            // Cập nhật giá trị ẩn để gửi lên server (nên dùng giá trị số thuần túy)
            document.getElementById('tong-tien-nhap-input').value = grandTotal;
        }

        function handleProductChange(rowId, selectElement) {
            const productId = selectElement.value;
            const priceInput = document.getElementById(`price-${rowId}`);

            if (productId) {
                const selectedProduct = productData.find(p => p.id == productId);
                if (selectedProduct) {
                    // Đặt giá nhập mặc định là giá bán (gia_ban_default)
                    const defaultPrice = selectedProduct.gia_ban_default;
                    // Format giá và đặt vào input
                    priceInput.value = formatCurrency(defaultPrice).replace(' VNĐ', '').trim();
                }
            } else {
                priceInput.value = '';
            }
            // Tính lại tổng tiền sau khi thay đổi sản phẩm
            updateRowTotal(rowId);
        }

        function handlePriceInput(inputElement, rowId) {
            // Lấy giá trị hiện tại (ví dụ: "1000000")
            let value = inputElement.value.replace(/[^\d]/g, ''); // Chỉ giữ lại số

            // Format lại theo định dạng VN (1.000.000)
            if (value) {
                value = new Intl.NumberFormat('vi-VN').format(value);
            }

            // Gán giá trị đã format trở lại input
            inputElement.value = value;

            // Tính lại tổng tiền
            updateRowTotal(rowId);
        }


        // --- HÀM TẠO GIAO DIỆN ---

        function createProductOptions() {
            let options = '<option value="">Chọn sản phẩm</option>';
            productData.forEach(product => {
                options += `<option value="${product.id}">${product.ten_san_pham}</option>`;
            });
            return options;
        }

        function addProductRow() {
            rowCount++;
            const rowId = rowCount;
            const productOptions = createProductOptions();

            const newRow = document.createElement('tr');
            newRow.classList.add('align-top');
            newRow.setAttribute('data-row-id', rowId);

            newRow.innerHTML = `
                <td class="px-3 py-3">
                    <select name="product_id[]" id="product-id-${rowId}" onchange="handleProductChange(${rowId}, this)" required class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                        ${productOptions}
                    </select>
                </td>
                <td class="px-3 py-3">
                    <input type="number" name="quantity[]" id="quantity-${rowId}" value="1" min="1" oninput="updateRowTotal(${rowId})" required class="w-full p-2 border border-gray-300 rounded-lg text-sm text-center focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                </td>
                <td class="px-3 py-3">
                    <input type="text" name="gia_nhap[]" id="price-${rowId}" value="0" oninput="handlePriceInput(this, ${rowId})" required class="w-full p-2 border border-gray-300 rounded-lg text-sm text-right focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-sm font-semibold text-right text-red-600">
                    <span id="total-${rowId}">0 VNĐ</span>
                </td>
                <td class="px-3 py-3 text-center">
                    <button type="button" onclick="removeProductRow(${rowId})" class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-100 transition" title="Xóa sản phẩm">
                        <i data-lucide="trash-2" class="lucide w-4 h-4"></i>
                    </button>
                </td>
            `;

            container.appendChild(newRow);
            lucide.createIcons(); // Khởi tạo icon cho hàng mới
            updateGrandTotal(); // Cập nhật tổng tiền (thêm hàng mới là 0)
        }

        function removeProductRow(rowId) {
            const row = document.querySelector(`[data-row-id="${rowId}"]`);
            if (row) {
                row.remove();
                updateGrandTotal();
            }
        }

        // --- KHỞI TẠO BAN ĐẦU ---
        document.addEventListener('DOMContentLoaded', () => {
            // Thêm hàng sản phẩm mặc định khi tải trang
            if (productData.length > 0) {
                addProductRow();
            } else {
                container.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">Không có sản phẩm nào để nhập. Vui lòng thêm sản phẩm trước.</td></tr>';
            }
        });
    </script>
</body>

</html>