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
<head><title>Gemini Recipe Generator</title></head>
<body>
    <h1>Generator Resep AI</h1>
    <form method="POST" enctype="multipart/form-data">
        <label>Masukkan Bahan:</label><br>
        <textarea name="bahan"></textarea><br>
        <label>Atau Upload Foto Bahan:</label><br>
        <input type="file" name="image"><br><br>
        <button type="submit">Generate Resep</button>
    </form>
    <hr>
    <h3>Hasil Resep:</h3>
    <pre><?php echo htmlspecialchars($resultText); ?></pre>
</body>
</html>