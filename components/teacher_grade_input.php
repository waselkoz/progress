<?php include 'layout_header.php'; 
if(($_SESSION['user_role'] ?? '') !== 'teacher') die("Access Denied.");

$class_id = $_GET['class_id'] ?? null;
if(!$class_id) die("No class selected.");


$stmt = $pdo->prepare("
    SELECT c.name, sec.name as sec_name, g.name as grp_name, ca.course_id, ca.section_id, ca.group_id 
    FROM course_assignment ca 
    JOIN courses c ON ca.course_id = c.id 
    JOIN section sec ON ca.section_id = sec.id 
    JOIN `group` g ON ca.group_id = g.id 
    WHERE ca.id = ? AND ca.teacher_id = ?
");
$stmt->execute([$class_id, $_SESSION['teacher_id']]);
$class_info = $stmt->fetch();

if(!$class_info) die("Invalid class assignment.");


$stmtGrading = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'grading_open'");
$grading_open_val = $stmtGrading->fetchColumn();
$grading_open = ($grading_open_val === false) ? true : ($grading_open_val == '1');


if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['grades'])) {
    if (!$grading_open) {
        die("Error: The Grading Platform is strictly locked by the Administration.");
    }
    foreach($_POST['grades'] as $student_id => $data) {
        $exam = $data['grade'] !== '' ? $data['grade'] : null;
        $td = $data['td'] !== '' ? $data['td'] : null;
        $tp = $data['tp'] !== '' ? $data['tp'] : null;
        $rattrapage = $data['rattrapage'] !== '' ? $data['rattrapage'] : null;
        $comment = $data['comment'] ?? null;
        $is_dette = isset($data['is_dette']) ? 1 : 0;
        
        $ca_sum = 0; $ca_count = 0;
        if ($td !== null) { $ca_sum += $td; $ca_count++; }
        if ($tp !== null) { $ca_sum += $tp; $ca_count++; }
        $ca_avg = ($ca_count > 0) ? ($ca_sum / $ca_count) : null;
        
        $main_exam = $exam;
        if ($rattrapage !== null && ($exam === null || $rattrapage > $exam)) {
            $main_exam = $rattrapage;
        }
        $final_grade = null;
        if ($main_exam !== null && $ca_avg !== null) {
            $final_grade = ($main_exam * 0.6) + ($ca_avg * 0.4);
        } elseif ($main_exam !== null) {
            $final_grade = $main_exam;
        } elseif ($ca_avg !== null) {
            $final_grade = $ca_avg;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO grades (student_id, course_id, grade, td_grade, tp_grade, final_grade, rattrapage_grade, is_dette, comment) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                grade = VALUES(grade), 
                td_grade = VALUES(td_grade),
                tp_grade = VALUES(tp_grade),
                final_grade = VALUES(final_grade),
                rattrapage_grade = VALUES(rattrapage_grade), 
                is_dette = VALUES(is_dette),
                comment = VALUES(comment), 
                last_updated = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([$student_id, $class_info['course_id'], $exam, $td, $tp, $final_grade, $rattrapage, $is_dette, $comment]);
    }
    echo "<script>window.onload = () => showToast('Grades successfully record for all components!');</script>";
}


$students = [];
$stmt = $pdo->prepare("
    SELECT s.id, u.name, s.student_number, g.grade, g.td_grade, g.tp_grade, g.final_grade, g.rattrapage_grade, g.is_dette, g.comment
    FROM student s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN grades g ON g.student_id = s.id AND g.course_id = ?
    WHERE s.group_id = ?
    ORDER BY u.name
");
$stmt->execute([$class_info['course_id'], $class_info['group_id']]);
$students = $stmt->fetchAll();

$stmtR = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'resit_period_open'");
$resit_open = $stmtR->fetchColumn() == '1';
?>

<div class="card-container">
    <div class="page-actions">
        <div>
            <h2 class="page-title">Input Grades: <?= htmlspecialchars($class_info['name']) ?></h2>
            <p class="helper-text" style="margin-bottom: 0;">Class Roster: <?= htmlspecialchars($class_info['sec_name']) ?> - <?= htmlspecialchars($class_info['grp_name']) ?></p>
        </div>
        <a href="teacher_grades.php" class="back-link">&larr; Back to Classes</a>
    </div>
    
    <form method="POST">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Exam (60%)</th>
                    <th>TD</th>
                    <th>TP</th>
                    <th>Resit (Rattrapage)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($students)): ?>
                <tr><td colspan="6">No students enrolled in this group.</td></tr>
                <?php else: ?>
                    <?php foreach($students as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong><br><em style="color:#888; font-size:11px;"><?= htmlspecialchars($s['student_number']) ?></em></td>
                        <td>
                            <input type="number" step="0.01" min="0" max="20" class="form-input" style="padding:8px; margin:0; <?= !$grading_open ? 'opacity:0.6; cursor:not-allowed;' : '' ?>"
                                   name="grades[<?= $s['id'] ?>][grade]" value="<?= htmlspecialchars($s['grade'] ?? '') ?>" placeholder="<?= $grading_open ? '-' : 'LOCKED' ?>" <?= $grading_open ? '' : 'readonly' ?>>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="20" class="form-input" style="padding:8px; margin:0; background: #f0f7ff; <?= !$grading_open ? 'opacity:0.6; cursor:not-allowed;' : '' ?>"
                                   name="grades[<?= $s['id'] ?>][td]" value="<?= htmlspecialchars($s['td_grade'] ?? '') ?>" placeholder="<?= $grading_open ? '-' : 'LOCKED' ?>" <?= $grading_open ? '' : 'readonly' ?>>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="20" class="form-input" style="padding:8px; margin:0; background: #f0fff4; <?= !$grading_open ? 'opacity:0.6; cursor:not-allowed;' : '' ?>"
                                   name="grades[<?= $s['id'] ?>][tp]" value="<?= htmlspecialchars($s['tp_grade'] ?? '') ?>" placeholder="<?= $grading_open ? '-' : 'LOCKED' ?>" <?= $grading_open ? '' : 'readonly' ?>>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="20" class="form-input" style="padding:8px; margin:0; background: #fff5f5; <?= (!$grading_open || !$resit_open) ? 'opacity:0.6; cursor:not-allowed;' : '' ?>"
                                   name="grades[<?= $s['id'] ?>][rattrapage]" value="<?= htmlspecialchars($s['rattrapage_grade'] ?? '') ?>" 
                                   placeholder="<?= ($grading_open && $resit_open) ? '-' : 'LOCKED' ?>" <?= ($grading_open && $resit_open) ? '' : 'readonly' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if(!empty($students) && $grading_open): ?>
        <div style="margin-top: 25px; text-align: right;">
            <button type="submit" class="btn-primary" style="padding: 14px 40px; font-size: 16px; background: #0A2B8E; border: none;">
                Save All Official Grades
            </button>
        </div>
        <?php elseif (!empty($students) && !$grading_open): ?>
        <div style="margin-top: 25px; text-align: right; color: #e53e3e; font-weight: 600;">
            <p>Grading is currently locked by the Administration.</p>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php include 'layout_footer.php'; ?>
