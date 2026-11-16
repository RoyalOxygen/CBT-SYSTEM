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

<<<<<<< HEAD
function groq_default_vision_model(): string {
    return 'meta-llama/llama-4-scout-17b-16e-instruct';
}

function groq_vision_model(): string {
    $model = trim((string) getSetting('groq_vision_model', groq_default_vision_model()));
    return $model !== '' ? $model : groq_default_vision_model();
}

=======
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
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
<<<<<<< HEAD
    $text = str_replace(["\x00", "\x0C"], '', $text);
=======
    $text = str_replace("\x00", '', $text);
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
    $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
    $text = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
    return trim($text);
}

<<<<<<< HEAD
function groq_bin(string $name): string {
    foreach (['/usr/bin/' . $name, '/usr/local/bin/' . $name] as $path) {
        if (is_executable($path)) {
            return $path;
        }
    }
    return '';
}

function groq_run(array $args, int $timeout = 60): string {
    if (!function_exists('proc_open')) {
        return '';
    }
    $errFile = sys_get_temp_dir() . '/groq_err_' . bin2hex(random_bytes(4));
    $desc = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['file', $errFile, 'w'],
    ];
    $proc = @proc_open($args, $desc, $pipes, null, null);
    if (!is_resource($proc)) {
        @unlink($errFile);
        return '';
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    $out = '';
    $start = microtime(true);
    while (true) {
        $status = proc_get_status($proc);
        $chunk = stream_get_contents($pipes[1]);
        if (is_string($chunk) && $chunk !== '') {
            $out .= $chunk;
        }
        if (empty($status['running'])) {
            $chunk = stream_get_contents($pipes[1]);
            if (is_string($chunk) && $chunk !== '') {
                $out .= $chunk;
            }
            break;
        }
        if ((microtime(true) - $start) > $timeout) {
            proc_terminate($proc, 9);
            break;
        }
        usleep(50000);
    }
    fclose($pipes[1]);
    proc_close($proc);
    @unlink($errFile);
    return $out;
}

function groq_work_copy(string $filePath, string $displayName): array {
    $ext = strtolower(pathinfo($displayName, PATHINFO_EXTENSION));
    if ($ext === '') {
        $ext = 'bin';
    }
    $dest = sys_get_temp_dir() . '/groq_up_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!@copy($filePath, $dest) || !is_file($dest) || filesize($dest) < 1) {
        return [$filePath, false];
    }
    return [$dest, true];
}

function groq_text_is_useful(string $text): bool {
    $text = groq_clean_text($text);
    if ($text === '') {
        return false;
    }
    $alnum = preg_replace('/[^A-Za-z0-9]/', '', $text) ?? '';
    return strlen($alnum) >= 8;
}

=======
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
function groq_pdf_unescape(string $s): string {
    $s = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)', '\\\\'], ["\n", "\r", "\t", '(', ')', '\\'], $s);
    $s = preg_replace_callback('/\\\\([0-7]{1,3})/', static function ($m) {
        return chr(octdec($m[1]));
    }, $s) ?? $s;
    return $s;
}

<<<<<<< HEAD
function groq_pdf_inflate(string $chunk): string {
    $tries = [@gzuncompress($chunk), @gzinflate($chunk), @gzinflate(substr($chunk, 2))];
    foreach ($tries as $inflated) {
        if (is_string($inflated) && $inflated !== '') {
            return $inflated;
        }
    }
    return $chunk;
}

