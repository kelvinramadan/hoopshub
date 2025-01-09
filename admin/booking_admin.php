<!--admin/booking_admin.php-->
<?php
require_once '../config.php';

try {
    // Handle deletion
    if (isset($_POST['delete_booking'])) {
        $booking_id = (int)$_POST['booking_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First delete from payments table
            $delete_payments = "DELETE FROM payments WHERE booking_id = ?";
            $stmt_payments = $conn->prepare($delete_payments);
            $stmt_payments->bind_param("i", $booking_id);
            $stmt_payments->execute();
            
            // Then delete from bookings table
            $delete_bookings = "DELETE FROM bookings WHERE id = ?";
            $stmt_bookings = $conn->prepare($delete_bookings);
            $stmt_bookings->bind_param("i", $booking_id);
            $stmt_bookings->execute();
            
            // If everything is fine, commit the transaction
            $conn->commit();
            $success = "Booking berhasil dihapus.";
            
        } catch (Exception $e) {
            // If there is an error, rollback the changes
            $conn->rollback();
            $error = "Gagal menghapus booking: " . $e->getMessage();
        }
    }

    // Fetch all bookings with user and court details
    $bookings_query = "SELECT b.*, u.username as nama_user, l.nama_lapangan 
                       FROM bookings b 
                       JOIN users u ON b.user_id = u.id 
                       JOIN lapangan l ON b.lapangan_id = l.id 
                       ORDER BY b.tanggal DESC, b.jam_mulai DESC";
    $bookings_result = $conn->query($bookings_query);
    
    if (!$bookings_result) {
        throw new Exception("Error fetching bookings: " . $conn->error);
    }

} catch(Exception $e) {
    $error = $e->getMessage();
}

include 'navbar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Kelola Booking</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            padding-top: 20px;
            background: #f0f2f5;
        }
        .admin-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .action-buttons {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="admin-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Kelola Booking</h2>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="bookingsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Pemesan</th>
                                    <th>Lapangan</th>
                                    <th>Tanggal</th>
                                    <th>Jam Mulai</th>
                                    <th>Jam Selesai</th>
                                    <th>Durasi</th>
                                    <th>Total Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (isset($bookings_result) && $bookings_result->num_rows > 0):
                                    while ($booking = $bookings_result->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo $booking['id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['nama_user']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['nama_lapangan']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($booking['tanggal'])); ?></td>
                                        <td><?php echo $booking['jam_mulai']; ?></td>
                                        <td><?php echo $booking['jam_selesai']; ?></td>
                                        <td><?php echo $booking['durasi']; ?> jam</td>
                                        <td>Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></td>
                                        <td class="action-buttons">
                                            <form method="POST" action="" class="d-inline" 
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus booking ini?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" name="delete_booking" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/jquery.dataTables.min.js"></script>
    <script src="../assets/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#bookingsTable').DataTable({
                order: [[3, 'desc'], [4, 'desc']], // Sort by date and time
                language: {
                    url: '../assets/js/dataTables.indonesian.json'
                }
            });
        });
    </script>
</body>
</html>