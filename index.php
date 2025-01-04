<?php
require_once 'config.php';

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
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 100px;
            background: #f0f2f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row g-4">
            <?php
            $result = $conn->query("SELECT * FROM news ORDER BY created_at DESC");
            while ($row = $result->fetch_assoc()) {
            ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="row g-0">
                        <div class="col-md-6">
                            <img src="uploads/<?php echo htmlspecialchars($row['image1']); ?>" 
                                 class="img-fluid rounded-start h-100" alt="News image 1">
                        </div>
                        <div class="col-md-6">
                            <img src="uploads/<?php echo htmlspecialchars($row['image2']); ?>" 
                                 class="img-fluid rounded-end h-100" alt="News image 2">
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                        <p class="card-text"><small class="text-muted">
                            Posted on <?php echo date('F j, Y', strtotime($row['created_at'])); ?>
                        </small></p>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>