function groq_pdf_parse_strings(string $source): string {
=======
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
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
    $parts = [];
    if (preg_match_all('/\[(.*?)\]\s*TJ/s', $source, $blocks)) {
        foreach ($blocks[1] as $block) {
            $line = '';
            if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/', $block, $strings)) {
                foreach ($strings[0] as $s) {
                    $line .= groq_pdf_unescape(substr($s, 1, -1));
                }
            }
<<<<<<< HEAD
            if (preg_match_all('/<([0-9A-Fa-f\s]+)>/', $block, $hexes)) {
                foreach ($hexes[1] as $hex) {
                    $hex = preg_replace('/\s+/', '', $hex) ?? $hex;
                    if ($hex !== '' && (strlen($hex) % 2) === 0) {
                        $decoded = @hex2bin($hex);
                        if (is_string($decoded)) {
                            $line .= $decoded;
                        }
                    }
                }
            }
            if (trim($line) !== '') {
=======
            if ($line !== '') {
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
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

<<<<<<< HEAD
function groq_pdf_stream_text(string $path): string {
    $content = file_get_contents($path);
    if ($content === false || $content === '') {
        return '';
    }
    $decoded = '';
    if (preg_match_all('/<<(.*?)>>\s*stream\s*\r?\n(.*?)\r?\nendstream/s', $content, $streams, PREG_SET_ORDER)) {
        foreach ($streams as $stream) {
            $dict = $stream[1];
            $chunk = $stream[2];
            if (stripos($dict, 'FlateDecode') !== false || strncmp($chunk, "\x78", 1) === 0) {
                $chunk = groq_pdf_inflate($chunk);
            }
            $decoded .= $chunk . "\n";
        }
    } elseif (preg_match_all('/stream\s*\r?\n(.*?)\r?\nendstream/s', $content, $bare)) {
        foreach ($bare[1] as $stream) {
            $decoded .= groq_pdf_inflate($stream) . "\n";
        }
    }
    $fromStreams = groq_pdf_parse_strings($decoded);
    if (groq_text_is_useful($fromStreams)) {
        return $fromStreams;
    }
    $fromRaw = groq_pdf_parse_strings($content);
    return groq_text_is_useful($fromRaw) ? $fromRaw : $fromStreams;
}

function groq_pdftotext(string $path): string {
    $bin = groq_bin('pdftotext');
    if ($bin === '') {
        return '';
    }
    $out = groq_run([$bin, '-layout', '-enc', 'UTF-8', '-q', $path, '-'], 90);
    return groq_clean_text($out);
}

function groq_pypdf_text(string $path): string {
    $py = <<<'PY'
import sys
try:
    from pypdf import PdfReader
except Exception:
    sys.exit(0)
try:
    reader = PdfReader(sys.argv[1], strict=False)
    parts = []
    for page in reader.pages:
        t = page.extract_text() or ""
        if t.strip():
            parts.append(t)
    sys.stdout.write("\n".join(parts))
except Exception:
    pass
PY;
    $script = sys_get_temp_dir() . '/groq_pypdf_' . bin2hex(random_bytes(4)) . '.py';
    if (@file_put_contents($script, $py) === false) {
        return '';
    }
    $pyBin = groq_bin('python3');
    if ($pyBin === '') {
        @unlink($script);
        return '';
    }
    $out = groq_run([$pyBin, $script, $path], 90);
    @unlink($script);
    return groq_clean_text($out);
}

function groq_pdf_text(string $path): string {
    foreach ([groq_pdftotext($path), groq_pypdf_text($path), groq_pdf_stream_text($path)] as $text) {
        if (groq_text_is_useful($text)) {
            return $text;
        }
    }
    $fallback = groq_pdftotext($path);
    if ($fallback !== '') {
        return $fallback;
    }
    $fallback = groq_pypdf_text($path);
    return $fallback !== '' ? $fallback : groq_pdf_stream_text($path);
}

=======
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
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

<<<<<<< HEAD
function groq_docx_part_text(string $xml): string {
    if ($xml === '') {
        return '';
    }
    $xml = str_replace(['</w:p>', '</w:tr>', '</w:tbl>', '</w:tc>'], "\n", $xml);
    return groq_clean_text(strip_tags($xml));
}

function groq_docx_text(string $path): string {
    if (!class_exists('ZipArchive')) {
        return groq_docx_part_text(groq_zip_xml($path, 'word/document.xml'));
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return '';
    }
    $chunks = [];
    $names = ['word/document.xml', 'word/header1.xml', 'word/header2.xml', 'word/footer1.xml', 'word/footer2.xml'];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = (string) $zip->getNameIndex($i);
        if (preg_match('#^word/(document|header\d+|footer\d+|footnotes|endnotes)\.xml$#', $name) && !in_array($name, $names, true)) {
            $names[] = $name;
        }
    }
    foreach ($names as $name) {
        $xml = $zip->getFromName($name);
        if (is_string($xml) && $xml !== '') {
            $part = groq_docx_part_text($xml);
            if ($part !== '') {
                $chunks[] = $part;
            }
        }
    }
    $zip->close();
    return groq_clean_text(implode("\n", $chunks));
}

=======
function groq_docx_text(string $path): string {
    $xml = groq_zip_xml($path, 'word/document.xml');
    if ($xml === '') {
        return '';
    }
    $xml = str_replace(['</w:p>', '</w:tr>', '</w:tbl>'], "\n", $xml);
    return groq_clean_text(strip_tags($xml));
}

>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
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
<<<<<<< HEAD
    $noise = ['Microsoft', 'WordDocument', 'Normal.dot', 'Times New Roman', 'Calibri', 'Arial', 'Root Entry'];
    $kept = [];
    foreach ($chunks as $chunk) {
        $skip = false;
        foreach ($noise as $n) {
            if (stripos($chunk, $n) !== false && strlen($chunk) < 40) {
                $skip = true;
                break;
            }
        }
        if (!$skip) {
            $kept[] = $chunk;
        }
    }
    return groq_clean_text(implode("\n", $kept));
}

function groq_temp_dir(string $prefix): string {
    $dir = sys_get_temp_dir() . '/' . $prefix . bin2hex(random_bytes(6));
    if (!@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return '';
    }
    return $dir;
}

function groq_rmdir(string $dir): void {
    if ($dir === '' || !is_dir($dir)) {
        return;
    }
    $files = @scandir($dir);
    if (!is_array($files)) {
        return;
    }
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            groq_rmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function groq_resize_jpeg(string $path, int $maxWidth = 1280): string {
    if (!function_exists('imagecreatefromstring')) {
        return $path;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return $path;
    }
    $img = @imagecreatefromstring($raw);
    if ($img === false) {
        return $path;
    }
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w <= 0 || $h <= 0) {
        imagedestroy($img);
        return $path;
    }
    if ($w > $maxWidth) {
        $nw = $maxWidth;
        $nh = max(1, (int) round($h * ($maxWidth / $w)));
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($img);
        $img = $dst;
    }
    $out = $path . '.jpg';
    imagejpeg($img, $out, 72);
    imagedestroy($img);
    if (is_file($out)) {
        return $out;
    }
    return $path;
}

function groq_jpeg_bytes(string $raw): string {
    if ($raw === '') {
        return '';
    }
    $tmp = sys_get_temp_dir() . '/groq_raw_' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $raw) === false) {
        return '';
    }
    $resized = groq_resize_jpeg($tmp);
    $data = @file_get_contents($resized);
    @unlink($tmp);
    if ($resized !== $tmp) {
        @unlink($resized);
    }
    return is_string($data) && $data !== '' ? $data : '';
}

