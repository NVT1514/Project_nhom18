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

        /* ==== TOP BAR ==== */
        .topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 245px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 25px;
            border-radius: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 0;
            z-index: 100;
        }

        .main-content {
            padding-top: 100px;
        }

        .search-box h1 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin: 0;
        }

        .user-box {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-box img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        /* ==== USER DROPDOWN ==== */
        .user-menu {
            position: relative;
        }

        .user-menu-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            color: #2c3e50;
        }

        .user-menu-btn:hover {
            background: #f1f3f6;
        }

        .user-menu-btn img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .dropdown-menu {
            position: absolute;
            top: 60px;
            right: 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .user-menu.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a,
        .dropdown-menu button {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 12px 16px;
            border: none;
            background: transparent;
            color: #898c95ff;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f3f3f3;
            text-align: left;
        }

        .dropdown-menu a:first-child,
        .dropdown-menu button:first-child {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .dropdown-menu a:last-child,
        .dropdown-menu button:last-child {
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            border-bottom: none;
        }

        .dropdown-menu a:hover,
        .dropdown-menu button:hover {
            background: #f1f3f6;
        }

        .dropdown-menu a i,
        .dropdown-menu button i {
            width: 20px;
            font-size: 1.1rem;
            color: #898c95ff;
        }

        .dropdown-menu button {
            color: #898c95ff;
        }

        .dropdown-menu button i {
            color: #898c95ff;
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
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar_admin.php'; ?>
        <div class="main-content">
            <!-- Form tìm kiếm -->
            <div class="user-management-container" style="padding: 20px;">
                <div class="topbar">
                    <div class="search-box">
                        <h1>Quản lý USER</h1>
                    </div>
                    <div class="user-box">
                        <i class="fa-regular fa-bell"></i>
                        <div class="user-menu">
                            <button class="user-menu-btn" onclick="toggleUserMenu()">
                                <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Avatar">
                                <span><?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="admin.php">
                                    <i class="fa-solid fa-user"></i>
                                    <span>Tài khoản của tôi</span>
                                </a>
                                <a href="#">
                                    <i class="fa-solid fa-file-upload"></i>
                                    <span>Lịch sử xuất nhập file</span>
                                </a>
                                <button onclick="logoutUser()">
                                    <i class="fa-solid fa-sign-out-alt"></i>
                                    <span>Đăng xuất</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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

            <!-- Bảng danh sách người dùng -->
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


        // Toggle user dropdown menu
        function toggleUserMenu() {
            const userMenu = document.querySelector('.user-menu');
            userMenu.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const userBtn = document.querySelector('.user-menu-btn');
            if (!userMenu.contains(event.target) && !userBtn.contains(event.target)) {
                userMenu.classList.remove('active');
            }
        });

        // Logout function
        function logoutUser() {
            if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                window.location.href = 'login.php';
            }
        }
    </script>
</body>

</html>