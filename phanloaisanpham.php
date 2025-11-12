<?php
// PHP logic đã được sửa và tối ưu bảo mật (xử lý NULL, kiểm tra trùng lặp, logic update)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Giả định "Database/connectdb.php" đã kết nối $conn
include "Database/connectdb.php";

$message = "";

// --- Lấy danh sách danh mục cha (Cấp 1) để điền vào Select Box ---
$parent_categories = [];
// Lấy thêm cả loai_chinh của danh mục cha để dùng cho JS tự động điền
$sql_parent = "SELECT id, ten_phan_loai, loai_chinh FROM phan_loai_san_pham WHERE parent_id IS NULL AND trang_thai = 'Đang sử dụng' ORDER BY ten_phan_loai ASC";
$result_parent = $conn->query($sql_parent);
if ($result_parent) {
    while ($row = $result_parent->fetch_assoc()) {
        $parent_categories[] = $row;
    }
}

// --- Xử lý thêm mới ---
if (isset($_POST['add'])) {
    $ten = trim($_POST['ten_phan_loai']);
    $mo_ta = trim($_POST['mo_ta']);
    $loai_chinh = $_POST['loai_chinh'] ?? 'Khác';
    $trang_thai = $_POST['trang_thai'] ?? 'Đang sử dụng';
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;

    // Nếu là danh mục Cấp 1, $loai_chinh là bắt buộc, nếu là danh mục con, $loai_chinh sẽ được lấy từ select box (thường được điền tự động)

    if ($ten != "") {
        $check = $conn->prepare("SELECT id FROM phan_loai_san_pham WHERE ten_phan_loai = ?");
        $check->bind_param("s", $ten);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "⚠️ Phân loại '$ten' đã tồn tại!";
        } else {
            // Xử lý Prepared Statement cho NULL (đã sửa)
            if ($parent_id === NULL) {
                // Thêm danh mục Cấp 1
                $sql = "INSERT INTO phan_loai_san_pham (ten_phan_loai, mo_ta, loai_chinh, trang_thai, parent_id)
                         VALUES (?, ?, ?, ?, NULL)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $ten, $mo_ta, $loai_chinh, $trang_thai);
            } else {
                // Thêm danh mục con (Cấp 2) - loai_chinh sẽ được lấy từ form (đã được JS điền)
                $sql = "INSERT INTO phan_loai_san_pham (ten_phan_loai, mo_ta, loai_chinh, trang_thai, parent_id)
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $ten, $mo_ta, $loai_chinh, $trang_thai, $parent_id);
            }

            if ($stmt->execute()) {
                $message = "✅ Thêm phân loại thành công!";
            } else {
                $message = "❌ Lỗi: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    } else {
        $message = "⚠️ Tên phân loại không được để trống!";
    }
}

// --- Xử lý cập nhật ---
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $ten = trim($_POST['ten_phan_loai']);
    $mo_ta = trim($_POST['mo_ta']);
    $loai_chinh = $_POST['loai_chinh'];
    $trang_thai = $_POST['trang_thai'];
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;

    if ($parent_id !== NULL && $parent_id == $id) {
        $message = "❌ Lỗi: Danh mục không thể làm Cha của chính nó!";
    } else {
        // Xử lý Prepared Statement cho NULL (đã sửa)
        if ($parent_id === NULL) {
            $sql = "UPDATE phan_loai_san_pham SET ten_phan_loai=?, mo_ta=?, loai_chinh=?, trang_thai=?, parent_id=NULL WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $ten, $mo_ta, $loai_chinh, $trang_thai, $id);
        } else {
            $sql = "UPDATE phan_loai_san_pham SET ten_phan_loai=?, mo_ta=?, loai_chinh=?, trang_thai=?, parent_id=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $ten, $mo_ta, $loai_chinh, $trang_thai, $parent_id, $id);
        }

        if ($stmt->execute()) {
            $message = "✅ Cập nhật phân loại thành công!";
        } else {
            $message = "❌ Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --- Xử lý xóa ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Cần kiểm tra và xóa/cập nhật khóa ngoại trước nếu có
    $sql = "DELETE FROM phan_loai_san_pham WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "🗑️ Đã xóa phân loại thành công!";
    } else {
        $message = "❌ Lỗi: " . $stmt->error . " (Kiểm tra khóa ngoại với bảng sản phẩm.)";
    }
    $stmt->close();
}

