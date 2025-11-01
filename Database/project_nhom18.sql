-- ==============================================
-- DATABASE: project_nhom18 (BẢN MỞ RỘNG - ĐÃ LOẠI BỎ orders & order_items)
-- ==============================================

SET FOREIGN_KEY_CHECKS = 0;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `project_nhom18` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `project_nhom18`;

-- =====================================================
-- 1️⃣ BẢNG USER
-- =====================================================
DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
    `id` INT(10) NOT NULL AUTO_INCREMENT,
    `Tai_Khoan` VARCHAR(200) NOT NULL UNIQUE,
    `Mat_Khau` VARCHAR(255) NOT NULL,
    `Ho_Ten` VARCHAR(200) DEFAULT NULL,
    `Email` VARCHAR(200) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `role` ENUM('user', 'admin', 'superadmin') NOT NULL DEFAULT 'user',
    `Ngay_Tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT INTO
    `user` (
        `id`,
        `Tai_Khoan`,
        `Mat_Khau`,
        `Ho_Ten`,
        `Email`,
        `role`,
        `Ngay_Tao`
    )
VALUES (
        1,
        'admin',
        '123',
        'Admin Hệ thống',
        'trieund002@gmail.com',
        'superadmin',
        '2024-01-01 00:00:00'
    ),
    (
        2,
        'ad',
        '123',
        'Người dùng Thử nghiệm',
        'unlcp001@gmail.com',
        'user',
        '2024-01-01 00:00:00'
    );

-- =====================================================
-- 1️⃣b BẢNG TÀI KHOẢN NGÂN HÀNG USER
-- =====================================================
DROP TABLE IF EXISTS `user_bank_accounts`;

CREATE TABLE `user_bank_accounts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `bank_name` VARCHAR(255) NOT NULL,
    `account_number` VARCHAR(50) NOT NULL,
    `display_name` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_user_bank_accounts_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- =====================================================
-- 2️⃣ PHÂN LOẠI SẢN PHẨM
-- =====================================================
DROP TABLE IF EXISTS `phan_loai_san_pham`;

