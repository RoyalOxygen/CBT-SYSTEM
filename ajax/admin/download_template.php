<?php
define('CBT_SYSTEM', true);
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_import_template.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['matric_number', 'first_name', 'last_name', 'other_name', 'email', 'phone', 'gender', 'date_of_birth']);
fputcsv($output, ['ABC/2024/001', 'John', 'Doe', 'Smith', 'john@email.com', '08012345678', 'Male', '2000-01-15']);
fputcsv($output, ['ABC/2024/002', 'Jane', 'Smith', '', 'jane@email.com', '08087654321', 'Female', '2001-03-20']);
fclose($output);
exit;
