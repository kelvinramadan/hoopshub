<!--admin/add_news.php -->
<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Delete News
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get image names before deletion
    $stmt = $conn->prepare("SELECT image1, image2 FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $news = $result->fetch_assoc();
    
    // Delete images from uploads directory
    if ($news) {
        unlink("../uploads/" . $news['image1']);
        unlink("../uploads/" . $news['image2']);
    }
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    header("Location: add_news.php?success=3");
    exit();
}

// Edit News
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $news = $result->fetch_assoc();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    // For Update
    if (isset($_POST['news_id'])) {
        $news_id = $_POST['news_id'];
        $image1 = $_POST['existing_image1'];
        $image2 = $_POST['existing_image2'];
        
        // Handle new image uploads if provided
        if (!empty($_FILES['image1']['name'])) {
            unlink("../uploads/" . $_POST['existing_image1']); // Delete old image
            $image1 = uploadImage($_FILES["image1"], "../uploads/");
        }
        if (!empty($_FILES['image2']['name'])) {
            unlink("../uploads/" . $_POST['existing_image2']); // Delete old image
            $image2 = uploadImage($_FILES["image2"], "../uploads/");
        }
        
        if ($image1 !== false && $image2 !== false) {
            $stmt = $conn->prepare("UPDATE news SET title = ?, content = ?, image1 = ?, image2 = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $title, $content, $image1, $image2, $news_id);
            $stmt->execute();
            header("Location: add_news.php?success=2");
            exit();
        }
    } 
    // For Insert
    else {
        $image1 = uploadImage($_FILES["image1"], "../uploads/");
        $image2 = uploadImage($_FILES["image2"], "../uploads/");
        
        if ($image1 && $image2) {
            $stmt = $conn->prepare("INSERT INTO news (title, content, image1, image2) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $title, $content, $image1, $image2);
            $stmt->execute();
            header("Location: add_news.php?success=1");
            exit();
        }
    }
}

function uploadImage($file, $target_dir) {
    if (empty($file['name'])) return false;
    
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    // Generate unique filename
    $filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $filename;
    
    if(getimagesize($file["tmp_name"]) === false) return false;
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") return false;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $filename;
    }
    return false;
}

include 'navbar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage News</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="news.css">
</head>
<body class="container mt-5">
    <div class="mt-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    if ($_GET['success'] == 1) echo "News added successfully!";
                    if ($_GET['success'] == 2) echo "News updated successfully!";
                    if ($_GET['success'] == 3) echo "News deleted successfully!";
                ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?php echo isset($_GET['edit']) ? 'Edit News' : 'Add News'; ?></h2>
            <?php if (isset($_GET['edit'])): ?>
                <a href="add_news.php" class="btn btn-secondary">Add New</a>
            <?php endif; ?>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <?php if (isset($_GET['edit'])): ?>
                <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                <input type="hidden" name="existing_image1" value="<?php echo $news['image1']; ?>">
                <input type="hidden" name="existing_image2" value="<?php echo $news['image2']; ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required 
                       value="<?php echo isset($news) ? htmlspecialchars($news['title']) : ''; ?>">
            </div>
            <div class="mb-3">
                <label>Content</label>
                <textarea name="content" class="form-control" rows="5" required><?php 
                    echo isset($news) ? htmlspecialchars($news['content']) : ''; 
                ?></textarea>
            </div>
            <div class="mb-3">
                <label>Image 1</label>
                <?php if (isset($news) && $news['image1']): ?>
                    <div class="mb-2">
                        <img src="../uploads/<?php echo htmlspecialchars($news['image1']); ?>" 
                             style="max-height: 100px">
                    </div>
                <?php endif; ?>
                <input type="file" name="image1" class="form-control" <?php echo isset($news) ? '' : 'required'; ?>>
            </div>
            <div class="mb-3">
                <label>Image 2</label>
                <?php if (isset($news) && $news['image2']): ?>
                    <div class="mb-2">
                        <img src="../uploads/<?php echo htmlspecialchars($news['image2']); ?>" 
                             style="max-height: 100px">
                    </div>
                <?php endif; ?>
                <input type="file" name="image2" class="form-control" <?php echo isset($news) ? '' : 'required'; ?>>
            </div>
            <button type="submit" class="btn btn-primary">
                <?php echo isset($_GET['edit']) ? 'Update' : 'Submit'; ?>
            </button>
        </form>

        <!-- News List -->
        <h3 class="mt-5">News List</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM news ORDER BY created_at DESC");
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo date('F j, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="add_news.php?edit=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-primary">Edit</a>
                            <a href="add_news.php?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this news?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>