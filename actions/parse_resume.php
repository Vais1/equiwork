<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Seeker') {
    http_response_code(403);
    echo json_encode(['error' => 'You are not authorized to parse resumes.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

function sanitize_parsed_data($data) {
    if (is_array($data)) {
        return array_map('sanitize_parsed_data', $data);
    }

    $clean = strip_tags(trim((string)$data));
    return htmlspecialchars_decode($clean, ENT_QUOTES | ENT_HTML5);
}

try {
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid file uploaded or file upload error occurred.']);
        exit;
    }

    $file = $_FILES['resume'];
    $allowed_mimes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
        'image/jpeg',
        'image/png'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        throw new RuntimeException('Unable to initialize file inspector.');
    }

    $real_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($real_mime, $allowed_mimes, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Unsupported file format. Please upload a PDF, DOCX, JPG, or PNG file.']);
        exit;
    }

    if ((int)$file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File size exceeds the 5MB maximum limit.']);
        exit;
    }

    $parsedText = '';

    $vendor_autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if ($real_mime === 'application/pdf' && file_exists($vendor_autoload)) {
        require_once $vendor_autoload;
    }

    if ($real_mime === 'application/pdf' && class_exists('Smalot\PdfParser\Parser')) {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file['tmp_name']);
            $parsedText = $pdf->getText();
        } catch (Throwable $e) {
            $parsedText = '';
        }
    }

    if (trim($parsedText) === '') {
        $cfile = new CURLFile($file['tmp_name'], $real_mime, $file['name']);
        $post_fields = [
            'file' => $cfile,
            'apikey' => OCR_API_KEY,
            'language' => 'eng',
            'isOverlayRequired' => 'false',
            'OCREngine' => '2'
        ];

        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Unable to start OCR request.');
        }

        curl_setopt($ch, CURLOPT_URL, 'https://api.ocr.space/parse/image');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            http_response_code(500);
            echo json_encode(['error' => 'Document extraction engine timeout or failure.']);
            exit;
        }

        $ocr_data = json_decode($response, true);
        if (!is_array($ocr_data)) {
            throw new RuntimeException('Invalid OCR response format.');
        }

        if (($ocr_data['IsErroredOnProcessing'] ?? false) === true) {
            $ocr_error = $ocr_data['ErrorMessage'][0] ?? 'Corrupted or unreadable image layer.';
            http_response_code(500);
            echo json_encode(['error' => 'OCR parsing failure: ' . $ocr_error]);
            exit;
        }

        if (!empty($ocr_data['ParsedResults']) && is_array($ocr_data['ParsedResults'])) {
            foreach ($ocr_data['ParsedResults'] as $result) {
                if (isset($result['ParsedText'])) {
                    $parsedText .= $result['ParsedText'] . "\n";
                }
            }
        }
    }

    if (trim($parsedText) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'No readable text could be identified. Please ensure the document is clear and not heavily distorted.']);
        exit;
    }

    $extractedData = [
        'email' => '',
        'phone' => '',
        'education' => '',
        'work_experience' => '',
        'skills' => []
    ];

    $normalizedText = preg_replace("/\r\n|\r/", "\n", $parsedText);

    if (preg_match('/[a-zA-Z0-9.!#$%&\'*+\/= ?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+/', $normalizedText, $matches)) {
        $extractedData['email'] = $matches[0];
    }

    if (preg_match('/(?:\+?\d{1,3}[\s.-]?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}/', $normalizedText, $matches)) {
        $extractedData['phone'] = $matches[0];
    }

    $lines = explode("\n", $normalizedText);
    $currentSection = null;
    $sections = [
        'education' => [],
        'experience' => [],
        'skills_text' => []
    ];

    $header_regex = [
        'education' => '/^(?:education|academic background|qualifications)\s*:?$/i',
        'experience' => '/^(?:experience|work history|employment history|career|professional experience)\s*:?$/i',
        'skills' => '/^(?:skills|technical skills|core competencies|expertise)\s*:?$/i',
        'ignore' => '/^(?:projects|certifications|references|objective|summary|languages)\s*:?$/i'
    ];

    foreach ($lines as $line) {
        $cleanLine = trim($line);
        if ($cleanLine === '') {
            continue;
        }

        $lowerLine = strtolower($cleanLine);
        $isHeader = false;

        foreach ($header_regex as $sec => $regex) {
            if (preg_match($regex, $lowerLine)) {
                $currentSection = $sec;
                $isHeader = true;
                break;
            }
        }

        if ($isHeader) {
            continue;
        }

        if ($currentSection && $currentSection !== 'ignore') {
            if ($currentSection === 'skills') {
                $sections['skills_text'][] = $cleanLine;
            } else {
                $sections[$currentSection][] = $cleanLine;
            }
        }
    }

    $extractedData['education'] = !empty($sections['education']) ? implode("\n", $sections['education']) : '';
    $extractedData['work_experience'] = !empty($sections['experience']) ? implode("\n", $sections['experience']) : '';

    $common_skills = [
        'javascript','php','python','java','html','css','sql','react','node','node.js','aws','docker','git',
        'communication','leadership','management','agile','scrum','c++','c#','ruby','go','typescript',
        'vue','angular','laravel','symfony','django','flask','spring','accessibility','wcag','aria','tailwind',
        'bootstrap','mysql','postgresql','mongodb','redis','linux','bash','problem solving','project management'
    ];

    $skills_blob = implode(' ', $sections['skills_text']);
    if (trim($skills_blob) === '') {
        $skills_blob = $normalizedText;
    }

    $lower_blob = strtolower($skills_blob);
    $found_skills = [];

    foreach ($common_skills as $skill) {
        $pattern = '/\b' . preg_quote($skill, '/') . '\b/i';
        if (preg_match($pattern, $lower_blob)) {
            $found_skills[] = ucwords($skill);
        }
    }

    $extractedData['skills'] = !empty($found_skills) ? array_values(array_unique($found_skills)) : [];

    $sanitizedData = sanitize_parsed_data($extractedData);
    if (is_array($sanitizedData['skills'])) {
        $sanitizedData['skills'] = implode(', ', $sanitizedData['skills']);
    }

    echo json_encode([
        'success' => true,
        'data' => $sanitizedData
    ]);
    exit;
} catch (Throwable $e) {
    error_log('Resume parse endpoint failure: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Resume parsing failed unexpectedly. Please try another file.']);
    exit;
}

