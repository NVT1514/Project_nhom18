-- ==============================================
-- DATABASE: project_nhom18 (BẢN ĐÃ CẬP NHẬT CHỨC NĂNG MENU ĐA CẤP)
-- ==============================================

SET FOREIGN_KEY_CHECKS = 0;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

SET time_zone = "+07:00";
-- Đã đặt múi giờ Việt Nam (+07) cho các chức năng liên quan đến ngày tháng

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
    `Email` VARCHAR(200) NOT NULL UNIQUE, -- Đã thêm UNIQUE cho Email
    `avatar` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `role` ENUM('user', 'admin', 'superadmin') NOT NULL DEFAULT 'user',
    `Ngay_Tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

ALTER TABLE `user`
ADD COLUMN `trang_thai` TINYINT(1) DEFAULT 1 AFTER `Ngay_Tao`;

-- Cập nhật dữ liệu hiện có (nếu cần, để đảm bảo các user cũ đang Hoạt động)
UPDATE `user` SET `trang_thai` = 1 WHERE `trang_thai` IS NULL;

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
-- 2️⃣ PHÂN LOẠI SẢN PHẨM (ĐÃ THÊM parent_id)
-- =====================================================
DROP TABLE IF EXISTS `phan_loai_san_pham`;

CREATE TABLE `phan_loai_san_pham` (
    `id` INT(10) NOT NULL AUTO_INCREMENT,
    `ten_phan_loai` VARCHAR(100) NOT NULL UNIQUE,
    `parent_id` INT(10) DEFAULT NULL, -- 💡 Cột MỚI: Dùng để trỏ đến ID danh mục cha (Menu cấp 1)
    `mo_ta` TEXT DEFAULT NULL,
    `loai_chinh` ENUM('Áo', 'Quần', 'Giày', 'Khác') NOT NULL DEFAULT 'Khác',
    `trang_thai` ENUM(
        'Đang sử dụng',
        'Ngừng sử dụng'
    ) DEFAULT 'Đang sử dụng',
    `ngay_tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    -- Khóa ngoại tự tham chiếu (Self-Referencing Foreign Key)
    CONSTRAINT `fk_phanloai_parent` FOREIGN KEY (`parent_id`) REFERENCES `phan_loai_san_pham` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT INTO
    `phan_loai_san_pham` (
        `id`,
        `ten_phan_loai`,
        `parent_id`,
        `mo_ta`,
        `loai_chinh`,
        `trang_thai`
    )
