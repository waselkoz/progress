<?php
$files = [
    'components/admin_notes.php',
    'components/teacher_grade_input.php',
    'components/layout_header.php',
    'components/admin_courses.php',
    'components/teacher_grades.php',
    'components/teacher_attendance.php',
    'components/academic_transcript.php',
    'components/admin_system.php',
    'components/admin_users.php',
    'components/admin_schedules.php',
    'components/dashboard.php',
    'components/exam_grades.php',
    'components/attendance.php',
    'components/my_modules.php',
    'components/profile.php',
    'components/timetable.php',
    'components/exams.php',
];

$all_ok = true;
foreach ($files as $file) {
    $output = [];
    $code = 0;
    exec("C:\\xampp\\php\\php.exe -l $file 2>&1", $output, $code);
    $status = $code === 0 ? 'OK' : 'ERROR';
    echo "[$status] $file: " . implode(' ', $output) . "\n";
    if ($code !== 0) $all_ok = false;
}
echo "\n" . ($all_ok ? "All files passed syntax check." : "Some files have errors! Review above.") . "\n";
