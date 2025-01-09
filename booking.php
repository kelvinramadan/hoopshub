<!--booking.php-->
<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get court ID from URL
$court_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch court details
$court_query = "SELECT * FROM lapangan WHERE id = ?";
$stmt = $conn->prepare($court_query);
$stmt->bind_param("i", $court_id);
$stmt->execute();
$court_result = $stmt->get_result();
$court = $court_result->fetch_assoc();

if (!$court) {
    header("Location: lapangan.php");
    exit();
}

// Process booking form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $durasi = (int)$_POST['durasi'];
    
    // Calculate end time
    $jam_selesai = date('H:i', strtotime($jam_mulai . ' + ' . $durasi . ' hours'));
    
    // Calculate total price
    $total_harga = $court['harga'] * $durasi;

    // Check if the selected time slot is available
    $check_query = "SELECT * FROM bookings 
                   WHERE lapangan_id = ? 
                   AND tanggal = ? 
                   AND ((jam_mulai <= ? AND jam_selesai > ?) 
                   OR (jam_mulai < ? AND jam_selesai >= ?))";
                   
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("isssss", $court_id, $tanggal, $jam_selesai, $jam_mulai, $jam_selesai, $jam_mulai);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "Maaf, jadwal yang Anda pilih sudah dipesan. Silakan pilih waktu lain.";
    } else {
        // Insert booking into database
        $insert_query = "INSERT INTO bookings (user_id, lapangan_id, tanggal, jam_mulai, jam_selesai, durasi, total_harga) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iisssid", $user_id, $court_id, $tanggal, $jam_mulai, $jam_selesai, $durasi, $total_harga);
        
        if ($insert_stmt->execute()) {
            $booking_id = $insert_stmt->insert_id;
            header("Location: payment.php?booking_id=" . $booking_id);
            exit();
        } else {
            $error = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}

include 'includes/navbar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Booking Lapangan</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 100px;
            background: #f0f2f5;
        }
        .booking-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .court-details {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="booking-form">
                    <h2 class="mb-4">Booking Lapangan</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="court-details">
                        <h4><?php echo htmlspecialchars($court['nama_lapangan']); ?></h4>
                        <p><?php echo htmlspecialchars($court['deskripsi']); ?></p>
                        <p><strong>Harga:</strong> Rp <?php echo number_format($court['harga'], 0, ',', '.'); ?>/jam</p>
                    </div>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="jam_mulai" class="form-label">Jam Mulai</label>
                            <select class="form-control" id="jam_mulai" name="jam_mulai" required>
                                <?php
                                // Generate time slots from 6 AM to 10 PM
                                for ($hour = 6; $hour <= 22; $hour++) {
                                    $time = sprintf("%02d:00", $hour);
                                    echo "<option value=\"$time\">$time</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="durasi" class="form-label">Durasi (jam)</label>
                            <select class="form-control" id="durasi" name="durasi" required>
                                <?php
                                for ($i = 1; $i <= 4; $i++) {
                                    echo "<option value=\"$i\">$i</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Booking Sekarang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>