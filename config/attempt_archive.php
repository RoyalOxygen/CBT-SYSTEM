<?php
if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

function attempt_archive_dir(): string {
    $dir = dirname(__DIR__) . '/storage/exam_archives';
    if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create exam archive folder');
    }
    return $dir;
}

function attempt_archive_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('PDO SQLite is not available');
    }
    $path = attempt_archive_dir() . '/attempts.sqlite';
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS attempt_snapshots (
            attempt_id INTEGER PRIMARY KEY,
            exam_id INTEGER NOT NULL,
            student_id INTEGER NOT NULL,
            reason TEXT NOT NULL,
            question_ids TEXT,
            questions_json TEXT NOT NULL,
            answers_json TEXT NOT NULL,
            attempt_json TEXT,
            created_at TEXT NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS composite_attempt_snapshots (
            attempt_id INTEGER PRIMARY KEY,
            exam_id INTEGER NOT NULL,
            student_id INTEGER NOT NULL,
            reason TEXT NOT NULL,
            question_ids TEXT,
            answers_json TEXT NOT NULL,
            attempt_json TEXT,
            created_at TEXT NOT NULL
        )'
    );
    return $pdo;
}

function attempt_archive_resumable_statuses(): array {
    return ['timed_out', 'auto_submitted'];
}

function attempt_archive_save(PDO $db, int $attemptId, string $reason): bool {
    $stmt = $db->prepare('SELECT * FROM exam_attempts WHERE id = ?');
    $stmt->execute([$attemptId]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) {
        return false;
    }

    $ids = [];
    if (!empty($attempt['question_ids'])) {
        $decoded = json_decode((string) $attempt['question_ids'], true);
        if (is_array($decoded)) {
            $ids = array_values(array_filter(array_map('intval', $decoded)));
        }
    }

    $questions = [];
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $qStmt = $db->prepare("SELECT * FROM questions WHERE id IN ($placeholders)");
        $qStmt->execute($ids);
        $map = [];
        foreach ($qStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['id']] = $row;
        }
        foreach ($ids as $id) {
            if (isset($map[$id])) {
                $questions[] = $map[$id];
            }
        }
    }

    $aStmt = $db->prepare('SELECT * FROM answers WHERE attempt_id = ?');
    $aStmt->execute([$attemptId]);
    $answers = $aStmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlite = attempt_archive_db();
    $sqlite->prepare(
        'INSERT INTO attempt_snapshots (attempt_id, exam_id, student_id, reason, question_ids, questions_json, answers_json, attempt_json, created_at)
         VALUES (:attempt_id, :exam_id, :student_id, :reason, :question_ids, :questions_json, :answers_json, :attempt_json, :created_at)
         ON CONFLICT(attempt_id) DO UPDATE SET
            exam_id = excluded.exam_id,
            student_id = excluded.student_id,
            reason = excluded.reason,
            question_ids = excluded.question_ids,
            questions_json = excluded.questions_json,
            answers_json = excluded.answers_json,
            attempt_json = excluded.attempt_json,
            created_at = excluded.created_at'
    )->execute([
        ':attempt_id' => $attemptId,
        ':exam_id' => (int) $attempt['exam_id'],
        ':student_id' => (int) $attempt['student_id'],
        ':reason' => $reason,
        ':question_ids' => json_encode($ids),
        ':questions_json' => json_encode($questions),
        ':answers_json' => json_encode($answers),
        ':attempt_json' => json_encode($attempt),
        ':created_at' => date('c'),
    ]);
    return true;
}

