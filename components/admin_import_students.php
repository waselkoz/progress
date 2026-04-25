<?php
include 'layout_header.php';
if (($_SESSION['user_role'] ?? '') !== 'admin')
    die("Access Denied.");

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_data'])) {
    $raw_data = $_POST['import_data'];
    $default_password = password_hash('123456', PASSWORD_DEFAULT);
    $section_id = $_POST['section_id'] ?? 1;
    $group_id = $_POST['group_id'] ?? 1;
    $enrollment_year = date('Y');

    $lines = explode("\n", trim($raw_data));
    $added = 0;

    $pdo->beginTransaction();
    try {
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line))
                continue;

            if (preg_match('/\'?([A-Za-z\s-]+)\'?\s*,\s*\'?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})\'?/', $line, $sqlMatches)) {
                $name = trim($sqlMatches[1], " '");
                $email = trim($sqlMatches[2], " '");
            } else {
                preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $line, $matches);
                if (isset($matches[0])) {
                    $email = $matches[0];
                    $name = trim(str_replace([$email, ',', "'", '"', '(', ')'], '', $line));
                    $name = trim(preg_replace('/[\t\n\r]/', ' ', $name));
                    $name = preg_replace('/^\d+\s*/', '', $name);
                } else {
                    continue;
                }
            }

            $email = str_replace('@usthb.dz', '@etu.usthb.dz', $email);

            $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, 'student', 1)");
            $stmt->execute([$name, $email, $default_password]);

            if ($stmt->rowCount() > 0) {
                $user_id = $pdo->lastInsertId();
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

$sections = $pdo->query("SELECT id, name FROM section ORDER BY name")->fetchAll();
$groups = $pdo->query("SELECT id, name, section_id FROM `group` ORDER BY name")->fetchAll();
?>

<<div class="card-container">
    <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
        <div>
            <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;">
                <?= $lang == 'ar' ? 'التسجيل المؤسسي: الاستيراد الجماعي' : 'Institutional Enrollment: Batch Intake' ?>
            </h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">
                <?= $lang == 'ar' ? 'توفير حسابات الطلاب الآلي من السجلات الأكاديمية.' : 'Automated student account provisioning from academic records.' ?>
            </p>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div
            style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500;">
            <i class="fas fa-check-circle" style="font-size: 18px;"></i>
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div
            style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500;">
            <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i>
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px;">
        <!-- Left: Instructions -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px;">
            <h4
                style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
                <?= $lang == 'ar' ? 'إرشادات الاستيراد' : 'Intake Guidelines' ?>
            </h4>
            <div style="font-size: 13px; color: #64748b; line-height: 1.6;">
                <p style="margin-bottom: 12px;">
                    <?= $lang == 'ar' ? 'يقبل نظام الاستيراد البيانات النصية الخام لتسجيل الطلاب الجماعي. يرجى الالتزام بالمعايير التالية:' : 'The intake system accepts raw text data for bulk student registration. Please adhere to the following standards:' ?>
                </p>
                <ul style="padding-left: 20px; margin-bottom: 20px;">
                    <li><?= $lang == 'ar' ? 'استخدم صيغة <strong>الفاصلة</strong> (الاسم، البريد الإلكتروني).' : 'Use <strong>Comma Separated</strong> format (Name, Email).' ?>
                    </li>
                    <li><?= $lang == 'ar' ? 'إدخال واحد لكل سطر.' : 'One entry per line.' ?></li>
                    <li><?= $lang == 'ar' ? 'سيتم ترحيل رسائل البريد الإلكتروني تلقائياً إلى نطاق <code>@etu.usthb.dz</code>.' : 'Emails will be automatically migrated to the <code>@etu.usthb.dz</code> domain.' ?>
                    </li>
                    <li><?= $lang == 'ar' ? 'يتم تعيين كلمة المرور الافتراضية على <code>123456</code> لجميع السجلات الجديدة.' : 'Default password is set to <code>123456</code> for all new records.' ?>
                    </li>
                </ul>
                <div
                    style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; font-family: monospace; font-size: 12px; color: #475569;">
                    wassim selama, wassim.selama@usthb.dz<br>
                    iamddine khettab, iamdine.khettab@usthb.dz
                </div>
            </div>
        </div>

        <!-- Right: Enrollment Form -->
        <div
            style="background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label
                            style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;"><?= $lang == 'ar' ? 'الدفعة المستهدفة' : 'Target Academic Section' ?></label>
                        <select name="section_id" class="form-input" required style="padding: 12px;">
                            <?php foreach ($sections as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label
                            style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;"><?= $lang == 'ar' ? 'تخصيص الفوج' : 'Group Allocation' ?></label>
                        <select name="group_id" class="form-input" required style="padding: 12px;">
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 30px;">
                    <label
                        style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;"><?= $lang == 'ar' ? 'إدخال البيانات الجماعية' : 'Batch Data Input' ?></label>
                    <textarea name="import_data" rows="10" class="form-input"
                        placeholder="<?= $lang == 'ar' ? 'أدخل بيانات الطلاب الخام هنا...' : 'Input raw student data here...' ?>"
                        required
                        style="font-family: monospace; padding: 20px; font-size: 13px; line-height: 1.5; background: #fcfdfe;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 20px;">
                    <span style="font-size: 12px; color: #94a3b8; font-style: italic;">
                        <i class="fas fa-lock"></i>
                        <?= $lang == 'ar' ? 'سيتم إنشاء جميع الحسابات بالقيم الافتراضية المؤسسية.' : 'All accounts will be created with institutional defaults.' ?>
                    </span>
                    <button type="submit" class="btn-primary"
                        style="padding: 14px 40px; background: #0A2B8E; font-weight: 700; border: none; box-shadow: 0 4px 6px -1px rgba(10, 43, 142, 0.2);">
                        <i class="fas fa-user-plus" style="margin-right: 10px;"></i>
                        <?= $lang == 'ar' ? 'تنفيذ التسجيل' : 'Execute Enrollment' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>

    <?php include 'layout_footer.php'; ?>