function groq_pdf_embedded_images(string $path, int $maxPages = 8): array {
    $content = @file_get_contents($path);
    if (!is_string($content) || $content === '') {
        return [];
    }
    $images = [];
    if (preg_match_all('/\xFF\xD8\xFF.{100,}?\xFF\xD9/s', $content, $m)) {
        foreach ($m[0] as $jpeg) {
            if (strlen($jpeg) < 4000) {
                continue;
            }
            $bytes = groq_jpeg_bytes($jpeg);
            if ($bytes !== '') {
                $images[] = $bytes;
            }
            if (count($images) >= $maxPages) {
                break;
            }
        }
    }
    return $images;
}

function groq_pdfium_page_images(string $path, int $maxPages = 8): array {
    $py = <<<'PY'
import sys
try:
    import pypdfium2 as pdfium
    from PIL import Image
except Exception:
    sys.exit(0)
src, outdir, max_pages = sys.argv[1], sys.argv[2], int(sys.argv[3])
try:
    pdf = pdfium.PdfDocument(src)
except Exception:
    sys.exit(0)
limit = min(len(pdf), max_pages)
for i in range(limit):
    page = pdf[i]
    bitmap = page.render(scale=1.4)
    img = bitmap.to_pil()
    img = img.convert("RGB")
    w, h = img.size
    if w > 1280:
        img = img.resize((1280, max(1, int(h * 1280 / w))))
    img.save("%s/page-%03d.jpg" % (outdir, i + 1), "JPEG", quality=72)
PY;
    $dir = groq_temp_dir('groq_pdfium_');
    if ($dir === '') {
        return [];
    }
    $script = $dir . '/render.py';
    if (@file_put_contents($script, $py) === false) {
        groq_rmdir($dir);
        return [];
    }
    $pyBin = groq_bin('python3');
    if ($pyBin === '') {
        groq_rmdir($dir);
        return [];
    }
    groq_run([$pyBin, $script, $path, $dir, (string) $maxPages], 90);
    $files = glob($dir . '/page-*.jpg') ?: [];
    natsort($files);
    $images = [];
    foreach ($files as $file) {
        $data = @file_get_contents($file);
        if (is_string($data) && $data !== '') {
            $images[] = $data;
        }
    }
    groq_rmdir($dir);
    return $images;
}

