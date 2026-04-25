<?php include 'layout_header.php'; 
if(($_SESSION['user_role'] ?? '') !== 'teacher') die("Access Denied.");

$class_id = $_GET['class_id'] ?? null;
if(!$class_id) die("No class selected.");


$stmt = $pdo->prepare("
    SELECT c.name, c.code as course_code, sec.name as sec_name, g.name as grp_name, ca.course_id, ca.section_id, ca.group_id 
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
    $course_code = $class_info['course_code'];
    $has_tp = in_array($course_code, ['SE', 'BDD', 'GL', 'PWEB']);
    $has_td = ($course_code !== 'PWEB');
    $has_exam = true; // All courses have exams

    foreach($_POST['grades'] as $student_id => $data) {
        $exam = $data['grade'] !== '' ? $data['grade'] : null;
        $td = $data['td'] !== '' ? $data['td'] : null;
        $tp = $data['tp'] !== '' ? $data['tp'] : null;
        $rattrapage = $data['rattrapage'] !== '' ? $data['rattrapage'] : null;
        $comment = $data['comment'] ?? null;
        $is_dette = isset($data['is_dette']) ? 1 : 0;
        
        // Ignore inputs that are not applicable to the module
        if (!$has_exam) $exam = null;
        if (!$has_td) $td = null;
        if (!$has_tp) $tp = null;
        
        $expected_count = 0;
        $entered_count = 0;
        
        if ($has_exam) {
            $expected_count++;
            if ($exam !== null) $entered_count++;
        }
        if ($has_td) {
            $expected_count++;
            if ($td !== null) $entered_count++;
        }
        if ($has_tp) {
            $expected_count++;
            if ($tp !== null) $entered_count++;
        }

        $main_exam = $exam;
        if ($rattrapage !== null && ($exam === null || $rattrapage > $exam)) {
            $main_exam = $rattrapage;
        }

        $final_grade = null;
        if ($entered_count === $expected_count && $expected_count > 0) {
            if ($has_exam && $has_td && $has_tp) {
                $final_grade = ($main_exam * 0.6) + ((($td + $tp) / 2) * 0.4);
            } elseif ($has_exam && $has_td) {
                $final_grade = ($main_exam * 0.6) + ($td * 0.4);
            } elseif ($has_exam && $has_tp) {
                $final_grade = ($main_exam * 0.6) + ($tp * 0.4);
            } elseif (!$has_exam && !$has_td && $has_tp) {
                $final_grade = $tp; // PWEB
            }
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
    echo "<script>window.onload = () => showToast('" . ($lang == 'ar' ? 'تم حفظ العلامات بنجاح.' : 'Grades saved successfully.') . "');</script>";
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
    <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: #eff6ff; color: #3b82f6; width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div>
                <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 22px;"><?= $lang == 'ar' ? 'ملف التقييم: ' : 'Evaluation Dossier: ' ?><?= htmlspecialchars($class_info['name']) ?></h2>
                <p style="color: #64748b; font-size: 13px; margin-top: 4px;">
                    <i class="fas fa-users" style="margin-right: 5px;"></i> <?= htmlspecialchars($class_info['sec_name']) ?> &bull; <?= htmlspecialchars($class_info['grp_name']) ?>
                </p>
            </div>
        </div>
        <a href="teacher_grades.php" class="btn-primary" style="background: #ffffff; color: #475569; border: 1px solid #e2e8f0; text-decoration: none; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-arrow-left"></i> <?= $lang == 'ar' ? 'العودة للقائمة' : 'Back to Roster' ?>
        </a>
    </div>

    <?php if (!$grading_open): ?>
        <div style="background: #fff1f2; border: 1px solid #ffe4e6; color: #be123c; padding: 15px 20px; border-radius: 10px; margin-bottom: 30px; display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 18px;"></i>
            <div style="font-size: 14px; font-weight: 500;">
                <?= $lang == 'ar' ? 'نظام رصد العلامات المركزي <strong>مغلق</strong> حالياً. التعديلات غير مسموح بها في الوقت الحالي.' : 'The central grading system is currently <strong>LOCKED</strong>. Modifications are prohibited at this time.' ?>
            </div>
        </div>
    <?php elseif (!$resit_open): ?>
        <div style="background: #fefce8; border: 1px solid #fef08a; color: #854d0e; padding: 15px 20px; border-radius: 10px; margin-bottom: 30px; display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-info-circle" style="font-size: 18px;"></i>
            <div style="font-size: 14px; font-weight: 500;">
                <?= $lang == 'ar' ? 'وحدة <strong>الاستدراك</strong> مغلقة حالياً. علامات الدورة العادية تبقى مفتوحة للرصد.' : 'The <strong>Resit</strong> window is currently closed. Main session grades remain open.' ?>
            </div>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <table class="data-table" style="box-shadow: none; border: 1px solid #f1f5f9;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 15px 20px; width: 300px;"><?= $lang == 'ar' ? 'معلومات الطالب' : 'Student Information' ?></th>
                    <th style="text-align: center; width: 150px;"><?= $lang == 'ar' ? 'الامتحان الرئيسي (60%)' : 'Main Exam (60%)' ?></th>
                    <th style="text-align: center; width: 120px;"><?= $lang == 'ar' ? 'علامة الأعمال الموجهة' : 'TD Mark' ?></th>
                    <th style="text-align: center; width: 120px;"><?= $lang == 'ar' ? 'علامة الأعمال التطبيقية' : 'TP Mark' ?></th>
                    <th style="text-align: center; width: 150px;"><?= $lang == 'ar' ? 'الاستدراك' : 'Resit' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($students)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 50px; color: #94a3b8;"><?= $lang == 'ar' ? 'لا يوجد طلاب مسجلون في هذا الفوج.' : 'No students found in this group.' ?></td></tr>
                <?php else: ?>
                    <?php foreach($students as $s): ?>
                    <tr class="user-row">
                        <td style="padding: 15px 20px;">
                            <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($s['name']) ?></div>
                            <div style="font-size: 11px; font-family: monospace; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($s['student_number']) ?></div>
                        </td>
                        <td style="text-align: center;">
                            <input type="number" step="0.01" min="0" max="20" class="form-input" 
                                   style="text-align: center; max-width: 100px; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 600; <?= (!$grading_open || !$has_exam) ? 'background: #e2e8f0; cursor: not-allowed; color: #94a3b8;' : '' ?>"
                                   name="grades[<?= $s['id'] ?>][grade]" value="<?= htmlspecialchars($s['grade'] ?? '') ?>" 
                                   <?= ($grading_open && $has_exam) ? '' : 'readonly' ?>>
                        </td>
                        <td style="text-align: center;">
                            <input type="number" step="0.01" min="0" max="20" class="form-input" 
                                   style="text-align: center; max-width: 90px; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 500; <?= (!$grading_open || !$has_td) ? 'background: #e2e8f0; cursor: not-allowed; color: #94a3b8;' : 'background: #f0f7ff;' ?>"
                                   name="grades[<?= $s['id'] ?>][td]" value="<?= htmlspecialchars($s['td_grade'] ?? '') ?>" 
                                   <?= ($grading_open && $has_td) ? '' : 'readonly' ?>>
                        </td>
                        <td style="text-align: center;">
                            <input type="number" step="0.01" min="0" max="20" class="form-input" 
                                   style="text-align: center; max-width: 90px; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 500; <?= (!$grading_open || !$has_tp) ? 'background: #e2e8f0; cursor: not-allowed; color: #94a3b8;' : 'background: #f0fff4;' ?>"
                                   name="grades[<?= $s['id'] ?>][tp]" value="<?= htmlspecialchars($s['tp_grade'] ?? '') ?>" 
                                   <?= ($grading_open && $has_tp) ? '' : 'readonly' ?>>
                        </td>
                        <td style="text-align: center;">
                            <input type="number" step="0.01" min="0" max="20" class="form-input" 
                                   style="text-align: center; max-width: 100px; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff5f5; font-weight: 700; color: #dc2626; <?= (!$grading_open || !$resit_open) ? 'background: #f1f5f9; cursor: not-allowed; color: #94a3b8;' : '' ?>"
                                   name="grades[<?= $s['id'] ?>][rattrapage]" value="<?= htmlspecialchars($s['rattrapage_grade'] ?? '') ?>" 
                                   <?= ($grading_open && $resit_open) ? '' : 'readonly' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if(!empty($students) && $grading_open): ?>
        <div style="margin-top: 40px; display: flex; justify-content: flex-end; align-items: center; gap: 20px;">
            <div style="color: #64748b; font-size: 13px; font-style: italic;">
                <i class="fas fa-info-circle"></i> <?= $lang == 'ar' ? 'ستكون العلامات مرئية للطلاب فور اعتمادها.' : 'Grades will be immediately visible to students upon submission.' ?>
            </div>
            <button type="submit" class="btn-primary" style="padding: 14px 45px; font-size: 16px; background: #0A2B8E; box-shadow: 0 4px 6px -1px rgba(10, 43, 142, 0.2);">
                <i class="fas fa-save" style="margin-right: 10px;"></i> <?= $lang == 'ar' ? 'اعتماد العلامات الرسمية' : 'Commit Official Grades' ?>
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php include 'layout_footer.php'; ?>
