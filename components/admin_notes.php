<?php include 'layout_header.php'; 
if(($_SESSION['user_role'] ?? '') !== 'admin') die("Access Denied.");

// Removed admin ability to edit grades

$students = $pdo->query("SELECT s.id, u.name FROM student s JOIN users u ON s.user_id = u.id ORDER BY u.name")->fetchAll();
$courses = $pdo->query("SELECT id, name, code FROM courses ORDER BY name")->fetchAll();
?>

<div class="card-container">
    <h2 class="page-title" style="margin-bottom: 25px;">Grade Management (Administration)</h2>
    
    <!-- Grade entry form removed for admins -->

    <h3 class="sub-header">Recently Recorded Grades</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Module</th>
                <th>Grade</th>
                <th>Resit</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $recent = $pdo->query("
                SELECT u.name as student_name, c.name as course_name, g.grade, g.rattrapage_grade, g.date_recorded
                FROM grades g
                JOIN student s ON g.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN courses c ON g.course_id = c.id
                ORDER BY g.date_recorded DESC LIMIT 10
            ")->fetchAll();
            foreach($recent as $r):
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
                <td><?= htmlspecialchars($r['course_name']) ?></td>
                <td><span class="badge <?= $r['grade'] >= 10 ? 'badge-success' : 'badge-danger' ?>"><?= number_format($r['grade'], 2) ?></span></td>
                <td><?= $r['rattrapage_grade'] !== null ? number_format($r['rattrapage_grade'], 2) : '-' ?></td>
                <td style="font-size: 13px; color: #718096;"><?= date('d/m/Y H:i', strtotime($r['date_recorded'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'layout_footer.php'; ?>
