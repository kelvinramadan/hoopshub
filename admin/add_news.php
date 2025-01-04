<!--admin/add_news.php -->
<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    
    $target_dir = "../uploads/";
    $image1 = uploadImage($_FILES["image1"], $target_dir);
    $image2 = uploadImage($_FILES["image2"], $target_dir);
    
    if ($image1 && $image2) {
        $stmt = $conn->prepare("INSERT INTO news (title, content, image1, image2) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $content, $image1, $image2);
        $stmt->execute();
        header("Location: add_news.php?success=1");
        exit();
    }
}

function uploadImage($file, $target_dir) {
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    if(getimagesize($file["tmp_name"]) === false) return false;
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") return false;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return basename($file["name"]);
    }
    return false;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add News</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="mt-4">
        <h2>Add News</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Content</label>
                <textarea name="content" class="form-control" rows="5" required></textarea>
            </div>
            <div class="mb-3">
                <label>Image 1</label>
                <input type="file" name="image1" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Image 2</label>
                <input type="file" name="image2" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</body>
</html>