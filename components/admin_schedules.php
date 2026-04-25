<?php include 'layout_header.php'; 

if ($user_role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$success = "";
$error = "";

$days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
$slots = [
    '08:00' => '08:00 - 09:30',
    '09:40' => '09:40 - 11:10',
    '11:20' => '11:20 - 12:50',
    '13:00' => '13:00 - 14:30',
    '14:40' => '14:40 - 16:10',
    '16:20' => '16:20 - 17:50'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_timetable'])) {
        $course_id = $_POST['course_id'];
        $day = $_POST['day'];
        $start = $_POST['start_time'];
        $room = $_POST['room'];
        
        $stmt = $pdo->prepare("INSERT INTO timetable (course_id, day_of_week, start_time, room) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$course_id, $day, $start, $room])) {
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
    } elseif (isset($_POST['generate_random'])) {
        $pdo->exec("DELETE FROM timetable");
        $pdo->exec("DELETE FROM exams");
        
        $all_courses = $pdo->query("SELECT id FROM courses")->fetchAll(PDO::FETCH_COLUMN);
        $rooms = ['Amphi A', 'Amphi B', 'Hall 102', 'Hall 204', 'Lab 15', 'Amphi 4'];
        
        foreach($all_courses as $cid) {
            $num_slots = rand(1, 2);
            for($i=0; $i<$num_slots; $i++) {
                $d = $days[array_rand($days)];
                $s = array_keys($slots)[array_rand($slots)];
                $r = $rooms[array_rand($rooms)];
                $pdo->prepare("INSERT INTO timetable (course_id, day_of_week, start_time, room) VALUES (?, ?, ?, ?)")->execute([$cid, $d, $s, $r]);
            }
        }
        
        $start_date = new DateTime('2026-05-15 09:00:00');
        foreach($all_courses as $idx => $cid) {
            $exam_date = clone $start_date;
            $exam_date->modify("+" . ($idx * 2) . " days");
            $exam_date->modify("+" . rand(0, 5) . " hours");
            $r = $rooms[array_rand($rooms)];
            $pdo->prepare("INSERT INTO exams (course_id, exam_date, room, exam_type) VALUES (?, ?, ?, 'Final')")->execute([$cid, $exam_date->format('Y-m-d H:i:s'), $r]);
        }
        
        $success = "Institutional schedule successfully generated and published!";
    }
}

$courses = $pdo->query("SELECT id, name, code FROM courses ORDER BY name")->fetchAll();
$timetable = $pdo->query("SELECT t.*, c.name as course_name FROM timetable t JOIN courses c ON t.course_id = c.id ORDER BY FIELD(day_of_week, 'Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday'), start_time")->fetchAll();
$exams = $pdo->query("SELECT e.*, c.name as course_name FROM exams e JOIN courses c ON e.course_id = c.id ORDER BY exam_date")->fetchAll();
?>

<div class="card-container">
    <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;"><?= $lang == 'ar' ? 'إدارة المخططات الزمنية' : 'Institutional Scheduling' ?></h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;"><?= $lang == 'ar' ? 'نشر الجداول الدراسية وتقويمات الامتحانات الرسمية.' : 'Publish official course timetables and examination calendars.' ?></p>
        </div>
        <form method="POST" onsubmit="return confirm('<?= $lang == 'ar' ? 'هل أنت متأكد؟ سيؤدي هذا إلى إعادة تعيين جميع الجداول الحالية.' : 'Are you sure? This will reset all current schedules.' ?>');">
            <button type="submit" name="generate_random" class="btn-primary" style="background: #10b981; border: none; padding: 12px 25px; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);">
                <i class="fas fa-magic" style="margin-right: 8px;"></i> <?= $lang == 'ar' ? 'توليد جدول آلي' : 'Auto-Generate Schedule' ?>
            </button>
        </form>
    </div>
    
    <?php if($success): ?><div style="background: #ecfdf5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #d1fae5;"><?= $success ?></div><?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        
        <div style="background: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="color: #1e293b; margin-bottom: 20px; font-size: 18px;"><?= $lang == 'ar' ? 'إضافة حصة دراسية' : 'Add Timetable Slot' ?></h3>
            <form method="POST">
                <input type="hidden" name="add_timetable" value="1">
                <label class="form-label"><?= $lang == 'ar' ? 'المقياس' : 'Module' ?></label>
                <select name="course_id" class="form-input" required>
                    <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label class="form-label"><?= $lang == 'ar' ? 'اليوم' : 'Day' ?></label>
                        <select name="day" class="form-input" required>
                            <?php foreach($days as $d): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label"><?= $lang == 'ar' ? 'القاعة' : 'Room' ?></label>
                        <input type="text" name="room" class="form-input" placeholder="e.g. Amphi A" required>
                    </div>
                </div>
                
                <label class="form-label"><?= $lang == 'ar' ? 'الفترة الزمنية' : 'Time Slot' ?></label>
                <select name="start_time" class="form-input">
                    <?php foreach($slots as $time => $label): ?>
                        <option value="<?= $time ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px; background: #0A2B8E;"><?= $lang == 'ar' ? 'تسجيل الحصة' : 'Register Slot' ?></button>
            </form>
        </div>

        <div style="background: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="color: #1e293b; margin-bottom: 20px; font-size: 18px;"><?= $lang == 'ar' ? 'برمجة امتحان' : 'Schedule an Exam' ?></h3>
            <form method="POST">
                <input type="hidden" name="add_exam" value="1">
                <label class="form-label"><?= $lang == 'ar' ? 'المقياس' : 'Module' ?></label>
                <select name="course_id" class="form-input" required>
                    <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label class="form-label"><?= $lang == 'ar' ? 'تاريخ الامتحان' : 'Exam Date' ?></label>
                        <input type="datetime-local" name="exam_date" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label"><?= $lang == 'ar' ? 'النوع' : 'Type' ?></label>
                        <select name="exam_type" class="form-input">
                            <option value="Final"><?= $lang == 'ar' ? 'امتحان نهائي' : 'Final Exam' ?></option>
                            <option value="Resit"><?= $lang == 'ar' ? 'استدراك' : 'Resit' ?></option>
                        </select>
                    </div>
                </div>
                
                <label class="form-label"><?= $lang == 'ar' ? 'قاعة الامتحان' : 'Examination Hall' ?></label>
                <input type="text" name="room" class="form-input" placeholder="e.g. Amphi 4" required>
                
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px; background: #0A2B8E;"><?= $lang == 'ar' ? 'نشر موعد الامتحان' : 'Publish Exam Date' ?></button>
            </form>
        </div>
    </div>

    <div style="margin-top: 40px; display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px;">
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;">
            <h3 style="color: #1e293b; margin-bottom: 15px; font-size: 16px;"><?= $lang == 'ar' ? 'المخطط الزمني الشامل الحالي' : 'Current Global Timetable' ?></h3>
            <table class="data-table" style="box-shadow: none;">
                <thead style="background: #f8fafc;">
                    <tr><th>Day</th><th>Slot</th><th>Module</th><th>Room</th></tr>
                </thead>
                <tbody>
                    <?php foreach($timetable as $t): ?>
                    <tr>
                        <td><span class="badge" style="background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe;"><?= $t['day_of_week'] ?></span></td>
                        <td><?= $slots[substr($t['start_time'],0,5)] ?? substr($t['start_time'],0,5) ?></td>
                        <td><strong><?= htmlspecialchars($t['course_name']) ?></strong></td>
                        <td><span style="color: #64748b;"><i class="fas fa-map-marker-alt" style="margin-right: 5px;"></i><?= htmlspecialchars($t['room']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;">
            <h3 style="color: #1e293b; margin-bottom: 15px; font-size: 16px;"><?= $lang == 'ar' ? 'قائمة الامتحانات الشاملة' : 'Global Exam List' ?></h3>
            <table class="data-table" style="box-shadow: none;">
                <thead style="background: #f8fafc;">
                    <tr><th>Date</th><th>Module</th><th>Type</th></tr>
                </thead>
                <tbody>
                    <?php foreach($exams as $ex): ?>
                    <tr>
                        <td style="font-size: 13px; font-weight: 600;"><?= date('d/m H:i', strtotime($ex['exam_date'])) ?></td>
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
