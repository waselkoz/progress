<?php include 'layout_header.php'; 

$user_role = $_SESSION['user_role'] ?? 'student';
$selected_section = $_GET['section_id'] ?? ($student_info['section_id'] ?? null);

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
$slots = ['08:00', '09:30', '11:00', '12:30', '14:00', '15:30'];

$grid = [];
foreach ($days as $day) {
    foreach ($slots as $slot) {
        $grid[$day][$slot] = null;
    }
}

if ($user_role === 'teacher' && isset($_SESSION['teacher_id'])) {
    $stmt = $pdo->prepare("
        SELECT t.day_of_week, t.start_time, t.end_time, t.room, c.name, c.code
        FROM timetable t
        JOIN courses c ON t.course_id = c.id
        JOIN course_assignment ca ON ca.course_id = c.id
        WHERE ca.teacher_id = ?
    ");
    $stmt->execute([$_SESSION['teacher_id']]);
    $entries = $stmt->fetchAll();
} elseif ($selected_section) {
    $stmt = $pdo->prepare("
        SELECT t.day_of_week, t.start_time, t.end_time, t.room, c.name, c.code
        FROM timetable t
        JOIN courses c ON t.course_id = c.id
        JOIN section s ON c.speciality_id = s.speciality_id AND c.year_id = s.year_id
        WHERE s.id = ?
    ");
    $stmt->execute([$selected_section]);
    $entries = $stmt->fetchAll();
} else {
    $entries = [];
}

foreach ($entries as $e) {
    $time_key = substr($e['start_time'], 0, 5);
    if (isset($grid[$e['day_of_week']][$time_key])) {
        $grid[$e['day_of_week']][$time_key] = $e;
    }
}
?>

<div class="card-container">
    <div class="page-actions">
        <h2 class="page-title">Weekly Class Schedule</h2>
        <?php if ($user_role === 'admin'): ?>
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <select name="section_id" class="form-input" style="padding: 5px 10px; min-width: 150px;" onchange="this.form.submit()">
                    <option value="">Select Section...</option>
                    <?php 
                    $secs = $pdo->query("SELECT id, name FROM section ORDER BY name")->fetchAll();
                    foreach($secs as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_section == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="data-table" style="table-layout: fixed; width: 100%; border: 1px solid #e2e8f0;">
            <thead>
                <tr>
                    <th style="width: 100px; background: #f8fafc;">Time Slot</th>
                    <?php foreach ($days as $day): ?>
                        <th style="text-align: center; background: #e8f0fe; color: #0A2B8E;"><?= $day ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slots as $slot): ?>
                <tr>
                    <td style="background: #f8fafc; font-weight: 600; text-align: center; color: #718096; font-size: 13px;">
                        <?= $slot ?>
                    </td>
                    <?php foreach ($days as $day): 
                        $entry = $grid[$day][$slot];
                    ?>
                        <td style="padding: 10px; height: 100px; vertical-align: top; background: <?= $entry ? '#fff' : '#fcfcfc' ?>; border: 1px solid #edf2f7;">
                            <?php if ($entry): ?>
                            <div class="timetable-cell">
                                    <div class="timetable-code"><?= htmlspecialchars($entry['code']) ?></div>
                                    <div class="timetable-name"><?= htmlspecialchars($entry['name']) ?></div>
                                    <div class="timetable-room">
                                        <span>📍</span>
                                        <strong><?= htmlspecialchars($entry['room']) ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
