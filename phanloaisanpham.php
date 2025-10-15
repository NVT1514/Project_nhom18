<?php
include "Database/connectdb.php";
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['tk']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'superadmin')) {
    header("Location: login.php");
    exit();
}

// --- Xử lý thêm mới ---
if (isset($_POST['add'])) {
    $ten = trim($_POST['ten_phan_loai']);
    $mo_ta = trim($_POST['mo_ta']);
    $loai_chinh = $_POST['loai_chinh'] ?? 'Khác';
    $trang_thai = $_POST['trang_thai'] ?? 'Đang sử dụng';

    if ($ten != "") {
        $sql = "INSERT INTO phan_loai_san_pham (ten_phan_loai, mo_ta, loai_chinh, trang_thai) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $ten, $mo_ta, $loai_chinh, $trang_thai);
        $stmt->execute();
        $message = "✅ Thêm phân loại thành công!";
    } else {
        $message = "⚠️ Tên phân loại không được để trống!";
    }
}

// --- Xử lý cập nhật ---
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $ten = trim($_POST['ten_phan_loai']);
    $mo_ta = trim($_POST['mo_ta']);
    $loai_chinh = $_POST['loai_chinh'];
    $trang_thai = $_POST['trang_thai'];

    $sql = "UPDATE phan_loai_san_pham SET ten_phan_loai=?, mo_ta=?, loai_chinh=?, trang_thai=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $ten, $mo_ta, $loai_chinh, $trang_thai, $id);
    $stmt->execute();
    $message = "✅ Cập nhật phân loại thành công!";
}

// --- Xử lý xóa ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM phan_loai_san_pham WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $message = "🗑️ Đã xóa phân loại thành công!";
}

// --- Lấy danh sách ---
$result = mysqli_query($conn, "SELECT * FROM phan_loai_san_pham ORDER BY ngay_tao DESC");
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Phân loại sản phẩm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css"> <!-- Giữ nguyên sidebar cũ -->
    <style>
        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 30px 40px;
            background: #f5f6fa;
            min-height: 100vh;
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 25px;
        }

        .message {
            background: #e3f7df;
            border-left: 5px solid #4caf50;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 15px;
        }

        form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 25px;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        input[type="text"],
        textarea,
        select {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            width: calc(25% - 10px);
        }

        textarea {
            height: 17px;
        }

        button {
            background: #2196f3;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #0d8bf2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            border-bottom: 1px solid #ddd;
            padding: 12px 10px;
            text-align: left;
        }

        th {
            background: #2196f3;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .actions a {
            margin-right: 8px;
            text-decoration: none;
            color: #2196f3;
            font-weight: 500;
        }

        .actions a:hover {
            text-decoration: underline;
        }

        .status {
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: bold;
        }

        .active {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .inactive {
            background: #ffcdd2;
            color: #c62828;
        }

        /* CANH TOÀN TRANG */
        .container {
            display: flex;
            min-height: 100vh;
            background: #f5f6fa;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- SIDEBAR -->
        <?php include "sidebar.php"; ?>

        <!-- MAIN -->
        <div class="main-content">
            <h1>📦 Quản lý Phân loại Sản phẩm</h1>

            <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

            <!-- FORM -->
            <form method="POST">
                <input type="hidden" name="id" id="id">

                <input type="text" name="ten_phan_loai" id="ten_phan_loai" placeholder="Tên phân loại..." required>

                <select name="loai_chinh" id="loai_chinh" required>
                    <option value="Quần">Quần</option>
                    <option value="Áo">Áo</option>
                    <option value="Giày">Giày</option>
                    <option value="Khác" selected>Khác</option>
                </select>

                <textarea name="mo_ta" id="mo_ta" placeholder="Mô tả..."></textarea>

                <select name="trang_thai" id="trang_thai">
                    <option value="Đang sử dụng">Đang sử dụng</option>
                    <option value="Ngừng sử dụng">Ngừng sử dụng</option>
                </select>

                <button type="submit" name="add" id="btn-add">➕ Thêm mới</button>
                <button type="submit" name="update" id="btn-update" style="display:none; background:#28a745;">💾 Cập nhật</button>
                <button type="button" id="btn-cancel" style="display:none; background:#6c757d;">❌ Hủy</button>
            </form>

            <!-- DANH SÁCH -->
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên phân loại</th>
                        <th>Loại chính</th>
                        <th>Mô tả</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $stt = 1;
                    while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?= $stt++ ?></td>
                            <td><?= htmlspecialchars($row['ten_phan_loai']) ?></td>
                            <td><?= htmlspecialchars($row['loai_chinh']) ?></td>
                            <td><?= htmlspecialchars($row['mo_ta']) ?></td>
                            <td>
                                <span class="status <?= $row['trang_thai'] == 'Đang sử dụng' ? 'active' : 'inactive' ?>">
                                    <?= $row['trang_thai'] ?>
                                </span>
                            </td>
                            <td><?= $row['ngay_tao'] ?></td>
                            <td class="actions">
                                <a href="#" class="edit"
                                    data-id="<?= $row['id'] ?>"
                                    data-ten="<?= htmlspecialchars($row['ten_phan_loai']) ?>"
                                    data-loai="<?= htmlspecialchars($row['loai_chinh']) ?>"
                                    data-mo_ta="<?= htmlspecialchars($row['mo_ta']) ?>"
                                    data-trang_thai="<?= $row['trang_thai'] ?>"><i class="fa fa-pen"></i> Sửa</a>
                                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Xác nhận xóa phân loại này?')">
                                    <i class="fa fa-trash"></i> Xóa
                                </a>
                            </td>
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

        editButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                document.getElementById('id').value = btn.dataset.id;
                document.getElementById('ten_phan_loai').value = btn.dataset.ten;
                document.getElementById('loai_chinh').value = btn.dataset.loai;
                document.getElementById('mo_ta').value = btn.dataset.mo_ta;
                document.getElementById('trang_thai').value = btn.dataset.trang_thai;
                addBtn.style.display = 'none';
                updateBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
            });
        });

        cancelBtn.addEventListener('click', () => {
            document.getElementById('id').value = '';
            document.getElementById('ten_phan_loai').value = '';
            document.getElementById('mo_ta').value = '';
            document.getElementById('trang_thai').value = 'Đang sử dụng';
            document.getElementById('loai_chinh').value = 'Khác';
            addBtn.style.display = 'inline-block';
            updateBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
        });
    </script>
</body>

</html>