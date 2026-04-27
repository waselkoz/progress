<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php'; 

$classes = [];
if(isset($_SESSION['teacher_id'])) {
    $stmt = $pdo->prepare("
        SELECT c.code, c.name, sec.name as section_name, g.name as group_name, ca.semester, ca.teaching_type, ca.hours_per_week
        FROM course_assignment ca
        JOIN courses c ON ca.course_id = c.id
        JOIN section sec ON ca.section_id = sec.id
        JOIN `group` g ON ca.group_id = g.id
        WHERE ca.teacher_id = ?
        ORDER BY sec.name, g.name
    ");
    $stmt->execute([$_SESSION['teacher_id']]);
    $classes = $stmt->fetchAll();
}
?>

<div class="card-container">
    <h2 style="color: #0A2B8E; margin-bottom: 15px;">My Assigned Classes</h2>
    <p style="color: #666; margin-bottom: 20px;">Overview of the modules and groups you are teaching this academic year.</p>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Module</th>
                <th>Course Name</th>
                <th>Section</th>
                <th>Group</th>
                <th>Type</th>
                <th>Hours/Wk</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($classes)): ?>
            <tr><td colspan="6">No classes assigned yet.</td></tr>
            <?php else: ?>
                <?php foreach($classes as $c): ?>
                <tr>
                    <td><span class="badge badge-info"><?= htmlspecialchars($c['code']) ?></span></td>
                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                    <td><?= htmlspecialchars($c['section_name']) ?></td>
                    <td><?= htmlspecialchars($c['group_name']) ?></td>
                    <td><?= htmlspecialchars($c['teaching_type']) ?></td>
                    <td><?= htmlspecialchars($c['hours_per_week']) ?> hrs</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'layout_footer.php'; ?>
