<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php'; 

$classes = [];
if(isset($_SESSION['teacher_id'])) {
    $stmt = $pdo->prepare("
        SELECT ca.id, c.code, c.name, sec.name as section_name, g.name as group_name
        FROM course_assignment ca
        JOIN courses c ON ca.course_id = c.id
        JOIN section sec ON ca.section_id = sec.id
        JOIN `group` g ON ca.group_id = g.id
        WHERE ca.teacher_id = ?
    ");
    $stmt->execute([$_SESSION['teacher_id']]);
    $classes = $stmt->fetchAll();
}
?>

<div class="card-container">
    <h2 style="color: #0A2B8E; margin-bottom: 15px;">Track Attendance</h2>
    <p style="color: #666; margin-bottom: 20px;">Track absences and late arrivals for students in your assigned modules.</p>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Course</th>
                <th>Section & Group</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($classes)): ?>
            <tr><td colspan="3">You have no class assignments.</td></tr>
            <?php else: ?>
                <?php foreach($classes as $c): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['name']) ?></strong> (<?= htmlspecialchars($c['code']) ?>)</td>
                    <td><?= htmlspecialchars($c['section_name']) ?> - <?= htmlspecialchars($c['group_name']) ?></td>
                    <td><a href="teacher_attendance_input.php?class_id=<?= $c['id'] ?>" class="logout-btn" style="background: #0A2B8E; color: white; border: none; box-shadow: none;">Mark Roll</a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'layout_footer.php'; ?>
