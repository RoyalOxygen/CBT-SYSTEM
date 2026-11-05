<?php
/**
 * CBT System - Fetch Student from CMS
 * Uses cURL to get student data from the CMS verification system
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST[CSRF_TOKEN_NAME] ?? '';
if (!validateCSRFToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$matricno = '';
if (is_array($input) && !empty($input['matricno'])) {
    $matricno = trim($input['matricno']);
} elseif (!empty($_POST['matricno'])) {
    $matricno = trim($_POST['matricno']);
}

if ($matricno === '') {
    echo json_encode(['success' => false, 'message' => 'Matric number is required']);
    exit;
}

$cmsHost = 'https://cms.tsuniversity.edu.ng';
$cmsSearchUrl = $cmsHost . '/verify/search.php';
$cmsLookupUrl = $cmsHost . '/verify/p-search.php';
$cookieJar = sys_get_temp_dir() . '/cbt_cms_cookies_' . preg_replace('/[^a-zA-Z0-9_-]/', '', session_id()) . '.txt';
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

try {
    $primeCh = curl_init();
    curl_setopt_array($primeCh, [
        CURLOPT_URL => $cmsSearchUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_ENCODING => '',
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
        ],
    ]);
    curl_exec($primeCh);
    $primeError = curl_error($primeCh);
    curl_close($primeCh);

    if ($primeError) {
        echo json_encode(['success' => false, 'message' => 'Could not reach the CMS: ' . $primeError]);
        exit;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $cmsLookupUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['matricno' => $matricno]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: ' . $cmsSearchUrl,
            'Origin: ' . $cmsHost,
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'X-Requested-With: XMLHttpRequest',
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (is_file($cookieJar)) {
        @unlink($cookieJar);
    }

    if ($curlError) {
        error_log("CMS cURL error for matricno=$matricno: $curlError");
        echo json_encode(['success' => false, 'message' => 'Could not reach the CMS: ' . $curlError]);
        exit;
    }

    if ($httpCode !== 200) {
        error_log("CMS returned HTTP $httpCode for matricno=$matricno");
        echo json_encode(['success' => false, 'message' => 'CMS returned HTTP ' . $httpCode]);
        exit;
    }

    if ($response === false || $response === '') {
        echo json_encode(['success' => false, 'message' => 'Empty response from CMS']);
        exit;
    }

    $trimmed = trim($response);
    if ($trimmed === '0') {
        echo json_encode(['success' => false, 'message' => 'This card has not been assigned']);
        exit;
    }
    if (strcasecmp($trimmed, 'error') === 0) {
        echo json_encode(['success' => false, 'message' => 'Matric number or JAMB number not found in CMS']);
        exit;
    }

    $studentData = parseCMSResponse($response, $matricno);

    if (!$studentData) {
        error_log("CMS parse failed for matricno=$matricno. Raw response (first 2000 chars): " . substr($response, 0, 2000));
        echo json_encode(['success' => false, 'message' => 'Student not found in CMS, or the CMS page format has changed.']);
        exit;
    }

    $db = getDB();
    $checkStmt = $db->prepare("SELECT id FROM students WHERE matric_number = ?");
    $checkStmt->execute([$studentData['matric_number']]);
    $studentData['exists'] = $checkStmt->fetch() ? true : false;

    $studentData['department_id'] = findDepartmentId($db, $studentData['department']);
    $studentData['level_id'] = findLevelId($db, $studentData['level']);
    $studentData['semester_id'] = getActiveSemesterId($db);
    $studentData['session_id'] = getActiveSessionId($db);

    echo json_encode([
        'success' => true,
        'student' => $studentData
    ]);

} catch (Exception $e) {
    error_log("CMS fetch error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Parse the CMS HTML fragment returned by p-search.php
 */
