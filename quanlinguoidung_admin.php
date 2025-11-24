<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "Database/connectdb.php";

// Biến lưu từ khóa tìm kiếm
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : "";

$admin_users = [];

// ================== LẤY DỮ LIỆU TÀI KHOẢN ADMIN/SUPERADMIN ==================
$sql_admin = "SELECT id, Tai_Khoan, Email, role, Ho_Ten, Mat_Khau, trang_thai 
              FROM user 
              WHERE role IN ('admin', 'superadmin')";

if (!empty($search_keyword)) {
    $sql_admin .= " AND (Tai_Khoan LIKE ? OR Email LIKE ? OR Ho_Ten LIKE ?)";
}
$sql_admin .= " ORDER BY role DESC, id DESC"; // Sắp xếp Superadmin lên trước

$stmt_admin = $conn->prepare($sql_admin);

if (!empty($search_keyword)) {
    $like_keyword = "%" . $search_keyword . "%";
    $stmt_admin->bind_param("sss", $like_keyword, $like_keyword, $like_keyword);
}

$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();

if ($result_admin->num_rows > 0) {
    while ($row = $result_admin->fetch_assoc()) {
        $admin_users[] = $row;
    }
}
$stmt_admin->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Admin</title>
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


        /* Màu sắc cho quyền Admin */
        .role-admin {
            color: #d63384;
            font-weight: bold;
        }

        .role-superadmin {
            color: #dc3545;
            font-weight: bold;
        }

        /* Trạng thái hoạt động */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .activate-btn {
            background-color: #28a745;
            color: white;
        }

        .activate-btn:hover {
            background-color: #218838;
        }

        .deactivate-btn {
            background-color: #ffc107;
            color: #333;
        }

        .deactivate-btn:hover {
            background-color: #e0a800;
        }

        .btn-reset {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 6px;
            display: inline-block;
            background-color: #6c757d;
            color: white;
        }

        .btn-reset:hover {
            background-color: #5a6268;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar_admin.php'; ?>
        <div class="main-content">
            <div class="user-management-container" style="padding: 20px; margin-bottom: 20px;">
                <div class="topbar">
                    <div class="search-box">
                        <h1>Quản lý ADMIN & SUPERADMIN</h1>
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

                <h2>🔍 Tìm kiếm</h2>
                <form class="search-form" method="get" action="">
                    <input type="text" name="search" placeholder="Tìm theo tên, tài khoản hoặc email..."
                        value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</button>
                    <?php if (!empty($search_keyword)): ?>
                        <a href="quanlinguoidung_admin.php" class="btn-reset"><i class="fa-solid fa-undo"></i> Bỏ lọc</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="user-management-container">
                <h2>👑 Danh sách ADMIN & SUPERADMIN (<?php echo count($admin_users); ?>)</h2>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a class="btn update-admin" href="tao_tai_khoan_admin.php"><i class="fa-solid fa-user-plus"></i> Thêm tài khoản Admin</a>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
                                <th>Họ Tên</th>
                                <th>Email</th>
                                <th>Quyền hạn</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($admin_users)): ?>
                                <?php foreach ($admin_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Tai_Khoan']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Ho_Ten']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                        <td><span class="role-<?php echo htmlspecialchars($user['role']); ?>"><?php echo strtoupper(htmlspecialchars($user['role'])); ?></span></td>
                                        <td>
                                            <?php
                                            $trang_thai = isset($user['trang_thai']) ? $user['trang_thai'] : 1;
                                            if ($trang_thai == 1):
                                            ?>
                                                <span class="status-badge status-active">
                                                    <i class="fa-solid fa-circle-check"></i> Hoạt động
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-inactive">
                                                    <i class="fa-solid fa-circle-xmark"></i> Ngừng hoạt động
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="action-buttons">
                                            <button class="view-btn" onclick="window.location.href='view_user_admin.php?id=<?php echo $user['id']; ?>'">
                                                <i class="fa-solid fa-eye"></i> Xem
                                            </button>

                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin' && $user['role'] !== 'superadmin'): ?>
                                                <?php if ($trang_thai == 1): ?>
                                                    <button class="deactivate-btn" data-user-id="<?php echo $user['id']; ?>" data-action="deactivate">
                                                        <i class="fa-solid fa-ban"></i> Ngừng
                                                    </button>
                                                <?php else: ?>
                                                    <button class="activate-btn" data-user-id="<?php echo $user['id']; ?>" data-action="activate">
                                                        <i class="fa-solid fa-check"></i> Kích hoạt
                                                    </button>
                                                <?php endif; ?>

                                                <button class="delete-btn" data-user-id="<?php echo htmlspecialchars($user['id']); ?>">
                                                    <i class="fa-solid fa-trash"></i> Xóa
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                                        <i class="fa-solid fa-inbox"></i> Không tìm thấy tài khoản Admin nào phù hợp với từ khóa tìm kiếm.
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
            // Xử lý xóa người dùng
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const confirmDelete = confirm('Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác.');

                    if (confirmDelete) {
                        window.location.href = 'delete_user_admin.php?id=' + userId;
                    }
                });
            });

            // Xử lý kích hoạt/ngừng hoạt động
            const statusButtons = document.querySelectorAll('.activate-btn, .deactivate-btn');
            statusButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const action = this.getAttribute('data-action');

                    let confirmMessage = '';
                    if (action === 'activate') {
                        confirmMessage = 'Bạn có chắc chắn muốn kích hoạt tài khoản này?';
                    } else {
                        confirmMessage = 'Bạn có chắc chắn muốn ngừng hoạt động tài khoản này?';
                    }

                    if (confirm(confirmMessage)) {
                        window.location.href = 'toggle_status_admin.php?id=' + userId + '&action=' + action;
                    }
                });
            });
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