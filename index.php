<?php
require 'vendor/autoload.php'; // Jika menggunakan library tambahan

// Fungsi untuk request ke Gemini API
function callGeminiAPI($prompt, $apiKey, $imageBase64 = null, $mimeType = null) {
    if (empty($apiKey)) return ['error' => 'API Key tidak boleh kosong'];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
    
    $parts = [['text' => $prompt]];
    
    // Jika ada gambar, tambahkan ke payload
    if ($imageBase64) {
        $parts[] = [
            'inline_data' => [
                'mime_type' => $mimeType,
                'data' => $imageBase64
            ]
        ];
    }

    $body = [
        'contents' => [
            ['parts' => $parts]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'response' => $response];
}

// Logika Halaman
$resultText = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $apiKey = getenv('GEMINI_API_KEY'); // API Key diambil dari Environment Variable (Aman)
    $bahan = $_POST['bahan'];
    $prompt = "Buatkan resep masakan lengkap dari bahan ini: " . $bahan;

    // Handle Image Upload
    $base64Image = null;
    $mimeType = null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $data = file_get_contents($_FILES['image']['tmp_name']);
        $base64Image = base64_encode($data);
        $mimeType = $_FILES['image']['type'];
        $prompt = "Identifikasi bahan dalam gambar ini dan buatkan resep masakannya.";
    }

    $apiResult = callGeminiAPI($prompt, $apiKey, $base64Image, $mimeType);
    
    if ($apiResult['code'] == 200) {
        $json = json_decode($apiResult['response'], true);
        $resultText = $json['candidates'][0]['content']['parts'][0]['text'] ?? "Gagal parsing respon.";
    } else {
        $resultText = "Error: " . $apiResult['code'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>✨ Resep AI Pinterest-Style</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <div class="main-wrapper">
        <nav class="navbar">
            <a href="index.php" class="navbar-brand">✨ Resepku</a>
            
            <div class="navbar-menu">
                <a href="index.php">🏠 Beranda</a>
                <a href="about.php">💖 Tentang Kami</a>
                <a href="faq.php">💡 Bantuan/FAQ</a>
            </div>
        </nav>
        
        <h1 class="page-title">Inspirasi Resep Harian Anda</h1>


        <div class="pinterest-grid">
            <div class="grid-item card form-card">
                <h2 class="card-title">Cari Resep Baru</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label for="bahan">🍳 Masukkan Bahan Utama:</label>
                        <textarea name="bahan" id="bahan" placeholder="Contoh: Ayam, Nasi, Telur..."><?php echo htmlspecialchars($bahan ?? ''); ?></textarea>
                    </div>
                    
                    <div class="input-group">
                        <label for="image">📸 Atau Unggah Foto:</label>
                        <input type="file" name="image" id="image" accept="image/*">
                    </div>

                    <button type="submit">
                        <span>🪄</span> Temukan Resep
                    </button>
                </form>
            </div>
            
            <div class="grid-item card result-card">
                <h2 class="card-title">Hasil Resep Kreatif</h2>
                <pre class="recipe-output"><?php echo htmlspecialchars($resultText ?: "Resep akan muncul di sini setelah Anda mencari."); ?></pre>
            </div>
        </div>
    </div> </body>
</html>