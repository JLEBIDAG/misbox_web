<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class NotificationServer implements MessageComponentInterface {
    protected $clients;
    protected $userConnections = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (isset($data['username'])) {
            $this->userConnections[$data['username']] = $from;
        }
        
        if (isset($data['action']) && $data['action'] === 'check_notifications') {
            $username = $data['username'];
            $count = $this->getUnreadNotificationCount($username);
            
            $response = ['count' => $count];
            $from->send(json_encode($response));
        }

        
    }

    public function getUnreadNotificationCount($username) {
        include 'inc/conn.php';

        $sql = "SELECT COUNT(*) as count FROM activity_logs a 
                INNER JOIN tbl_users u ON a.FK_UserID = u.PK_userID 
                WHERE a.is_viewed = 0 AND a.handle_by = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $conn->close();
        return $row['count'];
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new NotificationServer()
        )
    ),
    8080
);

echo "WebSocket server running on port 8080...\n";
$server->run();