// --- TÌM KIẾM, LỌC & LẤY DỮ LIỆU DANH SÁCH ---
$search = $_GET['search'] ?? '';
$filter_loai = $_GET['filter_loai'] ?? '';
$filter_parent = $_GET['filter_parent'] ?? '';

$sql = "SELECT p.*, parent.ten_phan_loai AS parent_name
        FROM phan_loai_san_pham p
        LEFT JOIN phan_loai_san_pham parent ON p.parent_id = parent.id
        WHERE 1=1";

$types = "";
$params = [];

if (!empty($search)) {
    $sql .= " AND p.ten_phan_loai LIKE ?";
    $types .= "s";
    $params[] = "%" . $search . "%";
}
if (!empty($filter_loai)) {
    $sql .= " AND p.loai_chinh = ?";
    $types .= "s";
    $params[] = $filter_loai;
}
// Xử lý lọc theo Parent ID (bao gồm cả NULL)
if (!empty($filter_parent)) {
    if ($filter_parent === 'NULL') {
        $sql .= " AND p.parent_id IS NULL";
    } else {
        $sql .= " AND p.parent_id = ?";
        $types .= "i";
        $params[] = (int)$filter_parent;
    }
}

$sql .= " ORDER BY p.parent_id ASC, p.ten_phan_loai ASC";

$stmt = $conn->prepare($sql);

