<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php'; 

$is_student = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student');
$is_teacher = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher');
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

if ($is_admin) {
    $sys_students = $pdo->query("SELECT COUNT(*) FROM student")->fetchColumn();
    $sys_teachers = $pdo->query("SELECT COUNT(*) FROM teacher")->fetchColumn();
    $sys_courses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
}

if ($is_student) {
    $total_courses = 0; $dept_name = "Unknown";
    if($student_info ?? false) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses c JOIN section s ON c.speciality_id = s.speciality_id AND c.year_id = s.year_id WHERE s.id = ?");
        $stmt->execute([$student_info['section_id']]);
        $total_courses = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT sp.name FROM speciality sp JOIN section s ON sp.id = s.speciality_id WHERE s.id = ?");
        $stmt->execute([$student_info['section_id']]);
        $dept_name = $stmt->fetchColumn();
    }
    ?>
    <div class="card-container">
        <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
            <div>
                <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;"><?= $lang == 'ar' ? 'بوابة الطالب' : 'Student Portal' ?></h2>
                <p style="color: #64748b; margin-top: 5px; font-size: 14px;"><?= $t['welcome'] ?>.</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px;">
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 5px solid #0A2B8E;">
                <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;"><?= $lang == 'ar' ? 'رقم التسجيل' : 'Registration No.' ?></div>
                <div style="font-size: 24px; font-weight: 800; color: #1e293b; font-family: monospace;"><?= htmlspecialchars($student_info['student_number'] ?? 'N/A') ?></div>
            </div>
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 5px solid #3b82f6;">
                <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"><?= $lang == 'ar' ? 'التخصص' : 'Speciality' ?></div>
                <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($dept_name) ?></div>
            </div>
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 5px solid #10b981;">
                <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"><?= $lang == 'ar' ? 'المقاييس' : 'Modules' ?></div>
                <div style="font-size: 24px; font-weight: 800; color: #1e293b;"><?= $total_courses ?></div>
            </div>
        </div>

        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="margin: 0; color: #1e293b; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-poll-h" style="color: #0A2B8E;"></i> <?= $lang == 'ar' ? 'أحدث النتائج' : 'Recent Result Feed' ?>
                </h3>
                <a href="exam_grades.php" style="color: #3b82f6; font-size: 13px; font-weight: 700; text-decoration: none;"><?= $lang == 'ar' ? 'عرض السجلات التفصيلية' : 'View Detailed Records' ?> &rarr;</a>
            </div>
            
            <table class="data-table" style="box-shadow: none;">
                <thead>
                    <tr>
                        <th style="padding: 15px 20px;"><?= $lang == 'ar' ? 'الوحدة الأكاديمية' : 'Academic Module' ?></th>
                        <th style="text-align: center;"><?= $lang == 'ar' ? 'الامتحان' : 'Exam' ?></th>
                        <th style="text-align: center;">TD/TP</th>
                        <th style="text-align: right;"><?= $lang == 'ar' ? 'العلامة الموزونة' : 'Weighted Grade' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT c.name, g.grade as exam, g.td_grade, g.tp_grade, g.final_grade
                        FROM grades g
                        JOIN courses c ON g.course_id = c.id
                        WHERE g.student_id = ?
                        ORDER BY g.last_updated DESC
                        LIMIT 5
                    ");
                    $stmt->execute([$_SESSION['student_id'] ?? 0]);
                    $recent_grades = $stmt->fetchAll();

                    if (empty($recent_grades)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;"><?= $lang == 'ar' ? 'لم يتم نشر أي نتائج تقييم في ملفك الشخصي حتى الآن.' : 'No evaluation results have been published in your portal profile.' ?></td></tr>
                    <?php else: 
                        foreach ($recent_grades as $rg): 
                            $ca_sum = 0; $ca_count = 0;
                            if ($rg['td_grade'] !== null) { $ca_sum += $rg['td_grade']; $ca_count++; }
                            if ($rg['tp_grade'] !== null) { $ca_sum += $rg['tp_grade']; $ca_count++; }
                            $ca_avg = ($ca_count > 0) ? ($ca_sum / $ca_count) : null;
                            
                            $final = $rg['final_grade'] ?? null;
                            if ($final === null) {
                                if ($rg['exam'] !== null && $ca_avg !== null) {
                                    $final = ($rg['exam'] * 0.6) + ($ca_avg * 0.4);
                                } elseif ($rg['exam'] !== null) {
                                    $final = $rg['exam'];
                                } elseif ($ca_avg !== null) {
                                    $final = $ca_avg;
                                }
                            }
                    ?>
                        <tr class="user-row">
                            <td style="padding: 15px 20px;"><strong><?= htmlspecialchars($rg['name']) ?></strong></td>
                            <td style="text-align: center; font-weight: 600;"><?= $rg['exam'] !== null ? number_format($rg['exam'], 2) : '<span style="color:#cbd5e1;">-</span>' ?></td>
                            <td style="text-align: center; color: #64748b;"><?= $ca_avg !== null ? number_format($ca_avg, 2) : '<span style="color:#cbd5e1;">-</span>' ?></td>
                            <td style="text-align: right;">
                                <span style="font-weight: 800; color: <?= ($final !== null && $final >= 10) ? '#059669' : '#dc2626' ?>; background: <?= ($final !== null && $final >= 10) ? '#ecfdf5' : '#fef2f2' ?>; padding: 5px 12px; border-radius: 6px;">
                                    <?= $final !== null ? number_format($final, 2) : 'N/A' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
} elseif ($is_teacher) {
    $total_assignments = 0;
    if(isset($_SESSION['teacher_id'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_assignment WHERE teacher_id = ?");
        $stmt->execute([$_SESSION['teacher_id']]);
        $total_assignments = $stmt->fetchColumn();
    }
    ?>
    <div class="card-container">
        <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
            <div>
                <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;"><?= $lang == 'ar' ? 'بوابة الأستاذ' : 'Teacher Portal' ?></h2>
                <p style="color: #64748b; margin-top: 5px; font-size: 14px;"><?= $t['welcome'] ?>.</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px;">
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px;">
                <div style="background: #f1f5f9; color: #64748b; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fas fa-id-badge"></i>
                </div>
                <div>
                    <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"><?= $lang == 'ar' ? 'معرف الأستاذ' : 'Faculty Identifier' ?></div>
                    <div style="font-size: 22px; font-weight: 800; color: #1e293b; font-family: monospace;"><?= htmlspecialchars($teacher_info['employee_number'] ?? 'N/A') ?></div>
                </div>
            </div>
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px;">
                <div style="background: #eff6ff; color: #3b82f6; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"><?= $lang == 'ar' ? 'الوحدات التعليمية' : 'Instructional Modules' ?></div>
                    <div style="font-size: 22px; font-weight: 800; color: #1e293b;"><?= $total_assignments ?> <span style="font-size: 14px; font-weight: 500; color: #94a3b8;"><?= $lang == 'ar' ? 'وحدة نشطة' : 'Active Units' ?></span></div>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 20px;">
            <a href="teacher_grades.php" style="flex: 1; text-decoration: none; background: #0A2B8E; color: white; padding: 25px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; transition: transform 0.2s; box-shadow: 0 4px 6px -1px rgba(10, 43, 142, 0.2);">
                <div>
                    <h4 style="margin: 0; font-size: 18px;"><?= $lang == 'ar' ? 'فتح دفتر العلامات' : 'Open Gradebook' ?></h4>
                    <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.8;"><?= $lang == 'ar' ? 'الوصول إلى قوائم الطلاب وإدخال العلامات.' : 'Access student rosters and input marks.' ?></p>
                </div>
                <i class="fas fa-chevron-right"></i>
            </a>
            <a href="profile.php" style="flex: 1; text-decoration: none; background: white; color: #1e293b; padding: 25px; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div>
                    <h4 style="margin: 0; font-size: 18px;"><?= $lang == 'ar' ? 'الملف الشخصي المؤسسي' : 'Institutional Profile' ?></h4>
                    <p style="margin: 5px 0 0 0; font-size: 13px; color: #64748b;"><?= $lang == 'ar' ? 'إدارة بيانات الاعتماد والأمان الخاصة بك.' : 'Manage your credentials and security.' ?></p>
                </div>
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
    <?php
} elseif ($is_admin) {
    ?>
    <div class="card-container">
        <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
            <div>
                <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;"><?= $lang == 'ar' ? 'لوحة الإدارة' : 'Admin Panel' ?></h2>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px;">
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-top: 4px solid #10b981;">
                <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"><?= $lang == 'ar' ? 'الطلاب المسجلون' : 'Enrolled Students' ?></div>
                <div style="font-size: 28px; font-weight: 800; color: #1e293b; margin-top: 10px;"><?= number_format($sys_students) ?></div>
            </div>
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-top: 4px solid #3b82f6;">
                <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"><?= $lang == 'ar' ? 'أعضاء هيئة التدريس النشطون' : 'Active Faculty' ?></div>
                <div style="font-size: 28px; font-weight: 800; color: #1e293b; margin-top: 10px;"><?= number_format($sys_teachers) ?></div>
            </div>
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-top: 4px solid #0A2B8E;">
                <div style="color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"><?= $lang == 'ar' ? 'الوحدات' : 'Catalog' ?></div>
                <div style="font-size: 28px; font-weight: 800; color: #1e293b; margin-top: 10px;"><?= number_format($sys_courses) ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px;">
                <h3 style="margin: 0 0 20px 0; color: #1e293b; font-size: 18px;"><?= $lang == 'ar' ? 'إدارة سريعة' : 'Quick Administration' ?></h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <a href="admin_users.php" style="background: white; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; text-decoration: none; color: #475569; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-users" style="color: #3b82f6;"></i> <?= $lang == 'ar' ? 'المستخدمون' : 'Users' ?>
                    </a>
                    <a href="admin_courses.php" style="background: white; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; text-decoration: none; color: #475569; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-book" style="color: #0A2B8E;"></i> <?= $lang == 'ar' ? 'الوحدات' : 'Modules' ?>
                    </a>
                    <a href="admin_system.php" style="background: white; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; text-decoration: none; color: #475569; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-cog" style="color: #64748b;"></i> <?= $lang == 'ar' ? 'الإعدادات' : 'Settings' ?>
                    </a>
                    <a href="admin_import_students.php" style="background: white; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; text-decoration: none; color: #475569; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-upload" style="color: #10b981;"></i> <?= $lang == 'ar' ? 'استيراد' : 'Import' ?>
                    </a>
                </div>
            </div>
            <div style="background: #eff6ff; border: 1px solid #dbeafe; border-radius: 16px; padding: 30px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                <div style="background: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 24px; margin-bottom: 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 style="margin: 0; color: #1e3a8a; font-size: 18px;"><?= $lang == 'ar' ? 'أمان النظام' : 'System Security' ?></h3>
                <p style="color: #3b82f6; font-size: 13px; margin: 10px 0 20px 0;"><?= $lang == 'ar' ? 'يتم تسجيل جميع الإجراءات الإدارية لأغراض التدقيق المؤسسي.' : 'All administrative actions are logged for institutional audit purposes.' ?></p>
                <a href="profile.php" style="background: #1e3a8a; color: white; text-decoration: none; padding: 10px 25px; border-radius: 8px; font-weight: 600; font-size: 14px;"><?= $lang == 'ar' ? 'مراجعة سياسة الوصول' : 'Review Access Policy' ?></a>
            </div>
        </div>
    </div>
    <?php
}
include 'layout_footer.php'; 
?>
