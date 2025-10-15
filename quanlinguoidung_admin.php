<?php
session_start();
include "Database/connectdb.php";

// Kiểm tra đăng nhập và quyền admin HOẶC superadmin
if (!isset($_SESSION['tk']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: login.php");
    exit();
}

// Lấy danh sách người dùng từ CSDL, CHỈ LẤY NHỮNG AI CÓ role LÀ 'user'
// Sử dụng Prepared Statement để an toàn hơn, mặc dù giá trị lọc là cố định
$sql = "SELECT id, Tai_Khoan, Email, role, Ho_Ten, Mat_Khau FROM user WHERE role = ?";

$stmt = $conn->prepare($sql);

// Gán giá trị 'user' vào câu lệnh WHERE
$role_filter = 'user';
$stmt->bind_param("s", $role_filter);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Người Dùng</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* CSS cho phần chính của trang */
        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f6f7fb;
        }

        .user-management-container {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .user-management-container h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.8rem;
            color: #333;
        }

        .update-admin {
            margin-bottom: 20px;
            display: inline-block;
            background-color: #16a361ff;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }

        .update-admin:hover {
            background-color: #13653f;
            text-decoration: none;
        }

        .table-responsive {
            overflow-x: auto;
        }

        /* Định dạng bảng */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .user-table th,
        .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .user-table thead th {
            background-color: #343a40;
            color: #ffffffff;
            font-weight: 600;
        }

        .user-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .user-table tbody tr:hover {
            background-color: #f1f1f1;
        }

        .user-table .action-buttons button {
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 5px;
        }

        .view-btn {
            background-color: #3498db;
            color: white;
        }

        .edit-btn {
            background-color: #f39c12;
            color: white;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <div class="user-management-container">
                <h2>Quản Lý Tài Khoản Khách Hàng</h2>

                <?php
                // Kiểm tra nếu quyền hạn là 'superadmin' thì mới hiển thị nút Cấp quyền
                if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'):
                ?>
                    <a class="btn update-admin" href="cap_quyen_admin.php">+ Cấp quyền admin</a>
                <?php endif; ?>

                <div class="table-responsive">
                </div>
            </div>
            <table class="user-table">
                <thead>
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
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['Tai_Khoan']); ?></td>
                                <td><?php echo htmlspecialchars($user['Ho_Ten']); ?></td>
                                <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td class="password-cell">
                                    <?php
                                    // Hiển thị mật khẩu (chỉ nên dùng trong môi trường thử nghiệm!)
                                    echo htmlspecialchars($user['Mat_Khau']);
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <button class="view-btn"><i class="fa-solid fa-eye"></i> Xem</button>
                                    <button class="delete-btn" data-user-id="<?php echo htmlspecialchars($user['id']); ?>"><i class="fa-solid fa-trash"></i> Xóa</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Không có người dùng nào được tìm thấy.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
    </div>
    <script>
        // Thêm vào cuối file user_management.php, trước thẻ </body>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const confirmDelete = confirm('Bạn có chắc chắn muốn xóa người dùng này?');

                    if (confirmDelete) {
                        // Gửi yêu cầu AJAX hoặc chuyển hướng để xử lý xóa
                        window.location.href = 'delete_user_admin.php?id=' + userId;
                    }
                });
            });
        });
    </script>
</body>

</html>