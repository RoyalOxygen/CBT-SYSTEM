<?php
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

function groq_default_model(): string {
    return 'openai/gpt-oss-20b';
}

function groq_api_key(): string {
    return trim((string) getSetting('groq_api_key', ''));
}

function groq_model(): string {
    $model = trim((string) getSetting('groq_model', groq_default_model()));
    return $model !== '' ? $model : groq_default_model();
}

function groq_question_schema(): array {
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
                    'required' => ['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'correct_answer'],
                    'additionalProperties' => false,
                ],
            ],
        ],
        'required' => ['questions'],
        'additionalProperties' => false,
    ];
}

function groq_http(string $url, array $payload, int $timeout = 180): array {
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL is required for Groq import'];
    }
    $key = groq_api_key();
    if ($key === '') {
        return ['ok' => false, 'error' => 'Groq API key is not configured. Add it in Settings.'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
        ],
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        return ['ok' => false, 'error' => 'Groq unreachable: ' . $err, 'status' => $status];
    }
    $json = json_decode((string) $raw, true);
    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'raw' => $raw,
        'json' => is_array($json) ? $json : [],
        'error' => $status >= 400 ? groq_error_message(is_array($json) ? $json : [], (string) $raw, $status) : '',
    ];
}

function groq_error_message(array $json, string $raw, int $status): string {
    if (!empty($json['error']['message'])) {
        return (string) $json['error']['message'];
    }
    if (!empty($json['message'])) {
        return (string) $json['message'];
    }
    $snippet = trim(substr($raw, 0, 200));
    return $snippet !== '' ? $snippet : ('Groq HTTP ' . $status);
}

function groq_clean_text(string $text): string {
    $text = str_replace("\x00", '', $text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
    $text = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
    return trim($text);
}

function groq_pdf_unescape(string $s): string {
    $s = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)', '\\\\'], ["\n", "\r", "\t", '(', ')', '\\'], $s);
    $s = preg_replace_callback('/\\\\([0-7]{1,3})/', static function ($m) {
        return chr(octdec($m[1]));
    }, $s) ?? $s;
    return $s;
}

function groq_pdf_text(string $path): string {
    $content = file_get_contents($path);
    if ($content === false || $content === '') {
        return '';
    }
    $decoded = '';
    if (preg_match_all('/stream\s*\r?\n(.*?)\r?\nendstream/s', $content, $streams)) {
        foreach ($streams[1] as $stream) {
            $chunk = $stream;
            if (strncmp($chunk, "\x78\x9c", 2) === 0 || strncmp($chunk, "\x78\x01", 2) === 0 || strncmp($chunk, "\x78\xda", 2) === 0) {
                $inflated = @gzuncompress($chunk);
                if ($inflated !== false) {
                    $chunk = $inflated;
                }
            } else {
                $inflated = @gzinflate($chunk);
                if ($inflated !== false) {
                    $chunk = $inflated;
                }
            }
            $decoded .= $chunk . "\n";
        }
    }
    $source = $decoded !== '' ? $decoded : $content;
    $parts = [];
    if (preg_match_all('/\[(.*?)\]\s*TJ/s', $source, $blocks)) {
        foreach ($blocks[1] as $block) {
            $line = '';
            if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/', $block, $strings)) {
                foreach ($strings[0] as $s) {
                    $line .= groq_pdf_unescape(substr($s, 1, -1));
                }
            }
            if ($line !== '') {
                $parts[] = $line;
            }
        }
    }
    if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)\s*Tj/s', $source, $tjs)) {
        foreach ($tjs[0] as $item) {
            if (preg_match('/^\((.*)\)\s*Tj/s', $item, $m)) {
                $parts[] = groq_pdf_unescape($m[1]);
            }
        }
    }
    return groq_clean_text(implode("\n", $parts));
}

function groq_zip_xml(string $path, string $inner): string {
    if (!class_exists('ZipArchive')) {
        return '';
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return '';
    }
    $xml = $zip->getFromName($inner);
    $zip->close();
    return is_string($xml) ? $xml : '';
}

function groq_docx_text(string $path): string {
    $xml = groq_zip_xml($path, 'word/document.xml');
    if ($xml === '') {
        return '';
    }
    $xml = str_replace(['</w:p>', '</w:tr>', '</w:tbl>'], "\n", $xml);
    return groq_clean_text(strip_tags($xml));
}

function groq_odt_text(string $path): string {
    $xml = groq_zip_xml($path, 'content.xml');
    if ($xml === '') {
        return '';
    }
    $xml = str_replace(['</text:p>', '</text:h>', '</table:table-row>', '</table:table-cell>'], "\n", $xml);
    return groq_clean_text(strip_tags($xml));
}

