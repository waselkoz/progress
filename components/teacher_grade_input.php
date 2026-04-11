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


if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['grades'])) {
    foreach($_POST['grades'] as $student_id => $data) {
        $exam = $data['grade'] !== '' ? $data['grade'] : null;
        $td = $data['td'] !== '' ? $data['td'] : null;
        $tp = $data['tp'] !== '' ? $data['tp'] : null;
        $proj = $data['project'] !== '' ? $data['project'] : null;
        $rattrapage = $data['rattrapage'] !== '' ? $data['rattrapage'] : null;
        $comment = $data['comment'];
        $is_dette = isset($data['is_dette']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO grades (student_id, course_id, grade, td_grade, tp_grade, project_grade, rattrapage_grade, is_dette, comment) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                grade = VALUES(grade), 
                td_grade = VALUES(td_grade),
                tp_grade = VALUES(tp_grade),
                project_grade = VALUES(project_grade),
                rattrapage_grade = VALUES(rattrapage_grade), 
                is_dette = VALUES(is_dette),
                comment = VALUES(comment), 
                last_updated = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([$student_id, $class_info['course_id'], $exam, $td, $tp, $proj, $rattrapage, $is_dette, $comment]);
    }
    echo "<script>window.onload = () => showToast('Grades successfully record for all components!');</script>";
}


$students = [];
$stmt = $pdo->prepare("
    SELECT s.id, u.name, s.student_number, g.grade, g.td_grade, g.tp_grade, g.project_grade, g.rattrapage_grade, g.is_dette, g.comment
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
                    <th>Project</th>
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
                            <input type="number" step="0.01" min="0" max="20" class="form-input" style="padding:8px; margin:0;"
                                   name="grades[<?= $s['id'] ?>][grade]" value="<?= htmlspecialchars($s['grade'] ?? '') ?>" placeholder="-">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="20" class="form-input" style="padding:8px; margin:0; background: #f0f7ff;"
                                   name="grades[<?= $s['id'] ?>][td]" value="<?= htmlspecialchars($s['td_grade'] ?? '') ?>" placeholder="-">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="20" class="form-input" style="padding:8px; margin:0; background: #f0fff4;"
                                   name="grades[<?= $s['id'] ?>][tp]" value="<?= htmlspecialchars($s['tp_grade'] ?? '') ?>" placeholder="-">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="20" class="form-input" style="padding:8px; margin:0; background: #fffaf0;"
                                   name="grades[<?= $s['id'] ?>][project]" value="<?= htmlspecialchars($s['project_grade'] ?? '') ?>" placeholder="-">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="20" class="form-input" style="padding:8px; margin:0; background: #fff5f5;"
                                   name="grades[<?= $s['id'] ?>][rattrapage]" value="<?= htmlspecialchars($s['rattrapage_grade'] ?? '') ?>" 
                                   placeholder="<?= $resit_open ? '-' : 'LOCKED' ?>" <?= $resit_open ? '' : 'readonly' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if(!empty($students)): ?>
        <div style="margin-top: 25px; text-align: right;">
            <button type="submit" class="btn-primary" style="padding: 14px 40px; font-size: 16px; background: #0A2B8E; border: none;">
                Save All Official Grades
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php include 'layout_footer.php'; ?>
