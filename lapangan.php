<!-- lapangan.php -->
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
    <title>Daftar Lapangan</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            padding-top: 100px;
            background: #f0f2f5;
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .card {
            height: 100%;
            margin-bottom: 20px;
        }
        .card-body {
            display: flex;
            flex-direction: column;
        }
        .card-text {
            flex-grow: 1;
        }
        .map-container {
            height: 200px;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .filters {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2>Daftar Lapangan</h2>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="filters">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <select name="kategori" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Kategori</option>
                                <option value="indoor" <?php echo isset($_GET['kategori']) && $_GET['kategori'] == 'indoor' ? 'selected' : ''; ?>>Indoor</option>
                                <option value="outdoor" <?php echo isset($_GET['kategori']) && $_GET['kategori'] == 'outdoor' ? 'selected' : ''; ?>>Outdoor</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="harga" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Harga</option>
                                <option value="1" <?php echo isset($_GET['harga']) && $_GET['harga'] == '1' ? 'selected' : ''; ?>>Di bawah Rp 100.000</option>
                                <option value="2" <?php echo isset($_GET['harga']) && $_GET['harga'] == '2' ? 'selected' : ''; ?>>Rp 100.000 - Rp 200.000</option>
                                <option value="3" <?php echo isset($_GET['harga']) && $_GET['harga'] == '3' ? 'selected' : ''; ?>>Di atas Rp 200.000</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="row">
            <?php
            // Build query with filters
            $query = "SELECT * FROM lapangan WHERE 1=1";
            
            if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
                $kategori = mysqli_real_escape_string($conn, $_GET['kategori']);
                $query .= " AND kategori = '$kategori'";
            }
            
            if (isset($_GET['harga']) && !empty($_GET['harga'])) {
                switch ($_GET['harga']) {
                    case '1':
                        $query .= " AND harga < 100000";
                        break;
                    case '2':
                        $query .= " AND harga BETWEEN 100000 AND 200000";
                        break;
                    case '3':
                        $query .= " AND harga > 200000";
                        break;
                }
            }
            
            $query .= " ORDER BY id DESC";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $imagePath = "uploads/courts/" . $row['photo'];
                    if (!file_exists($imagePath)) {
                        $imagePath = "assets/images/no-image.jpg";
                    }
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="position-relative">
                                <span class="category-badge badge <?php echo $row['kategori'] == 'indoor' ? 'bg-primary' : 'bg-success'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($row['kategori'])); ?>
                                </span>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($row['nama_lapangan']); ?>"
                                     onerror="this.src='assets/images/no-image.jpg'">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['nama_lapangan']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                                <p class="card-text">
                                    <strong>Harga:</strong> Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?>/jam
                                </p>
                                <div class="map-container" id="map-<?php echo $row['id']; ?>"></div>
                                <a href="booking.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Booking Sekarang</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-info">Belum ada lapangan tersedia.</div></div>';
            }
            ?>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php
        // Reset result pointer
        if ($result) {
            mysqli_data_seek($result, 0);
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['latitude']) && !empty($row['longitude'])) {
                    ?>
                    // Initialize map for each court
                    var map<?php echo $row['id']; ?> = L.map('map-<?php echo $row['id']; ?>').setView([<?php echo $row['latitude']; ?>, <?php echo $row['longitude']; ?>], 13);
                    
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map<?php echo $row['id']; ?>);
                    
                    L.marker([<?php echo $row['latitude']; ?>, <?php echo $row['longitude']; ?>])
                        .addTo(map<?php echo $row['id']; ?>)
                        .bindPopup("<?php echo htmlspecialchars($row['nama_lapangan']); ?>");
                    <?php
                }
            }
        }
        ?>
    });
    </script>
</body>
</html>