<?php
// actions/parse_resume.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

// Validate CSRF token for AJAX requests (expects HTTP_X_CSRF_TOKEN header)
if (!csrf_validate_request()) {
    csrf_fail_json();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// 1. Strict Server-Side Validation
if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid file uploaded or file upload error occurred.']);
    exit;
}

$file = $_FILES['resume'];

// Validate MIME type strictly
$allowed_mimes = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/msword',
    'image/jpeg',
    'image/png'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$real_mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($real_mime, $allowed_mimes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported file format. Please upload a PDF, DOCX, JPG, or PNG file.']);
    exit;
}

$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['error' => 'File size exceeds the 5MB maximum limit.']);
    exit;
}

// 2. Data Extraction Execution (Composer library with OCR fallback)
$parsedText = "";

// Attempt local Composer library parsing for PDF if available
$vendor_autoload = dirname(__DIR__) . '/vendor/autoload.php';
if ($real_mime === 'application/pdf' && file_exists($vendor_autoload)) {
    require_once $vendor_autoload;
    if (class_exists('Smalot\PdfParser\Parser')) {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($file['tmp_name']);
            $parsedText = $pdf->getText();
        } catch (\Exception $e) {
            // Silently fail to OCR fallback
            $parsedText = ""; 
        }
    }
}

// Fallback to OCR API if text is still empty (scanned PDF, DOCX, or Image)
if (trim($parsedText) === "") {
    $cfile = new CURLFile($file['tmp_name'], $real_mime, $file['name']);
    $post_fields = [
        'file' => $cfile,
        'apikey' => 'helloworld', // Replace with production OCR.space API key
        'language' => 'eng',
        'isOverlayRequired' => 'false',
        'OCREngine' => '2'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.ocr.space/parse/image");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Set realistic timeout to prevent hanging connections
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        http_response_code(500);
        echo json_encode(['error' => 'Document extraction engine timeout or failure.']);
        exit;
    }

    $data = json_decode($response, true);
    if (isset($data['IsErroredOnProcessing']) && $data['IsErroredOnProcessing']) {
        http_response_code(500);
        echo json_encode(['error' => 'OCR parsing failure: ' . ($data['ErrorMessage'][0] ?? 'Corrupted or unreadable image layer.')]);
        exit;
    }

    if (isset($data['ParsedResults']) && count($data['ParsedResults']) > 0) {
        foreach ($data['ParsedResults'] as $result) {
            if (isset($result['ParsedText'])) {
                $parsedText .= $result['ParsedText'] . "\n";
            }
        }
    }
}

// Final check if ANY text was retrieved
if (trim($parsedText) === "") {
    http_response_code(400);
    echo json_encode(['error' => 'No readable text could be identified. Please ensure the document is clear and not heavily distorted.']);
    exit;
}

// 3. Enterprise-level LLM Parsing with Gemini 2.5 Flash
$geminiApiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : getenv('GEMINI_API_KEY');

if (empty($geminiApiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'Gemini API key is not configured.']);
    exit;
}

// Normalize text for easier parsing
$normalizedText = preg_replace("/\r\n|\r/", "\n", $parsedText);

$prompt = 'You are an expert resume parser. Extract the following resume text into a strict JSON object with EXACTLY this structure:
{
  "first_name": "string or empty",
  "last_name": "string or empty",
  "email": "string or empty",
  "phone": "string or empty",
  "skills": ["array of exact string skills found"],
  "education": [
    {
      "institution": "string or empty",
      "degree": "string or empty",
      "dates": "string or empty",
      "details": "string or empty"
    }
  ],
  "work_experience": [
    {
      "job_title": "string or empty",
      "company": "string or empty",
      "location": "string or empty",
      "dates": "string or empty",
      "description": "string or empty"
    }
  ]
}
Return ONLY the valid JSON, no markdown code block formatting (like ```json) or extra text.

Resume Text:
"""
' . substr($normalizedText, 0, 15000) . '
"""';

$geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $geminiApiKey;

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.1,
        "responseMimeType" => "application/json"
    ]
];

$chGemini = curl_init();
curl_setopt($chGemini, CURLOPT_URL, $geminiUrl);
curl_setopt($chGemini, CURLOPT_POST, 1);
curl_setopt($chGemini, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($chGemini, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chGemini, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($chGemini, CURLOPT_TIMEOUT, 30);
curl_setopt($chGemini, CURLOPT_SSL_VERIFYPEER, false);

$geminiResponse = curl_exec($chGemini);
$geminiHttpCode = curl_getinfo($chGemini, CURLINFO_HTTP_CODE);
curl_close($chGemini);

if ($geminiHttpCode !== 200 || !$geminiResponse) {
    http_response_code(500);
    error_log("Gemini API Error: " . curl_error($chGemini) . " - Response: " . $geminiResponse);
    echo json_encode(['error' => 'AI parsing engine failed to respond. Please try again.']);
    exit;
}

$geminiData = json_decode($geminiResponse, true);
$aiResultJson = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
$extractedData = json_decode($aiResultJson, true);

if (!is_array($extractedData)) {
    $extractedData = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'skills' => [],
        'education' => [],
        'work_experience' => []
    ];
}

// 4. Robust Output Sanitization (XSS Prevention)
function sanitize_parsed_data($data) {
    if (is_array($data)) {
        return array_map('sanitize_parsed_data', $data);
    }
    // Remove HTML entity encoding issues for editable fields
    // json_encode safely transports strings, XSS should be prevented on final form submission
    $clean = strip_tags(trim($data));
    $clean = htmlspecialchars_decode($clean, ENT_QUOTES | ENT_HTML5);
    return $clean;
}

$sanitizedData = sanitize_parsed_data($extractedData);

// Format skills array back for frontend convenience
if (is_array($sanitizedData['skills'])) {
    $sanitizedData['skills'] = implode(", ", $sanitizedData['skills']);
}

// Return Secure JSON
echo json_encode([
    'success' => true,
    'data' => $sanitizedData
]);
exit;
