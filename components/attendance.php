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
    <h2 style="color: #0A2B8E; margin-bottom: 15px;"><?= $lang == 'ar' ? 'الحضور والغياب' : 'Attendance & Absences' ?></h2>
    
    <table class="data-table">
        <thead>
            <tr>
                <th><?= $lang == 'ar' ? 'التاريخ' : 'Date' ?></th>
                <th><?= $lang == 'ar' ? 'اسم المقياس' : 'Course Name' ?></th>
                <th><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></th>
                <th><?= $lang == 'ar' ? 'ملاحظات' : 'Remarks' ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($attendances)): ?>
            <tr><td colspan="4" style="text-align: center; padding: 30px; color: #64748b;"><?= $lang == 'ar' ? 'عمل رائع! حضورك مكتمل.' : 'Great job! You have perfect attendance.' ?></td></tr>
            <?php else: ?>
                <?php foreach($attendances as $record): ?>
                <tr>
                    <td><span class="badge badge-info" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;"><?= htmlspecialchars(date('d M Y', strtotime($record['date_recorded']))) ?></span></td>
                    <td><strong><?= htmlspecialchars($record['course_name']) ?></strong> (<?= htmlspecialchars($record['code']) ?>)</td>
                    <td>
                        <?php 
                        if($record['status'] == 'Absent') {
                            echo '<span class="badge badge-danger">' . ($lang == 'ar' ? 'غائب' : 'Absent') . '</span>';
                        } elseif ($record['status'] == 'Excused') {
                            echo '<span class="badge" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;">' . ($lang == 'ar' ? 'مبرر' : 'Excused') . '</span>';
                        } elseif ($record['status'] == 'Late') {
                            echo '<span class="badge" style="background:#fff7ed; color:#9a3412; border:1px solid #ffedd5;">' . ($lang == 'ar' ? 'متأخر' : 'Late') . '</span>';
                        } else {
                            echo '<span class="badge badge-success">' . ($lang == 'ar' ? 'حاضر' : 'Present') . '</span>';
                        }
                        ?>
                    </td>
                    <td><em style="color: #64748b; font-size: 13px;"><?= htmlspecialchars($record['remarks'] ?? '-') ?></em></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'layout_footer.php'; ?>