function parseCMSResponse($html, $matricno) {
    $studentData = [
        'matric_number' => $matricno,
        'first_name' => '',
        'last_name' => '',
        'other_name' => '',
        'email' => '',
        'photo' => '',
        'photo_data' => '',
        'department' => '',
        'programme' => '',
        'level' => '',
        'session' => '',
        'address' => '',
        'exists' => false
    ];

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
    $loaded = $doc->loadHTML($wrapped);
    libxml_clear_errors();

    if ($loaded) {
        $xpath = new DOMXPath($doc);

        $nameNode = $xpath->query("//div[contains(@class,'mt-3')]/h4");
        if ($nameNode->length === 0) {
            $nameNode = $xpath->query("//h4");
        }
        if ($nameNode->length > 0) {
            applySplitName($studentData, trim($nameNode->item(0)->textContent));
        }

        $h5Nodes = $xpath->query("//div[contains(@class,'mt-3')]/h5");
        if ($h5Nodes->length === 0) {
            $h5Nodes = $xpath->query("//h5[not(@class)]");
        }
        if ($h5Nodes->length > 0) {
            $matricText = trim($h5Nodes->item(0)->textContent);
            if ($matricText !== '') {
                $studentData['matric_number'] = $matricText;
            }
            if ($h5Nodes->length > 1) {
                $levelText = trim($h5Nodes->item(1)->textContent);
                if (preg_match('/\d+/', $levelText, $levelMatch)) {
                    $studentData['level'] = $levelMatch[0];
                }
            }
        }

        $deptNode = $xpath->query("//p[contains(@class,'text-secondary')]");
        if ($deptNode->length > 0) {
            $deptText = trim($deptNode->item(0)->textContent);
            $studentData['programme'] = $deptText;
            $studentData['department'] = cleanDepartmentName($deptText);
        }

        $addressNode = $xpath->query("//p[contains(@class,'text-muted')]");
        if ($addressNode->length > 0) {
            $studentData['address'] = trim($addressNode->item(0)->textContent);
        }

        $imgNode = $xpath->query("//img[contains(@src,'applicants/') or contains(@src,'passport') or contains(@class,'rounded-circle')]");
        if ($imgNode->length > 0) {
            $studentData['photo'] = resolveCmsUrl($imgNode->item(0)->getAttribute('src'));
        }

        $sessionRows = $xpath->query("//table//tbody/tr[td]");
        if ($sessionRows->length > 0) {
            $lastRow = $sessionRows->item($sessionRows->length - 1);
            $tds = [];
            foreach ($lastRow->childNodes as $child) {
                if ($child->nodeName === 'td') {
                    $tds[] = trim($child->textContent);
                }
            }
            if (!empty($tds[0])) {
                $studentData['session'] = $tds[0];
            }
            if (empty($studentData['level']) && !empty($tds[1]) && preg_match('/\d+/', $tds[1], $lm)) {
                $studentData['level'] = $lm[0];
            }
        }

        $textContent = $doc->textContent;
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $textContent, $emailMatch)) {
            $studentData['email'] = $emailMatch[0];
        }
    }

    if (empty($studentData['first_name']) || empty($studentData['last_name'])) {
        parseCMSResponseFallback($html, $studentData);
    }

    if (empty($studentData['photo'])) {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $imgMatch)) {
            $studentData['photo'] = resolveCmsUrl($imgMatch[1]);
        }
    }

    if (!empty($studentData['photo'])) {
        $photoData = downloadPhotoBase64($studentData['photo']);
        if ($photoData) {
            $studentData['photo_data'] = $photoData;
        }
    }

    if (empty($studentData['first_name']) || empty($studentData['last_name'])) {
        return null;
    }

    return $studentData;
}

function parseCMSResponseFallback($html, &$studentData) {
    if (preg_match('/<h4[^>]*>(.*?)<\/h4>/is', $html, $nameMatch)) {
        applySplitName($studentData, trim(html_entity_decode(strip_tags($nameMatch[1]), ENT_QUOTES, 'UTF-8')));
    }

    if (preg_match_all('/<h5[^>]*>(.*?)<\/h5>/is', $html, $h5Matches)) {
        if (!empty($h5Matches[1][0])) {
            $matricText = trim(html_entity_decode(strip_tags($h5Matches[1][0]), ENT_QUOTES, 'UTF-8'));
            if ($matricText !== '') {
                $studentData['matric_number'] = $matricText;
            }
        }
        if (!empty($h5Matches[1][1])) {
            $levelText = trim(strip_tags($h5Matches[1][1]));
            if (preg_match('/\d+/', $levelText, $levelMatch)) {
                $studentData['level'] = $levelMatch[0];
            }
        }
    }

    if (preg_match('/<p[^>]*class="[^"]*text-secondary[^"]*"[^>]*>(.*?)<\/p>/is', $html, $deptMatch)) {
        $deptText = trim(html_entity_decode(strip_tags($deptMatch[1]), ENT_QUOTES, 'UTF-8'));
        $studentData['programme'] = $deptText;
        $studentData['department'] = cleanDepartmentName($deptText);
    }

    if (preg_match('/<p[^>]*class="[^"]*text-muted[^"]*"[^>]*>(.*?)<\/p>/is', $html, $addrMatch)) {
        $studentData['address'] = trim(html_entity_decode(strip_tags($addrMatch[1]), ENT_QUOTES, 'UTF-8'));
    }
}

