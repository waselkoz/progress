<?php include 'layout_header.php'; 

$attendances = [];
if(isset($_SESSION['student_id'])) {
    $stmt = $pdo->prepare("
        SELECT a.date_recorded, a.status, a.remarks, c.name as course_name, c.code
        FROM attendance a
        JOIN courses c ON a.course_id = c.id
        WHERE a.student_id = ?
        ORDER BY a.date_recorded DESC
    ");
    $stmt->execute([$_SESSION['student_id']]);
    $attendances = $stmt->fetchAll();
}
?>

<div class="card-container">
    <h2 style="color: #0A2B8E; margin-bottom: 15px;">Attendance & Absences</h2>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Course Name</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($attendances)): ?>
            <tr><td colspan="4">Great job! You have perfect attendance.</td></tr>
            <?php else: ?>
                <?php foreach($attendances as $record): ?>
                <tr>
                    <td><span class="badge badge-info"><?= htmlspecialchars(date('d M Y', strtotime($record['date_recorded']))) ?></span></td>
                    <td><strong><?= htmlspecialchars($record['course_name']) ?></strong> (<?= htmlspecialchars($record['code']) ?>)</td>
                    <td>
                        <?php 
                        if($record['status'] == 'Absent') {
                            echo '<span class="badge badge-danger">Absent</span>';
                        } elseif ($record['status'] == 'Excused') {
                            echo '<span class="badge badge-success" style="background:#f39c12;">Excused</span>';
                        } elseif ($record['status'] == 'Late') {
                            echo '<span class="badge badge-success" style="background:#e67e22;">Late</span>';
                        } else {
                            echo '<span class="badge badge-success">Present</span>';
                        }
                        ?>
                    </td>
                    <td><em style="color: #666; font-size: 13px;"><?= htmlspecialchars($record['remarks'] ?? '-') ?></em></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'layout_footer.php'; ?>
