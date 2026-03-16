<?php
// actions/parse_resume.php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Handle file upload
if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid file uploaded.']);
    exit;
}

$file = $_FILES['resume'];
$allowed_mimes = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/msword'
];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowed_mimes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file format. Please upload a PDF or DOCX file.']);
    exit;
}

if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['error' => 'File size exceeds the 5MB limit.']);
    exit;
}

// Prepare file for cURL request to OCR API
$cfile = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
$post_fields = [
    'file' => $cfile,
    'apikey' => 'helloworld',
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

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to parse document via parsing service.']);
    exit;
}

$data = json_decode($response, true);
if (isset($data['IsErroredOnProcessing']) && $data['IsErroredOnProcessing']) {
    http_response_code(500);
    echo json_encode(['error' => 'Document parsing error: ' . (isset($data['ErrorMessage'][0]) ? $data['ErrorMessage'][0] : 'Unknown error')]);
    exit;
}

$parsedText = "";
if (isset($data['ParsedResults']) && count($data['ParsedResults']) > 0) {
    foreach ($data['ParsedResults'] as $result) {
        if (isset($result['ParsedText'])) {
            $parsedText .= $result['ParsedText'] . "\n";
        }
    }
}

if (trim($parsedText) === "") {
    http_response_code(400);
    echo json_encode(['error' => 'No readable text could be extracted from the document. Please ensure it is not an image-only PDF without OCR, or try another file.']);
    exit;
}

// Extract information using Regex
$email = '';
if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $parsedText, $matches)) {
    $email = $matches[0];
}

$phone = '';
if (preg_match('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $parsedText, $matches)) {
    $phone = $matches[0];
}

// Simple logic for extracting Work History and Skills (just approximations)
$skills_list = ['javascript', 'php', 'python', 'java', 'html', 'css', 'sql', 'react', 'node', 'aws', 'docker', 'git', 'communication', 'leadership', 'management'];
$found_skills = [];
$lower_text = strtolower($parsedText);
foreach ($skills_list as $skill) {
    if (strpos($lower_text, $skill) !== false) {
        $found_skills[] = ucfirst($skill);
    }
}

// Extract work experience snippet
$work_history = 'No explicit work history section detected. Please verify your document.';
if (preg_match('/(?:experience|work history|employment|career)[\s\S]*?(?=(?:education|skills|certifications|projects|references|$))/i', $parsedText, $matches)) {
    // Take first 300 chars of experience section to avoid huge text
    $work_history = trim(substr($matches[0], 0, 300)) . '...';
}

echo json_encode([
    'success' => true,
    'data' => [
        'email' => $email ? $email : 'Not found',
        'phone' => $phone ? $phone : 'Not found',
        'skills' => count($found_skills) > 0 ? implode(', ', $found_skills) : 'No common skills detected',
        'work_history' => $work_history
    ]
]);
