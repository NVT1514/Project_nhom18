-- ==============================================
-- DATABASE: project_nhom18 (phiên bản chuẩn hóa gọn gàng)
-- ==============================================

SET FOREIGN_KEY_CHECKS = 0;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `project_nhom18` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `project_nhom18`;

-- BẢNG USER

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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Dữ liệu mẫu
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

-- BẢNG PHÂN LOẠI SẢN PHẨM

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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Dữ liệu mẫu
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
-- 3️⃣ BẢNG SẢN PHẨM
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
    `ngay_tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_sanpham_phanloai` FOREIGN KEY (`phan_loai_id`) REFERENCES `phan_loai_san_pham` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Dữ liệu mẫu
INSERT INTO
    `san_pham` (
        `id`,
        `ten_san_pham`,
        `gia`,
        `mo_ta`,
        `hinh_anh`,
        `phan_loai`,
        `loai_chinh`,
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
        '2025-09-29 18:44:07'
    );

-- =====================================================
-- 4️⃣ BẢNG ORDERS
-- =====================================================
DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
    `order_id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `address` TEXT NOT NULL,
    `total` DECIMAL(15, 2) NOT NULL,
    `status` VARCHAR(50) DEFAULT 'Đang xử lý',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`order_id`),
    KEY `fk_orders_user` (`user_id`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- =====================================================
-- 5️⃣ BẢNG ORDER_ITEMS
-- =====================================================
DROP TABLE IF EXISTS `order_items`;

CREATE TABLE `order_items` (
    `item_id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `price` DECIMAL(15, 2) NOT NULL,
    PRIMARY KEY (`item_id`),
    KEY `fk_order_items_order` (`order_id`),
    KEY `fk_order_items_product` (`product_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;