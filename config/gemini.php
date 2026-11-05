<?php
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

function gemini_default_model(): string {
    return 'gemini-3.8-flash';
}

function gemini_api_key(): string {
    return trim((string) getSetting('gemini_api_key', ''));
}

function gemini_model(): string {
    $model = trim((string) getSetting('gemini_model', gemini_default_model()));
    return $model !== '' ? $model : gemini_default_model();
}

function gemini_mime_from_upload(array $file): string {
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $map = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    ];
    if (isset($map[$ext])) {
        return $map[$ext];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    return $detected ?: 'application/octet-stream';
}

function gemini_http(string $url, array $opts = []): array {
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL is required for Gemini import'];
    }
    $key = gemini_api_key();
    if ($key === '') {
        return ['ok' => false, 'error' => 'Gemini API key is not configured. Add it in Settings.'];
    }
    $headers = $opts['headers'] ?? [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $key,
        'Api-Revision: 2026-05-20',
    ];
    $ch = curl_init($url);
    $curlOpts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => (int) ($opts['timeout'] ?? 180),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADER => !empty($opts['header']),
    ];
    if (!empty($opts['post'])) {
        $curlOpts[CURLOPT_POST] = true;
        $curlOpts[CURLOPT_POSTFIELDS] = $opts['body'] ?? '';
    }
    if (!empty($opts['put_file'])) {
        $curlOpts[CURLOPT_CUSTOMREQUEST] = 'POST';
        $curlOpts[CURLOPT_POSTFIELDS] = $opts['body'] ?? '';
    }
    curl_setopt_array($ch, $curlOpts);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        return ['ok' => false, 'error' => 'Gemini unreachable: ' . $err, 'status' => $status];
    }
    $headerText = '';
    $body = $raw;
    if (!empty($opts['header'])) {
        $headerText = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
    }
    $json = json_decode((string) $body, true);
    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'raw' => $body,
        'json' => is_array($json) ? $json : [],
        'headers' => $headerText,
        'error' => $status >= 400 ? gemini_error_message(is_array($json) ? $json : [], (string) $body, $status) : '',
    ];
}

function gemini_error_message(array $json, string $raw, int $status): string {
    if (!empty($json['error']['message'])) {
        return (string) $json['error']['message'];
    }
    if (!empty($json['message'])) {
        return (string) $json['message'];
    }
    $snippet = trim(substr($raw, 0, 200));
    return $snippet !== '' ? $snippet : ('Gemini HTTP ' . $status);
}

function gemini_upload_file(string $path, string $mime, string $displayName): array {
    $key = gemini_api_key();
    $size = filesize($path);
    if ($size === false) {
        return ['ok' => false, 'error' => 'Could not read uploaded document'];
    }
    $start = gemini_http('https://generativelanguage.googleapis.com/upload/v1beta/files?key=' . urlencode($key), [
        'post' => true,
        'header' => true,
        'timeout' => 60,
        'headers' => [
            'Content-Type: application/json',
            'X-Goog-Upload-Protocol: resumable',
            'X-Goog-Upload-Command: start',
            'X-Goog-Upload-Header-Content-Length: ' . $size,
            'X-Goog-Upload-Header-Content-Type: ' . $mime,
            'x-goog-api-key: ' . $key,
        ],
        'body' => json_encode(['file' => ['display_name' => $displayName]]),
    ]);
    if (empty($start['ok'])) {
        return ['ok' => false, 'error' => $start['error'] ?: 'Gemini file upload failed to start'];
    }
    $uploadUrl = '';
    foreach (preg_split("/\r\n|\n|\r/", (string) $start['headers']) as $line) {
        if (stripos($line, 'x-goog-upload-url:') === 0) {
            $uploadUrl = trim(substr($line, strlen('x-goog-upload-url:')));
            break;
        }
    }
    if ($uploadUrl === '') {
        return ['ok' => false, 'error' => 'Gemini did not return an upload URL'];
    }
    $bytes = file_get_contents($path);
    if ($bytes === false) {
        return ['ok' => false, 'error' => 'Could not read uploaded document'];
    }
    $finish = gemini_http($uploadUrl, [
        'put_file' => true,
        'timeout' => 180,
        'headers' => [
            'Content-Length: ' . $size,
            'X-Goog-Upload-Offset: 0',
            'X-Goog-Upload-Command: upload, finalize',
            'x-goog-api-key: ' . $key,
        ],
        'body' => $bytes,
    ]);
    $uri = $finish['json']['file']['uri'] ?? ($finish['json']['uri'] ?? '');
    $outMime = $finish['json']['file']['mimeType'] ?? ($finish['json']['file']['mime_type'] ?? $mime);
    if (empty($finish['ok']) || $uri === '') {
        return ['ok' => false, 'error' => $finish['error'] ?: 'Gemini file upload failed'];
    }
    return ['ok' => true, 'uri' => $uri, 'mime' => $outMime];
}

