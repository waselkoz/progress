<?php include 'layout_header.php'; 


$classes = [];
if(isset($_SESSION['teacher_id'])) {
    $stmt = $pdo->prepare("
        SELECT ca.id, c.code, c.name, sec.name as section_name, g.name as group_name
        FROM course_assignment ca
        JOIN courses c ON ca.course_id = c.id
        JOIN section sec ON ca.section_id = sec.id
        JOIN `group` g ON ca.group_id = g.id
        WHERE ca.teacher_id = ?
    ");
    $stmt->execute([$_SESSION['teacher_id']]);
    $classes = $stmt->fetchAll();
}
?>

<div class="card-container">
    <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
        <div>
            <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;"><?= $lang == 'ar' ? 'المواد' : 'Courses' ?></h2>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
        <?php if(empty($classes)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 60px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <i class="fas fa-folder-open" style="font-size: 40px; color: #94a3b8; margin-bottom: 15px;"></i>
                <p style="color: #64748b; font-size: 16px;"><?= $lang == 'ar' ? 'ليس لديك أي مهام تدريسية نشطة لهذه الدورة الأكاديمية.' : 'You currently have no active class assignments for this academic cycle.' ?></p>
            </div>
        <?php else: ?>
            <?php foreach($classes as $c): ?>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-top: 4px solid #0A2B8E;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <span style="background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?= htmlspecialchars($c['code']) ?>
                        </span>
                    </div>
                    <h3 style="margin: 0 0 10px 0; color: #1e293b; font-size: 18px; line-height: 1.4;"><?= htmlspecialchars($c['name']) ?></h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 25px;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 13px;">
                            <i class="fas fa-layer-group" style="width: 16px;"></i>
                            <span><?= $lang == 'ar' ? 'الدفعة' : 'Section' ?>: <strong><?= htmlspecialchars($c['section_name']) ?></strong></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 13px;">
                            <i class="fas fa-users" style="width: 16px;"></i>
                            <span><?= $lang == 'ar' ? 'الفوج' : 'Group' ?>: <strong><?= htmlspecialchars($c['group_name']) ?></strong></span>
                        </div>
                    </div>

                    <a href="teacher_grade_input.php?class_id=<?= $c['id'] ?>" class="btn-primary" style="display: block; text-align: center; text-decoration: none; background: #0A2B8E; font-size: 14px; padding: 12px;">
                        <i class="fas fa-clipboard-check" style="margin-right: 8px;"></i> <?= $lang == 'ar' ? 'رصد العلامات' : 'Input Grades' ?>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
