<?php include 'layout_header.php'; 

$is_student = (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'student');
$is_teacher = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'teacher');
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

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
        <h2 class="page-title" style="margin-bottom: 25px;">Dashboard Overview</h2>
        <div class="stat-grid">
            <div class="stat-card">
                <p class="stat-label">Student ID</p>
                <h3 class="stat-value"><?= htmlspecialchars($student_info['student_number'] ?? 'N/A') ?></h3>
            </div>
            <div class="stat-card">
                <p class="stat-label">Speciality Track</p>
                <h3 class="stat-value"><?= htmlspecialchars($dept_name) ?></h3>
            </div>
            <div class="stat-card">
                <p class="stat-label">Modules This Year</p>
                <h3 class="stat-value"><?= $total_courses ?> Modules</h3>
            </div>
        </div>

        <h3 style="color: #0A2B8E; margin-bottom: 15px;">Recent Results Preview</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Exam (60%)</th>
                    <th>TD</th>
                    <th>TP</th>
                    <th>Final Result</th>
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
                    <tr><td colspan="5" style="text-align: center; padding: 30px; color: #718096;">No grades recorded for this period yet.</td></tr>
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
                    <tr>
                        <td><strong><?= htmlspecialchars($rg['name']) ?></strong></td>
                        <td><?= $rg['exam'] !== null ? number_format($rg['exam'], 2) : '-' ?></td>
                        <td><?= $rg['td_grade'] !== null ? number_format($rg['td_grade'], 2) : '-' ?></td>
                        <td><?= $rg['tp_grade'] !== null ? number_format($rg['tp_grade'], 2) : '-' ?></td>
                        <td>
                            <span style="font-weight: 700; color: <?= ($final !== null && $final >= 10) ? '#2ecc71' : '#e74c3c' ?>">
                                <?= $final !== null ? number_format($final, 2) : '-' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <div style="margin-top: 15px; text-align: right;">
            <a href="academic_transcript.php" style="color: #0A2B8E; font-weight: 600; text-decoration: none; font-size: 14px;">View Full Transcript →</a>
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
        <h2 class="page-title" style="margin-bottom: 25px;">Teacher Dashboard</h2>
        <div class="stat-grid">
            <div class="stat-card-blue">
                <p class="stat-label">Employee ID</p>
                <h3 class="stat-value"><?= htmlspecialchars($teacher_info['employee_number'] ?? 'N/A') ?></h3>
            </div>
            <div class="stat-card-blue">
                <p class="stat-label">Total Classes</p>
                <h3 class="stat-value"><?= $total_assignments ?> Modules</h3>
            </div>
        </div>
    </div>
    <?php
} elseif ($is_admin) {
    $sys_students = $pdo->query("SELECT COUNT(*) FROM student")->fetchColumn();
    $sys_teachers = $pdo->query("SELECT COUNT(*) FROM teacher")->fetchColumn();
    $sys_courses  = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    ?>
    <div class="card-container">
        <h2 class="page-title" style="margin-bottom: 25px;">Administrator Dashboard</h2>
        <div class="stat-grid">
            <div class="stat-card-blue">
                <p class="stat-label">Enrolled Students</p>
                <h3 class="stat-value"><?= $sys_students ?></h3>
            </div>
            <div class="stat-card-blue">
                <p class="stat-label">Total Teachers</p>
                <h3 class="stat-value"><?= $sys_teachers ?></h3>
            </div>
            <div class="stat-card-blue">
                <p class="stat-label">Active Modules</p>
                <h3 class="stat-value"><?= $sys_courses ?></h3>
            </div>
        </div>
    </div>
    <?php
}
include 'layout_footer.php'; 
?>
