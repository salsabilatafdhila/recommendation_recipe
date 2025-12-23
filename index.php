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
                
        <?php if ($error): ?>
            <div class="error-message">
                🚨 *Perhatian:* <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

<div class="main-wrapper">
    <h1 class="page-title">Inspirasi Resep Harian Anda</h1>

    <div class="recipe-container">
        <div class="card form-card">
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
        
<?php if (!empty($resultText) && !in_array($resultText, ["Mohon isi bahan atau upload gambar.", "Resep akan muncul di sini setelah Anda mencari."])): ?>
    <div class="card result-card show-animation">
        <h2 class="card-title">Hasil Resep Kreatif</h2>
        
    <div class="recipe-output-rich">
        <?php 
        // Bersihkan format Markdown dasar
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $resultText);
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
        
        $lines = explode("\n", $text);
        $inAccordion = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line == '---' || $line == '.') continue;

            // 1. JUDUL UTAMA (##) -> Rata Kiri & Pink
            if (strpos($line, '##') === 0) {
                if ($inAccordion) { echo "</div></details>"; $inAccordion = false; }
                $displayTitle = trim(str_replace('##', '', $line));
                echo "<h3 class='recipe-main-title'>" . $displayTitle . "</h3>";
                continue;
            }

            // 2. DETEKSI HEADER DROPDOWN (Penting: Mendeteksi ### atau I. II. III.)
            // Mencari baris yang diawali ### atau angka Romawi (I., II., III., IV.)
            if (strpos($line, '###') === 0 || preg_match('/^(I|II|III|IV|V)\./i', strip_tags($line))) {
                
                if ($inAccordion) echo "</div></details>";
                
                // Bersihkan teks dari simbol pagar untuk judul
                $sectionTitle = trim(str_replace('###', '', $line));
                
                echo "<details class='recipe-accordion' open>
                        <summary>" . $sectionTitle . " <span class='arrow-icon'>▲</span></summary>
                        <div class='accordion-content'>";
                $inAccordion = true;
                continue;
            }

            // 3. ISI LIST (Poin) -> Kotak Kuning Kecil
            if (strpos($line, '*') === 0 || strpos($line, '-') === 0 || preg_match('/^\d+\./', $line) || strpos($line, '•') === 0) {
                $cleanItem = ltrim($line, '*-•0123456789. ');
                echo "<div class='recipe-list-item'><span class='yellow-box'></span> " . $cleanItem . "</div>";
            } 
            // 4. TEKS BIASA
            else {
                echo "<p class='recipe-text'>" . $line . "</p>";
            }
        }

        if ($inAccordion) echo "</div></details>";
        ?>
    </div>

    <div class="share-section">
            <form action="share.php" method="POST">
                <input type="hidden" name="resep" value="<?php echo htmlspecialchars($resultText); ?>">
                <button type="submit" class="btn-share-trigger">📤 Bagikan Resep</button>
            </form>
        </div>
    </div>
<?php endif; ?>