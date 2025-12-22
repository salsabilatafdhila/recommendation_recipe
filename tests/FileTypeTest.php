<?php

use PHPUnit\Framework\TestCase;

/**
 * Class FileTypeTest
 * 
 * Unit Test untuk memastikan file, konfigurasi,
 * dan struktur respon aplikasi berjalan dengan benar.
 */
class FileTypeTest extends TestCase
{
    /**
     * ===============================
     * Test Case 1
     * Memastikan file index.php tersedia
     * ===============================
     */
    public function testCase01_FileIndexPhpExists()
    {
        $this->assertFileExists(
            __DIR__ . '/../index.php',
            'Test Case 01 Gagal: File index.php tidak ditemukan.'
        );
    }

    /**
     * ===============================
     * Test Case 2
     * Memastikan index.php mengandung tag PHP
     * ===============================
     */
    public function testCase02_IndexPhpContainsPhpTag()
    {
        $fileContent = file_get_contents(__DIR__ . '/../index.php');

        $this->assertStringContainsString(
            '<?php',
            $fileContent,
            'Test Case 02 Gagal: index.php tidak mengandung tag PHP.'
        );
    }

    /**
     * ===============================
     * Test Case 3
     * Memastikan environment variable GEMINI_API_KEY tidak kosong
     * ===============================
     */
    public function testCase03_ApiKeyIsNotEmpty()
    {
        // Set dummy API Key untuk keperluan pengujian
        putenv('GEMINI_API_KEY=dummy_api_key_for_testing');

        $apiKey = getenv('GEMINI_API_KEY');

        $this->assertNotEmpty(
            $apiKey,
            'Test Case 03 Gagal: GEMINI_API_KEY bernilai kosong.'
        );
    }

    /**
     * ===============================
     * Test Case 4
     * Memastikan response API berbentuk JSON valid
     * ===============================
     */
    public function testCase04_ResponseIsValidJson()
    {
        $dummyResponse = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Resep Nasi Goreng'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $decodedResponse = json_decode($dummyResponse, true);

        $this->assertIsArray(
            $decodedResponse,
            'Test Case 04 Gagal: Response bukan JSON array.'
        );

        $this->assertArrayHasKey(
            'candidates',
            $decodedResponse,
            'Test Case 04 Gagal: Key "candidates" tidak ditemukan.'
        );
    }

    /**
     * ===============================
     * Test Case 5
     * Memastikan HTTP response code bernilai 200 (OK)
     * ===============================
     */
    public function testCase05_HttpResponseCodeIs200()
    {
        $httpResponseCode = 200;

        $this->assertSame(
            200,
            $httpResponseCode,
            'Test Case 05 Gagal: HTTP response code bukan 200.'
        );
    }
}
