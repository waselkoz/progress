<?php include 'layout_header.php'; 

if ($user_role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_timetable'])) {
        $course_id = $_POST['course_id'];
        $day = $_POST['day'];
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        $room = $_POST['room'];
        
        $stmt = $pdo->prepare("INSERT INTO timetable (course_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$course_id, $day, $start, $end, $room])) {
            $success = "Timetable entry added successfully!";
        }
    } elseif (isset($_POST['add_exam'])) {
        $course_id = $_POST['course_id'];
        $date = $_POST['exam_date'];
        $room = $_POST['room'];
        $type = $_POST['exam_type'];
        
        $stmt = $pdo->prepare("INSERT INTO exams (course_id, exam_date, room, exam_type) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$course_id, $date, $room, $type])) {
            $success = "Exam schedule entry added successfully!";
        }
    }
}

$courses = $pdo->query("SELECT id, name, code FROM courses ORDER BY name")->fetchAll();
$timetable = $pdo->query("SELECT t.*, c.name as course_name FROM timetable t JOIN courses c ON t.course_id = c.id ORDER BY FIELD(day_of_week, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), start_time")->fetchAll();
$exams = $pdo->query("SELECT e.*, c.name as course_name FROM exams e JOIN courses c ON e.course_id = c.id ORDER BY exam_date")->fetchAll();
?>

<div class="card-container">
    <h2 style="color: #0A2B8E; margin-bottom: 25px;">Schedule Management</h2>
    
    <?php if($success): ?><div class="badge badge-success" style="margin-bottom: 20px; padding: 10px; display: block;"><?= $success ?></div><?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        
        <div style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <h3 style="color: #0A2B8E; margin-bottom: 20px;">Add Timetable Slot</h3>
            <form method="POST">
                <input type="hidden" name="add_timetable" value="1">
                <label class="form-label">Module</label>
                <select name="course_id" class="form-input" required>
                    <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label class="form-label">Day</label>
                        <select name="day" class="form-input" required>
                            <option value="Sunday">Sunday</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Room</label>
                        <input type="text" name="room" class="form-input" placeholder="e.g. Hall B" required>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label class="form-label">Start Time</label>
                        <select name="start_time" class="form-input">
                            <option value="08:00">08:00</option>
                            <option value="09:30">09:30</option>
                            <option value="11:00">11:00</option>
                            <option value="12:30">12:30</option>
                            <option value="14:00">14:00</option>
                            <option value="15:30">15:30</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">End Time</label>
                        <select name="end_time" class="form-input">
                            <option value="09:20">09:20</option>
                            <option value="10:50">10:50</option>
                            <option value="12:20">12:20</option>
                            <option value="13:50">13:50</option>
                            <option value="15:20">15:20</option>
                            <option value="16:50">16:50</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Register Slot</button>
            </form>
        </div>

        <div style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <h3 style="color: #0A2B8E; margin-bottom: 20px;">Schedule an Exam</h3>
            <form method="POST">
                <input type="hidden" name="add_exam" value="1">
                <label class="form-label">Module</label>
                <select name="course_id" class="form-input" required>
                    <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label class="form-label">Exam Date</label>
                        <input type="datetime-local" name="exam_date" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Type</label>
                        <select name="exam_type" class="form-input">
                            <option value="Final">Final Exam</option>
                            <option value="Resit">Resit (Rattrapage)</option>
                        </select>
                    </div>
                </div>
                
                <label class="form-label">Exanimation Hall</label>
                <input type="text" name="room" class="form-input" placeholder="e.g. Amphi 4" required>
                
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Publish Exam Date</button>
            </form>
        </div>
    </div>

    <div style="margin-top: 40px; display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px;">
        <div>
            <h3 style="color: #0A2B8E; margin-bottom: 15px;">Current Global Timetable</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Day</th><th>Slot</th><th>Module</th><th>Room</th></tr>
                </thead>
                <tbody>
                    <?php foreach($timetable as $t): ?>
                    <tr>
                        <td><span class="badge badge-info"><?= $t['day_of_week'] ?></span></td>
                        <td><?= substr($t['start_time'],0,5) ?></td>
                        <td><?= htmlspecialchars($t['course_name']) ?></td>
                        <td><strong><?= htmlspecialchars($t['room']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div>
            <h3 style="color: #0A2B8E; margin-bottom: 15px;">Global Exam List</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Date</th><th>Module</th><th>Type</th></tr>
                </thead>
                <tbody>
                    <?php foreach($exams as $ex): ?>
                    <tr>
                        <td style="font-size: 13px;"><?= date('d/m H:i', strtotime($ex['exam_date'])) ?></td>
                        <td><?= htmlspecialchars($ex['course_name']) ?></td>
                        <td><span class="badge <?= $ex['exam_type']=='Resit'?'badge-danger':'badge-info'?>"><?= $ex['exam_type'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