function applySplitName(&$studentData, $fullName) {
    $fullName = preg_replace('/\s+/', ' ', trim($fullName));
    if ($fullName === '') {
        return;
    }
    $parts = explode(' ', $fullName);
    $count = count($parts);
    if ($count === 1) {
        $studentData['first_name'] = $parts[0];
        $studentData['last_name'] = $parts[0];
        $studentData['other_name'] = '';
        return;
    }
    if ($count === 2) {
        $studentData['first_name'] = $parts[0];
        $studentData['last_name'] = $parts[1];
        $studentData['other_name'] = '';
        return;
    }
    $studentData['first_name'] = array_shift($parts);
    $studentData['last_name'] = array_pop($parts);
    $studentData['other_name'] = implode(' ', $parts);
}

function cleanDepartmentName($deptText) {
    $deptText = preg_replace('/^\s*(B\.?\s*SC\.?\s*(\(\s*ED\s*\))?|B\.?\s*A\.?\s*(\(\s*ED\s*\))?|B\.?\s*ED\.?|B\.?\s*ENG\.?|B\.?\s*TECH\.?|B\.?\s*LIS\.?|LL\.?B\.?)\s*/i', '', $deptText);
    return trim($deptText);
}

function resolveCmsUrl($src) {
    $src = trim($src);
    if ($src === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $src)) {
        return $src;
    }
    $src = preg_replace('#^(\.\./)+#', '', $src);
    $src = ltrim($src, '/');
    return 'https://cms.tsuniversity.edu.ng/' . $src;
}

function downloadPhotoBase64($url) {
    if (empty($url)) {
        return null;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Referer: https://cms.tsuniversity.edu.ng/verify/search.php',
            'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 15,
    ]);

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Photo preview download error ($url): $curlError");
        return null;
    }

    if ($httpCode !== 200 || empty($imageData)) {
        error_log("Photo preview download failed ($url): HTTP $httpCode");
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_buffer($finfo, $imageData);
    finfo_close($finfo);

    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
        return null;
    }

    return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
}

function findDepartmentId($db, $departmentName) {
    if (empty($departmentName)) {
        error_log("Department name is empty");
        return 0;
    }

    $stmt = $db->prepare("SELECT id FROM departments WHERE dept_name = ? AND status = 1");
    $stmt->execute([$departmentName]);
    $result = $stmt->fetch();
    if ($result) {
        return $result['id'];
    }

    $stmt = $db->prepare("SELECT id FROM departments WHERE dept_name LIKE ? AND status = 1 LIMIT 1");
    $stmt->execute(["%$departmentName%"]);
    $result = $stmt->fetch();
    if ($result) {
        return $result['id'];
    }

    $keywords = explode(' ', $departmentName);
    foreach ($keywords as $keyword) {
        if (strlen($keyword) < 4) {
            continue;
        }
        $stmt = $db->prepare("SELECT id FROM departments WHERE dept_name LIKE ? AND status = 1 LIMIT 1");
        $stmt->execute(["%$keyword%"]);
        $result = $stmt->fetch();
        if ($result) {
            return $result['id'];
        }
    }

    error_log("Department not found: $departmentName");
    return 0;
}

function findLevelId($db, $levelName) {
    if (empty($levelName)) {
        error_log("Level name is empty");
        return 0;
    }

    $levelNum = preg_replace('/[^0-9]/', '', $levelName);
    if ($levelNum === '') {
        error_log("Could not extract level number from: $levelName");
        return 0;
    }

    $stmt = $db->prepare("SELECT id FROM levels WHERE level_name LIKE ? AND status = 1 LIMIT 1");
    $stmt->execute(["%$levelNum%"]);
    $result = $stmt->fetch();
    if ($result) {
        return $result['id'];
    }

    error_log("Level not found: $levelName");
    return 0;
}

function getActiveSemesterId($db) {
    $stmt = $db->prepare("SELECT id FROM semesters WHERE status = 1 ORDER BY semester_order LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result ? $result['id'] : 1;
}

function getActiveSessionId($db) {
    $stmt = $db->prepare("SELECT id FROM sessions WHERE is_current = 1 AND status = 1 LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();

    if (!$result) {
        $stmt = $db->prepare("SELECT id FROM sessions WHERE status = 1 ORDER BY session_name DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch();
    }

    return $result ? $result['id'] : 1;
}