function groq_xlsx_text(string $path): string {
    if (!class_exists('ZipArchive')) {
        return '';
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return '';
    }
    $shared = [];
    $ss = $zip->getFromName('xl/sharedStrings.xml');
    if (is_string($ss) && preg_match_all('/<si>(.*?)<\/si>/s', $ss, $sis)) {
        foreach ($sis[1] as $si) {
            if (preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $si, $ts)) {
                $shared[] = groq_clean_text(implode('', $ts[1]));
            } else {
                $shared[] = '';
            }
        }
    }
    $lines = [];
    for ($i = 1; $i <= 12; $i++) {
        $sheet = $zip->getFromName('xl/worksheets/sheet' . $i . '.xml');
        if (!is_string($sheet) || $sheet === '') {
            if ($i > 1) {
                break;
            }
            continue;
        }
        if (!preg_match_all('/<row[^>]*>(.*?)<\/row>/s', $sheet, $rows)) {
            continue;
        }
        foreach ($rows[1] as $rowXml) {
            $cells = [];
            if (!preg_match_all('/<c([^>]*)>(.*?)<\/c>/s', $rowXml, $cellMatches, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($cellMatches as $cell) {
                $attrs = $cell[1];
                $inner = $cell[2];
                $value = '';
                if (preg_match('/<v[^>]*>(.*?)<\/v>/s', $inner, $v)) {
                    $value = html_entity_decode($v[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
                } elseif (preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $inner, $ts)) {
                    $value = html_entity_decode(implode('', $ts[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
                if (preg_match('/\bt="s"/', $attrs) && is_numeric($value) && isset($shared[(int) $value])) {
                    $value = $shared[(int) $value];
                }
                $cells[] = trim($value);
            }
            $line = trim(implode(' | ', array_filter($cells, static function ($c) {
                return $c !== '';
            })));
            if ($line !== '') {
                $lines[] = $line;
            }
        }
    }
    $zip->close();
    return groq_clean_text(implode("\n", $lines));
}

function groq_binary_office_text(string $path): string {
    $data = file_get_contents($path);
    if ($data === false) {
        return '';
    }
    $chunks = [];
    if (preg_match_all('/[\x20-\x7E]{4,}/', $data, $m)) {
        $chunks = $m[0];
    }
    return groq_clean_text(implode("\n", $chunks));
}

function groq_extract_document_text(string $filePath, string $displayName): array {
    $ext = strtolower(pathinfo($displayName, PATHINFO_EXTENSION));
    $text = '';
    if ($ext === 'pdf') {
        $text = groq_pdf_text($filePath);
    } elseif ($ext === 'docx') {
        $text = groq_docx_text($filePath);
    } elseif ($ext === 'odt') {
        $text = groq_odt_text($filePath);
    } elseif (in_array($ext, ['xlsx', 'ods'], true)) {
        $text = $ext === 'xlsx' ? groq_xlsx_text($filePath) : groq_odt_text($filePath);
    } elseif (in_array($ext, ['txt', 'csv'], true)) {
        $raw = file_get_contents($filePath);
        $text = groq_clean_text($raw === false ? '' : $raw);
    } elseif (in_array($ext, ['doc', 'xls'], true)) {
        $text = groq_binary_office_text($filePath);
    } else {
        return ['ok' => false, 'error' => 'Unsupported file type. Upload PDF, DOCX, DOC, XLSX, XLS, or TXT'];
    }
    if ($text === '') {
        return ['ok' => false, 'error' => 'Could not read text from the document. Try a text-based PDF, DOCX, or XLSX'];
    }
    $limit = 80000;
    if (strlen($text) > $limit) {
        $text = substr($text, 0, $limit);
    }
    return ['ok' => true, 'text' => $text];
}

function groq_output_text(array $json): string {
    $content = $json['choices'][0]['message']['content'] ?? '';
    if (is_string($content) && $content !== '') {
        return trim($content);
    }
    if (!empty($json['output_text']) && is_string($json['output_text'])) {
        return trim($json['output_text']);
    }
    return '';
}

function groq_student_schema(): array {
    return [
        'type' => 'object',
        'properties' => [
            'students' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'first_name' => ['type' => 'string'],
                        'last_name' => ['type' => 'string'],
                        'other_name' => ['type' => 'string'],
                        'full_name' => ['type' => 'string'],
                        'matric_number' => ['type' => 'string'],
                        'reg_number' => ['type' => 'string'],
                    ],
                    'required' => ['first_name', 'last_name', 'other_name', 'full_name', 'matric_number', 'reg_number'],
                    'additionalProperties' => false,
                ],
            ],
        ],
        'required' => ['students'],
        'additionalProperties' => false,
    ];
}

function groq_extract_students(string $filePath, string $displayName): array {
    $extracted = groq_extract_document_text($filePath, $displayName);
    if (empty($extracted['ok'])) {
        return $extracted;
    }
    $prompt = "Extract every student record from this document.\n"
        . "Rules:\n"
        . "- Return one object per student.\n"
        . "- Use matric_number or reg_number when present (registration number, matric no, regno, ID).\n"
        . "- If the document has separate first name and last name (surname) columns, fill first_name and last_name. Put any middle name in other_name.\n"
        . "- If the document only has a full name, put it in full_name and leave first_name, last_name, and other_name empty.\n"
        . "- Use empty strings for missing fields. Do not invent students or numbers.\n"
        . "- Preserve original spelling and punctuation of names and numbers.";

    $payload = [
        'model' => groq_model(),
        'temperature' => 0.1,
        'max_completion_tokens' => 32768,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You extract student records from documents. Return only JSON that matches the schema.',
            ],
            [
                'role' => 'user',
                'content' => $prompt . "\n\nDocument:\n" . $extracted['text'],
            ],
        ],
        'response_format' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'student_records',
                'strict' => true,
                'schema' => groq_student_schema(),
            ],
        ],
    ];

    $res = groq_http('https://api.groq.com/openai/v1/chat/completions', $payload, 180);
    if (empty($res['ok'])) {
        return ['ok' => false, 'error' => $res['error'] ?: 'Groq extraction failed'];
    }

    $text = groq_output_text($res['json']);
    if ($text === '') {
        return ['ok' => false, 'error' => 'Groq returned an empty response'];
    }
    $decoded = json_decode($text, true);
    if (!is_array($decoded) && preg_match('/\{.*\}/s', $text, $m)) {
        $decoded = json_decode($m[0], true);
    }
    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'Groq did not return valid JSON'];
    }
    $rows = $decoded['students'] ?? $decoded;
    if (!is_array($rows)) {
        return ['ok' => false, 'error' => 'Groq response had no students'];
    }
    return ['ok' => true, 'students' => $rows];
}

