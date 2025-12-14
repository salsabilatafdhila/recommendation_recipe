<?php
require 'vendor/autoload.php';

// 1. Load library Dotenv (Hanya jika file .env ada/Lokal)
// Ini agar di GitHub Action tidak error karena tidak ada file .env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// 2. Fungsi request Gemini (Tidak perlu diubah, tetap sama)
function callGeminiAPI($prompt, $apiKey, $imageBase64 = null, $mimeType = null) {
    if (empty($apiKey)) {
        return [
            'code' => 401, 
            'response' => json_encode(['error' => 'API Key Kosong. Pastikan file .env sudah dibuat atau Secrets disetting.'])
        ];
    }
    
    // ... (sisa kode fungsi callGeminiAPI sama persis seperti sebelumnya) ...
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=".$apiKey;
    
    $parts = [['text' => $prompt]];
    
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        return ['code' => 500, 'response' => json_encode(['error' => 'Koneksi Gagal: ' . $error_msg])];
    }

    curl_close($ch);

    return ['code' => $httpCode, 'response' => $response];
}

// 3. Logika Halaman
$resultText = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // CARA AMBIL KEY YANG AMAN:
    // $_ENV diambil dari file .env (lokal) atau Server Environment (GitHub Actions)
    $apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');
    
    $bahan = $_POST['bahan'] ?? '';
    $prompt = "Buatkan resep masakan lengkap dari bahan ini: " . $bahan;

    // ... (Logika upload gambar & pemanggilan API ke bawah sama persis) ...
    $base64Image = null;
    $mimeType = null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $data = file_get_contents($_FILES['image']['tmp_name']);
        $base64Image = base64_encode($data);
        $mimeType = $_FILES['image']['type'];
        $prompt = "Identifikasi bahan dalam gambar ini dan buatkan resep masakannya.";
    }

    if(!empty($bahan) || !empty($base64Image)){
        $apiResult = callGeminiAPI($prompt, $apiKey, $base64Image, $mimeType);
        
        if ($apiResult['code'] == 200) {
            $json = json_decode($apiResult['response'], true);
            $resultText = $json['candidates'][0]['content']['parts'][0]['text'] ?? "Gagal parsing respon API.";
        } else {
            $resultText = "Error Code: " . $apiResult['code'] . "\nResponse: " . $apiResult['response'];
        }
    } else {
        $resultText = "Mohon isi bahan atau upload gambar.";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Gemini Recipe Generator</title></head>
<body>
    <h1>Generator Resep AI (Secure Mode)</h1>
    <form method="POST" enctype="multipart/form-data">
        <label>Masukkan Bahan:</label><br>
        <textarea name="bahan"></textarea><br><br>
        <label>Atau Upload Foto Bahan:</label><br>
        <input type="file" name="image"><br><br>
        <button type="submit">Generate Resep</button>
    </form>
    <hr>
    <h3>Hasil Resep:</h3>
    <pre><?php echo htmlspecialchars($resultText); ?></pre>
</body>
</html>