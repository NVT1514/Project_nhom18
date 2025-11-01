<?php
/*
|---------------------------------------------------
| File: breadcrumb.php
| Mục đích: Tạo thanh breadcrumb dùng chung cho toàn hệ thống
| Cách dùng: include "breadcrumb.php";
|---------------------------------------------------
*/

if (!isset($breadcrumb_title)) $breadcrumb_title = "";
if (!isset($breadcrumb_items)) $breadcrumb_items = [];

/*
  💡 Ví dụ cấu hình:
  $breadcrumb_title = "Lịch sử mua hàng";
  $breadcrumb_items = [
      ["label" => "Trang chủ", "link" => "trang_chu_user.php"],
      ["label" => "Lịch sử mua hàng"]
  ];
*/
?>

<!-- ✅ Breadcrumb chung -->
<nav class="breadcrumb">
    <?php foreach ($breadcrumb_items as $index => $item): ?>
        <?php if (!empty($item['link']) && $index < count($breadcrumb_items) - 1): ?>
            <a href="<?= htmlspecialchars($item['link']) ?>">
                <?php if ($index === 0): ?><i class="fa fa-home"></i><?php endif; ?>
                <?= htmlspecialchars($item['label']) ?>
            </a>
            <span>›</span>
        <?php else: ?>
            <span class="current"><?= htmlspecialchars($item['label']) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>

<style>
    /* --- Breadcrumb style chung --- */
    .breadcrumb {
        font-size: 15px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .breadcrumb a {
        color: #007bff;
        text-decoration: none;
        transition: color 0.2s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .breadcrumb a:hover {
        color: #0056b3;
        text-decoration: underline;
    }

    .breadcrumb span {
        color: #888;
    }

    .breadcrumb .current {
        color: #333;
        font-weight: 600;
    }
</style>