VALUES
    -- 1: Danh mục Cha (Cấp 1 - Menu Chính)
    (
        1,
        'ÁO',
        NULL,
        'Các loại áo chung',
        'Áo',
        'Đang sử dụng'
    ),
    (
        2,
        'QUẦN',
        NULL,
        'Các loại quần chung',
        'Quần',
        'Đang sử dụng'
    ),
    -- 3-6: Danh mục Con (Cấp 2 - Dropdown)
    (
        3,
        'Áo Thun',
        1,
        'Các loại áo thun',
        'Áo',
        'Đang sử dụng'
    ),
    (
        4,
        'Áo Sơ Mi',
        1,
        'Các loại áo sơ mi',
        'Áo',
        'Đang sử dụng'
    ),
    (
        5,
        'Áo Khoác',
        1,
        'Các loại áo khoác',
        'Áo',
        'Đang sử dụng'
    ),
    (
        6,
        'Quần Jean',
        2,
        'Các loại quần jean',
        'Quần',
        'Đang sử dụng'
    ),
    (
        7,
        'Quần Âu',
        2,
        'Các loại quần âu',
        'Quần',
        'Ngừng sử dụng'
    ),
    (
        8,
        'Quần Short',
        2,
        'Các loại quần short',
        'Quần',
        'Đang sử dụng'
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
    `phan_loai` VARCHAR(100) NOT NULL, -- Vẫn giữ để tương thích với code cũ (nên loại bỏ sau này)
    `loai_chinh` ENUM('Áo', 'Quần', 'Giày', 'Khác') NOT NULL DEFAULT 'Khác',
    `phan_loai_id` INT(10) DEFAULT NULL, -- ID của danh mục CẤP CON (vd: Quần Jean - ID 6)
    `so_luong` INT(10) NOT NULL DEFAULT 0,
    `so_luong_ban` INT(10) NOT NULL DEFAULT 0, -- Đã chuyển cột này lên đây cho rõ ràng
    `trang_thai` ENUM(
        'Còn hàng',
        'Hết hàng',
        'Ngừng kinh doanh'
    ) NOT NULL DEFAULT 'Còn hàng',
    `ngay_tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_sanpham_phanloai` FOREIGN KEY (`phan_loai_id`) REFERENCES `phan_loai_san_pham` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Thêm cột SKU vào bảng san_pham
ALTER TABLE `san_pham`
ADD COLUMN `sku` VARCHAR(50) DEFAULT NULL UNIQUE AFTER `ten_san_pham`;

-- Dữ liệu mẫu SẢN PHẨM đã được cập nhật phan_loai_id để khớp với cấu trúc mới:
-- Áo thun (ID 3), Áo sơ mi (ID 4), Quần jean (ID 6)

INSERT INTO
    `san_pham` (
        `id`,
        `ten_san_pham`,
        `gia`,
        `mo_ta`,
        `hinh_anh`,
        `phan_loai`,
        `loai_chinh`,
        `phan_loai_id`, -- Đã thêm ID danh mục
        `so_luong`,
        `so_luong_ban`, -- Đã thêm số lượng bán
        `trang_thai`,
        `ngay_tao`
    )
VALUES (
        1,
        'áo thun cơ bản',
        200000,
        'ff',
        '../uploads/725105175.jpg',
        'Áo Thun',
        'Áo',
        3,
        0,
        55,
        'Hết hàng',
        '2025-09-29 17:02:41'
    ),
    (
        2,
        'áo sơ mi kẻ sọc',
        100000,
        'hh',
        '../uploads/725105175T.jpg',
        'Áo Sơ Mi',
        'Áo',
        4,
        5,
        12,
        'Còn hàng',
        '2025-11-06 14:00:00'
    ), -- Sản phẩm mới nhất (Hàng Mới)
    (
        3,
        'quần jean rách gối',
        250000,
        'sf',
        '../uploads/a4.png',
        'Quần Jean',
        'Quần',
        6,
        10,
        80,
        'Còn hàng',
        '2025-09-29 17:02:41'
    ),
    (
        4,
        'quần jean đen',
        2200000,
        'dd',
        '../uploads/z6923052583265_9c0b15c9dbd7f81dafda559f8036894f.jpg',
        'Quần Jean',
        'Quần',
        6,
        0,
        31,
        'Hết hàng',
        '2025-09-29 17:02:41'
    ),
    (
        5,
        'áo thun trơn vàng',
        22000,
        'f',
        '../uploads/yellow.jpg',
        'Áo Thun',
        'Áo',
        3,
        0,
        93,
        'Hết hàng',
        '2025-09-29 18:44:07'
    );

-- Loại bỏ lệnh ALTER TABLE trùng lặp:
-- ALTER TABLE `san_pham` ADD COLUMN `so_luong_ban` INT(10) NOT NULL DEFAULT 0 AFTER `so_luong`;
-- UPDATE `san_pham` SET `so_luong_ban` = FLOOR(RAND() * 100) WHERE id IN (1, 2, 3, 4, 5);

-- =====================================================
-- 4️⃣ CHI TIẾT ĐƠN HÀNG (GIỮ NGUYÊN)
-- =====================================================
CREATE TABLE `chi_tiet_don_hang` (
    `id` int(11) NOT NULL AUTO_INCREMENT, -- 💡 THÊM AUTO_INCREMENT
    `order_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `product_name` varchar(255) NOT NULL,
    `price` decimal(12, 2) NOT NULL,
    `quantity` int(11) NOT NULL,
    `size` varchar(10) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- =====================================================
-- 4️⃣b BẢNG ĐƠN HÀNG (GIỮ NGUYÊN)
-- =====================================================
CREATE TABLE `don_hang` (
    `id` int(11) NOT NULL AUTO_INCREMENT, -- 💡 THÊM AUTO_INCREMENT
    `user_id` int(11) NOT NULL,
    `fullname` varchar(255) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `address` varchar(255) NOT NULL,
    `total` decimal(12, 2) NOT NULL,
    `payment_method` enum('cod', 'vnpay', 'momo') DEFAULT 'cod',
    `order_id` varchar(50) NOT NULL,
    `created_at` datetime DEFAULT current_timestamp(),
    `status` tinyint(1) DEFAULT 0,
    `processed_stock` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- =====================================================
-- 4️⃣c BẢNG GIỎ HÀNG (GIỮ NGUYÊN)
-- =====================================================
CREATE TABLE `gio_hang` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `san_pham_id` int(11) NOT NULL,
    `size` varchar(10) NOT NULL,
    `so_luong` int(11) DEFAULT 1,
    `ngay_them` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`) -- Đã thêm primary key cho bảng này
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Thêm ràng buộc UNIQUE cho bộ 3 cột user_id, san_pham_id, size
ALTER TABLE `gio_hang`
ADD CONSTRAINT `uc_gio_hang_item` UNIQUE (
    `user_id`,
    `san_pham_id`,
    `size`
);

-- =====================================================
-- 4️⃣d BẢNG TÀI KHOẢN THANH TOÁN (GIỮ NGUYÊN)
-- =====================================================
CREATE TABLE `payment_accounts` (
    `id` int(11) NOT NULL,
    `bank_name` varchar(100) NOT NULL,
    `account_number` varchar(50) NOT NULL,
    `display_name` varchar(100) NOT NULL,
    PRIMARY KEY (`id`) -- Đã thêm primary key cho bảng này
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

-- =====================================================
-- 4️⃣e BẢNG TÀI KHOẢN (RESET MẬT KHẨU) (GIỮ NGUYÊN)
-- =====================================================
CREATE TABLE `tai_khoan` (
    `email` varchar(255) NOT NULL,
    `reset_token` varchar(255) NOT NULL,
    `reset_expire` datetime NOT NULL,
    PRIMARY KEY (`email`) -- Đã thêm primary key cho bảng này
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- ==============================================
-- 🔹 BẢNG VOUCHERS (GIỮ NGUYÊN)
-- ==============================================
DROP TABLE IF EXISTS `vouchers`;

CREATE TABLE `vouchers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ma_voucher` VARCHAR(50) NOT NULL UNIQUE,
    `mo_ta` TEXT DEFAULT NULL,
    `giam_phan_tram` INT(3) NOT NULL DEFAULT 0,
    `gia_tri_toi_da` DECIMAL(10, 2) DEFAULT NULL,
    `dieu_kien` VARCHAR(255) DEFAULT NULL,
    `ngay_bat_dau` DATE NOT NULL DEFAULT(CURRENT_DATE),
    `ngay_het_han` DATE NOT NULL,
    `trang_thai` ENUM('Hoạt động', 'Hết hạn', 'Ẩn') DEFAULT 'Hoạt động',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

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
        100000.00,
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
        50000.00,
        'Áp dụng lần mua đầu tiên',
        '2025-09-01',
        '2025-11-30',
        'Hoạt động'
    ),
    (
        'XMAS25',
        'Ưu đãi Giáng Sinh giảm 25%',
        25,
        150000.00,
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
    `id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `ten_san_pham` varchar(255) NOT NULL,
    `hanh_dong` enum('Nhập hàng', 'Xuất hàng') NOT NULL,
    `so_luong` int(11) NOT NULL,
    `nha_cung_cap` varchar(255) DEFAULT NULL,
    `tong_tien` decimal(15, 2) DEFAULT 0.00,
    `gia_moi` decimal(15, 2) DEFAULT NULL,
    `ngay_thuc_hien` datetime DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

ALTER TABLE `lich_su_kho`
ADD PRIMARY KEY (`id`),
ADD KEY `fk_lich_su_kho_sanpham` (`product_id`);

ALTER TABLE `lich_su_kho`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 4;

ALTER TABLE `lich_su_kho`
ADD CONSTRAINT `fk_lich_su_kho_sanpham` FOREIGN KEY (`product_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ==============================================
-- 4️⃣f BẢNG LỊCH SỬ NHẬP KHO
-- ==============================================
CREATE TABLE `lich_su_nhap_kho` (
    `id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) NOT NULL,
    `supplier` varchar(255) NOT NULL,
    `note` text DEFAULT NULL,
    `created_at` datetime NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

ALTER TABLE `lich_su_nhap_kho`
ADD PRIMARY KEY (`id`),
ADD KEY `product_id` (`product_id`);

ALTER TABLE `lich_su_nhap_kho`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 4;

ALTER TABLE `lich_su_nhap_kho`
ADD CONSTRAINT `lich_su_nhap_kho_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE;

-- ==============================================
-- 4️⃣g BẢNG LỊCH SỬ XUẤT KHO
-- ==============================================
CREATE TABLE `lich_su_xuat_kho` (
    `id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) NOT NULL,
    `reason` varchar(255) NOT NULL,
    `note` text DEFAULT NULL,
    `created_at` datetime NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `lich_su_xuat_kho`
ADD PRIMARY KEY (`id`),
ADD KEY `product_id` (`product_id`);

ALTER TABLE `lich_su_xuat_kho`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 2;

-- ==============================================
-- 4️⃣h BẢNG NHẬT KÝ HOẠT ĐỘNG
-- ==============================================
CREATE TABLE `nhat_ky_hoat_dong` (
    `id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `ten_tai_khoan` varchar(50) NOT NULL,
    `module` varchar(100) NOT NULL,
    `hanh_dong_chi_tiet` varchar(500) NOT NULL,
    `ngay_gio` datetime DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

ALTER TABLE `nhat_ky_hoat_dong`
ADD PRIMARY KEY (`id`),
ADD KEY `ngay_gio` (`ngay_gio`),
ADD KEY `module` (`module`);

ALTER TABLE `nhat_ky_hoat_dong`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 5;
--
-- Dumping data for table `nhat_ky_hoat_dong`
--

INSERT INTO
    `nhat_ky_hoat_dong` (
        `id`,
        `user_id`,
        `ten_tai_khoan`,
        `module`,
        `hanh_dong_chi_tiet`,
        `ngay_gio`
    )
VALUES (
        1,
        1,
        'admin',
        'Quản lý Sản phẩm',
        'đã thêm sản phẩm mới: Quần bò nam đẹp d (SL: 21)',
        '2025-11-16 10:55:44'
    ),
    (
        2,
        1,
        'admin',
        'Quản lý Đơn hàng',
        'đã cập nhật trạng thái đơn hàng #4 thành: **Đang chuẩn bị hàng**',
        '2025-11-16 11:05:14'
    ),
    (
        3,
        1,
        'admin',
        'Quản lý Sản phẩm',
        'đã **xóa** sản phẩm: Quần bò nam đẹp d (ID: 8)',
        '2025-11-16 11:08:20'
    ),
    (
        4,
        1,
        'admin',
        'Quản lý Đơn hàng',
        'đã cập nhật trạng thái đơn hàng #4 thành: **Đang giao**',
        '2025-11-16 12:46:00'
    );

-- ==============================================
-- 4️⃣h BẢNG PHIẾU KIỂM KÊ
-- ==============================================
CREATE TABLE `phieu_kiem_ke` (
    `id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `note` text DEFAULT NULL,
    `status` enum(
        'pending',
        'completed',
        'cancelled'
    ) DEFAULT 'pending',
    `created_by` varchar(100) NOT NULL,
    `created_at` datetime NOT NULL,
    `completed_at` datetime DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `phieu_kiem_ke` ADD PRIMARY KEY (`id`);

ALTER TABLE `phieu_kiem_ke`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 2;

-- ==============================================
-- 5️⃣ BẢNG CHI TIẾT KIỂM KÊ
-- ==============================================
CREATE TABLE `chi_tiet_kiem_ke` (
    `id` int(11) NOT NULL,
    `inventory_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `system_quantity` int(11) NOT NULL,
    `actual_quantity` int(11) DEFAULT 0,
    `difference` int(11) DEFAULT 0
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `chi_tiet_kiem_ke`
ADD PRIMARY KEY (`id`),
ADD KEY `inventory_id` (`inventory_id`),
ADD KEY `product_id` (`product_id`);

ALTER TABLE `chi_tiet_kiem_ke`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 8;

ALTER TABLE `chi_tiet_kiem_ke`
ADD CONSTRAINT `chi_tiet_kiem_ke_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `phieu_kiem_ke` (`id`) ON DELETE CASCADE;

-- ==============================================
-- 6️⃣ BẢNG BANNER (QUẢNG CÁO, THÔNG BÁO) (GIỮ NGUYÊN)
-- ==============================================
DROP TABLE IF EXISTS `banner`;

CREATE TABLE `banner` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tieu_de` VARCHAR(255) NULL,
    `hinh_anh` VARCHAR(255) NOT NULL,
    `lien_ket` VARCHAR(255) NULL,
    `vi_tri` ENUM(
        'Trang chủ Slide',
        'Dưới Sản phẩm',
        'Sidebar'
    ) DEFAULT 'Trang chủ Slide',
    `thu_tu` INT(5) NOT NULL DEFAULT 0,
    `trang_thai` ENUM('Hiển thị', 'Ẩn') NOT NULL DEFAULT 'Hiển thị',
    `ngay_tao` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;