CREATE TABLE `phan_loai_san_pham` (
    `id` INT(10) NOT NULL AUTO_INCREMENT,
    `ten_phan_loai` VARCHAR(100) NOT NULL UNIQUE,
    `mo_ta` TEXT DEFAULT NULL,
    `loai_chinh` ENUM('Áo', 'Quần', 'Giày', 'Khác') NOT NULL DEFAULT 'Khác',
    `trang_thai` ENUM(
        'Đang sử dụng',
        'Ngừng sử dụng'
    ) DEFAULT 'Đang sử dụng',
    `ngay_tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT INTO
    `phan_loai_san_pham` (
        `ten_phan_loai`,
        `mo_ta`,
        `loai_chinh`,
        `trang_thai`
    )
VALUES (
        'Áo thun',
        'Các loại áo thun',
        'Áo',
        'Đang sử dụng'
    ),
    (
        'Áo sơ mi',
        'Các loại áo sơ mi',
        'Áo',
        'Đang sử dụng'
    ),
    (
        'Quần jean',
        'Các loại quần jean',
        'Quần',
        'Đang sử dụng'
    ),
    (
        'Quần âu',
        'Các loại quần âu',
        'Quần',
        'Ngừng sử dụng'
    );

-- =====================================================
-- 3️⃣ SẢN PHẨM
-- =====================================================
DROP TABLE IF EXISTS `san_pham`;

CREATE TABLE `san_pham` (
    `id` INT(10) NOT NULL AUTO_INCREMENT,
    `ten_san_pham` VARCHAR(250) NOT NULL,
    `gia` DECIMAL(10, 0) NOT NULL,
    `mo_ta` TEXT DEFAULT NULL,
    `hinh_anh` VARCHAR(255) DEFAULT NULL,
    `phan_loai` VARCHAR(100) NOT NULL,
    `loai_chinh` ENUM('Áo', 'Quần', 'Giày', 'Khác') NOT NULL DEFAULT 'Khác',
    `phan_loai_id` INT(10) DEFAULT NULL,
    `so_luong` INT(10) NOT NULL DEFAULT 0,
    `trang_thai` ENUM(
        'Còn hàng',
        'Hết hàng',
        'Ngừng kinh doanh'
    ) NOT NULL DEFAULT 'Còn hàng',
    `ngay_tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_sanpham_phanloai` FOREIGN KEY (`phan_loai_id`) REFERENCES `phan_loai_san_pham` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Thêm cột so_luong_ban vào bảng san_pham sau cột so_luong
ALTER TABLE `san_pham`
ADD COLUMN `so_luong_ban` INT(10) NOT NULL DEFAULT 0 AFTER `so_luong`;

-- Cập nhật dữ liệu mẫu cho cột so_luong_ban
UPDATE `san_pham`
SET
    `so_luong_ban` = FLOOR(RAND() * 100)
WHERE
    id IN (1, 2, 3, 4, 5);

INSERT INTO
    `san_pham` (
        `id`,
        `ten_san_pham`,
        `gia`,
        `mo_ta`,
        `hinh_anh`,
        `phan_loai`,
        `loai_chinh`,
        `so_luong`,
        `trang_thai`,
        `ngay_tao`
    )
VALUES (
        1,
        'áo thun',
        200000,
        'ff',
        '../uploads/725105175.jpg',
        'Áo thun',
        'Áo',
        0,
        'Hết hàng',
        '2025-09-29 17:02:41'
    ),
    (
        2,
        'áo len',
        100000,
        'hh',
        '../uploads/725105175T.jpg',
        'Áo sơ mi',
        'Áo',
        5,
        'Còn hàng',
        '2025-09-29 17:02:41'
    ),
    (
        3,
        'quần jean',
        250000,
        'sf',
        '../uploads/a4.png',
        'Quần jean',
        'Quần',
        10,
        'Còn hàng',
        '2025-09-29 17:02:41'
    ),
    (
        4,
        'hh',
        2200000,
        'dd',
        '../uploads/z6923052583265_9c0b15c9dbd7f81dafda559f8036894f.jpg',
        'Quần jean',
        'Quần',
        0,
        'Hết hàng',
        '2025-09-29 17:02:41'
    ),
    (
        5,
        'áo thun',
        22000,
        'f',
        '../uploads/yellow.jpg',
        'Áo thun',
        'Áo',
        0,
        'Hết hàng',
        '2025-09-29 18:44:07'
    );

-- =====================================================
-- 4️⃣ CHI TIẾT ĐƠN HÀNG
-- =====================================================
CREATE TABLE `chi_tiet_don_hang` (
    `id` int(11) NOT NULL,
    `order_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `product_name` varchar(255) NOT NULL,
    `price` decimal(12, 2) NOT NULL,
    `quantity` int(11) NOT NULL,
    `size` varchar(10) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
-- BẢNG ĐƠN HÀNG
-- --------------------------------------------------------
CREATE TABLE `don_hang` (
    `id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `fullname` varchar(255) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `address` varchar(255) NOT NULL,
    `total` decimal(12, 2) NOT NULL,
    `payment_method` enum('cod', 'vnpay', 'momo') DEFAULT 'cod',
    `order_id` varchar(50) NOT NULL,
    `created_at` datetime DEFAULT current_timestamp(),
    `status` tinyint(1) DEFAULT 0,
    `processed_stock` tinyint(1) NOT NULL DEFAULT 0
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

SELECT * FROM don_hang ORDER BY id DESC;

-- --------------------------------------------------------
-- BẢNG GIỎ HÀNG
-- --------------------------------------------------------
CREATE TABLE `gio_hang` (
    `id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `san_pham_id` int(11) NOT NULL,
    `size` varchar(10) NOT NULL,
    `so_luong` int(11) DEFAULT 1,
    `ngay_them` datetime DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
-- BẢNG TÀI KHOẢN THANH TOÁN
-- --------------------------------------------------------
CREATE TABLE `payment_accounts` (
    `id` int(11) NOT NULL,
    `bank_name` varchar(100) NOT NULL,
    `account_number` varchar(50) NOT NULL,
    `display_name` varchar(100) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

INSERT INTO
    `payment_accounts` (
        `id`,
        `bank_name`,
        `account_number`,
        `display_name`
    )
VALUES (
        5,
        'MbBank',
        '2002012004',
        'BUI VAN TRIEU'
    );

-- --------------------------------------------------------
-- BẢNG TÀI KHOẢN (RESET MẬT KHẨU)
-- --------------------------------------------------------
CREATE TABLE `tai_khoan` (
    `email` varchar(255) NOT NULL,
    `reset_token` varchar(255) NOT NULL,
    `reset_expire` datetime NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================
-- 🔹 BẢNG VOUCHERS (DÙNG CHO TÍNH NĂNG ƯU ĐÃI KHÁCH HÀNG)
-- ==============================================

DROP TABLE IF EXISTS `vouchers`;

CREATE TABLE `vouchers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ma_voucher` VARCHAR(50) NOT NULL UNIQUE, -- Mã voucher (vd: SALE20)
    `mo_ta` TEXT DEFAULT NULL, -- Mô tả ngắn gọn
    `giam_phan_tram` INT(3) NOT NULL DEFAULT 0, -- % giảm giá
    `gia_tri_toi_da` DECIMAL(10, 2) DEFAULT NULL, -- Giảm tối đa bao nhiêu tiền (nếu có)
    `dieu_kien` VARCHAR(255) DEFAULT NULL, -- Điều kiện áp dụng (vd: "Đơn hàng từ 500K")
    `ngay_bat_dau` DATE NOT NULL DEFAULT(CURRENT_DATE), -- Ngày bắt đầu hiệu lực
    `ngay_het_han` DATE NOT NULL, -- Ngày hết hạn
    `trang_thai` ENUM('Hoạt động', 'Hết hạn', 'Ẩn') DEFAULT 'Hoạt động',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ==============================================
-- 🔹 DỮ LIỆU MẪU VOUCHER
-- ==============================================

INSERT INTO
    `vouchers` (
        `ma_voucher`,
        `mo_ta`,
        `giam_phan_tram`,
        `gia_tri_toi_da`,
        `dieu_kien`,
        `ngay_bat_dau`,
        `ngay_het_han`,
        `trang_thai`
    )
VALUES (
        'SALE20',
        'Giảm 20% cho tất cả sản phẩm',
        20,
        100000,
        'Áp dụng cho đơn từ 300K',
        '2025-10-01',
        '2025-12-31',
        'Hoạt động'
    ),
    (
        'FREESHIP',
        'Miễn phí vận chuyển toàn quốc',
        0,
        NULL,
        'Áp dụng cho đơn từ 200K',
        '2025-10-01',
        '2025-12-31',
        'Hoạt động'
    ),
    (
        'NEWUSER10',
        'Giảm 10% cho khách hàng mới',
        10,
        50000,
        'Áp dụng lần mua đầu tiên',
        '2025-09-01',
        '2025-11-30',
        'Hoạt động'
    ),
    (
        'XMAS25',
        'Ưu đãi Giáng Sinh giảm 25%',
        25,
        150000,
        'Đơn hàng từ 500K',
        '2025-12-01',
        '2026-01-10',
        'Hoạt động'
    );

-- ==============================================
-- 🔹 BẢNG LỊCH SỬ NHẬP / XUẤT KHO
-- ==============================================

DROP TABLE IF EXISTS `lich_su_kho`;

CREATE TABLE `lich_su_kho` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) NOT NULL,
    `ten_san_pham` VARCHAR(255) NOT NULL,
    `hanh_dong` ENUM('Nhập hàng', 'Xuất hàng') NOT NULL,
    `so_luong` INT(11) NOT NULL,
    `nha_cung_cap` VARCHAR(255) DEFAULT NULL,
    `tong_tien` DECIMAL(15, 2) DEFAULT 0,
    `ngay_thuc_hien` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_lich_su_kho_sanpham` FOREIGN KEY (`product_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

ALTER TABLE lich_su_kho
ADD COLUMN gia_moi DECIMAL(15, 2) NULL AFTER tong_tien;