function groq_pdf_page_images(string $path, int $maxPages = 8): array {
    $images = groq_pdfium_page_images($path, $maxPages);
    if ($images !== []) {
        return $images;
    }
    $bin = groq_bin('pdftoppm');
    if ($bin !== '') {
        $dir = groq_temp_dir('groq_pdf_');
        if ($dir !== '') {
            groq_run([$bin, '-jpeg', '-r', '110', '-f', '1', '-l', (string) $maxPages, $path, $dir . '/page'], 90);
            $files = glob($dir . '/page*.jpg') ?: [];
            natsort($files);
            foreach ($files as $file) {
                $resized = groq_resize_jpeg($file);
                $data = @file_get_contents($resized);
                if (is_string($data) && $data !== '') {
                    $images[] = $data;
                }
            }
            groq_rmdir($dir);
            if ($images !== []) {
                return $images;
            }
        }
    }
    return groq_pdf_embedded_images($path, $maxPages);
}

function groq_zip_images(string $path, array $prefixes, int $maxImages = 8): array {
    if (!class_exists('ZipArchive')) {
        return [];
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return [];
    }
    $images = [];
    for ($i = 0; $i < $zip->numFiles && count($images) < $maxImages; $i++) {
        $name = (string) $zip->getNameIndex($i);
        $ok = false;
        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix) && preg_match('/\.(jpe?g|png|gif|webp)$/i', $name)) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            continue;
        }
        $raw = $zip->getFromIndex($i);
        if (!is_string($raw) || $raw === '') {
            continue;
        }
        $tmp = sys_get_temp_dir() . '/groq_img_' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmp, $raw) === false) {
            continue;
        }
        $resized = groq_resize_jpeg($tmp);
        $data = @file_get_contents($resized);
        if (is_string($data) && $data !== '') {
            $images[] = $data;
        }
        @unlink($tmp);
        if ($resized !== $tmp) {
            @unlink($resized);
        }
    }
    $zip->close();
    return $images;
}

