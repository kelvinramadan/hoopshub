<!--admin/add_court.php -->
<?php
require_once '../config.php';

// Delete court
if (isset($_POST['delete'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    
    // Get photo filename before deleting record
    $query = "SELECT photo FROM lapangan WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row) {
        // Delete photo file if exists
        $photo_path = "../uploads/courts/" . $row['photo'];
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }
    }
    
    // Delete record
    $query = "DELETE FROM lapangan WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Lapangan berhasil dihapus!";
    } else {
        $error = "Gagal menghapus lapangan: " . mysqli_error($conn);
    }
}

// Add/Edit court
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add']) || isset($_POST['update']))) {
    $nama_lapangan = mysqli_real_escape_string($conn, $_POST['nama_lapangan']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $latitude = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conn, $_POST['longitude']);
    
    $uploadOk = true;
    $photo = null;
    
    // Handle file upload if a file was selected
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        $target_dir = "../uploads/courts/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $imageFileType = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $photo = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $photo;
        
        // Check if image file is actual image
        $check = getimagesize($_FILES["photo"]["tmp_name"]);
        if($check === false) {
            $error = "File bukan gambar.";
            $uploadOk = false;
        }
        
        // Check file size
        if ($_FILES["photo"]["size"] > 5000000) {
            $error = "Ukuran file terlalu besar (maksimal 5MB).";
            $uploadOk = false;
        }
        
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $error = "Hanya file JPG, JPEG, & PNG yang diperbolehkan.";
            $uploadOk = false;
        }
    }
    
    if ($uploadOk) {
        if (isset($_POST['update'])) {
            $id = mysqli_real_escape_string($conn, $_POST['id']);
            
            if ($photo) {
                // Delete old photo
                $query = "SELECT photo FROM lapangan WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $old_data = mysqli_fetch_assoc($result);
                
                if ($old_data && $old_data['photo']) {
                    $old_photo = "../uploads/courts/" . $old_data['photo'];
                    if (file_exists($old_photo)) {
                        unlink($old_photo);
                    }
                }
                
                // Move new photo
                move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
                
                // Update with new photo
                $query = "UPDATE lapangan SET nama_lapangan=?, deskripsi=?, harga=?, photo=?, kategori=?, latitude=?, longitude=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssdssssi", $nama_lapangan, $deskripsi, $harga, $photo, $kategori, $latitude, $longitude, $id);
            } else {
                // Update without changing photo
                $query = "UPDATE lapangan SET nama_lapangan=?, deskripsi=?, harga=?, kategori=?, latitude=?, longitude=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssdsssi", $nama_lapangan, $deskripsi, $harga, $kategori, $latitude, $longitude, $id);
            }
            
        } else { // Add new
            if ($photo) {
                move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
            }
            
            $query = "INSERT INTO lapangan (nama_lapangan, deskripsi, harga, photo, kategori, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssdssss", $nama_lapangan, $deskripsi, $harga, $photo, $kategori, $latitude, $longitude);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $success = isset($_POST['update']) ? "Lapangan berhasil diupdate!" : "Lapangan berhasil ditambahkan!";
            // Clear form after successful add
            if (!isset($_POST['update'])) {
                $_GET['edit'] = null;
            }
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Get court data for editing
$court_data = null;
if (isset($_GET['edit'])) {
    $id = mysqli_real_escape_string($conn, $_GET['edit']);
    $query = "SELECT * FROM lapangan WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $court_data = mysqli_fetch_assoc($result);
}

