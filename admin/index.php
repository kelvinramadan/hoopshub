<?php
require_once '../config.php';

// Fetch total bookings and revenue
$booking_stats = $conn->query("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(total_harga) as total_revenue
    FROM bookings
")->fetch_assoc();

// Fetch total courts by category
$court_stats = $conn->query("
    SELECT 
        kategori,
        COUNT(*) as total
    FROM lapangan
    GROUP BY kategori
")->fetch_all(MYSQLI_ASSOC);

// Fetch total news
$news_count = $conn->query("SELECT COUNT(*) as total FROM news")->fetch_assoc();

// Fetch recent bookings
$recent_bookings = $conn->query("
    SELECT b.*, u.username as nama_user, l.nama_lapangan 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN lapangan l ON b.lapangan_id = l.id 
    ORDER BY b.tanggal DESC, b.jam_mulai DESC 
    LIMIT 5
");

// Fetch recent news
$recent_news = $conn->query("
    SELECT * FROM news 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Fetch monthly revenue data for chart
$monthly_revenue = $conn->query("
    SELECT 
        DATE_FORMAT(tanggal, '%Y-%m') as month,
        SUM(total_harga) as revenue
    FROM bookings
    WHERE tanggal >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

include 'navbar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f0f2f5;
            padding-top: 20px;
        }
        .dashboard-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 10px;
            margin-bottom: 15px;
            transition: transform 0.2s;
            min-height: 100px;
        }

        .stat-card h3.fs-5 {
            font-size: 14px !important;
            margin: 0;
            color: #6c757d;
        }

        .stat-card h2.fs-3 {
            font-size: 20px !important;
            margin: 5px 0 0 0;
            font-weight: 600;
        }

        .stat-icon {
            width: 30px;
            height: 30px;
            background: #e3f2fd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .stat-icon i {
            font-size: 14px;
        }

        /* Adjust container padding */
        .container {
            padding-top: 10px;
        }

        /* Adjust row spacing */
        .row {
            margin-bottom: 10px;
        }

        /* Make columns tighter */
        .col-md-3 {
            padding-right: 8px;
            padding-left: 8px;
        }

        /* Dashboard title adjustment */
        h2.mb-4 {
            font-size: 20px;
            margin-bottom: 15px !important;
        }

        .recent-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .recent-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Dashboard Admin</h2>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-calendar"></i>
                    </div>
                    <h3 class="fs-5">Total Booking</h3>
                    <h2 class="fs-3"><?php echo number_format($booking_stats['total_bookings']); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="bi bi-house"></i>
                    </div>
                    <h3 class="fs-5">Total Lapangan</h3>
                    <h2 class="fs-3"><?php 
                        $total_courts = array_sum(array_column($court_stats, 'total'));
                        echo number_format($total_courts); 
                    ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="bi bi-newspaper"></i>
                    </div>
                    <h3 class="fs-5">Total Berita</h3>
                    <h2 class="fs-3"><?php echo number_format($news_count['total']); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="bi bi-cash"></i>
                    </div>
                    <h3 class="fs-5">Total Pendapatan</h3>
                    <h2 class="fs-3">Rp <?php echo number_format($booking_stats['total_revenue'], 0, ',', '.'); ?></h2>
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h4>Grafik Pendapatan 6 Bulan Terakhir</h4>
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <!-- Recent Bookings -->
            <div class="col-md-6">
                <div class="dashboard-card">
                    <h4>Booking Terbaru</h4>
                    <?php while($booking = $recent_bookings->fetch_assoc()): ?>
                        <div class="recent-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?php echo htmlspecialchars($booking['nama_user']); ?></strong>
                                    <div class="text-muted"><?php echo htmlspecialchars($booking['nama_lapangan']); ?></div>
                                </div>
                                <div class="text-end">
                                    <div>Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></div>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($booking['tanggal'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <div class="mt-3">
                        <a href="booking_admin.php" class="btn btn-sm btn-primary">Lihat Semua Booking</a>
                    </div>
                </div>
            </div>

            <!-- Recent News -->
            <div class="col-md-6">
                <div class="dashboard-card">
                    <h4>Berita Terbaru</h4>
                    <?php while($news = $recent_news->fetch_assoc()): ?>
                        <div class="recent-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><?php echo htmlspecialchars($news['title']); ?></div>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y', strtotime($news['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <div class="mt-3">
                        <a href="add_news.php" class="btn btn-sm btn-primary">Kelola Berita</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Initialize Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($monthly_revenue, 'month')); ?>,
            datasets: [{
                label: 'Pendapatan Bulanan',
                data: <?php echo json_encode(array_column($monthly_revenue, 'revenue')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.raw.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>