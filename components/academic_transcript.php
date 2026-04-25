<?php include 'layout_header.php'; 

$transcript = [];
$total_notes_coef = 0;
$total_coef = 0;

$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_semester'");
$current_semester = $stmt->fetchColumn() ?: 'S1';

if(isset($_SESSION['student_id'])) {
    $semester_filter = ($current_semester === 'S1') ? "AND c.semester = 'S1'" : "";
    
    $stmt = $pdo->prepare("
        SELECT c.code, c.name, c.credits, c.coefficient, g.grade as exam_grade, 
               g.td_grade, g.tp_grade, g.final_grade as db_final_grade, g.rattrapage_grade, g.is_dette, c.semester
        FROM courses c
        JOIN student s ON s.id = ?
        JOIN section sec ON s.section_id = sec.id
        LEFT JOIN grades g ON g.course_id = c.id AND g.student_id = s.id
        WHERE c.speciality_id = sec.speciality_id 
          AND c.year_id = sec.year_id
          $semester_filter
        GROUP BY c.id
    ");
    $stmt->execute([$_SESSION['student_id']]);
    $transcript = $stmt->fetchAll();
}

function getAppreciation($grade) {
    if ($grade === null || $grade === '') return '-';
    if ($grade >= 16) return '<span style="color: #2ecc71; font-weight: bold;">Excellent</span>';
    if ($grade >= 14) return '<span style="color: #27ae60; font-weight: bold;">Very Good</span>';
    if ($grade >= 12) return '<span style="color: #f39c12; font-weight: bold;">Good</span>';
    if ($grade >= 10) return '<span style="color: #e67e22; font-weight: bold;">Fair</span>';
    return '<span style="color: #e74c3c; font-weight: bold;">Insufficient</span>';
}
?>

<div class="card-container">
    <div class="page-actions" style="margin-bottom: 20px;">
        <h2 class="page-title"><?= $lang == 'ar' ? 'كشف النقاط الرسمي' : 'Official Transcript' ?></h2>
        <button onclick="window.print()" class="logout-btn" style="background: #0A2B8E; color: white; border: none; box-shadow: none;"><?= $lang == 'ar' ? 'تحميل PDF' : 'Download PDF' ?></button>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Code / Track</th>
                <th>Module Name</th>
                <th>Coef</th>
                <th>Exam (60%)</th>
                <th>TD</th>
                <th>TP</th>
                <th>Credits</th>
                <th>Resit</th>
                <th>Final</th>
                <th>Result</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($transcript)): ?>
            <tr><td colspan="10"><?= $lang == 'ar' ? 'لا توجد مقاييس مسندة بعد.' : 'No modules assigned yet.' ?></td></tr>
            <?php else: ?>
                <?php 
                    $total_credits = 0;
                    $acquired_credits = 0;
                    $modules_calc = [];
                    foreach($transcript as $t): 
                    $exam = $t['exam_grade'];
                    $td = $t['td_grade'];
                    $tp = $t['tp_grade'];
                    $rattrapage = $t['rattrapage_grade'];
                    $coef = $t['coefficient'] ?? 1;
                    $module_credits = $t['credits'] ?? 0;
                    
                    $total_credits += $module_credits;
                    
                    $ca_sum = 0; $ca_count = 0;
                    if ($td !== null) { $ca_sum += $td; $ca_count++; }
                    if ($tp !== null) { $ca_sum += $tp; $ca_count++; }
                    $ca_avg = ($ca_count > 0) ? ($ca_sum / $ca_count) : null;
                    
                    $main_exam = $exam;
                    if ($rattrapage !== null && ($exam === null || $rattrapage > $exam)) {
                        $main_exam = $rattrapage;
                    }
                    
                    $final_grade = $t['db_final_grade'] ?? null;
                    if ($final_grade === null) {
                        if ($main_exam !== null && $ca_avg !== null) {
                            $final_grade = ($main_exam * 0.6) + ($ca_avg * 0.4);
                        } elseif ($main_exam !== null) {
                            $final_grade = $main_exam;
                        } elseif ($ca_avg !== null) {
                            $final_grade = $ca_avg;
                        }
                    }
                    
                    if ($final_grade !== null) {
                        $total_notes_coef += ($final_grade * $coef);
                        $total_coef += $coef;
                        
                        if ($final_grade >= 10) {
                            $acquired_credits += $module_credits;
                        }
                    }
                    
                    $modules_calc[] = [
                        't' => $t,
                        'final_grade' => $final_grade,
                        'credits' => $module_credits,
                        'coef' => $coef
                    ];
                ?>
                <tr>
                    <td>
                        <span class="badge badge-info"><?= htmlspecialchars($t['code']) ?></span>
                        <?php if($t['is_dette']): ?><br><span class="badge badge-danger" style="margin-top: 5px;"><?= $lang == 'ar' ? 'دَيْن' : 'Debt' ?></span><?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                    <td style="text-align: center;"><strong><?= htmlspecialchars($coef) ?></strong></td>
                    
                    <td style="font-weight: 500; color: #4a5568;"><?= $exam !== null ? number_format($exam, 2) : '-' ?></td>
                    <td style="color: #666;"><?= $td !== null ? number_format($td, 2) : '-' ?></td>
                    <td style="color: #666;"><?= $tp !== null ? number_format($tp, 2) : '-' ?></td>
                    <td style="color: #0A2B8E; font-weight: 600;"><?= $module_credits ?></td>
                    <td style="color: #f39c12; font-weight: 600;"><?= $rattrapage !== null ? number_format($rattrapage, 2) : '-' ?></td>
                    
                    <td>
                        <span style="font-size: 16px; font-weight: 700; color: <?= ($final_grade !== null && $final_grade >= 10) ? '#2ecc71' : '#e74c3c' ?>">
                            <?= $final_grade !== null ? number_format($final_grade, 2) : 'N/A' ?>
                        </span>
                    </td>
                    <td><?= getAppreciation($final_grade) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if($total_coef > 0):
        $overall_avg = $total_notes_coef / $total_coef;
        if ($overall_avg >= 10) {
            $acquired_credits = $total_credits;
        }
    ?>
    <div class="info-panel-blue" style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <p class="stat-label"><?= $lang == 'ar' ? 'الأرصدة المكتسبة' : 'Acquired Credits' ?></p>
            <h2 class="stat-value" style="font-size: 28px; <?= $acquired_credits == $total_credits ? 'color: #2ecc71;' : '' ?>"><?= $acquired_credits ?> / <?= $total_credits ?></h2>
        </div>
        <div style="text-align: center;">
            <p class="stat-label"><?= $lang == 'ar' ? 'المعدل العام' : 'Overall Average' ?></p>
            <h2 class="stat-value" style="font-size: 32px;"><?= number_format($overall_avg, 2) ?> / 20</h2>
        </div>
        <div style="text-align: right;">
            <p class="stat-label"><?= $lang == 'ar' ? 'النتيجة النهائية' : 'Final Result' ?></p>
            <h3 style="margin: 0; font-size: 24px; color: <?= $overall_avg >= 10 ? '#2ecc71' : '#e74c3c' ?>;">
                <?= $overall_avg >= 10 ? ($lang == 'ar' ? 'مقبول' : 'Admitted') : ($lang == 'ar' ? 'راسب' : 'Failed') ?>
            </h3>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<?php include 'layout_footer.php'; ?>
