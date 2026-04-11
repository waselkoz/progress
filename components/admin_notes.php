<?php include 'layout_header.php'; 
if(($_SESSION['user_role'] ?? '') !== 'admin') die("Access Denied.");

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'])) {
    $sid = $_POST['student_id'];
    $cid = $_POST['course_id'];
    $val = $_POST['grade'] !== '' ? $_POST['grade'] : null;
    $rat = $_POST['rattrapage'] !== '' ? $_POST['rattrapage'] : null;
    $is_dette = isset($_POST['is_dette']) ? 1 : 0;
    
    $stmt = $pdo->prepare("
        INSERT INTO grades (student_id, course_id, grade, rattrapage_grade, is_dette) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            grade = VALUES(grade), 
            rattrapage_grade = VALUES(rattrapage_grade),
            is_dette = VALUES(is_dette)
    ");
    $stmt->execute([$sid, $cid, $val, $rat, $is_dette]);
    echo "<script>window.onload = () => showToast('Grade recorded with Debt status updated!');</script>";
}

$students = $pdo->query("SELECT s.id, u.name FROM student s JOIN users u ON s.user_id = u.id ORDER BY u.name")->fetchAll();
$courses = $pdo->query("SELECT id, name, code FROM courses ORDER BY name")->fetchAll();
?>

<div class="card-container">
    <h2 class="page-title" style="margin-bottom: 25px;">Grade Management (Administration)</h2>
    
    <div class="info-panel">
        <h4>Quick Input</h4>
        <form method="POST" class="form-grid">
            <div>
                <label class="form-label">Student</label>
                <select name="student_id" class="form-input" required>
                    <?php foreach($students as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Module (Course)</label>
                <select name="course_id" class="form-input" required>
                    <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Exam Grade (/20)</label>
                <input type="number" step="0.01" name="grade" class="form-input" min="0" max="20">
            </div>
            <div>
                <label class="form-label">Resit Grade (Optional)</label>
                <input type="number" step="0.01" name="rattrapage" class="form-input" min="0" max="20">
            </div>
            <div class="dette-row">
                <input type="checkbox" name="is_dette" id="dette-check" value="1">
                <label for="dette-check">Mark as Debt (Module de Dette)</label>
            </div>
            <button type="submit" class="logout-btn full-span" style="background: #0A2B8E; border: none; cursor: pointer; padding: 15px;">Save Official Grade</button>
        </form>
    </div>

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
