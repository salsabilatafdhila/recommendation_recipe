<?php
// share.php

// Mengambil data resep dari kiriman halaman sebelumnya
$resep = isset($_POST['resep']) ? $_POST['resep'] : (isset($_GET['resep']) ? $_GET['resep'] : '');

// Jika tidak ada resep, arahkan kembali ke beranda
if (empty($resep)) {
    header("Location: index.php");
    exit();
}

// Encode teks agar aman untuk URL (menangani spasi dan karakter khusus)
$shareText = "Halo! Lihat resep lezat dari *Resepku* ini:\n\n" . $resep;
$waUrl = "https://wa.me/?text=" . urlencode($shareText);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bagikan Resep ✨</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .share-container {
            max-width: 500px;
            margin: 50px auto;
            text-align: center;
            padding: 20px;
        }
        .preview-box {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            border: 1px dashed var(--pink-dark);
            margin-bottom: 20px;
            max-height: 200px;
            overflow-y: auto;
            text-align: left;
            font-size: 0.9em;
        }
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .share-btn {
            padding: 15px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            color: white;
            transition: 0.3s;
        }
        .wa-color { background-color: #25D366; }
        .ig-color { background-image: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); }
        .back-link { margin-top: 20px; display: block; color: var(--pink-dark); }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="share-container card">
            <h2 class="card-title">Bagikan Resep Anda</h2>
            
            <div class="preview-box">
                <strong>Pratinjau Resep:</strong><br>
                <?php echo nl2br(htmlspecialchars($resep)); ?>
            </div>

            <div class="btn-group">
                <a href="<?php echo $waUrl; ?>" target="_blank" class="share-btn wa-color">
                    🟢 Bagikan ke WhatsApp
                </a>

                <button onclick="copyAndGo()" class="share-btn ig-color" style="border:none; cursor:pointer; font-family:inherit;">
                    📸 Salin & Buka Instagram
                </button>
            </div>

            <a href="index.php" class="back-link">← Kembali ke Pencarian</a>
        </div>
    </div>

    <script>
    function copyAndGo() {
        const text = `<?php echo addslashes($resep); ?>`;
        navigator.clipboard.writeText(text).then(() => {
            alert("Resep berhasil disalin! Sekarang Anda bisa menempelkannya (paste) di Instagram Story atau DM.");
            window.location.href = "https://www.instagram.com/";
        });
    }
    </script>
</body>
</html>