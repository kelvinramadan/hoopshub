<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's bookings with payment information
$bookings_query = "SELECT b.*, l.nama_lapangan, p.reference_number, p.payment_method, p.created_at as payment_date
                  FROM bookings b 
                  LEFT JOIN lapangan l ON b.lapangan_id = l.id 
                  LEFT JOIN payments p ON b.id = p.booking_id 
                  WHERE b.user_id = ? 
                  ORDER BY b.tanggal DESC, b.jam_mulai DESC";

$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings_result = $stmt->get_result();

include 'includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 100px;
            background: #f0f2f5;
        }
        .bookings-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .booking-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .booking-card:hover {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .booking-card:last-child {
            margin-bottom: 0;
        }
        .booking-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .booking-time {
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="bookings-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">Riwayat Booking</h2>
                        <a href="lapangan.php" class="btn btn-primary">Booking Baru</a>
                    </div>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['success_message'];
                            unset($_SESSION['success_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($bookings_result->num_rows > 0): ?>
                        <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                            <div class="booking-card">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="mb-0"><?php echo htmlspecialchars($booking['nama_lapangan']); ?></h5>
                                            <span class="booking-status <?php echo $booking['reference_number'] ? 'status-paid' : 'status-pending'; ?>">
                                                <?php echo $booking['reference_number'] ? 'Sudah Dibayar' : 'Menunggu Pembayaran'; ?>
                                            </span>
                                        </div>
                                        <div class="booking-time mb-3">
                                            <strong>Tanggal:</strong> <?php echo date('d F Y', strtotime($booking['tanggal'])); ?><br>
                                            <strong>Waktu:</strong> <?php echo $booking['jam_mulai'] . ' - ' . $booking['jam_selesai']; ?> 
                                            (<?php echo $booking['durasi']; ?> jam)
                                        </div>
                                        <div class="mb-2">
                                            <strong>Total:</strong> 
                                            <span class="text-primary">
                                                Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?>
                                            </span>
                                        </div>
                                        <?php if ($booking['reference_number']): ?>
                                            <div class="payment-info">
                                                <small class="text-muted">
                                                    Dibayar pada <?php echo date('d F Y H:i', strtotime($booking['payment_date'])); ?><br>
                                                    Metode: <?php echo ucfirst(str_replace('_', ' ', $booking['payment_method'])); ?><br>
                                                    No. Ref: <?php echo htmlspecialchars($booking['reference_number']); ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-3">
                                                <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-primary">
                                                    Bayar Sekarang
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h5>Belum ada booking</h5>
                            <p class="mb-0">
                                Anda belum memiliki riwayat booking. 
                                <a href="lapangan.php" class="alert-link">Booking lapangan sekarang</a>.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination bisa ditambahkan di sini jika diperlukan -->
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
    </script>

    <!-- Optional: Add to Calendar Feature -->
    <script>
    function addToCalendar(date, time, courtName) {
        // Implementation untuk menambahkan ke calendar device
        // Bisa menggunakan Google Calendar API atau format .ics
    }
    </script>
</body>
</html>