function attempt_archive_has(int $attemptId): bool {
    try {
        $stmt = attempt_archive_db()->prepare('SELECT attempt_id FROM attempt_snapshots WHERE attempt_id = ?');
        $stmt->execute([$attemptId]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('attempt_archive_has: ' . $e->getMessage());
        return false;
    }
}

function attempt_archive_load(int $attemptId): ?array {
    $stmt = attempt_archive_db()->prepare('SELECT * FROM attempt_snapshots WHERE attempt_id = ?');
    $stmt->execute([$attemptId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function attempt_archive_delete(int $attemptId): void {
    attempt_archive_db()->prepare('DELETE FROM attempt_snapshots WHERE attempt_id = ?')->execute([$attemptId]);
}

function attempt_archive_write_result(PDO $db, int $attemptId): void {
    $stmt = $db->prepare(
        'SELECT ea.*, e.overall_score
         FROM exam_attempts ea
         JOIN exams e ON ea.exam_id = e.id
         WHERE ea.id = ?'
    );
    $stmt->execute([$attemptId]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) {
        return;
    }
    $answersStmt = $db->prepare(
        'SELECT a.selected_answer, q.correct_answer, q.marks
         FROM answers a
         JOIN questions q ON a.question_id = q.id
         WHERE a.attempt_id = ?'
    );
    $answersStmt->execute([$attemptId]);
    $answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalQuestions = (int) ($attempt['total_questions'] ?? 0);
    $answered = count($answers);
    $correct = 0;
    $score = 0.0;
    $marksAvailable = 0.0;
    foreach ($answers as $answer) {
        $marksAvailable += (float) $answer['marks'];
        if (($answer['selected_answer'] ?? '') === ($answer['correct_answer'] ?? '')) {
            $correct++;
            $score += (float) $answer['marks'];
        }
    }
    $wrong = $answered - $correct;
    $overall = function_exists('examOverallScore') ? examOverallScore($attempt['overall_score'] ?? 100) : 100;
    if (function_exists('calculateScaledExamScore')) {
        $computed = calculateScaledExamScore($correct, $totalQuestions, $score, $marksAvailable, $overall);
        $scoreDisplay = $computed['score'];
        $totalMarks = $computed['total_marks'];
        $percentage = $computed['percentage'];
    } else {
        $scoreDisplay = $score;
        $totalMarks = $marksAvailable;
        $percentage = $totalQuestions > 0 ? ($correct / $totalQuestions) * 100 : 0;
    }
    $grade = function_exists('getGrade') ? getGrade($percentage) : ['grade' => '', 'remark' => ''];
    $db->prepare(
        'INSERT INTO results (exam_id, student_id, attempt_id, total_questions, answered, correct, wrong, score, total_marks, percentage, grade, remark, total_questions_score, published)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
         ON DUPLICATE KEY UPDATE attempt_id = VALUES(attempt_id), total_questions = VALUES(total_questions), answered = VALUES(answered), correct = VALUES(correct), wrong = VALUES(wrong), score = VALUES(score), total_marks = VALUES(total_marks), percentage = VALUES(percentage), grade = VALUES(grade), remark = VALUES(remark), total_questions_score = VALUES(total_questions_score), published = 0'
    )->execute([
        (int) $attempt['exam_id'],
        (int) $attempt['student_id'],
        $attemptId,
        $totalQuestions,
        $answered,
        $correct,
        $wrong,
        $scoreDisplay,
        $totalMarks,
        $percentage,
        $grade['grade'] ?? '',
        $grade['remark'] ?? '',
        $correct,
    ]);
}

function attempt_archive_restore(PDO $db, int $attemptId, int $extraMinutes = 0): bool {
    $snap = attempt_archive_load($attemptId);
    if (!$snap) {
        return false;
    }

    $stmt = $db->prepare('SELECT * FROM exam_attempts WHERE id = ?');
    $stmt->execute([$attemptId]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) {
        return false;
    }

    $answers = json_decode((string) $snap['answers_json'], true);
    if (!is_array($answers)) {
        $answers = [];
    }

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM answers WHERE attempt_id = ?')->execute([$attemptId]);
        $ins = $db->prepare(
            'INSERT INTO answers (attempt_id, question_id, selected_answer, is_correct, marks_obtained, answered_at)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE selected_answer = VALUES(selected_answer), is_correct = VALUES(is_correct), marks_obtained = VALUES(marks_obtained), answered_at = VALUES(answered_at)'
        );
        foreach ($answers as $answer) {
            if (!is_array($answer) || empty($answer['question_id'])) {
                continue;
            }
            $ins->execute([
                $attemptId,
                (int) $answer['question_id'],
                $answer['selected_answer'] ?? null,
                isset($answer['is_correct']) ? (int) $answer['is_correct'] : null,
                $answer['marks_obtained'] ?? 0,
                $answer['answered_at'] ?? date('Y-m-d H:i:s'),
            ]);
        }

        $minutes = max(1, $extraMinutes);
        $examDuration = (int) ($attempt['original_duration'] ?? 0);
        if ($examDuration < 1) {
            $durStmt = $db->prepare('SELECT duration FROM exams WHERE id = ?');
            $durStmt->execute([(int) $attempt['exam_id']]);
            $examDuration = max(0, (int) $durStmt->fetchColumn());
        }
        $db->prepare(
            'UPDATE exam_attempts
             SET status = \'in_progress\',
                 end_time = NULL,
                 submission_method = NULL,
                 start_time = DATE_SUB(NOW(), INTERVAL ? MINUTE),
                 time_extension_minutes = ?,
                 original_duration = ?,
                 score = 0,
                 percentage = 0,
                 grade = NULL
             WHERE id = ?'
        )->execute([$examDuration, $minutes, $examDuration, $attemptId]);

        $db->prepare('DELETE FROM results WHERE attempt_id = ? OR (exam_id = ? AND student_id = ?)')
            ->execute([$attemptId, (int) $attempt['exam_id'], (int) $attempt['student_id']]);
        $db->prepare('UPDATE exam_students SET status = \'started\' WHERE exam_id = ? AND student_id = ?')
            ->execute([(int) $attempt['exam_id'], (int) $attempt['student_id']]);
        $db->prepare('UPDATE students SET current_exam_id = ? WHERE id = ?')
            ->execute([(int) $attempt['exam_id'], (int) $attempt['student_id']]);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    attempt_archive_delete($attemptId);
    return true;
}

function composite_attempt_archive_save(PDO $db, int $attemptId, string $reason): bool {
    $stmt = $db->prepare('SELECT * FROM composite_exam_attempts WHERE id = ?');
    $stmt->execute([$attemptId]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) {
        return false;
    }

    $aStmt = $db->prepare('SELECT * FROM composite_exam_answers WHERE attempt_id = ?');
    $aStmt->execute([$attemptId]);
    $answers = $aStmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlite = attempt_archive_db();
    $sqlite->prepare(
        'INSERT INTO composite_attempt_snapshots (attempt_id, exam_id, student_id, reason, question_ids, answers_json, attempt_json, created_at)
         VALUES (:attempt_id, :exam_id, :student_id, :reason, :question_ids, :answers_json, :attempt_json, :created_at)
         ON CONFLICT(attempt_id) DO UPDATE SET
            exam_id = excluded.exam_id,
            student_id = excluded.student_id,
            reason = excluded.reason,
            question_ids = excluded.question_ids,
            answers_json = excluded.answers_json,
            attempt_json = excluded.attempt_json,
            created_at = excluded.created_at'
    )->execute([
        ':attempt_id' => $attemptId,
        ':exam_id' => (int) $attempt['composite_exam_id'],
        ':student_id' => (int) $attempt['student_id'],
        ':reason' => $reason,
        ':question_ids' => $attempt['question_ids'] ?? '[]',
        ':answers_json' => json_encode($answers),
        ':attempt_json' => json_encode($attempt),
        ':created_at' => date('c'),
    ]);
    return true;
}

function composite_attempt_archive_load(int $attemptId): ?array {
    $stmt = attempt_archive_db()->prepare('SELECT * FROM composite_attempt_snapshots WHERE attempt_id = ?');
    $stmt->execute([$attemptId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function composite_attempt_archive_delete(int $attemptId): void {
    attempt_archive_db()->prepare('DELETE FROM composite_attempt_snapshots WHERE attempt_id = ?')->execute([$attemptId]);
}

function composite_attempt_archive_write_result(PDO $db, int $attemptId): void {
    $stmt = $db->prepare(
        'SELECT a.*, e.pass_mark, e.question_limit
         FROM composite_exam_attempts a
         JOIN composite_exams e ON a.composite_exam_id = e.id
         WHERE a.id = ?'
    );
    $stmt->execute([$attemptId]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) {
        return;
    }

    $lockedMainQuestionIds = [];
    if (!empty($attempt['question_ids'])) {
        $decoded = json_decode((string) $attempt['question_ids'], true);
        if (is_array($decoded) && !empty($decoded)) {
            $lockedMainQuestionIds = array_map('intval', $decoded);
        }
    }
    if ($lockedMainQuestionIds === []) {
        $fallbackStmt = $db->prepare(
            'SELECT id FROM composite_questions
             WHERE composite_exam_id = ? AND status = 1
             ORDER BY question_order, id
             LIMIT ?'
        );
        $fallbackStmt->execute([(int) $attempt['composite_exam_id'], intval($attempt['question_limit'] ?: 100)]);
        $lockedMainQuestionIds = array_map('intval', $fallbackStmt->fetchAll(PDO::FETCH_COLUMN));
    }
    if ($lockedMainQuestionIds === []) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($lockedMainQuestionIds), '?'));
    $subQStmt = $db->prepare(
        "SELECT sq.id AS sub_question_id, sq.marks, sq.correct_answer
         FROM composite_sub_questions sq
         WHERE sq.composite_question_id IN ($placeholders)"
    );
    $subQStmt->execute($lockedMainQuestionIds);
    $subQuestionMap = [];
    $totalMarksAvailable = 0.0;
    $totalSubQuestionsActual = 0;
    foreach ($subQStmt->fetchAll(PDO::FETCH_ASSOC) as $sq) {
        $subQuestionMap[(int) $sq['sub_question_id']] = [
            'marks' => (float) $sq['marks'],
            'correct_answer' => $sq['correct_answer'],
        ];
        $totalMarksAvailable += (float) $sq['marks'];
        $totalSubQuestionsActual++;
    }

    $answersStmt = $db->prepare('SELECT * FROM composite_exam_answers WHERE attempt_id = ?');
    $answersStmt->execute([$attemptId]);
    $answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);
    $answeredCount = 0;
    $correctCount = 0;
    $totalScore = 0.0;
    foreach ($answers as $a) {
        $subId = (int) ($a['composite_sub_question_id'] ?? 0);
        if (!isset($subQuestionMap[$subId])) {
            continue;
        }
        $answeredCount++;
        if (trim((string) ($a['selected_answer'] ?? '')) === (string) $subQuestionMap[$subId]['correct_answer']) {
            $correctCount++;
            $totalScore += $subQuestionMap[$subId]['marks'];
        }
    }
    $wrongCount = $answeredCount - $correctCount;
    $percentage = $totalMarksAvailable > 0 ? ($totalScore / $totalMarksAvailable) * 100 : 0;
    $grade = function_exists('getGrade') ? getGrade($percentage) : ['grade' => '', 'remark' => ''];

    $db->prepare(
        'UPDATE composite_exam_attempts SET
            score = ?, percentage = ?, correct_count = ?, answered_count = ?,
            total_questions = ?, total_sub_questions = ?
         WHERE id = ?'
    )->execute([
        $totalScore,
        $percentage,
        $correctCount,
        $answeredCount,
        count($lockedMainQuestionIds),
        $totalSubQuestionsActual,
        $attemptId,
    ]);

    $db->prepare(
        'INSERT INTO composite_exam_results
            (composite_exam_id, student_id, attempt_id, total_questions, total_sub_questions,
             answered, correct, wrong, score, total_marks, percentage, grade, remark, published)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
         ON DUPLICATE KEY UPDATE
            attempt_id = VALUES(attempt_id), total_questions = VALUES(total_questions),
            total_sub_questions = VALUES(total_sub_questions), answered = VALUES(answered),
            correct = VALUES(correct), wrong = VALUES(wrong), score = VALUES(score),
            total_marks = VALUES(total_marks), percentage = VALUES(percentage),
            grade = VALUES(grade), remark = VALUES(remark), published = 0'
    )->execute([
        (int) $attempt['composite_exam_id'],
        (int) $attempt['student_id'],
        $attemptId,
        count($lockedMainQuestionIds),
        $totalSubQuestionsActual,
        $answeredCount,
        $correctCount,
        $wrongCount,
        $totalScore,
        $totalMarksAvailable,
        $percentage,
        $grade['grade'] ?? '',
        $grade['remark'] ?? '',
    ]);
}

function composite_attempt_archive_restore(PDO $db, int $attemptId, int $extraMinutes = 0): bool {
    $snap = composite_attempt_archive_load($attemptId);
    if (!$snap) {
        return false;
    }

    $stmt = $db->prepare('SELECT * FROM composite_exam_attempts WHERE id = ?');
    $stmt->execute([$attemptId]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$attempt) {
        return false;
    }

    $answers = json_decode((string) $snap['answers_json'], true);
    if (!is_array($answers)) {
        $answers = [];
    }

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM composite_exam_answers WHERE attempt_id = ?')->execute([$attemptId]);
        $ins = $db->prepare(
            'INSERT INTO composite_exam_answers (attempt_id, composite_sub_question_id, selected_answer, is_correct, marks_obtained, answered_at)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE selected_answer = VALUES(selected_answer), is_correct = VALUES(is_correct), marks_obtained = VALUES(marks_obtained), answered_at = VALUES(answered_at)'
        );
        foreach ($answers as $answer) {
            if (!is_array($answer) || empty($answer['composite_sub_question_id'])) {
                continue;
            }
            $ins->execute([
                $attemptId,
                (int) $answer['composite_sub_question_id'],
                $answer['selected_answer'] ?? null,
                isset($answer['is_correct']) ? (int) $answer['is_correct'] : null,
                $answer['marks_obtained'] ?? 0,
                $answer['answered_at'] ?? date('Y-m-d H:i:s'),
            ]);
        }

        $minutes = max(1, $extraMinutes);
        $examDuration = (int) ($attempt['original_duration'] ?? 0);
        if ($examDuration < 1) {
            $durStmt = $db->prepare('SELECT duration FROM composite_exams WHERE id = ?');
            $durStmt->execute([(int) $attempt['composite_exam_id']]);
            $examDuration = max(0, (int) $durStmt->fetchColumn());
        }
        $db->prepare(
            "UPDATE composite_exam_attempts
             SET status = 'in_progress',
                 end_time = NULL,
                 submission_method = NULL,
                 start_time = DATE_SUB(NOW(), INTERVAL ? MINUTE),
                 time_extension_minutes = ?,
                 original_duration = ?,
                 score = 0,
                 percentage = 0,
                 correct_count = 0,
                 answered_count = 0
             WHERE id = ?"
        )->execute([$examDuration, $minutes, $examDuration, $attemptId]);

        $db->prepare('DELETE FROM composite_exam_results WHERE attempt_id = ? OR (composite_exam_id = ? AND student_id = ?)')
            ->execute([$attemptId, (int) $attempt['composite_exam_id'], (int) $attempt['student_id']]);
        $db->prepare("UPDATE composite_exam_students SET status = 'started' WHERE composite_exam_id = ? AND student_id = ?")
            ->execute([(int) $attempt['composite_exam_id'], (int) $attempt['student_id']]);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    composite_attempt_archive_delete($attemptId);
    return true;
}
