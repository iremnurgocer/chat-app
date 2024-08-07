<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['receiver_id'])) {
    header("Location: login.php");
    exit;
}

$receiverId = (int)$_GET['receiver_id'];
$senderId = (int)$_SESSION['user_id'];

// Alıcı kullanıcının adını çek
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
$stmt->execute([':id' => $receiverId]);
$receiver = $stmt->fetch();

if (!$receiver) {
    echo "Kullanıcı bulunamadı.";
    exit;
}

// Özel mesajları veritabanından çek
$stmt = $pdo->prepare("SELECT * FROM private_messages WHERE (sender_id = :sender_id AND receiver_id = :receiver_id) OR (sender_id = :receiver_id2 AND receiver_id = :sender_id2) ORDER BY created_at ASC");
$stmt->execute([
    ':sender_id' => $senderId,
    ':receiver_id' => $receiverId,
    ':receiver_id2' => $receiverId,
    ':sender_id2' => $senderId
]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Okunmamış mesajları güncelle
$stmt = $pdo->prepare("UPDATE private_messages SET is_read = TRUE WHERE receiver_id = :receiver_id AND sender_id = :sender_id AND is_read = FALSE");
$stmt->execute([':receiver_id' => $senderId, ':sender_id' => $receiverId]);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Özel Mesajlaşma - <?php echo htmlspecialchars($receiver['username']); ?></title>
    <link rel="stylesheet" href="public/styles.css">
</head>
<body>
    <div id="chat">
        <h1><?php echo htmlspecialchars($receiver['username']); ?> ile Mesajlaşma</h1>
        <div id="messages">
            <?php foreach ($messages as $message): ?>
                <div class="message <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                    <?php echo htmlspecialchars($message['message']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="text" id="message" placeholder="Mesajınızı yazın..." onkeydown="checkEnter(event)">
        <button onclick="sendMessage()">Gönder</button>
        <a href="chat.php">Geri Dön</a>
    </div>

    <script>
        var conn = new WebSocket('ws://localhost:8080');
        var username = "<?php echo $_SESSION['username']; ?>";
        var userId = "<?php echo $_SESSION['user_id']; ?>";
        var receiverId = "<?php echo $receiverId; ?>";

        function scrollToBottom() {
            var messages = document.getElementById('messages');
            messages.scrollTop = messages.scrollHeight;
        }

        conn.onopen = function(e) {
            console.log("Bağlantı kuruldu!");
            scrollToBottom(); // Sayfa yüklendiğinde en alta kaydır
        };

        conn.onmessage = function(e) {
            var data = JSON.parse(e.data);
            if (data.is_private && ((data.sender_id == userId && data.receiver_id == receiverId) || (data.sender_id == receiverId && data.receiver_id == userId))) {
                var messages = document.getElementById('messages');
                var message = document.createElement('div');
                message.className = 'message ' + (data.sender_id == userId ? 'sent' : 'received');
                message.textContent = data.message;
                messages.appendChild(message);
                scrollToBottom(); // Yeni mesaj geldiğinde en alta kaydır
            }
        };

        function sendMessage() {
            var message = document.getElementById('message').value;
            if (message.trim() === '') return;

            var data = {
                message: message,
                sender_id: userId,
                receiver_id: receiverId,
                is_private: true
            };
            conn.send(JSON.stringify(data));

            document.getElementById('message').value = '';  // Mesaj gönderildikten sonra input alanını temizle
        }

        function checkEnter(event) {
            if (event.key === 'Enter') {
                event.preventDefault();  // Enter tuşuna basıldığında formun yeniden gönderilmesini engelle
                sendMessage();
            }
        }

        // Sayfa yüklendiğinde en alta kaydır
        window.onload = scrollToBottom;
    </script>
</body>
</html>
