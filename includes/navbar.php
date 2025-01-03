<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        nav {
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: #666;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #333;
        }

        .profile-btn {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .profile-btn:hover {
            background-color: #f5f5f5;
        }

        .profile-btn img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-popup {
            display: none;
            position: absolute;
            top: 70px;
            right: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            width: 300px;
            padding: 1rem;
            z-index: 1001;
        }

        .profile-popup.active {
            display: block;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .profile-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-info h3 {
            color: #333;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .profile-info p {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .profile-menu {
            margin-top: 1rem;
        }

        .profile-menu button {
            width: 100%;
            padding: 0.75rem 1rem;
            text-align: left;
            background: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            color: #333;
            font-size: 0.875rem;
        }

        .profile-menu button:hover {
            background-color: #f5f5f5;
        }

        .profile-menu button.logout {
            color: #dc3545;
        }

        .hamburger {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }

        .hamburger div {
            width: 25px;
            height: 3px;
            background-color: #333;
            margin: 5px 0;
            transition: 0.3s;
        }

        @media (max-width: 768px) {
            .hamburger {
                display: block;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 70px;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .nav-links.active {
                display: flex;
            }

            .profile-popup {
                right: 1rem;
                width: calc(100% - 2rem);
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-container">
            <div class="logo">Logo</div>
            
            <ul class="nav-links">
                <li><a href="#beranda">Beranda</a></li>
                <li><a href="#produk">Produk</a></li>
                <li><a href="#layanan">Layanan</a></li>
                <li><a href="#kontak">Kontak</a></li>
            </ul>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="profile-btn">
                    <img src="<?php echo isset($_SESSION['profile_photo']) ? htmlspecialchars($_SESSION['profile_photo']) : '/api/placeholder/32/32'; ?>" alt="Profile">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </button>

                <button class="hamburger">
                    <div></div>
                    <div></div>
                    <div></div>
                </button>
            </div>
        </div>

        <div class="profile-popup">
            <div class="profile-header">
                <img src="<?php echo isset($_SESSION['profile_photo']) ? htmlspecialchars($_SESSION['profile_photo']) : '/api/placeholder/60/60'; ?>" alt="Profile">
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></h3>
                    <p><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    <p><?php echo htmlspecialchars($_SESSION['nomor_telp']); ?></p>
                </div>
            </div>
            <div class="profile-menu">
                <button onclick="window.location.href='edit_photo.php'">Ubah Profile Photo</button>
                <button class="logout" onclick="window.location.href='logout.php'">Keluar</button>
            </div>
        </div>
    </nav>

    <script>
        // Toggle Profile Popup
        const profileBtn = document.querySelector('.profile-btn');
        const profilePopup = document.querySelector('.profile-popup');

        profileBtn.addEventListener('click', () => {
            profilePopup.classList.toggle('active');
        });

        // Close popup when clicking outside
        document.addEventListener('click', (e) => {
            if (!profileBtn.contains(e.target) && !profilePopup.contains(e.target)) {
                profilePopup.classList.remove('active');
            }
        });

        // Toggle Mobile Menu
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');

        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    </script>
</body>
</html>