<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php";

// Biến lưu từ khóa tìm kiếm
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : "";

$regular_users = [];

// ================== LẤY DỮ LIỆU TÀI KHOẢN USER (KHÁCH HÀNG) ==================
$sql_user = "SELECT id, Tai_Khoan, Email, role, Ho_Ten, Mat_Khau, phone 
             FROM user 
             WHERE role = 'user'";

if (!empty($search_keyword)) {
    $sql_user .= " AND (Tai_Khoan LIKE ? OR Email LIKE ? OR Ho_Ten LIKE ? OR phone LIKE ?)";
}
$sql_user .= " ORDER BY id DESC";

$stmt_user = $conn->prepare($sql_user);

if (!$stmt_user) {
    die("Lỗi prepare SQL: " . $conn->error);
}

if (!empty($search_keyword)) {
    $like_keyword = "%" . $search_keyword . "%";
    $stmt_user->bind_param("ssss", $like_keyword, $like_keyword, $like_keyword, $like_keyword);
}

$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    while ($row = $result_user->fetch_assoc()) {
        $regular_users[] = $row;
    }
}
$stmt_user->close();
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
        body {
            font-family: Arial, sans-serif;
        }

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
            margin-bottom: 30px;
        }

        .user-management-container h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.8rem;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .role-user {
            color: #17a2b8;
            font-weight: bold;
        }

        .search-form {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-form input[type="text"] {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-form button {
            background-color: #3498db;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .search-form button:hover {
            background-color: #2980b9;
        }

        .btn-reset {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            display: inline-block;
            background-color: #6c757d;
            color: white;
            font-size: 14px;
        }

        .btn-reset:hover {
            background-color: #5a6268;
            text-decoration: none;
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

        .user-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-buttons button {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }

        .view-btn {
            background-color: #3498db;
            color: white;
        }

        .view-btn:hover {
            background-color: #2980b9;
        }

        /* CSS MỚI CHO NÚT XÓA */
        .delete-btn {
            background-color: #e74c3c;
            /* Đỏ */
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
            /* Đỏ đậm hơn */
        }

        /* KẾT THÚC CSS MỚI */

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stats-card i {
            font-size: 40px;
            opacity: 0.8;
        }

        .stats-info h3 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
        }

        .stats-info p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* CSS cho thông báo */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar_admin.php'; ?>
        <div class="main-content">

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['msg_type']; ?>">
                    <?php echo $_SESSION['message']; ?>
                </div>
                <?php
                unset($_SESSION['message']);
                unset($_SESSION['msg_type']);
                ?>
            <?php endif; ?>
            <div class="user-management-container" style="padding: 20px;">
                <h2>🔍 Tìm kiếm Người Dùng</h2>
                <form class="search-form" method="get" action="">
                    <input type="text" name="search" placeholder="Tìm theo tên, tài khoản, email hoặc số điện thoại..."
                        value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</button>
                    <?php if (!empty($search_keyword)): ?>
                        <a href="quanliuser.php" class="btn-reset"><i class="fa-solid fa-undo"></i> Bỏ lọc</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="user-management-container">
                <h2>👤 Danh Sách Người Dùng (<?php echo count($regular_users); ?>)</h2>

                <div class="table-responsive">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
                                <th>Họ Tên</th>
                                <th>Email</th>
                                <th>Số điện thoại</th>
                                <th>Quyền hạn</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($regular_users)): ?>
                                <?php foreach ($regular_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Tai_Khoan']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Ho_Ten']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                        <td><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<span style="color: #999;">Chưa cập nhật</span>'; ?></td>
                                        <td><span class="role-user"><?php echo strtoupper(htmlspecialchars($user['role'])); ?></span></td>
                                        <td class="action-buttons">
                                            <button class="view-btn" onclick="window.location.href='view_user_admin.php?id=<?php echo $user['id']; ?>'">
                                                <i class="fa-solid fa-eye"></i> Xem chi tiết
                                            </button>
                                            <button class="delete-btn" onclick="confirmDeletion(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['Tai_Khoan']); ?>')">
                                                <i class="fa-solid fa-trash-can"></i> Xóa
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="no-data">
                                            <i class="fa-solid fa-inbox"></i>
                                            <p>Không tìm thấy người dùng nào phù hợp với từ khóa tìm kiếm.</p>
                                        </div>
                                    </td>
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
            // Highlight từ khóa tìm kiếm trong bảng (optional)
            const searchKeyword = "<?php echo htmlspecialchars($search_keyword); ?>";
            if (searchKeyword) {
                console.log('Đang tìm kiếm: ' + searchKeyword);
            }
        });

        /**
         * Hàm xác nhận xóa người dùng và chuyển hướng đến delete_user.php
         * @param {number} userId - ID của người dùng cần xóa.
         * @param {string} username - Tên tài khoản để hiển thị trong thông báo.
         */
        function confirmDeletion(userId, username) {
            if (confirm(`Bạn có chắc chắn muốn xóa tài khoản "${username}" (ID: ${userId}) không? Hành động này không thể hoàn tác!`)) {
                // Nếu người dùng nhấn OK, chuyển hướng đến script xử lý xóa
                window.location.href = `delete_user.php?id=${userId}`;
            }
        }
    </script>
</body>

</html>