function groq_ocr_images(array $images): array {
    if ($images === []) {
        return ['ok' => false, 'error' => 'No page images were found for OCR'];
    }
    $parts = [];
    $limit = min(8, count($images));
    for ($i = 0; $i < $limit; $i++) {
        $b64 = base64_encode($images[$i]);
        $payload = [
            'model' => groq_vision_model(),
            'temperature' => 0,
            'max_completion_tokens' => 8192,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Transcribe every visible word from this document page exactly. Preserve question numbers, options A-E, ticks, asterisks, underlines, and answer keys. Return plain text only.',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . $b64,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $res = groq_http('https://api.groq.com/openai/v1/chat/completions', $payload, 120);
        if (empty($res['ok'])) {
            return ['ok' => false, 'error' => $res['error'] ?: 'Groq vision OCR failed'];
        }
        $page = groq_output_text($res['json']);
        if ($page !== '') {
            $parts[] = $page;
        }
    }
    $text = groq_clean_text(implode("\n\n", $parts));
    if ($text === '') {
        return ['ok' => false, 'error' => 'Groq vision OCR returned no text'];
    }
    return ['ok' => true, 'text' => $text];
}

function groq_extract_document_text(string $filePath, string $displayName): array {
    if (!is_file($filePath) || filesize($filePath) < 1) {
        return ['ok' => false, 'error' => 'Uploaded file could not be read'];
    }
    [$workPath, $copied] = groq_work_copy($filePath, $displayName);
    $ext = strtolower(pathinfo($displayName, PATHINFO_EXTENSION));
    $text = '';
    $images = [];
    if ($ext === 'pdf') {
        $text = groq_pdf_text($workPath);
        if (!groq_text_is_useful($text)) {
            $images = groq_pdf_page_images($workPath);
        }
    } elseif ($ext === 'docx') {
        $text = groq_docx_text($workPath);
        if (!groq_text_is_useful($text)) {
            $images = groq_zip_images($workPath, ['word/media/']);
        }
    } elseif ($ext === 'odt') {
        $text = groq_odt_text($workPath);
        if (!groq_text_is_useful($text)) {
            $images = groq_zip_images($workPath, ['Pictures/', 'media/']);
        }
    } elseif (in_array($ext, ['xlsx', 'ods'], true)) {
        $text = $ext === 'xlsx' ? groq_xlsx_text($workPath) : groq_odt_text($workPath);
    } elseif (in_array($ext, ['txt', 'csv'], true)) {
        $raw = file_get_contents($workPath);
        $text = groq_clean_text($raw === false ? '' : $raw);
    } elseif (in_array($ext, ['doc', 'xls'], true)) {
        $text = groq_binary_office_text($workPath);
    } else {
        if ($copied) {
            @unlink($workPath);
        }
        return ['ok' => false, 'error' => 'Unsupported file type. Upload PDF, DOCX, DOC, XLSX, XLS, or TXT'];
    }
    if (!groq_text_is_useful($text)) {
        if ($images === []) {
            if ($copied) {
                @unlink($workPath);
            }
            return ['ok' => false, 'error' => 'No readable text or page images were found. Upload a text PDF/DOCX or a scanned PDF with visible pages'];
        }
        $ocr = groq_ocr_images($images);
        if (empty($ocr['ok'])) {
            if ($copied) {
                @unlink($workPath);
            }
            return ['ok' => false, 'error' => $ocr['error'] ?: 'Groq vision OCR failed'];
        }
        $text = (string) $ocr['text'];
    }
    if ($copied) {
        @unlink($workPath);
    }
    if (!groq_text_is_useful($text)) {
        return ['ok' => false, 'error' => 'Could not read text from the document after OCR'];
=======
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
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
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
<<<<<<< HEAD
    if (is_array($content)) {
        $parts = [];
        foreach ($content as $item) {
            if (is_string($item) && $item !== '') {
                $parts[] = $item;
            } elseif (is_array($item) && !empty($item['text']) && is_string($item['text'])) {
                $parts[] = $item['text'];
            }
        }
        $joined = trim(implode("\n", $parts));
        if ($joined !== '') {
            return $joined;
        }
    }
=======
>>>>>>> 577fa60932f24e4df65a88e28187ee1b0012f1a1
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
