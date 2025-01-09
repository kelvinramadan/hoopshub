<!-- register.php -->
<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $nomor_telp = $_POST['nomor_telp'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, nomor_telp, email, username, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nama_lengkap, $nomor_telp, $email, $username, $password);
    
    try {
        if ($stmt->execute()) {
            header("Location: login.php?message=Registration successful! Please login.");
            exit();
        } else {
            $error = "Registration failed: " . $conn->error;
        }
    } catch(Exception $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - NBA Fan Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="loginregist.css">
</head>
<body>
    <div class="images/nba.jpg"></div>
    <div class="container">
        <div class="auth-container">
            <!-- Side Image Container -->
            <div class="auth-image">
                <div class="images/lamelo.png"></div>
            </div>
            
            <div class="auth-form">
                <!-- NBA Logo -->
                <div class="logo-container">
                    <img src="images/logo.png" alt="NBA Logo" class="nba-logo">
                </div>
                <h2>Create Account</h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="nama_lengkap" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="nomor_telp" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Register</button>
                </form>

                <div class="auth-links">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>