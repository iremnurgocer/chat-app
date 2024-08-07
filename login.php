<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kullanıcıyı users tablosundan çek
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Kullanıcıyı active_users tablosuna ekle
        $stmt = $pdo->prepare("INSERT INTO active_users (user_id, username) VALUES (:user_id, :username) ON DUPLICATE KEY UPDATE username = VALUES(username)");
        $stmt->execute([':user_id' => $user['id'], ':username' => $user['username']]);

        header("Location: chat.php");
        exit;
    } else {
        echo "Giriş başarısız!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
</head>
<body>
    <h1>Giriş Yap</h1>
    <form method="POST">
        Kullanıcı Adı: <input type="text" name="username" required>
        Şifre: <input type="password" name="password" required>
        <button type="submit">Giriş Yap</button>
    </form>
    <p>Hesabınız yok mu? <a href="register.php">Kayıt ol</a></p>
</body>
</html>
