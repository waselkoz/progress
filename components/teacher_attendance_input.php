<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php'; 
if(($_SESSION['user_role'] ?? '') !== 'teacher') die("Access Denied.");

$class_id = $_GET['class_id'] ?? null;
if(!$class_id) die("No class selected.");

$stmt = $pdo->prepare("
    SELECT c.name, sec.name as sec_name, g.name as grp_name, ca.course_id, ca.section_id, ca.group_id 
    FROM course_assignment ca JOIN courses c ON ca.course_id = c.id JOIN section sec ON ca.section_id = sec.id JOIN `group` g ON ca.group_id = g.id 
    WHERE ca.id = ? AND ca.teacher_id = ?
");
$stmt->execute([$class_id, $_SESSION['teacher_id']]);
$class_info = $stmt->fetch();
if(!$class_info) die("Invalid class assignment.");


if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['attendance'])) {
    $date = date('Y-m-d'); 
    foreach($_POST['attendance'] as $student_id => $data) {
        $status = $data['status'];
        $remarks = $data['remarks'];
        if($status !== 'None') {
            $stmt = $pdo->prepare("
                INSERT INTO attendance (student_id, course_id, date_recorded, status, remarks) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$student_id, $class_info['course_id'], $date, $status, $remarks]);
        }
    }
    echo "<script>window.onload = () => showToast('Attendance registered for Today.');</script>";
}

$students = [];
$stmt = $pdo->prepare("
    SELECT s.id, u.name, s.student_number
    FROM student s JOIN users u ON s.user_id = u.id
    WHERE s.group_id = ? ORDER BY u.name
");
$stmt->execute([$class_info['group_id']]);
$students = $stmt->fetchAll();
?>

<div class="card-container">
    <div class="page-actions">
        <div>
            <h2 class="page-title">Take Attendance: <?= htmlspecialchars($class_info['name']) ?></h2>
            <p class="helper-text" style="margin-bottom:0;">Date: <?= date('d M Y') ?> | Roster: <?= htmlspecialchars($class_info['sec_name']) ?> - <?= htmlspecialchars($class_info['grp_name']) ?></p>
        </div>
        <a href="teacher_attendance.php" class="back-link">&larr; Back to Tracking</a>
    </div>
    
    <form method="POST">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Select Status</th>
                    <th>Remarks / Excuse</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($students)): ?>
                <tr><td colspan="3">No students found.</td></tr>
                <?php else: ?>
                    <?php foreach($students as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['name']) ?></strong><br><em style="color:#888; font-size: 12px;"><?= htmlspecialchars($s['student_number']) ?></em></td>
                        <td style="width: 250px;">
                            <select class="form-input" style="margin-bottom: 0;" name="attendance[<?= $s['id'] ?>][status]">
                                <option value="None">-- Select Status --</option>
                                <option value="Present">Present</option>
                                <option value="Absent">Absent</option>
                                <option value="Late">Late</option>
                                <option value="Excused">Excused</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-input" style="margin-bottom: 0;"
                                   name="attendance[<?= $s['id'] ?>][remarks]" placeholder="Late due to traffic...">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if(!empty($students)): ?>
        <div style="margin-top: 25px; text-align: right;">
            <button type="submit" class="logout-btn" style="background: #10b981; box-shadow: 0 4px 15px rgba(16,185,129,0.3); padding: 14px 30px; font-size: 16px;">
                Submit Roll Call
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php include 'layout_footer.php'; ?>
