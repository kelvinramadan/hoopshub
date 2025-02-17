<!--get_messages.php-->
<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    exit('Not logged in');
}

$query = "
    SELECT m.*, u.username, u.profile_photo 
    FROM messages m 
    JOIN users u ON m.user_id = u.id 
    ORDER BY m.created_at DESC 
    LIMIT 100
";
$result = $conn->query($query);

if ($result) {
    while ($message = $result->fetch_assoc()): ?>
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
    <?php endwhile;
    $result->free();
}