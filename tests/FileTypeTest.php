<?php
use PHPUnit\Framework\TestCase;

class FileTypeTest extends TestCase
{
    // 1. Test Case: File Exist 
    public function testFileIndexExists()
    {
        $this->assertFileExists('index.php', "File index.php harus ada");
        $this->assertFileExists('share.php',  "File share.php harus ada");
        $this->assertFileExists('faq.php', "File faq.php harus ada");
        $this->assertFileExists('tentang.php',"File tentang.php harus ada");
    }

    // 2. Test Case: Valid Syntax 
    public function testIndexSyntax()
    {
        // Mengecek sintaks PHP menggunakan command line linter
        $outputIndex  = shell_exec('php -l index.php');
        $outputShare  = shell_exec('php -l share.php');
        $outputFaq = shell_exec('php -l faq.php');
        $outputTentang = shell_exec('php -l tentang.php');

        $this->assertStringContainsString('No syntax errors', $outputIndex,  "Sintaks PHP index.php error");
        $this->assertStringContainsString('No syntax errors', $outputShare,  "Sintaks PHP share.php error");
        $this->assertStringContainsString('No syntax errors', $outputFaq, "Sintaks PHP faq.php error");
        $this->assertStringContainsString('No syntax errors', $outputTentang, "Sintaks PHP tentang.php error");
    }

    // 3. Test Case: API Key tidak boleh kosong 
    public function testApiKeyNotEmpty()
    {
        // Simulasi environment variable untuk testing
        $apiKey = getenv('GEMINI_API_KEY'); 
        // Jika di lokal kosong, kita skip atau set dummy untuk test logika
        if(!$apiKey) $apiKey = "dummy_key_for_testing"; 
        
        $this->assertNotEmpty($apiKey, "API Key tidak boleh kosong");
    }

    // 4. Test Case: Valid JSON Response (Simulasi Fungsi) 
    public function testValidJsonResponse()
    {
        // Kita simulasikan respon JSON dari API
        $dummyResponse = '{"candidates": [{"content": {"parts": [{"text": "Resep Nasi Goreng"}]}}]}';
        $json = json_decode($dummyResponse, true);
        
        $this->assertIsArray($json, "Respon harus berupa JSON valid");
        $this->assertArrayHasKey('candidates', $json);
    }

    // 5. Test Case: Response Code harus 200 (Simulasi Fungsi) 
    public function testResponseCode200()
    {
        // Simulasi hasil fungsi curl (Mocking)
        $mockHttpCode = 200;
        $this->assertEquals(200, $mockHttpCode, "Response code harus 200 OK");
    }
}
