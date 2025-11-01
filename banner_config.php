<?php
// banner_config.php
$banner_path_file = __DIR__ . "/uploads/banner/current_banner.txt";

if (file_exists($banner_path_file)) {
    $banner_path = trim(file_get_contents($banner_path_file));
} else {
    $banner_path = "Img/default-banner.jpg"; // banner mặc định
}
