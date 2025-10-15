<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản Lý Cấp Quyền & Admin</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* --- Tổng quan và Typography --- */
        body {
            background-color: #f4f7f6;
            font-family: 'Arial', sans-serif;
            padding: 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h3 {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }

        /* --- Bố cục Chính --- */
        .content-layout {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        /* --- Form Cấp Quyền --- */
        .upgrade-form-container {
            flex: 0 0 350px;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .upgrade-form-container:hover {
            transform: translateY(-3px);
        }

        /* --- Bảng Danh Sách --- */
        .admin-list-container {
            flex: 1;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .table-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .table-title a {
            margin-bottom: 20px;
            display: inline-block;
            background-color: #16a361ff;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }

        .table-title a:hover {
            color: white;
            background-color: #13653f;
            text-decoration: none;
        }

        .table-admin th,
        .table-admin td {
            vertical-align: middle;
            text-align: center;
        }

        .table-admin thead th {
            background-color: #007bff;
            color: white;
            border: none;
        }

        .table-admin tbody tr:hover {
            background-color: #e9f7ff;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            transition: background-color 0.2s;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <h1 class="text-center my-4" style="color: #007bff;">Quản Lý Cấp Quyền và Tài Khoản Admin</h1>
        <hr>

        <div class="content-layout">

            <!-- Form Cấp Quyền -->
            <div class="upgrade-form-container">
                <h3>Cấp Quyền/Thay Đổi Quyền</h3>
                <form id="upgradeRoleForm">
                    <div class="form-group">
                        <label for="userIdOrEmail">ID, Email hoặc Tên đăng nhập:</label>
                        <input type="text" class="form-control" id="userIdOrOrUsername" name="identifier" required
                            placeholder="Nhập ID, Email hoặc Tên đăng nhập">
                    </div>

                    <div class="form-group">
                        <label for="newRole">Quyền Hạn Mới:</label>
                        <select class="form-control" id="newRole" name="new_role" required>
                            <option value="admin">Admin</option>
                            <option value="user" selected>User</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success btn-block mt-4">
                        <i class="fas fa-arrow-circle-up"></i> Cấp/Thay Đổi Quyền
                    </button>

                    <div id="message" class="mt-3"></div>
                </form>
            </div>

            <!-- Danh sách Admin -->
            <div class="admin-list-container">
                <div class="table-title">
                    <h3>Danh Sách Tài Khoản Admin Hiện Tại 👑</h3>
                    <a class="btn back" href="quanlinguoidung_admin.php">Quay lại</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-admin">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
                                <th>Họ Tên</th>
                                <th>Email</th>
                                <th>Quyền hạn</th>
                                <th>Mật khẩu</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="adminListTableBody">
                            <!-- Dữ liệu sẽ được nạp bằng AJAX -->
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-3 text-right">Tổng cộng: <span id="adminCount">0</span> Admin</p>
            </div>
        </div>
    </div>

    <!-- Script -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>

    <script>
        $(document).ready(function() {

            // --- Gửi Form Cấp Quyền ---
            $('#upgradeRoleForm').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: 'cap_nhat_quyen_admin.php',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        var messageDiv = $('#message');
                        messageDiv.removeClass('alert-success alert-danger');

                        if (response.success) {
                            messageDiv.addClass('alert alert-success').html(response.message);
                            $('#userIdOrOrUsername').val('');
                            loadAdminList();
                        } else {
                            messageDiv.addClass('alert alert-danger').html(response.message);
                        }
                    },
                    error: function() {
                        $('#message').addClass('alert alert-danger').html('Lỗi kết nối đến máy chủ.');
                    }
                });
            });

            // --- Tải danh sách admin từ server ---
            function loadAdminList() {
                $.ajax({
                    url: 'danh_sach_admin.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var tbody = $('#adminListTableBody');
                            tbody.empty();
                            response.admins.forEach(function(admin) {
                                tbody.append(`
                                    <tr>
                                        <td>${admin.id}</td>
                                        <td>${admin.Tai_Khoan}</td>
                                        <td>${admin.Ho_Ten || ''}</td>
                                        <td>${admin.Email || ''}</td>
                                        <td>${admin.role}</td>
                                        <td>${admin.Mat_Khau || '(ẩn)'}</td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewAdmin(${admin.id})">
                                                <i class="fas fa-eye"></i> Xem
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteAdmin(${admin.id})">
                                                <i class="fas fa-trash-alt"></i> Xóa
                                            </button>
                                        </td>
                                    </tr>
                                `);
                            });
                            $('#adminCount').text(response.admins.length);
                        }
                    },
                    error: function() {
                        alert('Không thể tải danh sách admin.');
                    }
                });
            }

            // --- Xem chi tiết ---
            window.viewAdmin = function(adminId) {
                alert('Xem chi tiết Admin ID: ' + adminId);
            };

            // --- Tải danh sách admin khi mở trang ---
            loadAdminList();
        });

        // --- Xóa Admin ---
        window.deleteAdmin = function(adminId) {
            if (confirm('Bạn có chắc muốn xóa tài khoản Admin này?')) {
                $.ajax({
                    url: 'xoa_admin.php',
                    method: 'POST',
                    data: {
                        id: adminId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('Lỗi: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Không thể kết nối đến máy chủ để xóa Admin.');
                    }
                });
            }
        };
    </script>
</body>

</html>