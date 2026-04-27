<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php';

$exam_grades = [];
$total_coef = 0;
$weighted_sum = 0;
$total_credits_earned = 0;
$total_credits_possible = 0;
$all_above_ten = true;

if (isset($_SESSION['student_id'])) {
    $stmt = $pdo->prepare("
        SELECT c.code, c.name, c.coefficient, c.credits, 
               g.grade, g.td_grade, g.tp_grade, g.final_grade, g.rattrapage_grade,
               g.comment, g.date_recorded 
        FROM courses c 
        JOIN student s ON s.id = ?
        JOIN section sec ON s.section_id = sec.id
        LEFT JOIN grades g ON g.course_id = c.id AND g.student_id = s.id 
        WHERE c.speciality_id = sec.speciality_id 
          AND c.year_id = sec.year_id
          AND c.semester = (SELECT setting_value FROM system_settings WHERE setting_key = 'current_semester')
        ORDER BY c.name
    ");
    $stmt->execute([$_SESSION['student_id']]);
    $exam_grades = $stmt->fetchAll();

    $missing_any_grade = false;

    foreach ($exam_grades as $gm) {
        $grade = $gm['final_grade'];
        
        if ($grade === null || $grade === '') {
            $ca_sum = 0;
            $ca_count = 0;
            if ($gm['td_grade'] !== null && $gm['td_grade'] !== '') {
                $ca_sum += (float)$gm['td_grade'];
                $ca_count++;
            }
            if ($gm['tp_grade'] !== null && $gm['tp_grade'] !== '') {
                $ca_sum += (float)$gm['tp_grade'];
                $ca_count++;
            }
            $ca_avg = ($ca_count > 0) ? ($ca_sum / $ca_count) : null;

            $exam = ($gm['grade'] !== null && $gm['grade'] !== '') ? (float)$gm['grade'] : null;
            $rattrapage = ($gm['rattrapage_grade'] !== null && $gm['rattrapage_grade'] !== '') ? (float)$gm['rattrapage_grade'] : null;
            
            if ($rattrapage !== null && ($exam === null || $rattrapage > $exam)) {
                $exam = $rattrapage;
            }

            if ($exam !== null && $ca_avg !== null) {
                $grade = ($exam * 0.6) + ($ca_avg * 0.4);
            } elseif ($exam !== null) {
                $grade = $exam;
            } elseif ($ca_avg !== null) {
                $grade = $ca_avg;
            }
        }

        if ($grade === null || $grade === '') {
            $missing_any_grade = true;
        }

        if ($grade !== null && $grade !== '') {
            $weighted_sum += ($grade * $gm['coefficient']);
            $total_coef += $gm['coefficient'];
            if ($grade >= 10) {
                $total_credits_earned += $gm['credits'];
            } else {
                $all_above_ten = false;
            }
        } else {
            $all_above_ten = false;
        }
        $total_credits_possible += $gm['credits'];
    }
}

if ($missing_any_grade) {
    $moyenne = null;
    $total_credits_earned = 0;
} else {
    $moyenne = ($total_coef > 0) ? ($weighted_sum / $total_coef) : null;
    if ($moyenne !== null && $moyenne >= 10) {
        $total_credits_earned = $total_credits_possible;
    }
}
?>

<div class="card-container">
    <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
        <div>
            <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;">
                <?= $lang == 'ar' ? 'النتائج' : 'Results' ?></h2>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px;">
        <div
            style="background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); text-align: center; border-top: 5px solid #0A2B8E;">
            <div
                style="font-size: 12px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px;">
                <?= $lang == 'ar' ? 'المعدل العام' : 'Average' ?></div>
            <div
                style="font-size: 48px; font-weight: 800; color: <?= ($moyenne !== null && $moyenne >= 10) ? '#059669' : ($moyenne !== null ? '#dc2626' : '#cbd5e1') ?>;">
                <?= $moyenne !== null ? number_format($moyenne, 2) : '0.00' ?>
            </div>
            <div style="margin-top: 10px; font-size: 14px; color: #64748b; font-weight: 500;">
                <?php if ($moyenne === null): ?>
                    <span style="color: #94a3b8;"><i class="fas fa-clock"></i>
                        <?= $lang == 'ar' ? 'النتائج معلقة' : 'Results Pending' ?></span>
                <?php else: ?>
                    <?= $moyenne >= 10 ? '<span style="color:#059669;"><i class="fas fa-check-circle"></i> ' . ($lang == 'ar' ? 'السداسي مقبول' : 'Semester Admitted') . '</span>' : '<span style="color:#dc2626;"><i class="fas fa-times-circle"></i> ' . ($lang == 'ar' ? 'السداسي مرفوض' : 'Semester Relegated') . '</span>' ?>
                <?php endif; ?>
            </div>
        </div>

        <div
            style="background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); text-align: center; border-top: 5px solid #3b82f6;">
            <div
                style="font-size: 12px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px;">
                <?= $lang == 'ar' ? 'الأرصدة المكتسبة' : 'Institutional Credits' ?></div>
            <div style="font-size: 48px; font-weight: 800; color: #1e293b;">
                <?= $total_credits_earned ?> <span style="font-size: 24px; color: #94a3b8;">/
                    <?= $total_credits_possible ?: '30' ?></span>
            </div>

            <?php
            $progress_pct = ($total_credits_possible > 0) ? ($total_credits_earned / $total_credits_possible) * 100 : 0;
            ?>
            <div
                style="width: 100%; height: 8px; background: #f1f5f9; border-radius: 10px; margin: 15px 0; overflow: hidden;">
                <div
                    style="width: <?= $progress_pct ?>%; height: 100%; background: <?= $progress_pct == 100 ? '#10b981' : '#3b82f6' ?>; transition: width 0.5s ease;">
                </div>
            </div>

            <div style="font-size: 13px; color: #64748b; font-weight: 600;">
                <?= $lang == 'ar' ? 'تقدم اكتساب الأرصدة (ECTS)' : 'ECTS Acquisition Progress' ?>
            </div>
        </div>
    </div>

    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
        <table class="data-table" style="box-shadow: none; margin: 0;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 15px 20px;"><?= $lang == 'ar' ? 'الوحدة الأكاديمية' : 'Academic Module' ?></th>
                    <th style="text-align: center;"><?= $lang == 'ar' ? 'المعامل' : 'Coef' ?></th>
                    <th style="text-align: center;"><?= $lang == 'ar' ? 'الامتحان' : 'Exam' ?></th>
                    <th style="text-align: center;">TD</th>
                    <th style="text-align: center;">TP</th>
                    <th style="text-align: center;"><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></th>
                    <th style="text-align: right;"><?= $lang == 'ar' ? 'العلامة النهائية' : 'Final Mark' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exam_grades)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 50px; color: #94a3b8;"><?= $lang == 'ar' ? 'لا توجد سجلات لهذا السداسي.' : 'No records found for this semester.' ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($exam_grades as $gm):
                        $grade = $gm['final_grade'];
                        if ($grade === null) {
                            $ca_sum = 0;
                            $ca_count = 0;
                            if ($gm['td_grade'] !== null) {
                                $ca_sum += $gm['td_grade'];
                                $ca_count++;
                            }
                            if ($gm['tp_grade'] !== null) {
                                $ca_sum += $gm['tp_grade'];
                                $ca_count++;
                            }
                            $ca_avg = ($ca_count > 0) ? ($ca_sum / $ca_count) : null;
                            $exam = $gm['grade'];
                            if ($gm['rattrapage_grade'] !== null && ($exam === null || $gm['rattrapage_grade'] > $exam)) {
                                $exam = $gm['rattrapage_grade'];
                            }
                            if ($exam !== null && $ca_avg !== null) {
                                $grade = ($exam * 0.6) + ($ca_avg * 0.4);
                            } elseif ($exam !== null) {
                                $grade = $exam;
                            } elseif ($ca_avg !== null) {
                                $grade = $ca_avg;
                            }
                        }
                        ?>
                        <?php 
                        $disp_exam = $gm['grade'];
                        $is_resit_shown = false;
                        if ($gm['rattrapage_grade'] !== null && ($disp_exam === null || $gm['rattrapage_grade'] > $disp_exam)) {
                            $disp_exam = $gm['rattrapage_grade'];
                            $is_resit_shown = true;
                        }
                        ?>
                        <tr class="user-row">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($gm['name']) ?></div>
                                <div style="font-size: 11px; font-family: monospace; color: #64748b;">
                                    <?= htmlspecialchars($gm['code']) ?> &bull; <?= $gm['credits'] ?> <?= $lang == 'ar' ? 'رصيد' : 'Credits' ?></div>
                            </td>
                            <td style="text-align: center; color: #475569; font-weight: 500;"><?= $gm['coefficient'] ?></td>
                            <td style="text-align: center; color: #1e293b; font-weight: 600;">
                                <?= $disp_exam !== null ? number_format($disp_exam, 2) : '<span style="color:#cbd5e1">-</span>' ?>
                                <?php if($is_resit_shown): ?><span style="color: #dc2626; font-size: 10px; margin-left: 4px;" title="Resit">R</span><?php endif; ?>
                            </td>
                            <td style="text-align: center; color: #475569;"><?= $gm['td_grade'] !== null ? number_format($gm['td_grade'], 2) : '<span style="color:#cbd5e1">-</span>' ?></td>
                            <td style="text-align: center; color: #475569;"><?= $gm['tp_grade'] !== null ? number_format($gm['tp_grade'], 2) : '<span style="color:#cbd5e1">-</span>' ?></td>
                            <td style="text-align: center;">
                                <?php if ($grade !== null): ?>
                                    <span
                                        style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; 
                                                 background: <?= $grade >= 10 ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $grade >= 10 ? '#059669' : '#dc2626' ?>; border: 1px solid currentColor;">
                                        <?= $grade >= 10 ? ($lang == 'ar' ? 'ناجح' : 'Validated') : ($lang == 'ar' ? 'راسب' : 'Failed') ?>
                                    </span>
                                <?php else: ?>
                                    <span
                                        style="color: #94a3b8; font-size: 12px; font-style: italic;"><?= $lang == 'ar' ? 'معلق' : 'Pending' ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; padding: 15px 20px;">
                                <span
                                    style="font-size: 18px; font-weight: 800; color: <?= $grade !== null ? ($grade >= 10 ? '#1e293b' : '#dc2626') : '#cbd5e1' ?>">
                                    <?= $grade !== null ? number_format($grade, 2) : '---' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div
        style="margin-top: 30px; display: flex; align-items: center; gap: 15px; background: #f1f5f9; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
        <i class="fas fa-info-circle" style="color: #3b82f6; font-size: 20px;"></i>
        <div style="font-size: 13px; color: #475569; line-height: 1.5;">
            <?= $lang == 'ar' ? 'هذه النتائج أولية. للحصول على كشف رسمي، توجه إلى مصلحة الشؤون الطلابية.' : 'These results are provisional. Official transcripts must be requested from the Administration Office.' ?>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>