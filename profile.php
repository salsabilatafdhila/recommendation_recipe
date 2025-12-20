<?php
// Data Simulasi (Bisa dihubungkan ke Database nantinya)
$userData = [
    'nama'   => 'Chef User',
    'status' => 'Member Gold',
    'bio'    => 'Pecinta kuliner nusantara yang senang bereksperimen dengan AI.',
    'foto'   => 'https://ui-avatars.com/api/?name=Chef+User&background=ff4d6d&color=fff&size=128'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profil Saya | ✨ Resep AI</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        /* CSS Tambahan khusus halaman profil tanpa merusak style.css utama */
        .profile-wrapper {
            max-width: 800px;
            margin: 40px auto;
            text-align: center;
        }
        
        .profile-header-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }

        .profile-pic {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 5px solid #fff0f3;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .stats-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        .stat-box b { font-size: 1.2rem; color: #ff4d6d; display: block; }
        .stat-box span { font-size: 0.85rem; color: #888; }

        /* Style untuk menu aktif agar user tahu mereka sedang di halaman profil */
        .nav-active {
            color: #ff4d6d !important;
            font-weight: bold;
            border-bottom: 2px solid #ff4d6d;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <nav class="navbar">
            <a href="index.php" class="navbar-brand">✨ Resepku</a>
            
            <div class="navbar-menu">
                <a href="index.php">🏠 Beranda</a>
                <a href="about.php">💖 Tentang Kami</a>
                <a href="faq.php">💡 Bantuan/FAQ</a>
                <a href="profile.php">👤 Profil</a>
            </div>
        </nav>

        <div class="profile-wrapper">
            <div class="profile-header-card">
                <img src="<?php echo $userData['foto']; ?>" alt="Profile Picture" class="profile-pic">
                
                <h1 style="margin: 0; color: #333;"><?php echo $userData['nama']; ?></h1>
                <p style="color: #ff4d6d; font-weight: 500; margin: 5px 0;"><?php echo $userData['status']; ?></p>
                
                <p style="max-width: 500px; margin: 20px auto; color: #666; line-height: 1.6;">
                    <?php echo $userData['bio']; ?>
                </p>

                <div class="stats-container">
                    <div class="stat-box">
                        <b>12</b>
                        <span>Resep Tersimpan</span>
                    </div>
                    <div class="stat-box">
                        <b>48</b>
                        <span>Riwayat AI</span>
                    </div>
                    <div class="stat-box">
                        <b>5</b>
                        <span>Koleksi</span>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <button onclick="alert('Fitur Edit Profil segera hadir!')" style="background: #eee; border: none; padding: 10px 25px; border-radius: 20px; cursor: pointer; font-weight: 600; color: #555;">
                        ⚙️ Pengaturan Akun
                    </button>
                </div>
            </div>

            <div style="margin-top: 40px; text-align: left;">
                <h2 style="font-size: 1.2rem; margin-bottom: 20px;">🕒 Riwayat Resep Terakhir</h2>
                <div class="pinterest-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                    <div class="grid-item card" style="padding: 15px; font-size: 0.9rem;">
                        <b>🍗 Ayam Bakar Madu</b>
                        <p style="font-size: 0.75rem; color: #aaa;">Dibuat: 12 Des 2025</p>
                    </div>
                    <div class="grid-item card" style="padding: 15px; font-size: 0.9rem;">
                        <b>🥗 Salad Buah Spesial</b>
                        <p style="font-size: 0.75rem; color: #aaa;">Dibuat: 10 Des 2025</p>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</body>
</html>