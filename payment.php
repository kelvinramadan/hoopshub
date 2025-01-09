<!--payment.php-->
<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Fetch booking details
$booking_query = "SELECT b.*, l.nama_lapangan, l.harga 
                 FROM bookings b 
                 JOIN lapangan l ON b.lapangan_id = l.id 
                 WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$booking_result = $stmt->get_result();
$booking = $booking_result->fetch_assoc();

if (!$booking) {
    header("Location: my_bookings.php");
    exit();
}

// Check if payment already exists
$check_payment = "SELECT * FROM payments WHERE booking_id = ?";
$check_stmt = $conn->prepare($check_payment);
$check_stmt->bind_param("i", $booking_id);
$check_stmt->execute();
$payment_exists = $check_stmt->get_result()->num_rows > 0;

if ($payment_exists) {
    header("Location: my_bookings.php");
    exit();
}

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];
    $reference_number = $_POST['reference_number'];
    
    // Validate reference number
    if (empty($reference_number)) {
        $error = "Nomor referensi tidak boleh kosong";
    } else {
        // Insert payment record
        $insert_query = "INSERT INTO payments (booking_id, amount, payment_method, reference_number) 
                        VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("idss", $booking_id, $booking['total_harga'], $payment_method, $reference_number);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success_message'] = "Pembayaran berhasil disubmit.";
            header("Location: my_bookings.php");
            exit();
        } else {
            $error = "Terjadi kesalahan. Silakan coba lagi.";
        }
    }
}

include 'includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Booking</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 100px;
            background: #f0f2f5;
        }
        .payment-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .booking-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .payment-method {
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            background-color: #f8f9fa;
        }
        .payment-method.selected {
            border-color: #0d6efd;
            background-color: #f0f7ff;
        }
        .bank-details {
            padding: 10px;
            background: #fff;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="payment-form">
                    <h2 class="mb-4">Pembayaran Booking</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="booking-details">
                        <h4>Detail Booking</h4>
                        <p><strong>Lapangan:</strong> <?php echo htmlspecialchars($booking['nama_lapangan']); ?></p>
                        <p><strong>Tanggal:</strong> <?php echo date('d F Y', strtotime($booking['tanggal'])); ?></p>
                        <p><strong>Waktu:</strong> <?php echo $booking['jam_mulai'] . ' - ' . $booking['jam_selesai']; ?></p>
                        <p><strong>Durasi:</strong> <?php echo $booking['durasi']; ?> jam</p>
                        <p class="mb-0"><strong>Total Pembayaran:</strong> 
                            <span class="text-primary">
                                Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?>
                            </span>
                        </p>
                    </div>

                    <form method="POST" action="" id="paymentForm">
                        <div class="mb-4">
                            <h5>Pilih Metode Pembayaran</h5>
                            <div class="payment-method" data-method="transfer_bca">
                                <input type="radio" name="payment_method" value="transfer_bca" required>
                                <label>Transfer BCA</label>
                                <div class="bank-details">
                                    <small><strong>No. Rekening:</strong> 1234567890</small><br>
                                    <small><strong>A/N:</strong> PT Sport Center</small>
                                </div>
                            </div>
                            <div class="payment-method" data-method="transfer_mandiri">
                                <input type="radio" name="payment_method" value="transfer_mandiri">
                                <label>Transfer Mandiri</label>
                                <div class="bank-details">
                                    <small><strong>No. Rekening:</strong> 0987654321</small><br>
                                    <small><strong>A/N:</strong> PT Sport Center</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reference_number" class="form-label">Nomor Referensi Transfer</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                   required placeholder="Masukkan nomor referensi transfer">
                            <div class="form-text">
                                Masukkan nomor referensi yang tertera pada bukti transfer Anda
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Konfirmasi Pembayaran</button>
                            <a href="my_bookings.php" class="btn btn-outline-secondary">Kembali ke Riwayat Booking</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle payment method selection
        const paymentMethods = document.querySelectorAll('.payment-method');
        
        paymentMethods.forEach(method => {
            method.addEventListener('click', function() {
                // Remove selected class from all methods
                paymentMethods.forEach(m => m.classList.remove('selected'));
                
                // Add selected class to clicked method
                this.classList.add('selected');
                
                // Check the radio button
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });

        // Form validation
        const form = document.getElementById('paymentForm');
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const errors = [];

            // Check payment method
            const paymentMethod = form.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                isValid = false;
                errors.push('Silakan pilih metode pembayaran');
            }

            // Check reference number
            const referenceNumber = form.querySelector('#reference_number').value.trim();
            if (!referenceNumber) {
                isValid = false;
                errors.push('Silakan masukkan nomor referensi transfer');
            }

            if (!isValid) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    });
    </script>
</body>
</html>