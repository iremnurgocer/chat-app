<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kullanıcı adını kontrol et
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user) {
        echo "Bu kullanıcı adı zaten mevcut. Lütfen başka bir kullanıcı adı seçin.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Yeni kullanıcıyı ekle
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        if ($stmt->execute([':username' => $username, ':password' => $passwordHash])) {
            echo "Kayıt başarılı! <a href='login.php'>Giriş yap</a>";
        } else {
            echo "Kayıt başarısız!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
</head>
<body>
<h1>Kayıt Ol</h1>
<form method="POST">
    Kullanıcı Adı: <input type="text" name="username" required>
    Şifre: <input type="password" name="password" required>
    <button type="submit">Kayıt Ol</button>
</form>
<p>Zaten hesabınız var mı? <a href="login.php">Giriş yap</a></p>
</body>
</html>
