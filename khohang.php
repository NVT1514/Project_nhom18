<?php
include "sidebar.php";
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kho hàng</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* =============================
           CSS riêng cho khohang.php
        ============================== */
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f6fa;
            margin: 0;
            display: flex;
        }

        .main-content {
            flex: 1;
            padding: 40px;
        }

        .content-wrapper {
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .content-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .content-header i {
            color: #007bff;
            font-size: 24px;
        }

        .content-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        /* Thanh công cụ */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .toolbar input,
        .toolbar select {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        .toolbar input:focus,
        .toolbar select:focus {
            border-color: #4a90e2;
        }

        .toolbar button {
            background-color: #28a745;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
            font-weight: 600;
        }

        .toolbar button:hover {
            background-color: #218838;
        }

        /* Bảng dữ liệu */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background-color: #f1f3f6;
            text-align: left;
            padding: 12px 16px;
            color: #333;
            font-weight: 600;
        }

        td {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            color: #555;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        tr:hover {
            background-color: #f2f6ff;
        }

        /* Trạng thái tồn kho */
        .stock {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .in-stock {
            background-color: #d4edda;
            color: #155724;
        }

        .low-stock {
            background-color: #fff3cd;
            color: #856404;
        }

        .out-stock {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Nút hành động */
        .btn {
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #fff;
        }

        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-delete {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }
    </style>
    <script src="https://kit.fontawesome.com/a2d9d5b5a1.js" crossorigin="anonymous"></script>
</head>

<body>
    <div class="main-content">
        <div class="content-wrapper">
            <div class="content-header">
                <i class="fas fa-warehouse"></i>
                <h1>Quản lý Kho hàng</h1>
            </div>

            <div class="toolbar">
                <input type="text" placeholder="Nhập tên sản phẩm hoặc mã sản phẩm...">
                <select>
                    <option>Tất cả</option>
                    <option>Còn hàng</option>
                    <option>Sắp hết</option>
                    <option>Hết hàng</option>
                </select>
                <button>+ Thêm sản phẩm</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mã sản phẩm</th>
                        <th>Tên sản phẩm</th>
                        <th>Phân loại</th>
                        <th>Số lượng tồn</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>SP001</td>
                        <td>Áo Thun Nam</td>
                        <td>Áo Thun</td>
                        <td>50</td>
                        <td><span class="stock in-stock">Còn hàng</span></td>
                        <td>
                            <button class="btn btn-edit">Sửa</button>
                            <button class="btn btn-delete">Xóa</button>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>SP002</td>
                        <td>Áo Khoác Nữ</td>
                        <td>Áo Khoác</td>
                        <td>5</td>
                        <td><span class="stock low-stock">Sắp hết</span></td>
                        <td>
                            <button class="btn btn-edit">Sửa</button>
                            <button class="btn btn-delete">Xóa</button>
                        </td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>SP003</td>
                        <td>Quần Jean Nam</td>
                        <td>Quần</td>
                        <td>0</td>
                        <td><span class="stock out-stock">Hết hàng</span></td>
                        <td>
                            <button class="btn btn-edit">Sửa</button>
                            <button class="btn btn-delete">Xóa</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>