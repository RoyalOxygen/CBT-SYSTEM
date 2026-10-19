<?php
/**
 * CBT System - Tables participating in online/offline synchronisation.
 *
 * priority        : lower numbers are applied first (parents before children)
 * fks             : column => referenced table, resolved through sync_id_map
 * natural_key     : columns used to de-duplicate records created independently
 * push            : leaf-to-hub changes are pushed (default true)
 */

if (!defined('CBT_SYSTEM')) {
    die('Unauthorized access');
}

return [
    'settings' => [
        'priority' => 5,
        'natural_key' => ['setting_key'],
    ],

    'departments' => ['priority' => 10],
    'levels' => ['priority' => 10],
    'semesters' => ['priority' => 10],
    'sessions' => ['priority' => 10],

    'exams' => [
        'priority' => 15,
        'fks' => [
            'department_id' => 'departments',
            'level_id' => 'levels',
            'semester_id' => 'semesters',
            'session_id' => 'sessions',
        ],
    ],
    'composite_exams' => [
        'priority' => 15,
        'fks' => [
            'department_id' => 'departments',
            'level_id' => 'levels',
            'semester_id' => 'semesters',
            'session_id' => 'sessions',
        ],
    ],

    'students' => [
        'priority' => 20,
        'natural_key' => ['matric_number'],
        'fks' => [
            'department_id' => 'departments',
            'level_id' => 'levels',
            'semester_id' => 'semesters',
            'session_id' => 'sessions',
            'current_exam_id' => 'exams',
        ],
    ],

    'questions' => [
        'priority' => 25,
        'fks' => ['exam_id' => 'exams'],
    ],
    'exam_batches' => [
        'priority' => 25,
        'fks' => ['exam_id' => 'exams'],
    ],
    'composite_questions' => [
        'priority' => 25,
        'fks' => ['composite_exam_id' => 'composite_exams'],
    ],
    'composite_sub_questions' => [
        'priority' => 26,
        'fks' => ['composite_question_id' => 'composite_questions'],
    ],
    'composite_sub_options' => [
        'priority' => 27,
        'fks' => ['composite_sub_question_id' => 'composite_sub_questions'],
    ],

    'exam_students' => [
        'priority' => 30,
        'natural_key' => ['exam_id', 'student_id'],
        'fks' => [
            'exam_id' => 'exams',
            'batch_id' => 'exam_batches',
            'student_id' => 'students',
        ],
    ],
    'composite_exam_students' => [
        'priority' => 30,
        'natural_key' => ['composite_exam_id', 'student_id'],
        'fks' => [
            'composite_exam_id' => 'composite_exams',
            'student_id' => 'students',
        ],
    ],

    'exam_attempts' => [
        'priority' => 40,
        'fks' => [
            'exam_id' => 'exams',
            'batch_id' => 'exam_batches',
            'student_id' => 'students',
        ],
    ],
    'composite_exam_attempts' => [
        'priority' => 40,
        'fks' => [
            'composite_exam_id' => 'composite_exams',
            'student_id' => 'students',
        ],
    ],

    'answers' => [
        'priority' => 45,
        'natural_key' => ['attempt_id', 'question_id'],
        'fks' => [
            'attempt_id' => 'exam_attempts',
            'question_id' => 'questions',
        ],
    ],
    'composite_exam_answers' => [
        'priority' => 45,
        'natural_key' => ['attempt_id', 'composite_sub_question_id'],
        'fks' => [
            'attempt_id' => 'composite_exam_attempts',
            'composite_sub_question_id' => 'composite_sub_questions',
        ],
    ],

    'results' => [
        'priority' => 50,
        'natural_key' => ['attempt_id'],
        'fks' => [
            'exam_id' => 'exams',
            'student_id' => 'students',
            'attempt_id' => 'exam_attempts',
        ],
    ],
    'composite_exam_results' => [
        'priority' => 50,
        'natural_key' => ['attempt_id'],
        'fks' => [
            'composite_exam_id' => 'composite_exams',
            'student_id' => 'students',
            'attempt_id' => 'composite_exam_attempts',
        ],
    ],

    'fingerprint_templates' => [
        'priority' => 35,
        'natural_key' => ['student_id'],
        'fks' => ['student_id' => 'students'],
    ],

    'time_extensions' => [
        'priority' => 55,
        'fks' => [
            'exam_id' => 'exams',
            'student_id' => 'students',
            'attempt_id' => 'exam_attempts',
        ],
    ],
];