function groq_extract_questions(string $filePath, string $displayName, int $questionCount): array {
    $count = max(1, min(200, $questionCount));
    $extracted = groq_extract_document_text($filePath, $displayName);
    if (empty($extracted['ok'])) {
        return $extracted;
    }
    $prompt = "Extract exactly {$count} multiple-choice exam questions from this document.\n"
        . "Rules:\n"
        . "- Each question must have question_text, option_a, option_b, option_c, option_d, option_e, and correct_answer (A, B, C, D, or E).\n"
        . "- Use an empty string for missing options.\n"
        . "- Correct answers are marked in the document (asterisk, bold, underline, tick, ANSWER:, answer key, or numbered key). Use those marks. Do not guess.\n"
        . "- If a numbered answer key is at the end, match it to question numbers.\n"
        . "- Return only the first {$count} complete questions that have a marked correct answer.\n"
        . "- Preserve the original wording. Do not invent options or questions.";

    $payload = [
        'model' => groq_model(),
        'temperature' => 0.1,
        'max_completion_tokens' => 32768,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You extract multiple-choice exam questions from documents. Return only JSON that matches the schema.',
            ],
            [
                'role' => 'user',
                'content' => $prompt . "\n\nDocument:\n" . $extracted['text'],
            ],
        ],
        'response_format' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'exam_questions',
                'strict' => true,
                'schema' => groq_question_schema(),
            ],
        ],
    ];

    $res = groq_http('https://api.groq.com/openai/v1/chat/completions', $payload, 180);
    if (empty($res['ok'])) {
        return ['ok' => false, 'error' => $res['error'] ?: 'Groq extraction failed'];
    }

    $text = groq_output_text($res['json']);
    if ($text === '') {
        return ['ok' => false, 'error' => 'Groq returned an empty response'];
    }
    $decoded = json_decode($text, true);
    if (!is_array($decoded) && preg_match('/\{.*\}/s', $text, $m)) {
        $decoded = json_decode($m[0], true);
    }
    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'Groq did not return valid JSON'];
    }
    $rows = $decoded['questions'] ?? $decoded;
    if (!is_array($rows)) {
        return ['ok' => false, 'error' => 'Groq response had no questions'];
    }
    return ['ok' => true, 'questions' => $rows];
}
