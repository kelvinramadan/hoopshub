<!--forum.php-->
<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/navbar.php';

// Fetch messages with user information
$query = "
    SELECT m.*, u.username, u.profile_photo 
    FROM messages m 
    JOIN users u ON m.user_id = u.id 
    ORDER BY m.created_at DESC 
    LIMIT 100
";
$result = $conn->query($query);
$messages = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat Room</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/chat.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            padding-top: 100px;
            background: #f0f2f5;
        }
        .chat-container {
            height: calc(100vh - 200px);
            overflow-y: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .message-box {
            padding: 15px;
            margin: 10px;
            border-bottom: 1px solid #eee;
        }
        .message-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .profile-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        .message-info {
            flex-grow: 1;
        }
        .username {
            font-weight: bold;
            color: #1a73e8;
        }
        .timestamp {
            font-size: 0.8em;
            color: #666;
        }
        .message-content {
            margin-left: 50px;
            word-wrap: break-word;
        }
        .message-form {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 20px;
            background: white;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="chat-container" id="chatContainer">
            <?php foreach ($messages as $message): ?>
                <div class="message-box">
                    <div class="message-header">
                        <img src="<?php echo htmlspecialchars($message['profile_photo'] ?: 'assets/images/default-avatar.png'); ?>" 
                             class="profile-photo" alt="Profile photo">
                        <div class="message-info">
                            <span class="username"><?php echo htmlspecialchars($message['username']); ?></span>
                            <span class="timestamp"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="message-content">
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="message-form">
            <form id="messageForm" class="row g-3">
                <div class="col-10">
                    <input type="text" name="message" id="messageInput" class="form-control" placeholder="Type your message..." required>
                </div>
                <div class="col-2">
                    <button type="submit" class="btn btn-primary w-100">Send</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatContainer = document.getElementById('chatContainer');
            const messageForm = document.getElementById('messageForm');
            const messageInput = document.getElementById('messageInput');

            // Auto-scroll to bottom on page load
            function scrollToBottom() {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
            scrollToBottom();

            // Handle form submission
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData();
                formData.append('message', messageInput.value);

                fetch('send_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = ''; // Clear input
                        updateMessages();
                    }
                })
                
            });

            // Function to update messages
            function updateMessages() {
                fetch('get_messages.php')
                    .then(response => response.text())
                    .then(html => {
                        chatContainer.innerHTML = html;
                        scrollToBottom();
                    });
            }

            // Update messages every 5 seconds
            setInterval(updateMessages, 5000);
        });
    </script>
</body>
</html>