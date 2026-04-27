<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php'; 

$user_role = $_SESSION['user_role'] ?? 'student';
$selected_section = $_GET['section_id'] ?? ($student_info['section_id'] ?? null);

$days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
$slots = [
    '08:00' => '08:00 - 09:30',
    '09:40' => '09:40 - 11:10',
    '11:20' => '11:20 - 12:50',
    '13:00' => '13:00 - 14:30',
    '14:40' => '14:40 - 16:10',
    '16:20' => '16:20 - 17:50'
];

$grid = [];
foreach ($days as $day) {
    foreach ($slots as $time => $label) {
        $grid[$day][$time] = [];
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
        $grid[$e['day_of_week']][$time_key][] = $e;
    }
}

$day_names = [
    'Saturday'  => ($lang == 'ar' ? 'السبت' : 'Saturday'),
    'Sunday'    => ($lang == 'ar' ? 'الأحد' : 'Sunday'),
    'Monday'    => ($lang == 'ar' ? 'الاثنين' : 'Monday'),
    'Tuesday'   => ($lang == 'ar' ? 'الثلاثاء' : 'Tuesday'),
    'Wednesday' => ($lang == 'ar' ? 'الأربعاء' : 'Wednesday'),
    'Thursday'  => ($lang == 'ar' ? 'الخميس' : 'Thursday')
];
?>
<div class="card-container" style="background: #fff; padding: 40px; border-radius: 0; border: 1px solid #000;">
    
    <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <img src="../img/USTHB.png" alt="USTHB" style="width: 70px;">
            <div style="flex: 1; padding: 0 20px;">
                <h3 style="margin: 0; font-size: 16px; text-transform: uppercase;">University of Science and Technology Houari Boumediene</h3>
                <p style="margin: 5px 0; font-size: 13px; font-weight: 600;">Vice-rectorate in charge of the higher education of graduation, the continuing education et degrees</p>
            </div>
            <div style="width: 70px; font-size: 10px; text-align: right;">USTHB<br>Algeria</div>
        </div>
        <h2 style="margin: 20px 0 10px 0; font-size: 20px; text-decoration: underline; text-transform: uppercase;">
            <?= $lang == 'ar' ? 'جدول الحصص الأسبوعي' : 'Weekly Class Schedule' ?>
        </h2>
        <div style="display: flex; justify-content: center; gap: 40px; font-weight: 700; font-size: 14px;">
            <span><?= $lang == 'ar' ? 'السنة الجامعية:' : 'College year:' ?> 2025/2026</span>
            <span><?= $lang == 'ar' ? 'السداسي:' : 'Semester:' ?> 2</span>
            <span><?= $lang == 'ar' ? 'التاريخ:' : 'Date:' ?> <?= date('d/m/Y') ?></span>
        </div>
    </div>

    <div class="page-actions" style="margin-bottom: 20px;">
        <?php if ($user_role === 'admin'): ?>
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <select name="section_id" class="form-input" style="padding: 5px 10px; min-width: 150px; border: 1px solid #000;" onchange="this.form.submit()">
                    <option value=""><?= $lang == 'ar' ? 'اختر الدفعة...' : 'Select Section...' ?></option>
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
        <table style="width: 100%; border-collapse: collapse; border: 2px solid #000; table-layout: fixed;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; width: 120px; height: 50px; background: #fff;"></th>
                    <?php foreach ($slots as $time => $label): ?>
                        <th style="border: 1px solid #000; font-size: 13px; background: #fff; color: #000; text-align: center;"><?= $label ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $day): ?>
                <tr>
                    <td style="border: 1px solid #000; height: 100px; font-weight: 800; text-align: center; background: #fff; font-size: 14px;">
                        <?= $day_names[$day] ?>
                    </td>
                    <?php foreach ($slots as $time => $label): 
                        $cell_entries = $grid[$day][$time];
                    ?>
                        <td style="border: 1px solid #000; vertical-align: top; padding: 5px; background: #fff;">
                            <?php if (!empty($cell_entries)): ?>
                                <?php foreach($cell_entries as $entry): ?>
                                    <div style="font-size: 11px; margin-bottom: 8px; line-height: 1.2;">
                                        <div style="font-weight: 900; text-transform: uppercase;"><?= htmlspecialchars($entry['name']) ?></div>
                                        <div style="display: flex; justify-content: space-between; margin-top: 2px;">
                                            <span style="background: #fff; border: 1px solid #000; padding: 0 3px; font-weight: 700; font-size: 9px;"><?= htmlspecialchars($entry['code']) ?></span>
                                            <span style="font-style: italic; font-size: 10px;"><?= htmlspecialchars($entry['room']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 20px; font-size: 11px; font-style: italic; color: #666; text-align: right;">
        Institutional Progress Portal - Electronic Copy
    </div>
</div>

<?php include 'layout_footer.php'; ?>
