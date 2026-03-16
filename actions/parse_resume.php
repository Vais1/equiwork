<?php
// actions/parse_resume.php
header('Content-Type: application/json; charset=utf-8');

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

// 3. Advanced NLP & RegEx Classification
$extractedData = [
    'email' => '',
    'phone' => '',
    'education' => '',
    'work_experience' => '',
    'skills' => []
];

// Normalize text for easier parsing
$normalizedText = preg_replace("/\r\n|\r/", "\n", $parsedText);

// Extract Email - RFC 5322 compatible simplification
if (preg_match('/[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+/', $normalizedText, $matches)) {
    $extractedData['email'] = $matches[0];
}

// Extract Phone - Handles intl format, parentheses, common separators
if (preg_match('/(?:\+?\d{1,3}[\s.-]?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}/', $normalizedText, $matches)) {
    $extractedData['phone'] = $matches[0];
}

// Advanced NLP-like Section Block Chunking
$lines = explode("\n", $normalizedText);
$currentSection = null;
$sections = [
    'education' => [],
    'experience' => [],
    'skills_text' => []
];

$header_regex = [
    'education'  => '/^(?:education|academic background|qualifications)\s*:?$/i',
    'experience' => '/^(?:experience|work history|employment history|career|professional experience)\s*:?$/i',
    'skills'     => '/^(?:skills|technical skills|core competencies|expertise)\s*:?$/i',
    'ignore'     => '/^(?:projects|certifications|references|objective|summary|languages)\s*:?$/i'
];

foreach ($lines as $line) {
    $cleanLine = trim($line);
    $lowerLine = strtolower($cleanLine);
    
    if (empty($cleanLine)) continue;

    $isHeader = false;
    foreach ($header_regex as $sec => $regex) {
        if (preg_match($regex, $lowerLine)) {
            $currentSection = $sec;
            $isHeader = true;
            break;
        }
    }

    if ($isHeader) continue;
    
    if ($currentSection && $currentSection !== 'ignore') {
        if ($currentSection === 'skills') {
            $sections['skills_text'][] = $cleanLine;
        } else {
            $sections[$currentSection][] = $cleanLine;
        }
    }
}

// Format Extracted Arrays back to string representations
$extractedData['education'] = !empty($sections['education']) ? implode("\n", array_slice($sections['education'], 0, 5)) : 'No formal education identified.';
$extractedData['work_experience'] = !empty($sections['experience']) ? implode("\n", array_slice($sections['experience'], 0, 8)) : 'No explicit work history identified.';

// Intelligent Skills Extraction
$common_skills = [
    'javascript','php','python','java','html','css','sql','react','node','node.js','aws','docker','git',
    'communication','leadership','management','agile','scrum','c++','c#','ruby','go','typescript',
    'vue','angular','laravel','symfony','django','flask','spring','accessibility','wcag','aria','tailwind',
    'bootstrap','mysql','postgresql','mongodb','redis','linux','bash','problem solving','project management'
];

$found_skills = [];
// Look in specific skills section first
$skills_blob = implode(" ", $sections['skills_text']);

// If specific skills section was empty, parse the whole text
if (trim($skills_blob) === "") {
    $skills_blob = $normalizedText;
}

$lower_blob = strtolower($skills_blob);

// Exact word match boundaries to avoid substring false positives (e.g. 'go' in 'good')
foreach ($common_skills as $skill) {
    $pattern = '/\b' . preg_quote($skill, '/') . '\b/i';
    if (preg_match($pattern, $lower_blob)) {
        $found_skills[] = ucwords($skill);
    }
}

$extractedData['skills'] = !empty($found_skills) ? array_unique($found_skills) : ['No specific technical skills matched.'];

// 4. Robust Output Sanitization (XSS Prevention)
function sanitize_parsed_data($data) {
    if (is_array($data)) {
        return array_map('sanitize_parsed_data', $data);
    }
    // ENT_QUOTES | ENT_HTML5 secures single & double quotes. 
    return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
