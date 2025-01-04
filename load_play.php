<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $play_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT * FROM plays WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $play_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = ['success' => false];
    
    if ($play = $result->fetch_assoc()) {
        $response['success'] = true;
        $response['play_data'] = $play['play_data'];
        $response['title'] = $play['title'];
        $response['description'] = $play['description'];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
}
?>