<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'config.php';

// Aktif kullanıcıları veritabanından çek
$stmt = $pdo->query("SELECT user_id, username FROM active_users WHERE user_id != " . $_SESSION['user_id']);
$activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Geçmiş mesajları veritabanından çek
$stmt = $pdo->query("SELECT users.username, messages.message, messages.user_id FROM messages INNER JOIN users ON messages.user_id = users.id ORDER BY messages.created_at ASC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Okunmamış özel mesajları çek
$stmt = $pdo->prepare("SELECT sender_id, COUNT(*) as unread_count FROM private_messages WHERE receiver_id = :receiver_id AND is_read = FALSE GROUP BY sender_id");
$stmt->execute([':receiver_id' => $_SESSION['user_id']]);
$unreadCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadMessages = [];
foreach ($unreadCounts as $unread) {
    $unreadMessages[$unread['sender_id']] = $unread['unread_count'];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Chat Uygulaması</title>
    <link rel="stylesheet" href="public/styles.css">
    <style>
        .unread-count {
            color: red;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
<div id="chat">
    <h1>Chat Uygulaması</h1>
    <div>
        <h2>Aktif Kullanıcılar</h2>
        <ul id="userList">
            <?php foreach ($activeUsers as $user): ?>
                <li>
                    <?php echo htmlspecialchars($user['username']); ?>
                    <a href="private_chat.php?receiver_id=<?php echo $user['user_id']; ?>">Mesaj Gönder</a>
                    <?php if (isset($unreadMessages[$user['user_id']])): ?>
                        <span class="unread-count">+<?php echo $unreadMessages[$user['user_id']]; ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div id="messages">
        <?php foreach ($messages as $message): ?>
            <div class="name <?php echo $message['user_id'] == $_SESSION['user_id'] ? 'sentname' : 'receivedname'; ?>">
                <?php echo htmlspecialchars( $message['username']); ?>
            </div>
            <div class="message <?php echo $message['user_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                <?php echo htmlspecialchars( $message['message']); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <input type="text" id="message" placeholder="Mesajınızı yazın..." onkeydown="checkEnter(event)">
    <button onclick="sendMessage()">Gönder</button>
    <a href="logout.php">Çıkış Yap</a>
</div>

<script>
    var conn = new WebSocket('ws://localhost:8080');
    var username = "<?php echo $_SESSION['username']; ?>";
    var userId = "<?php echo $_SESSION['user_id']; ?>";

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
        var messages = document.getElementById('messages');
        var message = document.createElement('div');
        message.className = 'message ' + (data.user_id == userId ? 'sent' : 'received');
        message.textContent = data.username + ': ' + data.message;
        messages.appendChild(message);
        scrollToBottom(); // Yeni mesaj geldiğinde en alta kaydır

        // Özel mesajsa okunmamış mesaj sayısını güncelle
        if (data.is_private && data.receiver_id == userId) {
            var userList = document.getElementById('userList');
            var userLink = userList.querySelector('a[href="private_chat.php?receiver_id=' + data.sender_id + '"]');
            if (userLink) {
                var unreadCountSpan = userLink.nextElementSibling;
                if (unreadCountSpan) {
                    var unreadCount = parseInt(unreadCountSpan.textContent.replace('+', ''));
                    unreadCountSpan.textContent = '+' + (unreadCount + 1);
                } else {
                    var span = document.createElement('span');
                    span.className = 'unread-count';
                    span.textContent = '+1';
                    userLink.parentNode.appendChild(span);
                }
            }
        }
    };

    function sendMessage() {
        var message = document.getElementById('message').value;
        if (message.trim() === '') return;

        var data = JSON.stringify({ message: message, user_id: userId, username: username });
        conn.send(data);

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
