<?php
// Tên file: lien_he.php - Thiết kế Sliding Contact Drawer Tinh Tế

// Khai báo các biến tùy chỉnh (Bạn có thể sửa các giá trị này)
$phone_number = '0987654321';
$zalo_link = 'https://zalo.me/0987654321';
$messenger_link = 'https://m.me/trangfanpagecuaban';
$hotline_label = 'Hotline: ' . $phone_number;

// Màu sắc
$main_color = '#0056b3'; // Xanh đậm, sang trọng cho nút chính
$close_color = '#dc3545'; // Đỏ cho nút đóng (X)
$phone_color = '#28a745'; // Xanh lá cho Phone
$zalo_color = '#0084ff'; // Xanh Zalo
$messenger_color = '#ffc107'; // Vàng cho Messenger
?>

<style>
    /* --- ANIMATION KEYFRAMES --- */
    @keyframes slideInRight {
        0% {
            transform: translateX(100%);
            opacity: 0;
        }

        100% {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes pulseEffect {
        0% {
            box-shadow: 0 0 0 0 rgba(0, 86, 179, 0.4);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(0, 86, 179, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(0, 86, 179, 0);
        }
    }

    /* --- CONTACT DRAWER WRAPPER --- */
    .contact-drawer-wrapper {
        position: fixed;
        bottom: 40px;
        right: 30px;
        z-index: 1050;
        display: flex;
        align-items: center;
    }

    /* Nút Chính (Main FAB) */
    .main-fab-drawer {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: <?= $main_color ?>;
        /* Sử dụng biến PHP */
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.8rem;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
        cursor: pointer;
        transition: transform 0.3s ease, background-color 0.3s ease;
        animation: pulseEffect 2s infinite;
        z-index: 1051;
    }

    .main-fab-drawer:hover {
        transform: scale(1.05);
    }

    /* --- DRAWER CONTENT (Ngăn Kéo) --- */
    .drawer-content {
        display: flex;
        align-items: center;
        background-color: white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        border-radius: 30px;
        padding: 8px;
        height: 50px;
        margin-right: -10px;

        /* Vị trí ẩn mặc định */
        transform: translateX(100%);
        opacity: 0;
        visibility: hidden;
        transition: transform 0.4s ease-out, opacity 0.4s ease-out, visibility 0.4s ease-out;
    }

    /* Khi trạng thái 'open' */
    .contact-drawer-wrapper.open .drawer-content {
        transform: translateX(0);
        /* Trượt ra */
        opacity: 1;
        visibility: visible;
        margin-right: 15px;
    }

    /* --- CÁC NÚT CON (DRAWER BUTTON) --- */
    .drawer-button {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: white;
        padding: 0 10px;
        margin: 0 5px;
        border-radius: 20px;
        transition: background-color 0.3s ease, transform 0.2s ease;
        height: 35px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .drawer-button:hover {
        transform: translateY(-2px);
    }

    .drawer-button i {
        font-size: 1.2rem;
    }

    .button-label {
        font-size: 0.9rem;
        font-weight: 500;
        margin-left: 8px;
        line-height: 1;
    }

    /* --- MÀU SẮC RIÊNG --- */
    .btn-phone {
        background-color: <?= $phone_color ?>;
    }

    .btn-zalo {
        background-color: <?= $zalo_color ?>;
    }

    .btn-messenger {
        background-color: <?= $messenger_color ?>;
    }

    /* Chuyển màu cho nút chính khi mở */
    .contact-drawer-wrapper.open .main-fab-drawer {
        background-color: <?= $close_color ?>;
        animation: none;
    }
</style>


<div class="contact-drawer-wrapper" id="contactDrawerWrapper">

    <div class="drawer-content" id="drawerContent">

        <a href="tel:<?= $phone_number ?>" class="drawer-button btn-phone" title="<?= $hotline_label ?>">
            <i class="fa-solid fa-phone"></i>
            <span class="button-label">Gọi ngay</span>
        </a>

        <a href="<?= $zalo_link ?>" target="_blank" class="drawer-button btn-zalo" title="Chat Zalo">
            <i class="fa-brands fa-whatsapp"></i>
            <span class="button-label">Zalo</span>
        </a>

        <a href="<?= $messenger_link ?>" target="_blank" class="drawer-button btn-messenger" title="Chat Messenger">
            <i class="fa-brands fa-facebook-messenger"></i>
            <span class="button-label">Messenger</span>
        </a>
    </div>

    <div class="main-fab-drawer" id="mainFabDrawer" title="Liên hệ tư vấn">
        <i class="fa-solid fa-headset"></i>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const wrapper = document.getElementById('contactDrawerWrapper');
        const mainFab = document.getElementById('mainFabDrawer');
        const initialIcon = mainFab.querySelector('i');

        mainFab.addEventListener('click', function() {
            // Đóng/Mở ngăn kéo
            wrapper.classList.toggle('open');

            // Thay đổi icon từ headset sang 'X' và ngược lại
            if (wrapper.classList.contains('open')) {
                initialIcon.className = 'fa-solid fa-times';
            } else {
                initialIcon.className = 'fa-solid fa-headset';
            }
        });

        // Đóng menu khi click ra ngoài
        document.addEventListener('click', function(event) {
            if (!wrapper.contains(event.target) && wrapper.classList.contains('open')) {
                wrapper.classList.remove('open');
                initialIcon.className = 'fa-solid fa-headset';
            }
        });
    });
</script>