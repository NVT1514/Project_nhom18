<?php
session_start();
$voucher = trim($_POST['voucher']);

if ($voucher == "OCT20") {
    $_SESSION['discount'] = 20000;
} elseif ($voucher == "OCT70") {
    $_SESSION['discount'] = 70000;
} else {
    $_SESSION['discount'] = 0;
}
header("Location: cart.php");
exit();
