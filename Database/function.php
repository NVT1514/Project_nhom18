<?php
include "connectdb.php";


function dang_ky($tai_khoan, $mat_khau, $email): bool|int
{
    global $conn;

    // Kiểm tra tài khoản đã tồn tại chưa
    $sql_check = "SELECT * FROM `user` WHERE tai_khoan = '$tai_khoan' OR email = '$email'";
    $kq_check = mysqli_query($conn, $sql_check);

    if (mysqli_num_rows($kq_check) > 0) {
        return 0; // tài khoản đã tồn tại
    }

    // Mã hóa mật khẩu trước khi lưu (MD5)
    $mat_khau = md5($mat_khau);

    // Nếu chưa tồn tại thì thêm mới
    $sql = "INSERT INTO `user`(`tai_khoan`, `mat_khau`, `email`)
            VALUES ('$tai_khoan','$mat_khau','$email')";

    if (mysqli_query($conn, $sql)) {
        return 1; // thành công
    } else {
        return -1; // lỗi
    }
}

function check_exist_account($tai_khoan)
{
    global $conn;
    $sql = "SELECT * FROM `user` WHERE `tai_khoan` = '$tai_khoan'";
    $kq = mysqli_query($conn, $sql);
    if (mysqli_num_rows($kq) > 0) {
        return 1;
    } else {
        return 0;
    }
}

function check_dang_nhap($tai_khoan, $mat_khau)
{
    global $conn;

    $sql = "SELECT * FROM `user` 
            WHERE (`tai_khoan` = '$tai_khoan' OR `email` = '$tai_khoan') 
              AND `mat_khau` = '$mat_khau'";

    $kq = mysqli_query($conn, $sql);

    if (!$kq) {
        die("Lỗi SQL: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($kq) > 0) {
        return 1;
    } else {
        return 0;
    }
}

function lay_tai_khoan($tk, $mk)
{
    global $conn;

    $sql = "SELECT * FROM `user` 
            WHERE (`tai_khoan` = '$tk' OR `email` = '$tk') 
              AND `mat_khau` = '$mk'";
    $kq = mysqli_query($conn, $sql);

    if (mysqli_num_rows($kq) > 0) {
        $row = mysqli_fetch_array($kq);
        return $row;
    } else {
        return 0;
    }
}
?>

<?php
// ✅ Cập nhật hàm them_san_pham để có thêm tham số $so_luong và $ma_sku
function them_san_pham($ten_san_pham, $gia, $mo_ta, $hinh_anh, $phan_loai, $loai_chinh, $so_luong, $ma_sku)
{
    global $conn;

    // 1️⃣ Kiểm tra dữ liệu đầu vào (Thêm kiểm tra $ma_sku)
    if (empty($ten_san_pham) || empty($gia) || empty($phan_loai) || empty($loai_chinh) || empty($so_luong) || empty($ma_sku)) {
        return "❌ Vui lòng nhập đầy đủ thông tin sản phẩm (bao gồm Mã SKU).";
    }

    // ✅ Kiểm tra Mã SKU đã tồn tại chưa (Đã đổi tên cột từ 'ma_sku' thành 'sku')
    $sql_check_sku = "SELECT id FROM san_pham WHERE sku = ? LIMIT 1"; // <== ĐÃ SỬA TỪ ma_sku THÀNH sku
    $stmt_sku = $conn->prepare($sql_check_sku);
    $stmt_sku->bind_param("s", $ma_sku);
    $stmt_sku->execute();
    $result_sku = $stmt_sku->get_result();

    if ($result_sku->num_rows > 0) {
        return "❌ Mã SKU này đã tồn tại. Vui lòng chọn Mã SKU khác.";
    }

    // 2️⃣ Kiểm tra phân loại hợp lệ trong bảng phan_loai_san_pham
    $sql_check = "SELECT id FROM phan_loai_san_pham 
                  WHERE ten_phan_loai = ? 
                  AND loai_chinh = ? 
                  AND trang_thai = 'Đang sử dụng'
                  LIMIT 1";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("ss", $phan_loai, $loai_chinh);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return "❌ Phân loại hoặc loại chính không hợp lệ (không tồn tại hoặc đã ngừng sử dụng).";
    }

    $phan_loai_data = $result->fetch_assoc();
    $phan_loai_id = $phan_loai_data['id']; // để lưu khóa ngoại

    // 3️⃣ Xử lý hình ảnh (nếu có)
    // ... (logic xử lý hình ảnh giữ nguyên)
    $ten_file = null;
    if (!empty($hinh_anh['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $ten_file = time() . "_" . basename($hinh_anh["name"]);
        $target_file = $target_dir . $ten_file;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowTypes = ["jpg", "jpeg", "png", "gif", "webp"];
        if (!in_array($imageFileType, $allowTypes)) {
            return "❌ Định dạng hình ảnh không hợp lệ (chỉ jpg, jpeg, png, gif, webp).";
        }

        if (!move_uploaded_file($hinh_anh["tmp_name"], $target_file)) {
            return "❌ Lỗi khi tải hình ảnh lên.";
        }
    }

    // 4️⃣ Thêm sản phẩm (Đã đổi tên cột từ 'ma_sku' thành 'sku')
    $sql_insert = "INSERT INTO san_pham 
        (ten_san_pham, gia, mo_ta, hinh_anh, phan_loai, loai_chinh, phan_loai_id, so_luong, sku)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; // <== ĐÃ SỬA TỪ ma_sku THÀNH sku

    $stmt = $conn->prepare($sql_insert);
    // Tham số: sdssssiis (string, double, string, string, string, string, integer, integer, string)
    $stmt->bind_param(
        "sdssssiis",
        $ten_san_pham,
        $gia,
        $mo_ta,
        $ten_file,
        $phan_loai,
        $loai_chinh,
        $phan_loai_id,
        $so_luong,
        $ma_sku // Tên biến PHP vẫn là $ma_sku
    );

    if ($stmt->execute()) {
        return true;
    } else {
        return "❌ Lỗi khi thêm sản phẩm: " . $stmt->error;
    }
}
?>

<?php
// Lấy danh sách sản phẩm
function get_all_products()
{
    global $conn;
    $sql = "SELECT * FROM san_pham ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);

    $products = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    return $products;
}
?>


<?php
// Lấy sản phẩm mới (sắp xếp theo ngày thêm)
function lay_san_pham_moi($limit = 8)
{
    global $conn;
    $sql = "SELECT * FROM san_pham ORDER BY ngay_tao DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Lấy sản phẩm bán chạy (theo số lượng bán)
function lay_san_pham_ban_chay($limit = 8)
{
    global $conn;
    $sql = "SELECT * FROM san_pham ORDER BY so_luong_ban DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
