<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php'; 

$modules = [];
if(isset($student_info['section_id'])) {
    $stmt = $pdo->prepare("
        SELECT c.name, c.code, c.coefficient, c.credits, c.semester, c.hours
        FROM courses c
        JOIN section s ON c.speciality_id = s.speciality_id AND c.year_id = s.year_id
        WHERE s.id = ?
        ORDER BY c.semester, c.name
    ");
    $stmt->execute([$student_info['section_id']]);
    $modules = $stmt->fetchAll();
}
?>

<div class="card-container">
    <h2 style="color: #0A2B8E; margin-bottom: 25px;">Module Catalog</h2>
    
    <div class="info-panel-blue">
        <p>The following list includes all mandatory modules for your speciality and academic year.</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Module Name</th>
                <th>Credits</th>
                <th>Coefficient</th>
                <th>Sem.</th>
                <th>Hours</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($modules)): ?>
                <tr><td colspan="6">No modules found for your program.</td></tr>
            <?php else: ?>
                <?php foreach($modules as $m): ?>
                <tr>
                    <td><span class="badge badge-info"><?= htmlspecialchars($m['code']) ?></span></td>
                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                    <td style="text-align: center;"><?= htmlspecialchars($m['credits']) ?></td>
                    <td style="text-align: center; font-weight: 600;"><?= htmlspecialchars($m['coefficient']) ?></td>
                    <td><span class="badge" style="background: #f1f5f9; color: #475569;"><?= htmlspecialchars($m['semester']) ?></span></td>
                    <td><?= htmlspecialchars($m['hours']) ?>h</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'layout_footer.php'; ?>
