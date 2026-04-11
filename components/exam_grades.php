<?php include 'layout_header.php';

$exam_grades = [];
if (isset($_SESSION['student_id'])) {
    $stmt = $pdo->prepare("
        SELECT c.code, c.name, g.grade, g.comment, g.date_recorded 
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
}
?>

<div class="card-container">
    <h2 style="color: #0A2B8E; margin-bottom: 15px;">Exam Grades & Feedback</h2>

    <table class="data-table">
        <thead>
            <tr>
                <th>Date Recorded</th>
                <th>Course Name</th>
                <th>Grade</th>
                <th>Comments / Feedback</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($exam_grades)): ?>
                <tr>
                    <td colspan="4">No exam grades have been recorded yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($exam_grades as $gm): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($gm['date_recorded']))) ?></td>
                        <td><strong><?= htmlspecialchars($gm['name']) ?></strong> (<?= htmlspecialchars($gm['code']) ?>)</td>
                        <td>
                            <span
                                style="font-size: 18px; font-weight: bold; color: <?= $gm['grade'] >= 10 ? '#2ecc71' : '#e74c3c' ?>">
                                <?= number_format($gm['grade'], 2) ?>
                            </span>
                        </td>
                        <td><em style="color: #666;"><?= htmlspecialchars($gm['comment'] ?? 'No comments') ?></em></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'layout_footer.php'; ?>