if (!empty($types)) {
    // Sử dụng splat operator để bind_param với mảng tham số
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Phân loại sản phẩm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <style>
        /* CSS KHÔNG THAY ĐỔI NHIỀU, CHỈ THÊM LỚP ẨN */
        /* ======================================== */
        /* === 1. THIẾT LẬP CHUNG VÀ BỐ CỤC MAIN === */
        /* ======================================== */
        :root {
            --primary-color: #007bff;
            /* Xanh dương */
            --success-color: #28a745;
            /* Xanh lá */
            --danger-color: #dc3545;
            /* Đỏ */
            --secondary-color: #6c757d;
            /* Xám */
            --bg-light: #f8f9fa;
            --border-color: #dee2e6;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-light);
        }

        .main-content {
            padding: 20px 30px;
            /* Giả sử sidebar đã thiết lập container/main-content */
        }

        h1 {
            color: #343a40;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }

        /* ======================================== */
        /* === 2. THÔNG BÁO MESSAGE === */
        /* ======================================== */
        .message {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: #fff3cd;
            /* Vàng nhạt */
            border: 1px solid #ffeeba;
            color: #856404;
        }

        /* Tùy chỉnh màu sắc cho thông báo thành công (nếu có) */
        .message[class*="✅"] {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        /* Tùy chỉnh màu sắc cho thông báo lỗi (nếu có) */
        .message[class*="❌"] {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        /* ======================================== */
        /* === 3. FORM THÊM/SỬA === */
        /* ======================================== */
        form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            /* Tăng khoảng cách giữa các cột */
            width: 100%;
            margin-bottom: 15px;
        }

        .form-row>* {
            flex: 1;
        }

        /* Thêm lớp CSS để ẩn/hiện */
        .hidden-group {
            display: none;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }

        input[type="text"],
        textarea,
        select {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        textarea {
            height: 80px;
            /* Tăng chiều cao textarea */
            resize: vertical;
        }

        /* Nút hành động trong form */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            /* Đẩy các nút sang phải */
            margin-top: 20px;
        }

        .action-buttons button,
        .action-buttons #btn-cancel {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s, transform 0.1s;
        }

        #btn-add {
            background: var(--primary-color);
            color: white;
        }

        #btn-add:hover {
            background: #0056b3;
        }

        #btn-update {
            background: var(--success-color);
            color: white;
        }

        #btn-update:hover {
            background: #1e7e34;
        }

        #btn-cancel {
            background: var(--secondary-color);
            color: white;
        }

        #btn-cancel:hover {
            background: #5a6268;
        }

        /* ======================================== */
        /* === 4. THANH TÌM KIẾM VÀ LỌC === */
        /* ======================================== */
        .search-form {
            background: var(--bg-light);
            padding: 15px 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .search-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-group input[type="text"],
        .search-group select {
            padding: 9px;
            width: auto;
            flex-grow: 1;
            /* Cho phép input tìm kiếm mở rộng hơn */
        }

        .search-group input[type="text"] {
            flex-basis: 300px;
            /* Ưu tiên input tìm kiếm rộng hơn */
        }

        .search-group button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 9px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .search-group button:hover {
            background-color: #0056b3;
        }

        .clear-filter {
            color: var(--danger-color);
            text-decoration: none;
            font-weight: 600;
            padding: 9px 10px;
            border-radius: 6px;
        }

        .clear-filter:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }

        /* ======================================== */
        /* === 5. BẢNG DỮ LIỆU TABLE === */
        /* ======================================== */
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: var(--box-shadow);
            border-radius: 8px;
            overflow: hidden;
            /* Quan trọng để border-radius hoạt động */
        }

        thead {
            background-color: var(--primary-color);
            color: white;
            text-align: left;
        }

        th,
        td {
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            background-color: #f2f2f2;
            /* Zebra stripe */
        }

        tbody tr:hover {
            background-color: #e9ecef;
        }

        /* Hiển thị danh mục con */
        tbody td:nth-child(2) {
            /* Cột Tên phân loại */
            font-weight: 600;
        }

        /* Cột Danh mục Cha */
        tbody td:nth-child(3) span {
            font-size: 0.9em;
        }

        /* Trạng thái (Status Badge) */
        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 700;
            display: inline-block;
        }

        .status.active {
            background-color: #d4edda;
            color: var(--success-color);
        }

        .status.inactive {
            background-color: #f8d7da;
            color: var(--danger-color);
        }

        /* Nút hành động trong bảng */
        .actions a {
            text-decoration: none;
            padding: 5px 8px;
            border-radius: 4px;
            margin-right: 5px;
            font-size: 0.9em;
            transition: opacity 0.2s;
        }

        .actions a:hover {
            opacity: 0.8;
        }

        .actions a.edit {
            color: var(--primary-color);
        }

        .actions a.edit:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }

        .actions a[href*="delete"] {
            color: var(--danger-color);
        }

        .actions a[href*="delete"]:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include "sidebar_admin.php"; ?>

        <div class="main-content">
            <h1>📦 Quản lý Phân loại Sản phẩm (Đa cấp)</h1>

            <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

            <form method="POST">
                <input type="hidden" name="id" id="id">

                <div class="form-row">
                    <div class="form-group-triple">
                        <label for="ten_phan_loai">Tên phân loại <span style="color: red;">*</span></label>
                        <input type="text" name="ten_phan_loai" id="ten_phan_loai" placeholder="Áo thun, Quần jean..." required>
                    </div>

                    <div class="form-group-triple">
                        <label for="parent_id">Danh mục Cha (Cấp 1)</label>
                        <select name="parent_id" id="parent_id">
                            <option value="0">-- Là Danh mục CHA --</option>
                            <?php
                            // Lặp qua danh mục cha, thêm data-loai-chinh để JS lấy thông tin
                            foreach ($parent_categories as $cat) { ?>
                                <option value="<?= $cat['id'] ?>" data-loai-chinh="<?= htmlspecialchars($cat['loai_chinh']) ?>">
                                    <?= htmlspecialchars($cat['ten_phan_loai']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group-triple" id="loai_chinh_group">
                        <label for="loai_chinh">Loại Chính</label>
                        <select name="loai_chinh" id="loai_chinh" required>
                            <option value="Quần">Quần</option>
                            <option value="Áo">Áo</option>
                            <option value="Giày">Giày</option>
                            <option value="Khác" selected>Khác</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-half">
                        <label for="mo_ta">Mô tả</label>
                        <textarea name="mo_ta" id="mo_ta" placeholder="Mô tả ngắn về phân loại này..."></textarea>
                    </div>

                    <div class="form-group-half">
                        <label for="trang_thai">Trạng thái</label>
                        <select name="trang_thai" id="trang_thai">
                            <option value="Đang sử dụng">Đang sử dụng</option>
                            <option value="Ngừng sử dụng">Ngừng sử dụng</option>
                        </select>
                    </div>
                </div>

                <div class="action-buttons">
                    <div class="btn-group-right">
                        <button type="submit" name="add" id="btn-add">➕ Thêm mới</button>
                        <button type="submit" name="update" id="btn-update" style="display:none;">💾 Cập nhật</button>
                        <button type="button" id="btn-cancel" style="display:none;">❌ Hủy</button>
                    </div>
                </div>
            </form>

            <div class="search-bar">
                <form method="GET" class="search-form">
                    <div class="search-group">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Nhập tên phân loại...">

                        <select name="filter_parent">
                            <option value="">-- Lọc theo Danh mục CHA --</option>
                            <option value="NULL" <?= $filter_parent === 'NULL' ? 'selected' : '' ?>>-- DANH MỤC CHA (Cấp 1) --</option>
                            <?php
                            // Lặp lại danh mục cha cho thanh lọc
                            foreach ($parent_categories as $cat) { ?>
                                <option value="<?= $cat['id'] ?>" <?= (string)$filter_parent === (string)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['ten_phan_loai']) ?></option>
                            <?php } ?>
                        </select>

                        <select name="filter_loai">
                            <option value="">-- Lọc theo Loại Chính --</option>
                            <option value="Quần" <?= $filter_loai == 'Quần' ? 'selected' : '' ?>>Quần</option>
                            <option value="Áo" <?= $filter_loai == 'Áo' ? 'selected' : '' ?>>Áo</option>
                            <option value="Giày" <?= $filter_loai == 'Giày' ? 'selected' : '' ?>>Giày</option>
                            <option value="Khác" <?= $filter_loai == 'Khác' ? 'selected' : '' ?>>Khác</option>
                        </select>
                        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</button>
                        <?php if (!empty($search) || !empty($filter_loai) || !empty($filter_parent)) { ?>
                            <a href="phanloaisanpham.php" class="clear-filter">❌ Xóa lọc</a>
                        <?php } ?>
                    </div>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên phân loại</th>
                        <th>Danh mục Cha (Cấp 1)</th>
                        <th>Loại chính</th>
                        <th>Mô tả</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stt = 1;
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Giá trị data-parent phải là '0' nếu parent_id là NULL để JS chọn đúng option
                            $data_parent_id = $row['parent_id'] !== NULL ? (string)$row['parent_id'] : '0';
                    ?>
                            <tr>
                                <td><?= $stt++ ?></td>
                                <td>
                                    <?php if (!empty($row['parent_id'])) { ?>
                                        <i class="fa-solid fa-angle-right" style="margin-right: 5px; color: var(--secondary-color);"></i>
                                    <?php } ?>
                                    **<?= htmlspecialchars($row['ten_phan_loai']) ?>**
                                </td>
                                <td>
                                    <?php
                                    echo $row['parent_name'] ? htmlspecialchars($row['parent_name']) : '<span style="color:#2196f3; font-weight:bold;">-- CHA --</span>';
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($row['loai_chinh']) ?></td>
                                <td><?= htmlspecialchars($row['mo_ta']) ?></td>
                                <td>
                                    <span class="status <?= $row['trang_thai'] == 'Đang sử dụng' ? 'active' : 'inactive' ?>">
                                        <?= $row['trang_thai'] ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($row['ngay_tao'])) ?></td>
                                <td class="actions">
                                    <a href="#" class="edit"
                                        data-id="<?= $row['id'] ?>"
                                        data-ten="<?= htmlspecialchars($row['ten_phan_loai']) ?>"
                                        data-loai="<?= htmlspecialchars($row['loai_chinh']) ?>"
                                        data-parent="<?= $data_parent_id ?>"
                                        data-mo_ta="<?= htmlspecialchars($row['mo_ta']) ?>"
                                        data-trang_thai="<?= $row['trang_thai'] ?>">
                                        <i class="fa fa-pen"></i> Sửa
                                    </a>
                                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Xác nhận xóa phân loại này? (Các danh mục con và sản phẩm liên quan có thể bị ảnh hưởng)')">
                                        <i class="fa fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php }
                    } else { ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">Không tìm thấy phân loại nào!</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const editButtons = document.querySelectorAll('.edit');
        const cancelBtn = document.getElementById('btn-cancel');
        const addBtn = document.getElementById('btn-add');
        const updateBtn = document.getElementById('btn-update');
        const parentSelect = document.getElementById('parent_id');
        const loaiChinhGroup = document.getElementById('loai_chinh_group');
        const loaiChinhSelect = document.getElementById('loai_chinh');

        // Dữ liệu Danh mục Cha (Cấp 1) và Loại Chính tương ứng
        // Lấy thông tin loai_chinh của các danh mục cha từ các option có sẵn
        const parentCategoriesData = {};
        parentSelect.querySelectorAll('option').forEach(option => {
            const id = option.value;
            const loaiChinh = option.dataset.loaiChinh;
            if (id !== '0' && loaiChinh) {
                parentCategoriesData[id] = loaiChinh;
            }
        });

        // Hàm xử lý hiển thị/ẩn trường Loại Chính
        function toggleLoaiChinh(parentId, isEditMode = false, currentLoaiChinh = 'Khác') {
            if (parentId === '0') {
                // Là Danh mục CHA (Cấp 1)
                loaiChinhGroup.style.display = 'block'; // Hiển thị
                loaiChinhSelect.required = true;

                // Nếu là chế độ Thêm mới, đặt giá trị mặc định là 'Khác'
                if (!isEditMode) {
                    loaiChinhSelect.value = 'Khác';
                }
            } else {
                // Là Danh mục CON (Cấp 2)
                loaiChinhGroup.style.display = 'block'; // Hiển thị

                // Tự động điền giá trị Loại Chính của danh mục cha
                const loaiChinhOfParent = parentCategoriesData[parentId];
                if (loaiChinhOfParent) {
                    loaiChinhSelect.value = loaiChinhOfParent;
                } else if (isEditMode) {
                    // Trong trường hợp sửa, giữ lại giá trị cũ nếu không tìm thấy
                    loaiChinhSelect.value = currentLoaiChinh;
                }

                // Đặt lại trạng thái required/disabled nếu cần, nhưng thường danh mục con vẫn cần gửi đi loai_chinh
                loaiChinhSelect.required = true;
            }
        }

        // 1. Ẩn Loai Chinh khi trang vừa tải
        loaiChinhGroup.style.display = 'none';

        // 2. Lắng nghe sự kiện thay đổi của Danh mục Cha
        parentSelect.addEventListener('change', () => {
            const selectedParentId = parentSelect.value;
            toggleLoaiChinh(selectedParentId);
        });

        // 3. Xử lý nút SỬA
        editButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                // 1. Đổ dữ liệu vào form
                document.getElementById('id').value = btn.dataset.id;
                document.getElementById('ten_phan_loai').value = btn.dataset.ten;
                document.getElementById('mo_ta').value = btn.dataset.mo_ta;
                document.getElementById('trang_thai').value = btn.dataset.trang_thai;

                let currentLoai = btn.dataset.loai;

                // 2. Xử lý Parent ID và hiển thị Loại Chính
                let parentId = btn.dataset.parent;
                parentSelect.value = parentId;

                // Gọi hàm hiển thị Loại Chính với chế độ sửa
                toggleLoaiChinh(parentId, true, currentLoai);

                // Đảm bảo Loại Chính được chọn đúng giá trị trong chế độ sửa
                loaiChinhSelect.value = currentLoai;


                // 3. Chuyển đổi trạng thái nút
                addBtn.style.display = 'none';
                updateBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
            });
        });

        // 4. Xử lý nút HỦY
        cancelBtn.addEventListener('click', () => {
            // 1. Reset form
            document.getElementById('id').value = '';
            document.getElementById('ten_phan_loai').value = '';
            document.getElementById('mo_ta').value = '';
            document.getElementById('trang_thai').value = 'Đang sử dụng';
            loaiChinhSelect.value = 'Khác'; // Reset về mặc định
            parentSelect.value = '0'; // Reset về Cấp CHA

            // 2. Ẩn lại trường Loại Chính
            loaiChinhGroup.style.display = 'none';
            loaiChinhSelect.required = false;

            // 3. Chuyển đổi trạng thái nút
            addBtn.style.display = 'inline-block';
            updateBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
        });
    </script>
</body>

</html>