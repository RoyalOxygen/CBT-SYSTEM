<?php
/**
 * CBT System - Export Results
 * Supports: Excel, CSV, PDF (via HTML print view)
 * 
 * Columns: S/N, Full Name, Matric Number, Level, Exam Code, 
 *          Score (percentage), Grade, Remark
 */

define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';

if (!isAdminLoggedIn()) {
    exit('Not authenticated');
}

try {
    $db = getDB();
    $examId = intval($_GET['exam_id'] ?? 0);
    $examType = $_GET['exam_type'] ?? '';
    $published = $_GET['published'] ?? '';
    $resultType = $_GET['result_type'] ?? 'all';
    $format = $_GET['format'] ?? 'excel';
    
    $isPdf = ($format === 'pdf');
    $isExcel = ($format === 'excel');
    
    $allData = [];
    
    // =====================================================
    // GET REGULAR RESULTS
    // =====================================================
    if ($resultType === 'all' || $resultType === 'regular') {
        $where = ['1=1'];
        $params = [];
        
        if ($examId && $examType === 'regular') { 
            $where[] = "r.exam_id = ?"; 
            $params[] = $examId; 
        }
        if ($published !== '') { 
            $where[] = "r.published = ?"; 
            $params[] = $published; 
        }
        
        $whereStr = implode(' AND ', $where);
        
        $stmt = $db->prepare("
            SELECT 
                r.correct,
                r.wrong,
                r.total_questions,
                r.answered,
                r.score,
                r.total_marks,
                r.percentage,
                r.grade,
                r.remark,
                r.published,
                e.exam_code,
                e.exam_title,
                s.matric_number,
                s.first_name,
                s.last_name,
                s.other_name,
                l.level_name
            FROM results r
            JOIN exams e ON r.exam_id = e.id
            JOIN students s ON r.student_id = s.id
            LEFT JOIN levels l ON e.level_id = l.id
            WHERE $whereStr
            ORDER BY r.percentage DESC
        ");
        $stmt->execute($params);
        $regularRows = $stmt->fetchAll();
        
        foreach ($regularRows as $row) {
            $row['result_source'] = 'Regular';
            $allData[] = $row;
        }
    }
    
    // =====================================================
    // GET COMPOSITE RESULTS
    // =====================================================
    if ($resultType === 'all' || $resultType === 'composite') {
        $where = ['1=1'];
        $params = [];
        
        if ($examId && $examType === 'composite') { 
            $where[] = "r.composite_exam_id = ?"; 
            $params[] = $examId; 
        }
        if ($published !== '') { 
            $where[] = "r.published = ?"; 
            $params[] = $published; 
        }
        
        $whereStr = implode(' AND ', $where);
        
        $stmt = $db->prepare("
            SELECT 
                r.correct,
                r.wrong,
                r.total_sub_questions as total_questions,
                r.answered,
                r.score,
                r.total_marks,
                r.percentage,
                r.grade,
                r.remark,
                r.published,
                ce.exam_code,
                ce.exam_title,
                s.matric_number,
                s.first_name,
                s.last_name,
                s.other_name,
                l.level_name
            FROM composite_exam_results r
            JOIN composite_exams ce ON r.composite_exam_id = ce.id
            JOIN students s ON r.student_id = s.id
            LEFT JOIN levels l ON ce.level_id = l.id
            WHERE $whereStr
            ORDER BY r.percentage DESC
        ");
        $stmt->execute($params);
        $compositeRows = $stmt->fetchAll();
        
        foreach ($compositeRows as $row) {
            $row['result_source'] = 'Composite';
            $allData[] = $row;
        }
    }
    
    if (empty($allData)) {
        exit('No results found to export');
    }
    
    // Sort by percentage DESC
    usort($allData, function($a, $b) {
        return floatval($b['percentage'] ?? 0) <=> floatval($a['percentage'] ?? 0);
    });
    
    // Get exam info for display
    $examInfo = null;
    if ($examId && $examType) {
        $table = ($examType === 'composite') ? 'composite_exams' : 'exams';
        $stmt = $db->prepare("SELECT exam_code, exam_title FROM $table WHERE id = ?");
        $stmt->execute([$examId]);
        $examInfo = $stmt->fetch();
    }
    
    // =====================================================
    // ROUTE TO EXPORT TYPE
    // =====================================================
    if ($isPdf) {
        exportPDF($allData, $examInfo, $resultType);
    } elseif ($isExcel) {
        exportExcel($allData);
    } else {
        exportCSV($allData);
    }
    
} catch (PDOException $e) {
    error_log("Export results error: " . $e->getMessage());
    exit('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Export results general error: " . $e->getMessage());
    exit('Error: ' . $e->getMessage());
}

// =====================================================
// HELPER: BUILD FULL NAME
// =====================================================
function buildFullName($row) {
    $firstName = trim($row['first_name'] ?? '');
    $lastName = trim($row['last_name'] ?? '');
    $otherName = trim($row['other_name'] ?? '');
    return trim($lastName . ' ' . $firstName . ($otherName ? ' ' . $otherName : ''));
}

// =====================================================
// EXPORT EXCEL
// =====================================================
function exportExcel($data) {
    $headers = [
        'S/N', 'Full Name', 'Matric Number', 'Level', 'Exam Code',
        'Score', 'Grade', 'Remark'
    ];
    
    $rows = [];
    $counter = 1;
    foreach ($data as $row) {
        $rows[] = [
            $counter++,
            buildFullName($row),
            $row['matric_number'] ?? 'N/A',
            $row['level_name'] ?? 'N/A',
            $row['exam_code'] ?? 'N/A',
            number_format(floatval($row['percentage'] ?? 0), 2),
            $row['grade'] ?? 'N/A',
            $row['remark'] ?? 'N/A'
        ];
    }
    
    $filename = 'results_' . date('Y-m-d_His');
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, $headers, "\t");
    foreach ($rows as $r) fputcsv($output, $r, "\t");
    fclose($output);
    exit;
}

// =====================================================
// EXPORT CSV
// =====================================================
function exportCSV($data) {
    $headers = [
        'S/N', 'Full Name', 'Matric Number', 'Level', 'Exam Code',
        'Score', 'Grade', 'Remark'
    ];
    
    $rows = [];
    $counter = 1;
    foreach ($data as $row) {
        $rows[] = [
            $counter++,
            buildFullName($row),
            $row['matric_number'] ?? 'N/A',
            $row['level_name'] ?? 'N/A',
            $row['exam_code'] ?? 'N/A',
            number_format(floatval($row['percentage'] ?? 0), 2),
            $row['grade'] ?? 'N/A',
            $row['remark'] ?? 'N/A'
        ];
    }
    
    $filename = 'results_' . date('Y-m-d_His');
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, $headers);
    foreach ($rows as $r) fputcsv($output, $r);
    fclose($output);
    exit;
}

// =====================================================
// EXPORT PDF (via print view)
// =====================================================
function exportPDF($data, $examInfo, $resultType) {
    $totalStudents = count($data);
    $avgScore = 0;
    if ($totalStudents > 0) {
        $sum = 0;
        foreach ($data as $row) $sum += floatval($row['percentage'] ?? 0);
        $avgScore = $sum / $totalStudents;
    }
    
    $typeLabel = 'All Exam Types';
    if ($resultType === 'regular') $typeLabel = 'Regular Exams';
    elseif ($resultType === 'composite') $typeLabel = 'Composite Exams';
    
    $generatedAt = date('F d, Y \a\t h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Results Report</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #e9ecef;
        padding: 20px;
        color: #1a1a2e;
        font-size: 12px;
    }
    
    /* Action bar - hidden when printing */
    .action-bar {
        background: white;
        border-radius: 12px;
        padding: 16px 24px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }
    .action-bar .info {
        font-size: 13px;
        color: #6c757d;
    }
    .action-bar .info strong {
        color: #1a1a2e;
    }
    .action-bar .buttons {
        display: flex;
        gap: 10px;
    }
    .btn-print {
        background: #1a73e8;
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-print:hover {
        background: #1557b0;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(26,115,232,0.3);
    }
    .btn-close {
        background: #f1f3f4;
        color: #5f6368;
        border: none;
        padding: 10px 24px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
    }
    .btn-close:hover {
        background: #e8eaed;
    }
    
    /* PDF Document */
    .pdf-document {
        background: white;
        max-width: 1000px;
        margin: 0 auto;
        padding: 40px 50px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
    
    /* Header */
    .pdf-header {
        text-align: center;
        border-bottom: 4px solid #1a1a2e;
        padding-bottom: 20px;
        margin-bottom: 24px;
    }
    .pdf-header .university-name {
        font-size: 22px;
        font-weight: 700;
        color: #1a1a2e;
        letter-spacing: 1px;
        margin-bottom: 6px;
    }
    .pdf-header .report-title {
        font-size: 16px;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 4px;
    }
    .pdf-header .report-subtitle {
        font-size: 12px;
        color: #9aa0a6;
    }
    .pdf-header .gold-line {
        width: 100px;
        height: 3px;
        background: #e8c84c;
        margin: 12px auto 0;
        border-radius: 2px;
    }
    
    /* Info Grid */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 30px;
        margin-bottom: 24px;
        padding: 16px 20px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #e8c84c;
    }
    .info-item {
        display: flex;
        gap: 8px;
        font-size: 12px;
    }
    .info-item .label {
        color: #6c757d;
        font-weight: 600;
        min-width: 100px;
    }
    .info-item .value {
        color: #1a1a2e;
        font-weight: 600;
    }
    
    /* Summary Stats */
    .summary-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }
    .summary-stat {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 8px;
        text-align: center;
        border: 1px solid #e9ecef;
    }
    .summary-stat .stat-value {
        font-size: 22px;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1.2;
    }
    .summary-stat .stat-label {
        font-size: 10px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 2px;
    }
    
    /* Table */
    .pdf-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 24px;
    }
    .pdf-table thead th {
        background: #1a1a2e;
        color: white;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 10px 8px;
        text-align: left;
        border: none;
    }
    .pdf-table thead th.center {
        text-align: center;
    }
    .pdf-table tbody td {
        padding: 9px 8px;
        font-size: 11.5px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }
    .pdf-table tbody td.center {
        text-align: center;
    }
    .pdf-table tbody tr:nth-child(even) {
        background: #fafbfc;
    }
    .pdf-table tbody tr:hover {
        background: #f0f6ff;
    }
    .pdf-table .matric {
        font-family: 'Courier New', monospace;
        font-size: 11px;
        font-weight: 600;
        color: #0f3460;
    }
    .pdf-table .student-name {
        font-weight: 600;
        color: #1a1a2e;
    }
    .pdf-table .exam-code {
        font-family: 'Courier New', monospace;
        font-size: 11px;
        font-weight: 600;
        color: #0f3460;
    }
    .pdf-table .score {
        font-weight: 700;
        color: #1a73e8;
    }
    .grade-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 11px;
        min-width: 24px;
        text-align: center;
    }
    .grade-A { background: #e6f4ea; color: #34a853; }
    .grade-B { background: #e6f4ea; color: #34a853; }
    .grade-C { background: #e8f0fe; color: #1a73e8; }
    .grade-D { background: #fef9e7; color: #f9ab00; }
    .grade-E { background: #fef9e7; color: #f9ab00; }
    .grade-F { background: #fce8e6; color: #ea4335; }
    
    /* Footer */
    .pdf-footer {
        margin-top: 30px;
        padding-top: 16px;
        border-top: 2px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 10px;
        color: #9aa0a6;
    }
    .pdf-footer .page-info {
        font-weight: 600;
    }
    
    /* Print Styles */
    @media print {
        body {
            background: white;
            padding: 0;
            font-size: 10px;
        }
        .action-bar { display: none !important; }
        .pdf-document {
            box-shadow: none;
            padding: 15px;
            max-width: 100%;
            border-radius: 0;
        }
        .pdf-header { padding-bottom: 12px; margin-bottom: 16px; }
        .pdf-header .university-name { font-size: 18px; }
        .pdf-table { font-size: 9.5px; }
        .pdf-table thead th { font-size: 9px; padding: 6px 4px; }
        .pdf-table tbody td { padding: 5px 4px; font-size: 9.5px; }
        .summary-stat .stat-value { font-size: 16px; }
        .pdf-table { page-break-inside: auto; }
        .pdf-table tr { page-break-inside: avoid; page-break-after: auto; }
        .pdf-table thead { display: table-header-group; }
        @page {
            size: A4 portrait;
            margin: 12mm 10mm;
        }
    }
    
    @media (max-width: 768px) {
        .pdf-document { padding: 20px; }
        .info-grid { grid-template-columns: 1fr; }
        .summary-stats { grid-template-columns: 1fr; }
        .action-bar { flex-direction: column; gap: 12px; }
        .pdf-table { font-size: 10px; }
        .pdf-table thead th { font-size: 9px; padding: 6px 4px; }
        .pdf-table tbody td { padding: 6px 4px; font-size: 10px; }
    }
</style>
</head>
<body>

<!-- Action Bar (hidden when printing) -->
<div class="action-bar">
    <div class="info">
        <strong>Results Report</strong> — <?php echo $totalStudents; ?> record(s)
        <?php if ($examInfo): ?>
            <br><small><?php echo e($examInfo['exam_code'] . ' - ' . $examInfo['exam_title']); ?></small>
        <?php endif; ?>
    </div>
    <div class="buttons">
        <button class="btn-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Save as PDF / Print
        </button>
        <button class="btn-close" onclick="window.close()">Close</button>
    </div>
</div>

<!-- PDF Document -->
<div class="pdf-document">
    
    <!-- Header -->
    <div class="pdf-header">
        <div class="university-name">TARABA STATE UNIVERSITY</div>
        <div class="report-title">Examination Results Report</div>
        <div class="report-subtitle">
            <?php echo e($typeLabel); ?>
            <?php if ($examInfo): ?>
                &nbsp;•&nbsp; <?php echo e($examInfo['exam_code']); ?>
            <?php endif; ?>
        </div>
        <div class="gold-line"></div>
    </div>
    
    <!-- Info -->
    <div class="info-grid">
        <div class="info-item">
            <span class="label">Report Type:</span>
            <span class="value"><?php echo e($typeLabel); ?></span>
        </div>
        <div class="info-item">
            <span class="label">Generated:</span>
            <span class="value"><?php echo $generatedAt; ?></span>
        </div>
        <?php if ($examInfo): ?>
        <div class="info-item">
            <span class="label">Exam Code:</span>
            <span class="value"><?php echo e($examInfo['exam_code']); ?></span>
        </div>
        <div class="info-item">
            <span class="label">Exam Title:</span>
            <span class="value"><?php echo e($examInfo['exam_title']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Summary Stats -->
    <div class="summary-stats">
        <div class="summary-stat">
            <div class="stat-value"><?php echo number_format($totalStudents); ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="summary-stat">
            <div class="stat-value"><?php echo number_format($avgScore, 1); ?>%</div>
            <div class="stat-label">Average Score</div>
        </div>
        <div class="summary-stat">
            <div class="stat-value">
                <?php 
                $passedCount = 0;
                foreach ($data as $row) {
                    if (floatval($row['percentage'] ?? 0) >= 40) $passedCount++;
                }
                echo $passedCount;
                ?>
            </div>
            <div class="stat-label">Passed (≥40%)</div>
        </div>
    </div>
    
    <!-- Results Table -->
    <table class="pdf-table">
        <thead>
            <tr>
                <th style="width: 45px;">S/N</th>
                <th>Full Name</th>
                <th style="width: 140px;">Matric Number</th>
                <th style="width: 90px;">Level</th>
                <th style="width: 100px;">Exam Code</th>
                <th class="center" style="width: 70px;">Score</th>
                <th class="center" style="width: 60px;">Grade</th>
                <th style="width: 100px;">Remark</th>
            </tr>
        </thead>
        <tbody>
            <?php $counter = 1; foreach ($data as $row): 
                $percentage = floatval($row['percentage'] ?? 0);
                $grade = $row['grade'] ?? 'F';
                $remark = $row['remark'] ?? 'N/A';
            ?>
            <tr>
                <td class="center"><?php echo $counter++; ?></td>
                <td class="student-name"><?php echo e(buildFullName($row)); ?></td>
                <td class="matric"><?php echo e($row['matric_number'] ?? 'N/A'); ?></td>
                <td><?php echo e($row['level_name'] ?? 'N/A'); ?></td>
                <td class="exam-code"><?php echo e($row['exam_code'] ?? 'N/A'); ?></td>
                <td class="center score"><?php echo number_format($percentage, 2); ?>%</td>
                <td class="center">
                    <span class="grade-badge grade-<?php echo e(substr($grade, 0, 1)); ?>">
                        <?php echo e($grade); ?>
                    </span>
                </td>
                <td><?php echo e($remark); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Footer -->
    <div class="pdf-footer">
        <div>
            <strong>Taraba State University</strong> — CBT System
        </div>
        <div class="page-info">
            Generated on <?php echo date('M d, Y'); ?> • <?php echo $totalStudents; ?> record(s)
        </div>
    </div>
    
</div>

<script>
// Auto-trigger print dialog when page loads (optional)
// Uncomment the line below if you want print dialog to open automatically:
// window.addEventListener('load', function() { setTimeout(() => window.print(), 500); });
</script>

</body>
</html>
<?php
}
?>