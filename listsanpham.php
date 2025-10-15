<?php
include "Database/connectdb.php";

$phan_loai = isset($_GET['phan_loai']) ? $_GET['phan_loai'] : '';

if ($phan_loai != '') {
    $sql = "SELECT * FROM san_pham WHERE phan_loai = '$phan_loai'";
} else {
    $sql = "SELECT * FROM san_pham";
}

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}


// Phân quyền truy cập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['tk'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Sản phẩm</title>
    <link rel="stylesheet" href="../css/listsanpham.css">

</head>

<body>
    <?php include 'header.php'; ?>
    <h2>Danh sách sản phẩm <?php echo $phan_loai ? "($phan_loai)" : ""; ?></h2>
    <div class="product-list">
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <div class="product-card">
                <img src="<?php echo $row['hinh_anh']; ?>" alt="<?php echo $row['ten_san_pham']; ?>">
                <h3><?php echo $row['ten_san_pham']; ?></h3>
                <p style="color:red; font-size: 18px;font-weight: bold;">Giá: <?php echo number_format($row['gia'], 0, ',', '.'); ?>đ</p>

                <div class="button-group">
                    <a href="chitietsanpham.php?id=<?php echo $row['id']; ?>">
                        <button class="btn btn-detail">Xem chi tiết sản phẩm </button>
                    </a>
                    <button class="btn btn-cart">Thêm vào giỏ</button>
                </div>

            </div>
        <?php } ?>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>