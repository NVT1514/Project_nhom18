<?php
session_start();
include "Database/connectdb.php";

if (
    !isset($_SESSION['tk']) ||
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin') ||
    !isset($_GET['id'])
) {
    header("Location: login.php");
    exit();
}
$userId = $_GET['id'];
$sql = "DELETE FROM user WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
if ($stmt->execute()) {
    header("Location: quanlinguoidung_admin.php?status=success_delete");
} else {
    header("Location: quanlinguoidung_admin.php?status=error_delete");
}
$stmt->close();
$conn->close();