function gemini_question_schema(): array {
    return [
        'type' => 'object',
        'properties' => [
            'questions' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'question_text' => ['type' => 'string'],
                        'option_a' => ['type' => 'string'],
                        'option_b' => ['type' => 'string'],
                        'option_c' => ['type' => 'string'],
                        'option_d' => ['type' => 'string'],
                        'option_e' => ['type' => 'string'],
                        'correct_answer' => ['type' => 'string', 'enum' => ['A', 'B', 'C', 'D', 'E']],
                    ],
                    'required' => ['question_text', 'option_a', 'option_b', 'correct_answer'],
                ],
            ],
        ],
        'required' => ['questions'],
    ];
}

function gemini_extract_questions(string $filePath, string $mime, string $displayName, int $questionCount): array {
    $count = max(1, min(200, $questionCount));
    $prompt = "Extract exactly {$count} multiple-choice exam questions from this document.\n"
        . "Rules:\n"
        . "- Each question must have question_text, option_a, option_b, and correct_answer (A, B, C, D, or E).\n"
        . "- Include option_c, option_d, option_e when they exist; otherwise omit or use empty string.\n"
        . "- Correct answers are marked in the document (asterisk, bold, underline, tick, ANSWER:, answer key, or numbered key). Use those marks. Do not guess.\n"
        . "- If a numbered answer key is at the end, match it to question numbers.\n"
        . "- Return only the first {$count} complete questions that have a marked correct answer.\n"
        . "- Preserve the original wording. Do not invent options or questions.";

    $inlineLimit = 4 * 1024 * 1024;
    $size = filesize($filePath);
    $input = [];
    if ($size !== false && $size <= $inlineLimit) {
        $data = file_get_contents($filePath);
        if ($data === false) {
            return ['ok' => false, 'error' => 'Could not read uploaded document'];
        }
        $input[] = [
            'type' => 'document',
            'data' => base64_encode($data),
            'mime_type' => $mime,
        ];
    } else {
        $uploaded = gemini_upload_file($filePath, $mime, $displayName);
        if (empty($uploaded['ok'])) {
            return $uploaded;
        }
        $input[] = [
            'type' => 'document',
            'uri' => $uploaded['uri'],
            'mime_type' => $uploaded['mime'],
        ];
    }
    $input[] = ['type' => 'text', 'text' => $prompt];

    $payload = [
        'model' => gemini_model(),
        'input' => $input,
        'response_format' => [
            'type' => 'text',
            'mime_type' => 'application/json',
            'schema' => gemini_question_schema(),
        ],
    ];

    $res = gemini_http('https://generativelanguage.googleapis.com/v1beta/interactions', [
        'post' => true,
        'timeout' => 180,
        'body' => json_encode($payload),
    ]);
    if (empty($res['ok'])) {
        return ['ok' => false, 'error' => $res['error'] ?: 'Gemini extraction failed'];
    }

    $text = gemini_output_text($res['json']);
    if ($text === '') {
        return ['ok' => false, 'error' => 'Gemini returned an empty response'];
    }
    $decoded = json_decode($text, true);
    if (!is_array($decoded)) {
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
        }
    }
    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'Gemini did not return valid JSON'];
    }
    $rows = $decoded['questions'] ?? $decoded;
    if (!is_array($rows)) {
        return ['ok' => false, 'error' => 'Gemini response had no questions'];
    }
    return ['ok' => true, 'questions' => $rows];
}

function gemini_output_text(array $json): string {
    if (!empty($json['output_text']) && is_string($json['output_text'])) {
        return trim($json['output_text']);
    }
    if (!empty($json['interaction']['output_text']) && is_string($json['interaction']['output_text'])) {
        return trim($json['interaction']['output_text']);
    }
    $steps = $json['steps'] ?? ($json['interaction']['steps'] ?? []);
    if (is_array($steps) && $steps) {
        $last = $steps[count($steps) - 1];
        $content = $last['content'] ?? [];
        if (is_array($content)) {
            foreach ($content as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    return trim($part['text']);
                }
            }
        }
        if (is_string($content)) {
            return trim($content);
        }
    }
    $candidates = $json['candidates'][0]['content']['parts'] ?? [];
    if (is_array($candidates)) {
        foreach ($candidates as $part) {
            if (!empty($part['text']) && is_string($part['text'])) {
                return trim($part['text']);
            }
        }
    }
    return '';
}
