<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php';

$enrolment = null;
if (isset($_SESSION['student_id'])) {
    $stmt = $pdo->prepare("
        SELECT s.student_number, s.enrollment_year, sec.name as section_name, 
               g.name as group_name, sp.name as speciality_name, y.name as year_name,
               s.section_id
        FROM student s
        JOIN `group` g ON s.group_id = g.id
        JOIN section sec ON s.section_id = sec.id
        JOIN speciality sp ON sec.speciality_id = sp.id
        JOIN years y ON sec.year_id = y.id
        WHERE s.id = ?
    ");
    $stmt->execute([$_SESSION['student_id']]);
    $enrolment = $stmt->fetch();

    if ($enrolment) {
        $coursesStmt = $pdo->prepare("SELECT name, code, coefficient, credits, semester FROM courses WHERE speciality_id = (SELECT speciality_id FROM section WHERE id = ?) AND year_id = (SELECT year_id FROM section WHERE id = ?)");
        $coursesStmt->execute([$enrolment['section_id'], $enrolment['section_id']]);
        $assigned_modules = $coursesStmt->fetchAll();
    }
}
?>

<div class="card-container">
    <h2 style="color: #0A2B8E; margin-bottom: 25px;">My Academic Placement</h2>

    <?php if (!$enrolment): ?>
        <p>Your academic placement details have not been finalized.</p>
    <?php else: ?>
        <div
            style="max-width: 600px; margin: 0 auto; background: #f8fafc; padding: 40px; border-radius: 20px; border: 1px solid #e2e8f0; text-align: center;">
            <h3 style="color: #0A2B8E; font-size: 24px; margin-bottom: 10px;">
                <?= htmlspecialchars($enrolment['speciality_name']) ?></h3>
            <p style="color: #718096; margin-bottom: 30px;">Batch Year: <?= htmlspecialchars($enrolment['year_name']) ?></p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; text-align: left;">
                <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #edf2f7;">
                    <p style="font-size: 13px; color: #718096; text-transform: uppercase;">Official Section</p>
                    <p style="font-size: 20px; font-weight: 700; color: #0A2B8E; margin-top: 5px;">
                        <?= htmlspecialchars($enrolment['section_name']) ?></p>
                </div>
                <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #edf2f7;">
                    <p style="font-size: 13px; color: #718096; text-transform: uppercase;">Assigned Group</p>
                    <p style="font-size: 20px; font-weight: 700; color: #0A2B8E; margin-top: 5px;">
                        <?= htmlspecialchars($enrolment['group_name']) ?></p>
                </div>
            </div>

            <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #e2e8f0;">
                <p style="font-size: 14px; color: #718096;">Student Official ID</p>
                <p style="font-size: 18px; font-weight: 600; color: #2d3748; margin-top: 5px;">
                    <?= htmlspecialchars($enrolment['student_number']) ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'layout_footer.php'; ?>