include 'navbar.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Lapangan</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="court.css">
    <style>
        #map {
            height: 250px;
            width: 100%;
            margin-top: 10px;
            z-index: 0;
        }
        .preview-image {
            max-width: 200px;
            margin-top: 10px;
            display: none;
        }
        .existing-image {
            max-width: 200px;
            margin-top: 10px;
        }
        .court-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .table > tbody > tr > td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Form Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><?php echo isset($_GET['edit']) ? 'Edit Lapangan' : 'Tambah Lapangan Baru'; ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php if (isset($_GET['edit'])): ?>
                                <input type="hidden" name="id" value="<?php echo $court_data['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lapangan</label>
                                        <input type="text" name="nama_lapangan" class="form-control" required 
                                               value="<?php echo isset($court_data) ? htmlspecialchars($court_data['nama_lapangan']) : ''; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select name="kategori" class="form-control" required>
                                            <option value="">Pilih Kategori</option>
                                            <option value="indoor" <?php echo (isset($court_data) && $court_data['kategori'] == 'indoor') ? 'selected' : ''; ?>>Indoor</option>
                                            <option value="outdoor" <?php echo (isset($court_data) && $court_data['kategori'] == 'outdoor') ? 'selected' : ''; ?>>Outdoor</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea name="deskripsi" class="form-control" rows="3" required><?php echo isset($court_data) ? htmlspecialchars($court_data['deskripsi']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Harga per Jam</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" name="harga" class="form-control" required 
                                                   value="<?php echo isset($court_data) ? $court_data['harga'] : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Lokasi Lapangan</label>
                                        <div id="map"></div>
                                        <input type="hidden" name="latitude" id="latitude" 
                                               value="<?php echo isset($court_data) ? $court_data['latitude'] : ''; ?>">
                                        <input type="hidden" name="longitude" id="longitude" 
                                               value="<?php echo isset($court_data) ? $court_data['longitude'] : ''; ?>">
                                        <small class="text-muted">Klik pada peta untuk menentukan lokasi atau drag marker</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Foto Lapangan</label>
                                        <?php if (isset($court_data) && $court_data['photo']): ?>
                                            <div class="mb-2">
                                                <img src="../uploads/courts/<?php echo htmlspecialchars($court_data['photo']); ?>" 
                                                     class="existing-image" alt="Current photo">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*" 
                                               <?php echo !isset($court_data) ? 'required' : ''; ?> onchange="previewImage(this);">
                                        <img id="preview" class="preview-image">
                                        <?php if (isset($court_data)): ?>
                                            <small class="text-muted">Upload foto baru jika ingin mengganti foto yang ada</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <?php if (isset($_GET['edit'])): ?>
                                    <button type="submit" name="update" class="btn btn-primary">Update Lapangan</button>
                                <?php else: ?>
                                    <button type="submit" name="add" class="btn btn-primary">Simpan Lapangan</button>
                                <?php endif; ?>
                                <?php if (isset($_GET['edit'])): ?>
                                    <a href="lapangan.php" class="btn btn-secondary">Batal Edit</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- List Section -->
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Daftar Lapangan</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nama Lapangan</th>
                                <th>Kategori</th>
                                <th>Harga/Jam</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM lapangan ORDER BY id DESC";
                            $result = $conn->query($query);
                            
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $imagePath = "../uploads/courts/" . $row['photo'];
                                    if (!file_exists($imagePath)) {
                                        $imagePath = "../assets/images/no-image.jpg";
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                                 class="court-image" 
                                                 alt="<?php echo htmlspecialchars($row['nama_lapangan']); ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($row['nama_lapangan']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $row['kategori'] == 'indoor' ? 'bg-primary' : 'bg-success'; ?>">
                                                <?php echo ucfirst($row['kategori']); ?>
                                            </span>
                                        </td>
                                        <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lapangan ini?');">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="delete" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">Belum ada data lapangan.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
    // Image preview function
    function previewImage(input) {
        var preview = document.getElementById('preview');
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Maps initialization
    let map, marker;
    
    function initMap() {
        <?php if (isset($court_data) && $court_data['latitude'] && $court_data['longitude']): ?>
            const initialLocation = [<?php echo $court_data['latitude']; ?>, <?php echo $court_data['longitude']; ?>];
        <?php else: ?>
            const initialLocation = [-6.200000, 106.816666]; // Default to Jakarta
        <?php endif; ?>
        
        map = L.map('map').setView(initialLocation, 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        marker = L.marker(initialLocation, {
            draggable: true
        }).addTo(map);
        
        marker.on('dragend', function(e) {
            updateCoordinates(marker.getLatLng());
        });
        
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateCoordinates(e.latlng);
        });
        
        updateCoordinates(marker.getLatLng());
    }

    function updateCoordinates(latlng) {
        document.getElementById('latitude').value = latlng.lat;
        document.getElementById('longitude').value = latlng.lng;
    }

    window.onload = initMap;
    </script>
</body>
</html>