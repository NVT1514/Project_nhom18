<?php
include "Database/connectdb.php";
session_start();

$id = intval($_POST['id']);
$action = $_POST['action'];

if ($action == "increase") {
    $sql = "UPDATE cart SET quantity = quantity + 1 WHERE id = $id";
} else {
    $sql = "UPDATE cart SET quantity = GREATEST(quantity - 1, 1) WHERE id = $id";
}

mysqli_query($conn, $sql);
header("Location: cart.php");
exit();
