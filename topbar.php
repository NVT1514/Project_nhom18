<!-- topbar.php -->
<style>
    .topbar {
        background-color: #fff;
        padding: 10px 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 60px;
    }

    .search-box {
        flex: 1;
        margin-left: 20px;
        position: relative;
    }

    .search-box input {
        width: 100%;
        padding: 8px 40px 8px 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 15px;
    }

    .search-box i {
        position: absolute;
        right: 10px;
        top: 9px;
        color: #888;
        font-size: 18px;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .notification {
        position: relative;
        font-size: 22px;
        color: #555;
    }

    .notification .badge {
        position: absolute;
        top: -5px;
        right: -7px;
        background-color: red;
        color: #fff;
        border-radius: 50%;
        font-size: 11px;
        padding: 2px 5px;
    }

    .user-info {
        display: flex;
        align-items: center;
        background-color: #f5f5f5;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .user-info i {
        margin-right: 8px;
        color: #2196f3;
    }

    .user-info span {
        font-weight: 600;
    }
</style>

<div class="topbar">
    <div class="logo">
        <img src="https://cdn.sapo.vn/sapo-logo.svg" alt="Logo" height="35">
    </div>

    <div class="search-box">
        <input type="text" placeholder="Nhập từ khóa tìm kiếm...">
        <i class="fa fa-search"></i>
    </div>

    <div class="topbar-right">
        <div class="notification">
            <i class="fa fa-bell"></i>
            <span class="badge">12</span>
        </div>
        <div class="user-info">
            <i class="fa fa-user-circle"></i>
            <span>CSKH Sapoweb</span>
        </div>
    </div>
</div>