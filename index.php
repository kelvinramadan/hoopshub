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
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 100px; /* Untuk memberikan ruang di bawah fixed navbar */
            background: #f0f2f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">User Profile</h5>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($_SESSION['nomor_telp']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>