<?php
include "Database/connectdb.php";
include "Database/function.php";

// Xử lý thêm sản phẩm
if (isset($_POST['them_san_pham'])) {
    $ten_san_pham  = $_POST['ten_san_pham'];
    $gia           = $_POST['gia'];
    $mo_ta         = $_POST['mo_ta'];
    $phan_loai     = $_POST['phan_loai'];
    $loai_chinh    = $_POST['loai_chinh'];
    $hinh_anh      = $_FILES['hinh_anh'];

    // Thêm sản phẩm (có kiểm tra phân loại hợp lệ trong function)
    $ket_qua = them_san_pham($ten_san_pham, $gia, $mo_ta, $hinh_anh, $phan_loai, $loai_chinh);

    if ($ket_qua === true) {
        echo "<script>alert('✅ Thêm sản phẩm thành công!'); window.location.href='ds_sanpham.php';</script>";
        exit;
    } else {
        echo "<div class='alert alert-danger text-center'>$ket_qua</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Sản Phẩm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
        }

        main.main-content {
            flex: 1;
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            box-sizing: border-box;
        }

        .form-container {
            width: 850px;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin: 0 auto 40px;
            animation: fadeIn 0.6s ease;
            position: relative;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Đảm bảo phần nút không đè nội dung */
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .form-header h2 {
            font-size: 28px;
            margin: 0;
        }

        /* Nút danh sách sản phẩm căn phải và cách tiêu đề 10px */
        .btn-list {
            display: inline-block;
            background-color: #198754;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 15px;
            transition: 0.25s;
        }

        .btn-list:hover {
            background-color: #13653f;
        }

        label {
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 18px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #6c5ce7;
            box-shadow: 0 0 4px rgba(108, 92, 231, 0.2);
        }

        textarea {
            resize: vertical;
            min-height: 70px;
        }

        .btn-primary {
            display: block;
            width: 50%;
            max-width: 220px;
            margin: 20px auto 0;
            padding: 10px;
            background: linear-gradient(135deg, #6c5ce7, #0984e3);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: 0.25s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        .alert-danger {
            background: #ffe0e0;
            color: #c0392b;
            padding: 10px;
            border-radius: 8px;
            width: fit-content;
            margin: 10px auto;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fa-solid fa-shirt"></i> Thêm sản phẩm mới</h2>
                    <a href="ds_sanpham.php" class="btn-list"><i class="fa-solid fa-list"></i> Danh sách sản phẩm</a>
                </div>

                <form action="" method="POST" enctype="multipart/form-data">
                    <label for="ten_san_pham">Tên sản phẩm</label>
                    <input type="text" id="ten_san_pham" name="ten_san_pham" required>

                    <label for="gia">Giá (VNĐ)</label>
                    <input type="number" id="gia" name="gia" step="0.01" required>

                    <label for="mo_ta">Mô tả</label>
                    <textarea id="mo_ta" name="mo_ta"></textarea>

                    <label for="hinh_anh">Hình ảnh</label>
                    <input type="file" id="hinh_anh" name="hinh_anh">

                    <label for="loai_chinh">Loại chính</label>
                    <select id="loai_chinh" name="loai_chinh" required>
                        <option value="">-- Chọn loại chính --</option>
                        <?php
                        $sql = "SELECT DISTINCT loai_chinh FROM phan_loai_san_pham WHERE trang_thai='Đang sử dụng' ORDER BY loai_chinh ASC";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['loai_chinh']) . '">' . htmlspecialchars($row['loai_chinh']) . '</option>';
                        }
                        ?>
                    </select>

                    <label for="phan_loai">Phân loại</label>
                    <select id="phan_loai" name="phan_loai" required>
                        <option value="">-- Chọn loại sản phẩm --</option>
                    </select>

                    <button type="submit" name="them_san_pham" class="btn-primary">Thêm sản phẩm</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.getElementById("loai_chinh").addEventListener("change", function() {
            const loaiChinh = this.value;
            const phanLoaiSelect = document.getElementById("phan_loai");

            phanLoaiSelect.innerHTML = '<option>Đang tải...</option>';

            if (loaiChinh) {
                fetch(`ajax_get_phan_loai.php?mode=loai&loai_chinh=${encodeURIComponent(loaiChinh)}`)
                    .then(response => response.text())
                    .then(data => {
                        phanLoaiSelect.innerHTML = data;
                    })
                    .catch(error => {
                        console.error("Lỗi khi tải loại sản phẩm:", error);
                        phanLoaiSelect.innerHTML = '<option value="">Không tải được dữ liệu</option>';
                    });
            } else {
                phanLoaiSelect.innerHTML = '<option value="">-- Chọn loại sản phẩm --</option>';
            }
        });
    </script>
</body>

</html>