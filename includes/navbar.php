<!-- includes/navbar.php -->
<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set upload constraints
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', __DIR__ . '/uploads');

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo'])) {
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        $file = $_FILES['profile_photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }
        
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG and GIF allowed');
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File too large. Maximum size is 5MB');
        }
        
        // Create upload directory if it doesn't exist
        if (!file_exists(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }
        
        // Generate unique filename
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('profile_', true) . '.' . $ext;
        $filepath = UPLOAD_PATH . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Delete old photo if exists
        if (!empty($_SESSION['profile_photo'])) {
            $old_photo = __DIR__ . '/' . $_SESSION['profile_photo'];
            if (file_exists($old_photo)) {
                unlink($old_photo);
            }
        }
        
        // Update database
        $db_path = 'uploads/' . $filename;
        $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
        $stmt->bind_param("si", $db_path, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        
        // Update session
        $_SESSION['profile_photo'] = $db_path;
        
        $response['status'] = 'success';
        $response['message'] = 'Profile photo updated successfully';
        $response['photo_url'] = $db_path;
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Rest of the navbar.php HTML code remains the same as in your original file
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HoopsHub - Navigation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        nav {
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: #666;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #333;
        }

        .profile-btn {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .profile-btn:hover {
            background-color: #f5f5f5;
        }

        .profile-btn img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-popup {
            display: none;
            position: absolute;
            top: 70px;
            right: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            width: 300px;
            padding: 1rem;
            z-index: 1001;
        }

        .profile-popup.active {
            display: block;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .profile-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-info h3 {
            color: #333;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .profile-info p {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .profile-menu {
            margin-top: 1rem;
        }

        .profile-menu button {
            width: 100%;
            padding: 0.75rem 1rem;
            text-align: left;
            background: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            color: #333;
            font-size: 0.875rem;
        }

        .profile-menu button:hover {
            background-color: #f5f5f5;
        }

        .profile-menu button.logout {
            color: #dc3545;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1002;
        }

        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }

        .preview-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 20px auto;
            overflow: hidden;
        }

        .preview-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hamburger {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }

        .hamburger div {
            width: 25px;
            height: 3px;
            background-color: #333;
            margin: 5px 0;
            transition: 0.3s;
        }

        @media (max-width: 768px) {
            .hamburger {
                display: block;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 70px;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .nav-links.active {
                display: flex;
            }

            .profile-popup {
                right: 1rem;
                width: calc(100% - 2rem);
                max-width: 300px;
            }
        }
        
        .profile-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .profile-photo-lg {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        #photoPreview {
            max-width: 200px;
            max-height: 200px;
            margin: 10px 0;
        }
        
        .upload-progress {
            height: 4px;
            margin: 10px 0;
            display: none;
        }
        
        .modal-header {
            border-bottom: none;
        }
        
        .modal-footer {
            border-top: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">HoopsHub</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lapangan.php">Lapangan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="layanan.php">Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="forum.php">Forum</a>
                    </li>
                </ul>
                
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle text-decoration-none" type="button" id="profileDropdown" data-bs-toggle="dropdown">
                        <img src="<?php echo !empty($_SESSION['profile_photo']) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/default-profile.png'; ?>" 
                             alt="Profile" class="profile-photo">
                        <span class="ms-2"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </button>
                    
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-item">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo !empty($_SESSION['profile_photo']) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/default-profile.png'; ?>" 
                                     alt="Profile" class="profile-photo-lg">
                                <div class="ms-3">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($_SESSION['email']); ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item" onclick="openPhotoModal()">Update Photo</button></li>
                        <li><button class="dropdown-item" onclick="logout()">Logout</button></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Photo Upload Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Profile Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <form id="photoForm" enctype="multipart/form-data">
                        <img id="photoPreview" src="<?php echo !empty($_SESSION['profile_photo']) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/default-profile.png'; ?>" 
                             alt="Preview" class="img-fluid">
                        
                        <div class="progress upload-progress">
                            <div class="progress-bar" role="progressbar"></div>
                        </div>
                        
                        <input type="file" id="photoInput" name="profile_photo" accept="image/jpeg,image/png,image/gif" class="form-control">
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Upload Photo</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap components
        const photoModal = new bootstrap.Modal(document.getElementById('photoModal'));
        
        // Preview functionality
        document.getElementById('photoInput').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Handle form submission
        document.getElementById('photoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const progressBar = document.querySelector('.progress-bar');
            const progressDiv = document.querySelector('.upload-progress');
            
            try {
                progressDiv.style.display = 'block';
                progressBar.style.width = '0%';
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                progressBar.style.width = '100%';
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    // Update all profile images
                    document.querySelectorAll('img[alt="Profile"]').forEach(img => {
                        img.src = result.photo_url;
                    });
                    
                    photoModal.hide();
                    alert(result.message);
                } else {
                    alert(result.message || 'Upload failed. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                progressDiv.style.display = 'none';
            }
        });

        function openPhotoModal() {
            photoModal.show();
        }

        function logout() {
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>