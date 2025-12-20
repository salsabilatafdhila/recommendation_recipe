<?php
require 'vendor/autoload.php';

// --- 1. SETUP LINGKUNGAN (.ENV) ---
// Inisialisasi variabel untuk mencegah Warnings PHP di awal
$resultText = "";
$bahan = ""; 
$error = null; 
$apiKey = null; // Default value untuk API Key

// Load library Dotenv (Hanya jika file .env ada/Lokal)
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

// Ambil API Key dari ENV. Ini harus dilakukan SETELAH Dotenv dimuat.
$apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');

// 2. Fungsi request Gemini 
function callGeminiAPI($prompt, $apiKey, $imageBase64 = null, $mimeType = null) {
    if (empty($apiKey)) {
        // Jika API Key kosong, langsung kembalikan error.
        return [
            'code' => 401, 
            'response' => json_encode(['error' => ['message' => 'API Key Kosong. Harap periksa file .env Anda.']])
        ];
    }
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

    if (empty($apiKey)) {
    $error = "DIAGNOSIS KRITIS: API Key GAGAL dimuat dari .env atau lingkungan. Pastikan nama variabel GEMINI_API_KEY benar.";
} else {
    // Jika API Key terbaca, tampilkan 5 karakter pertama (jangan tampilkan seluruh kunci)
    $error = "DIAGNOSIS KRITIS: API Key berhasil dimuat (Dimulai dengan: " . substr($apiKey, 0, 5) . "). Lanjutkan mencari.";
}

    
    $parts = [['text' => $prompt]];
    
    if ($imageBase64) {
        $image_part = [
            'inline_data' => [
                'mime_type' => $mimeType,
                'data' => $imageBase64
            ]
        ];
        array_unshift($parts, $image_part);
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['code' => 500, 'response' => json_encode(['error' => ['message' => 'Curl Koneksi Gagal: ' . $error_msg]])];
    }

    curl_close($ch);

    return ['code' => $httpCode, 'response' => $response];
}

// 3. Logika Halaman (POST Handler)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $bahan = $_POST['bahan'] ?? '';
    $prompt = "Buatkan resep masakan lengkap dari bahan ini: " . $bahan;

    // Logika upload gambar
    $base64Image = null;
    $mimeType = null;
    if (isset($_FILES['image']['tmp_name']) && !empty($_FILES['image']['tmp_name'])) {
        $data = file_get_contents($_FILES['image']['tmp_name']);
        $base64Image = base64_encode($data);
        $mimeType = $_FILES['image']['type'];
        $prompt = "Identifikasi bahan dalam gambar ini dan buatkan resep masakannya.";
    }

    if(!empty($bahan) || !empty($base64Image)){
        
        // Cek API Key sebelum memanggil API (untuk pesan error 401 yang jelas)
        if (empty($apiKey)) {
            $error = "HTTP Error Code: 401";
            $resultText = "Gagal: API Key tidak ditemukan. Pastikan file .env ada dan terisi.";
        } else {
            $apiResult = callGeminiAPI($prompt, $apiKey, $base64Image, $mimeType);
            
            if ($apiResult['code'] == 200) {
                $json = json_decode($apiResult['response'], true);
                $resultText = $json['candidates'][0]['content']['parts'][0]['text'] ?? "Gagal parsing respon API.";
            } else {
                // Penanganan Error HTTP (404, 503, dll.)
                $error = "HTTP Error Code: " . $apiResult['code'];
                $response_json = json_decode($apiResult['response'] ?? '{}', true);

                if (isset($response_json['error']['message'])) {
                    // Pesan detail dari Google (misal: invalid API Key, Quota Exceeded, Model Overloaded)
                    $resultText = "Gagal mendapatkan resep: " . $response_json['error']['message'];
                } else {
                    $resultText = "Respon API Error tidak dapat diuraikan. Kode: " . $apiResult['code'];
                }
            }
        }
    } else {
        $resultText = "Mohon isi bahan atau upload gambar.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>✨ Resep AI</title>
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
                <a href="profile.php">👤 Profil</a>
            </div>
        </nav>
        
        <h1 class="page-title">Inspirasi Resep Harian Anda</h1>
        
        <?php if ($error): ?>
            <div class="error-message">
                🚨 *Perhatian:* <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="pinterest-grid">
            <div class="grid-item card form-card">
                <h2 class="card-title">Cari Resep Baru</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label for="bahan">🍳 Masukkan Bahan Utama:</label>
                        <textarea name="bahan" id="bahan" placeholder="Contoh: Ayam, Nasi, Telur..."><?php echo htmlspecialchars($bahan); ?></textarea>
                    </div>
                    
                    <div class="input-group">
                        <label for="image">📸 Atau Unggah Foto Bahan:</label>
                        <input type="file" name="image" id="image" accept="image/*">
                    </div>

                    <button type="submit">
                        <span>🪄</span> Generate Resep
                    </button>
                </form>
            </div>
            
            <div class="grid-item card result-card">
    <h2 class="card-title">Hasil Resep Kreatif</h2>
    
    <pre class="recipe-output"><?php echo htmlspecialchars($resultText ?: "Resep akan muncul di sini setelah Anda mencari."); ?></pre>

    <?php if (!empty($resultText) && $resultText != "Mohon isi bahan atau upload gambar." && $resultText != "Resep akan muncul di sini setelah Anda mencari."): ?>
        <div class="share-section" style="margin-top: 20px; padding-top: 15px; border-top: 2px dashed var(--pink-light);">
            <form action="share.php" method="POST">
                <input type="hidden" name="resep" value="<?php echo htmlspecialchars($resultText); ?>">
                
                <button type="submit" class="btn-share-trigger">
                    📤 Bagikan ke WhatsApp / Instagram
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>