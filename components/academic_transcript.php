<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php'; 

$transcript = [];
$total_notes_coef = 0;
$total_coef = 0;
$total_credits = 0;
$acquired_credits = 0;

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

$processed_modules = [];
foreach($transcript as $t) {
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
    
    $final_grade = ($t['db_final_grade'] !== null && $t['db_final_grade'] !== '') ? (float)$t['db_final_grade'] : null;
    if ($final_grade === null) {
        if ($main_exam !== null && $ca_avg !== null) {
            $final_grade = ($main_exam * 0.6) + ($ca_avg * 0.4);
        } elseif ($main_exam !== null) {
            $final_grade = $main_exam;
        } elseif ($ca_avg !== null) {
            $final_grade = $ca_avg;
        }
    }
    
    if ($final_grade !== null && $final_grade !== '') {
        $total_notes_coef += ($final_grade * $coef);
        $total_coef += $coef;
        if ($final_grade >= 10) {
            $acquired_credits += $module_credits;
        }
    }

    $processed_modules[] = [
        'data' => $t,
        'final' => $final_grade,
        'coef' => $coef,
        'credits' => $module_credits,
        'exam' => $exam,
        'td' => $td,
        'tp' => $tp,
        'rattrapage' => $rattrapage
    ];
}

$semester_avg = ($total_coef > 0) ? ($total_notes_coef / $total_coef) : 0;
?>

<style>
    @media print {
        .sidebar, .header, .logout-btn, .page-actions, .stat-grid { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
        .card-container { box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; }
        body { background: white !important; margin: 0 !important; padding: 0 !important; width: 100% !important; overflow: hidden; }
        .print-header { display: block !important; margin-bottom: 20px; border-bottom: 2px solid #0A2B8E; padding-bottom: 10px; }
        .data-table { border: 1px solid #e2e8f0 !important; width: 100% !important; margin-top: 0 !important; font-size: 11px !important; }
        .data-table th, .data-table td { padding: 8px 10px !important; }
        .data-table th { background: #f8fafc !important; color: #0A2B8E !important; }
        .hide-on-print { display: none !important; }
        @page { size: landscape; margin: 0.5cm; }
        .print-layout { display: flex !important; gap: 20px; align-items: flex-start; width: 100% !important; }
        .print-sidebar { display: flex !important; flex-direction: column; gap: 15px; min-width: 220px; padding: 15px; background: #f8fafc !important; border: 1px solid #e2e8f0 !important; border-radius: 10px; }
        .print-footer-note { display: block !important; margin-top: 15px; font-size: 9px; color: #94a3b8; text-align: center; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
    .print-header, .print-sidebar, .print-footer-note { display: none; }
</style>

<div class="card-container">
    <div class="print-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="color: #0A2B8E; margin: 0; font-size: 24px;">USTHB - RELEVÉ DE NOTES</h1>
                <p style="color: #64748b; margin: 5px 0 0 0; font-size: 14px;"><?= $lang == 'ar' ? 'كشف النقاط الرسمي' : 'Official Academic Transcript' ?></p>
            </div>
            <div style="text-align: right;">
                <h3 style="margin: 0; color: #1e293b;"><?= htmlspecialchars($_SESSION['user_name']) ?></h3>
                <p style="margin: 3px 0 0 0; color: #64748b; font-size: 12px;"><?= $lang == 'ar' ? 'رقم التسجيل: ' : 'ID: ' ?><?= htmlspecialchars($student_info['student_number'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <div class="page-actions" style="margin-bottom: 20px;">
        <h2 class="page-title"><?= $lang == 'ar' ? 'كشف النقاط الرسمي' : 'Official Transcript' ?></h2>
        <button onclick="window.print()" class="logout-btn" style="background: #0A2B8E; color: white; border: none; box-shadow: none;"><?= $lang == 'ar' ? 'تحميل PDF' : 'Download PDF' ?></button>
    </div>

    <div class="print-layout">
        <div style="flex: 1;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code / Track</th>
                        <th>Module Name</th>
                        <th>Coef</th>
                        <th>Credits</th>
                        <th>Final</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($processed_modules)): ?>
                        <tr><td colspan="10"><?= $lang == 'ar' ? 'لا توجد مقاييس مسندة بعد.' : 'No modules assigned yet.' ?></td></tr>
                    <?php else: ?>
                        <?php foreach($processed_modules as $pm): $t = $pm['data']; ?>
                        <tr>
                            <td>
                                <span class="badge badge-info"><?= htmlspecialchars($t['code']) ?></span>
                                <?php if($t['is_dette']): ?><br><span class="badge badge-danger" style="margin-top: 5px;"><?= $lang == 'ar' ? 'دَيْن' : 'Debt' ?></span><?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                            <td style="text-align: center;"><strong><?= htmlspecialchars($pm['coef']) ?></strong></td>
                            <td style="color: #0A2B8E; font-weight: 600;"><?= $pm['credits'] ?></td>
                            <td>
                                <span style="font-size: 16px; font-weight: 700; color: <?= ($pm['final'] !== null && $pm['final'] >= 10) ? '#2ecc71' : '#e74c3c' ?>">
                                    <?= $pm['final'] !== null ? number_format($pm['final'], 2) : 'N/A' ?>
                                </span>
                            </td>
                            <td><?= getAppreciation($pm['final']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="print-sidebar">
            <div style="border-bottom: 2px solid #0A2B8E; padding-bottom: 10px; margin-bottom: 15px;">
                <h4 style="margin: 0; color: #0A2B8E; font-size: 16px; text-transform: uppercase;"><?= $lang == 'ar' ? 'ملخص النتائج' : 'Results Summary' ?></h4>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div>
                    <div style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 5px;"><?= $lang == 'ar' ? 'معدل السداسي' : 'Semester Average' ?></div>
                    <div style="font-size: 32px; font-weight: 800; color: #0A2B8E; line-height: 1;"><?= number_format($semester_avg, 2) ?></div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 5px;">/ 20.00 Scale</div>
                </div>

                <div>
                    <div style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 5px;"><?= $lang == 'ar' ? 'الأرصدة المكتسبة' : 'Total Credits' ?></div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e293b;"><?= $acquired_credits ?> <span style="font-size: 14px; font-weight: 400; color: #94a3b8;">/ <?= $total_credits ?></span></div>
                </div>

                <div style="margin-top: 10px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 5px;"><?= $lang == 'ar' ? 'الحالة الأكاديمية' : 'Academic Status' ?></div>
                    <?php if($semester_avg >= 10): ?>
                        <div style="color: #059669; font-weight: 700; font-size: 14px;"><i class="fas fa-check-circle"></i> <?= $lang == 'ar' ? 'سداسي ناجح' : 'Semester Validated' ?></div>
                    <?php else: ?>
                        <div style="color: #dc2626; font-weight: 700; font-size: 14px;"><i class="fas fa-exclamation-circle"></i> <?= $lang == 'ar' ? 'سداسي غير ناجح' : 'Semester Not Validated' ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="print-footer-note">
        <?= $lang == 'ar' ? 'تم إنشاء هذا المستند إلكترونياً ولا يحتاج إلى ختم.' : 'This document is electronically generated and valid without a physical stamp.' ?>
        <br>Generated on: <?= date('d/m/Y H:i') ?>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
