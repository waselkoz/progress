<?php include 'layout_header.php';

$user_role = $_SESSION['user_role'] ?? 'student';
$selected_section = $_GET['section_id'] ?? ($student_info['section_id'] ?? null);

$exams = [];
if ($user_role === 'teacher' && isset($_SESSION['teacher_id'])) {
    $stmt = $pdo->prepare("
        SELECT e.exam_date, e.room, e.exam_type, c.name, c.code
        FROM exams e
        JOIN courses c ON e.course_id = c.id
        JOIN course_assignment ca ON ca.course_id = c.id
        WHERE ca.teacher_id = ?
        ORDER BY e.exam_date ASC
    ");
    $stmt->execute([$_SESSION['teacher_id']]);
    $exams = $stmt->fetchAll();
} elseif ($selected_section) {
    $stmt = $pdo->prepare("
        SELECT e.exam_date, e.room, e.exam_type, c.name, c.code
        FROM exams e
        JOIN courses c ON e.course_id = c.id
        JOIN section s ON c.speciality_id = s.speciality_id AND c.year_id = s.year_id
        WHERE s.id = ?
        ORDER BY e.exam_date ASC
    ");
    $stmt->execute([$selected_section]);
    $exams = $stmt->fetchAll();
}
?>

<div class="card-container">
    <div class="page-actions">
        <h2 class="page-title">Exam Dates &amp; Schedule</h2>
        <?php if ($user_role === 'admin'): ?>
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <select name="section_id" class="form-input" style="padding: 5px 10px; min-width: 150px;"
                    onchange="this.form.submit()">
                    <option value="">Select Section...</option>
                    <?php
                    $secs = $pdo->query("SELECT id, name FROM section ORDER BY name")->fetchAll();
                    foreach ($secs as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_section == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
    </div>
    <div class="alert-banner-red" Official Examination Period</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Module</th>
                    <th>Type</th>
                    <th>Examination Hall</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exams)): ?>
                    <tr>
                        <td colspan="5">No exams scheduled for your section yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($exams as $e):
                        $date = new DateTime($e['exam_date']);
                        $is_past = $date < new DateTime();
                        ?>
                        <tr style="<?= $is_past ? 'opacity: 0.6;' : '' ?>">
                            <td style="font-weight: 600; color: #0A2B8E;">
                                <?= $date->format('d M Y') ?><br>
                                <span style="font-size: 13px; color: #718096;"><?= $date->format('H:i') ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($e['name']) ?></strong><br>
                                <span style="font-size: 12px; color: #a0aec0;"><?= htmlspecialchars($e['code']) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $e['exam_type'] === 'Resit' ? 'badge-danger' : 'badge-info' ?>">
                                    <?= strtoupper($e['exam_type']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px; color: #4a5568;">
                                    <span>📍</span>
                                    <strong><?= htmlspecialchars($e['room']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <?php if ($is_past): ?>
                                    <span class="badge" style="background: #e2e8f0; color: #64748b;">COMPLETED</span>
                                <?php else: ?>
                                    <span class="badge badge-success">UPCOMING</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include 'layout_footer.php'; ?>