<!--admin/add_court.php -->
<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lapangan = isset($_POST['nama_lapangan']) ? mysqli_real_escape_string($conn, $_POST['nama_lapangan']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? mysqli_real_escape_string($conn, $_POST['deskripsi']) : '';
    $harga = isset($_POST['harga']) ? mysqli_real_escape_string($conn, $_POST['harga']) : '';
    $kategori = isset($_POST['kategori']) ? mysqli_real_escape_string($conn, $_POST['kategori']) : '';
    $latitude = isset($_POST['latitude']) ? mysqli_real_escape_string($conn, $_POST['latitude']) : '';
    $longitude = isset($_POST['longitude']) ? mysqli_real_escape_string($conn, $_POST['longitude']) : '';
    
    // Handle file upload
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        $target_dir = "../uploads/courts/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $photo = basename($_FILES["photo"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
        
        // Check if image file is actual image
        $check = getimagesize($_FILES["photo"]["tmp_name"]);
        if($check === false) {
            $error = "File bukan gambar.";
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["photo"]["size"] > 5000000) {
            $error = "Ukuran file terlalu besar (maksimal 5MB).";
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $error = "Hanya file JPG, JPEG, & PNG yang diperbolehkan.";
            $uploadOk = 0;
        }
        
        // Generate unique filename
        $photo = uniqid() . '.' . $imageFileType;
        $target_file = $target_dir . $photo;
        
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                $query = "INSERT INTO lapangan (nama_lapangan, deskripsi, harga, photo, kategori, latitude, longitude) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                         
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssdssss", $nama_lapangan, $deskripsi, $harga, $photo, $kategori, $latitude, $longitude);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Lapangan berhasil ditambahkan!";
                    header("refresh:2;url=lapangan.php");
                } else {
                    $error = "Error: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = "Gagal mengupload file.";
            }
        }
    } else {
        $error = "Silakan pilih file foto.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Lapangan</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet CSS dan JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        body {
            padding-top: 100px;
            background: #f0f2f5;
        }
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            display: none;
        }
        #map {
            height: 400px;
            width: 100%;
            margin-top: 10px;
            z-index: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Tambah Lapangan Baru</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Nama Lapangan</label>
                                <input type="text" name="nama_lapangan" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" class="form-control" required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="indoor">Indoor</option>
                                    <option value="outdoor">Outdoor</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Harga per Jam</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="harga" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Lokasi Lapangan</label>
                                <div id="map"></div>
                                <input type="hidden" name="latitude" id="latitude">
                                <input type="hidden" name="longitude" id="longitude">
                                <small class="text-muted">Klik pada peta untuk menentukan lokasi atau drag marker</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Foto Lapangan</label>
                                <input type="file" name="photo" id="photo" class="form-control" accept="image/*" required onchange="previewImage(this);">
                                <img id="preview" class="preview-image">
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="lapangan.php" class="btn btn-secondary">Kembali</a>
                            </div>
                        </form>
                    </div>
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
        // Koordinat default (Jakarta)
        const defaultLocation = [-6.200000, 106.816666];
        
        // Inisialisasi peta
        map = L.map('map').setView(defaultLocation, 13);
        
        // Tambahkan layer OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Tambahkan marker yang bisa di-drag
        marker = L.marker(defaultLocation, {
            draggable: true
        }).addTo(map);
        
        // Event saat marker selesai di-drag
        marker.on('dragend', function(e) {
            updateCoordinates(marker.getLatLng());
        });
        
        // Event saat peta diklik
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateCoordinates(e.latlng);
        });
        
        // Set koordinat awal
        updateCoordinates(marker.getLatLng());
    }

    function updateCoordinates(latlng) {
        document.getElementById('latitude').value = latlng.lat;
        document.getElementById('longitude').value = latlng.lng;
    }

    // Inisialisasi peta saat halaman dimuat
    window.onload = initMap;
    </script>
</body>
</html>