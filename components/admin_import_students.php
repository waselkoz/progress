<?php
include 'layout_header.php';
if(($_SESSION['user_role'] ?? '') !== 'admin') die("Access Denied.");

$success_msg = "";
$error_msg = "";

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_data'])) {
    $raw_data = $_POST['import_data'];
    $default_password = password_hash('123456', PASSWORD_DEFAULT);
    $section_id = $_POST['section_id'] ?? 1;
    $group_id = $_POST['group_id'] ?? 1;
    $enrollment_year = date('Y');

    $lines = explode("\n", trim($raw_data));
    $added = 0;

    $pdo->beginTransaction();
    try {
        foreach($lines as $line) {
            $line = trim($line);
            if(empty($line)) continue;
            
            // Parse dense text, TSV, CSV, or pure raw SQL dump rows:
            // Example: (1, 'NAME', 'EMAIL@dz', 'HASH', 'student', NULL)
            if (preg_match('/\'?([A-Za-z\s-]+)\'?\s*,\s*\'?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})\'?/', $line, $sqlMatches)) {
                $name = trim($sqlMatches[1], " '");
                $email = trim($sqlMatches[2], " '");
            } else {
                // General fallback: Regex find email, use rest as name
                preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $line, $matches);
                if(isset($matches[0])) {
                    $email = $matches[0];
                    $name = trim(str_replace([$email, ',', "'", '"', '(', ')'], '', $line));
                    $name = trim(preg_replace('/[\t\n\r]/', ' ', $name));
                    $name = preg_replace('/^\d+\s*/', '', $name); // remove leading numbers if any
                } else {
                    continue; // skip invalid lines completely
                }
            }

            // Insert User
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, 'student', 1)");
            $stmt->execute([$name, $email, $default_password]);
            
            if($stmt->rowCount() > 0) {
                $user_id = $pdo->lastInsertId();
                // Insert Student
                $student_num = "STU" . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO student (user_id, student_number, section_id, group_id, enrollment_year) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $student_num, $section_id, $group_id, $enrollment_year]);
                $added++;
            }
        }
        $pdo->commit();
        $success_msg = "Successfully created $added new student accounts!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Import failed: " . $e->getMessage();
    }
}

// Fetch sections and groups for the dropdown
$sections = $pdo->query("SELECT id, name FROM section ORDER BY name")->fetchAll();
$groups = $pdo->query("SELECT id, name, section_id FROM `group` ORDER BY name")->fetchAll();
?>

<div class="card-container">
    <h2 class="page-title">Bulk Student Importer</h2>
    
    <?php if($success_msg): ?>
        <div style="background: #C6F6D5; color: #22543D; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <strong>Success:</strong> <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>
    
    <?php if($error_msg): ?>
        <div style="background: #FED7D7; color: #822727; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <strong>Error:</strong> <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
        <h4 style="margin-bottom: 15px; color:#1e429f;">Paste Student Data</h4>
        <p style="color: #4a5568; margin-bottom: 20px; font-size: 14px;">
            Format each line as: <code>Student Name, email@usthb.dz</code> (Comma separated).<br>
            All imported students will be assigned the default password <code>123456</code> and instantly activated.
        </p>

        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:600; color:#4a5568;">Assign to Section:</label>
                    <select name="section_id" class="form-input" required>
                        <?php foreach($sections as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:600; color:#4a5568;">Assign to Group:</label>
                    <select name="group_id" class="form-input" required>
                        <?php foreach($groups as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <textarea name="import_data" rows="12" class="form-input" placeholder="ABAOUI MELISSA-LYNA, abaoui.melissalyna@usthb.dz&#10;ABBAS MAYA MYRIAM, abbas.mayamyriam@usthb.dz" required style="font-family: monospace;"></textarea>
            
            <button type="submit" class="btn-primary" style="margin-top: 20px;">Import Students</button>
        </form>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
