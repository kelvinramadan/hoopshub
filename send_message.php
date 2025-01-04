<!--send_message.php-->
<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (user_id, message, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $_SESSION['user_id'], $message);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            error_log($conn->error);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Empty message']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}