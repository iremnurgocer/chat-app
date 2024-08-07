<?php
session_start();
require 'config.php';

// Kullanıcıyı active_users tablosundan sil
$stmt = $pdo->prepare("DELETE FROM active_users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);

session_destroy();
header("Location: login.php");
exit;
