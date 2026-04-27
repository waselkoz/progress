<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php'; 
if(($_SESSION['user_role'] ?? '') !== 'admin') die("Access Denied.");

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['semester'])) {
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('current_semester', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$_POST['semester']]);
    echo "<script>window.onload = () => showToast('" . ($lang == 'ar' ? 'تم تحديث السداسي.' : 'Semester updated.') . "');</script>";
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_resit'])) {
    $new_val = $_POST['resit_val'] == '1' ? '1' : '0';
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('resit_period_open', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$new_val]);
    echo "<script>window.onload = () => showToast('" . ($lang == 'ar' ? ($new_val == '1' ? 'الاستدراك مفتوح.' : 'الاستدراك مغلق.') : ($new_val == '1' ? 'Resit period is now OPEN.' : 'Resit period is now CLOSED.')) . "');</script>";
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_grading'])) {
    $new_val = $_POST['grading_val'] == '1' ? '1' : '0';
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('grading_open', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$new_val]);
    echo "<script>window.onload = () => showToast('" . ($lang == 'ar' ? ($new_val == '1' ? 'رصد العلامات مفتوح.' : 'رصد العلامات مغلق.') : ($new_val == '1' ? 'Grading is now OPEN.' : 'Grading is now LOCKED.')) . "');</script>";
}

$stmtS = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_semester'");
$current_semester = $stmtS->fetchColumn() ?: 'S1';

$stmtR = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'resit_period_open'");
$resit_open = $stmtR->fetchColumn() == '1';

$stmtG = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'grading_open'");
$grading_open_val = $stmtG->fetchColumn();
$grading_open = ($grading_open_val === false) ? true : ($grading_open_val == '1');
?>

<div class="card-container">
        <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
            <div>
                <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;"><?= $lang == 'ar' ? 'إعدادات النظام' : 'System Settings' ?></h2>
            </div>
        </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px;">
        
        <div style="background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: #eff6ff; color: #3b82f6; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 style="margin: 0; color: #1e293b; font-size: 18px;"><?= $lang == 'ar' ? 'السداسي' : 'Semester' ?></h3>
            </div>
            <form method="POST" style="display: flex; gap: 10px;">
                <select name="semester" class="form-input" style="margin-bottom: 0; flex: 1;">
                    <option value="S1" <?= $current_semester == 'S1' ? 'selected' : '' ?>><?= $lang == 'ar' ? 'السداسي 1 (S1)' : 'Semester 1 (S1)' ?></option>
                    <option value="S2" <?= $current_semester == 'S2' ? 'selected' : '' ?>><?= $lang == 'ar' ? 'السداسي 2 (S2)' : 'Semester 2 (S2)' ?></option>
                </select>
                <button type="submit" class="btn-primary" style="background: #0A2B8E; padding: 10px 20px;"><?= $lang == 'ar' ? 'تحديث' : 'Update' ?></button>
            </form>
        </div>

        
        <div style="background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: #fff7ed; color: #f59e0b; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-redo-alt"></i>
                </div>
                <h3 style="margin: 0; color: #1e293b; font-size: 18px;"><?= $lang == 'ar' ? 'الاستدراك' : 'Resit' ?></h3>
            </div>
            <form method="POST">
                <input type="hidden" name="toggle_resit" value="1">
                <input type="hidden" name="resit_val" value="<?= $resit_open ? '0' : '1' ?>">
                <button type="submit" class="btn-primary" style="background: <?= $resit_open ? '#ef4444' : '#10b981' ?>; width: 100%; border: none;">
                    <?php if($resit_open): ?>
                        <i class="fas fa-lock"></i> <?= $lang == 'ar' ? 'غلق الوصول للاستدراك' : 'Close Resit Access' ?>
                    <?php else: ?>
                        <i class="fas fa-lock-open"></i> <?= $lang == 'ar' ? 'فتح الوصول للاستدراك' : 'Open Resit Access' ?>
                    <?php endif; ?>
                </button>
            </form>
        </div>

        
        <div style="background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: #f0fdf4; color: #22c55e; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-pen-nib"></i>
                </div>
                <h3 style="margin: 0; color: #1e293b; font-size: 18px;"><?= $lang == 'ar' ? 'رصد العلامات' : 'Grading' ?></h3>
            </div>
            <form method="POST">
                <input type="hidden" name="toggle_grading" value="1">
                <input type="hidden" name="grading_val" value="<?= $grading_open ? '0' : '1' ?>">
                <button type="submit" class="btn-primary" style="background: <?= $grading_open ? '#ef4444' : '#10b981' ?>; width: 100%; border: none;">
                    <?php if($grading_open): ?>
                        <i class="fas fa-shield-alt"></i> <?= $lang == 'ar' ? 'قفل نظام العلامات' : 'Lock Grading System' ?>
                    <?php else: ?>
                        <i class="fas fa-unlock"></i> <?= $lang == 'ar' ? 'فتح نظام العلامات' : 'Unlock Grading System' ?>
                    <?php endif; ?>
                </button>
            </form>
        </div>
    </div>

    
    <h3 style="color: #1e293b; margin-bottom: 20px; font-size: 18px;"><?= $lang == 'ar' ? 'الصيانة الإدارية' : 'Administrative Maintenance' ?></h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <div style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px dashed #cbd5e1;">
            <h4 style="margin: 0 0 10px 0; color: #475569; font-size: 16px;"><?= $lang == 'ar' ? 'الترحيل المؤسسي' : 'Institutional Rollover' ?></h4>
            <p style="font-size: 13px; color: #94a3b8; margin-bottom: 15px;"><?= $lang == 'ar' ? 'تجهيز النظام للسنة الأكاديمية القادمة (أرشفة البيانات الحالية).' : 'Prepare the system for the next academic year (archives current data).' ?></p>
            <button onclick="showToast('<?= $lang == 'ar' ? 'بدأت إجراءات الترحيل.' : 'Rollover procedure initialized.' ?>');" class="btn-primary" style="background: #64748b; width: 100%;"><?= $lang == 'ar' ? 'بدء الترحيل' : 'Initialize Rollover' ?></button>
        </div>

        <div style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px dashed #cbd5e1;">
            <h4 style="margin: 0 0 10px 0; color: #475569; font-size: 16px;"><?= $lang == 'ar' ? 'أمان قاعدة البيانات' : 'Database Security' ?></h4>
            <p style="font-size: 13px; color: #94a3b8; margin-bottom: 15px;"><?= $lang == 'ar' ? 'إنشاء نسخة احتياطية كاملة لجميع بيانات وأصول البوابة.' : 'Generate a complete SQL backup of all portal data and assets.' ?></p>
            <button onclick="showToast('<?= $lang == 'ar' ? 'بدأ تصدير قاعدة البيانات...' : 'Database export started...' ?>');" class="btn-primary" style="background: #64748b; width: 100%;"><?= $lang == 'ar' ? 'تصدير كامل للنظام' : 'Full System Export' ?></button>
        </div>
    </div>
</div>
</div>

<?php include 'layout_footer.php'; ?>
