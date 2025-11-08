<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php";


// Biến lưu từ khóa tìm kiếm
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : "";

// Câu SQL lấy người dùng có role = 'user', kèm điều kiện tìm kiếm
$sql = "SELECT id, Tai_Khoan, Email, role, Ho_Ten, Mat_Khau 
        FROM user 
        WHERE role = ?";

if (!empty($search_keyword)) {
    $sql .= " AND (Tai_Khoan LIKE ? OR Email LIKE ? OR Ho_Ten LIKE ?)";
}

$stmt = $conn->prepare($sql);

$role_filter = 'user';

if (!empty($search_keyword)) {
    $like_keyword = "%" . $search_keyword . "%";
    $stmt->bind_param("ssss", $role_filter, $like_keyword, $like_keyword, $like_keyword);
} else {
    $stmt->bind_param("s", $role_filter);
}

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

        /* Form tìm kiếm */
        .search-form {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-form input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .search-form button {
            background-color: #3498db;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
        }

        .search-form button:hover {
            background-color: #2e7dc1;
        }

        .table-responsive {
            overflow-x: auto;
        }

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
            color: #fff;
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

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar_admin.php'; ?>
        <div class="main-content">
            <div class="user-management-container">
                <h2>Quản Lý Tài Khoản Khách Hàng</h2>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a class="btn update-admin" href="cap_quyen_admin.php">+ Cấp quyền admin</a>
                <?php endif; ?>

                <!-- Thanh tìm kiếm -->
                <form class="search-form" method="get" action="">
                    <input type="text" name="search" placeholder="Tìm theo tên, tài khoản hoặc email..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</button>
                </form>

                <div class="table-responsive">
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
                                        <td><?php echo htmlspecialchars($user['Mat_Khau']); ?></td>
                                        <td class="action-buttons">
                                            <button class="view-btn" onclick="window.location.href='view_user_admin.php?id=<?php echo $user['id']; ?>'">
                                                <i class="fa-solid fa-eye"></i> Xem
                                            </button>
                                            <button class="delete-btn" data-user-id="<?php echo htmlspecialchars($user['id']); ?>"><i class="fa-solid fa-trash"></i> Xóa</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">Không tìm thấy người dùng nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const confirmDelete = confirm('Bạn có chắc chắn muốn xóa người dùng này?');

                    if (confirmDelete) {
                        window.location.href = 'delete_user_admin.php?id=' + userId;
                    }
                });
            });
        });
    </script>
</body>

</html>