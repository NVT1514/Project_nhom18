<style>
    .bar-features {
        width: 100%;
        max-width: none;
        height: 80px;
        margin: 0 auto 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 0 24px;
        background: #ffffffff;
        /* Màu nền xanh nhạt */
        border-radius: 0;
        /* Bo góc nhẹ cho đẹp */
    }

    .bar-feature-item {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        min-width: 220px;
        flex: 1;
    }

    .bar-feature-icon {
        width: 48px;
        height: 48px;
        border: 2px solid #283d83ff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: #1a2750;
        flex-shrink: 0;
    }

    .bar-feature-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .bar-feature-title {
        font-weight: bold;
        font-size: 22px;
        margin-bottom: 2px;
        color: #111;
    }

    .bar-feature-desc {
        color: #555;
        font-size: 16px;
    }

    @media (max-width: 900px) {
        .bar-features {
            flex-direction: column;
            gap: 18px;
            padding: 0 8px;
        }

        .bar-feature-item {
            min-width: 0;
        }
    }
</style>
<div class="bar-features">
    <div class="bar-feature-item">
        <div class="bar-feature-icon">
            <i class="fa-solid fa-truck-fast"></i>
        </div>
        <div class="bar-feature-content">
            <div class="bar-feature-title">Miễn phí vận chuyển</div>
            <div class="bar-feature-desc">đơn từ 399K</div>
        </div>
    </div>
    <div class="bar-feature-item">
        <div class="bar-feature-icon">
            <i class="fa-solid fa-box-open"></i>
        </div>
        <div class="bar-feature-content">
            <div class="bar-feature-title">Đổi hàng tận nhà</div>
            <div class="bar-feature-desc">Trong vòng 15 ngày</div>
        </div>
    </div>
    <div class="bar-feature-item">
        <div class="bar-feature-icon">
            <i class="fa-solid fa-credit-card"></i>
        </div>
        <div class="bar-feature-content">
            <div class="bar-feature-title">Thanh toán COD</div>
            <div class="bar-feature-desc">Yên tâm mua sắm</div>
        </div>
    </div>
    <div class="bar-feature-item">
        <div class="bar-feature-icon">
            <i class="fa-solid fa-phone-volume"></i>
        </div>
        <div class="bar-feature-content">
            <div class="bar-feature-title">Hotline: 0123 456789</div>
            <div class="bar-feature-desc">Hỗ trợ bạn từ 8h-22h</div>
        </div>
    </div>
</div>
<!-- Nhớ đã có link fontawesome ở <head> -->