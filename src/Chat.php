<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    private $pdo;
    private $clientIdMap;

    public function __construct(PDO $pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
        $this->clientIdMap = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Yeni bağlantı! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $messageText = $data['message'];
        $senderId = $data['user_id'];
        $receiverId = $data['receiver_id'] ?? null;
        $isPrivate = $data['is_private'] ?? false;

        // Bağlantı kaydını güncelle
        $this->clientIdMap[$from->resourceId] = $senderId;

        // Mesajı sadece ilgili bağlantılara gönder
        foreach ($this->clients as $client) {
            if ($isPrivate) {
                if ($this->clientIdMap[$client->resourceId] == $receiverId || $client == $from) {
                    $client->send($msg);
                }
            } else {
                $client->send($msg);
            }
        }

        // Mesajı veritabanına kaydet
        if ($isPrivate) {
            $stmt = $this->pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, message, is_read) VALUES (:sender_id, :receiver_id, :message, FALSE)");
            $stmt->execute([':sender_id' => $senderId, ':receiver_id' => $receiverId, ':message' => $messageText]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO messages (message, user_id) VALUES (:message, :user_id)");
            $stmt->execute([':message' => $messageText, ':user_id' => $senderId]);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->clientIdMap[$conn->resourceId]);
        echo "Bağlantı kapatıldı ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Hata: {$e->getMessage()}\n";
        $conn->close();
    }
}
