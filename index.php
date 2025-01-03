<!--index.php-->
<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/navbar.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>HOME</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 100px; /* Untuk memberikan ruang di bawah fixed navbar */
            background: #f0f2f5;
        }
    </style>
</head>
<body>
    
</body>
</html>