<?php include 'layout_header.php'; 
if(($_SESSION['user_role'] ?? '') !== 'admin') die("Access Denied.");

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['semester'])) {
    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'current_semester'");
    $stmt->execute([$_POST['semester']]);
    echo "<script>window.onload = () => showToast('System Semester updated to " . $_POST['semester'] . "');</script>";
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_resit'])) {
    $new_val = $_POST['resit_val'] == '1' ? '1' : '0';
    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'resit_period_open'");
    $stmt->execute([$new_val]);
    echo "<script>window.onload = () => showToast('Resit Period is now " . ($new_val == '1' ? 'OPEN' : 'CLOSED') . "');</script>";
}

$stmtS = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_semester'");
$current_semester = $stmtS->fetchColumn() ?: 'S1';

$stmtR = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'resit_period_open'");
$resit_open = $stmtR->fetchColumn() == '1';
?>

<div class="card-container">
    <h2 style="color: #0A2B8E; margin-bottom: 25px;">System & Configuration</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px;">
        <div style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <h3 style="margin-bottom: 10px; color: #0A2B8E; font-size: 18px;">Active Academic Semester</h3>
            <p style="font-size: 13px; color: #718096; margin-bottom: 15px;">Set the current teaching semester.</p>
            <form method="POST" style="display: flex; gap: 10px;">
                <select name="semester" class="form-input" style="margin-bottom: 0;">
                    <option value="S1" <?= $current_semester == 'S1' ? 'selected' : '' ?>>Semester 1 (S1)</option>
                    <option value="S2" <?= $current_semester == 'S2' ? 'selected' : '' ?>>Semester 2 (S2)</option>
                </select>
                <button type="submit" class="logout-btn" style="background: #0A2B8E; border: none; min-width: 120px;">Update</button>
            </form>
        </div>

        <div style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <h3 style="margin-bottom: 10px; color: #0A2B8E; font-size: 18px;">Resit (Rattrapage) Control</h3>
            <p style="font-size: 13px; color: #718096; margin-bottom: 15px;">Enable/Disable global resit grade input for Teachers.</p>
            <form method="POST">
                <input type="hidden" name="toggle_resit" value="1">
                <input type="hidden" name="resit_val" value="<?= $resit_open ? '0' : '1' ?>">
                <button type="submit" class="logout-btn" style="background: <?= $resit_open ? '#e74c3c' : '#2ecc71' ?>; border: none; width: 100%;">
                    <?= $resit_open ? 'Close Resit Period' : 'Open Resit Period' ?>
                </button>
            </form>
        </div>
    </div>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px;">
            <h3 style="color: #2d3748; margin-bottom: 10px; font-size: 16px;">Year Rollover</h3>
            <p style="font-size: 13px; color: #718096;">Archive current data for the next session.</p>
            <button onclick="showToast('Archiving initialized...');" class="logout-btn" style="margin-top: 15px; background: #0A2B8E; border: none; width: 100%;">Initialize</button>
        </div>

        <div style="flex: 1; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px;">
            <h3 style="color: #2d3748; margin-bottom: 10px; font-size: 16px;">Maintenance</h3>
            <p style="font-size: 13px; color: #718096;">Lock portals for system maintenance.</p>
            <button onclick="showToast('Locking portals...');" class="logout-btn" style="margin-top: 15px; background: #e74c3c; border: none; width: 100%;">Enable Lock</button>
        </div>
        
        <div style="flex: 1; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px;">
            <h3 style="color: #2d3748; margin-bottom: 10px; font-size: 16px;">Backups</h3>
            <p style="font-size: 13px; color: #718096;">Export full institutional database dump.</p>
            <button onclick="showToast('Exporting SQL...');" class="logout-btn" style="margin-top: 15px; background: #0A2B8E; border: none; width: 100%;">Export DB</button>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
