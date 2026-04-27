<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php'; 

$profile = null;
if(isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT u.name, u.email, u.role, u.password_changed, 
               s.student_number, s.birth_date, s.enrollment_year,
               t.employee_number, t.hire_date,
               a.admin_level
        FROM users u
        LEFT JOIN student s ON u.id = s.user_id
        LEFT JOIN teacher t ON u.id = t.user_id
        LEFT JOIN admin a ON u.id = a.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
}

$badge_class = 'badge-info';
if (($profile['role'] ?? '') == 'teacher') $badge_class = 'badge-success';
if (($profile['role'] ?? '') == 'admin') $badge_class = 'badge-danger';

$msg = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    if ($new_pass !== $confirm_pass) {
        $msg = "Passwords do not match!";
        $msg_type = "error";
    } elseif (strlen($new_pass) < 6) {
        $msg = "Password must be at least 6 characters.";
        $msg_type = "error";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, password_changed = 1 WHERE id = ?");
        $stmt->execute([$hashed, $_SESSION['user_id']]);
        $profile['password_changed'] = 1;
        $msg = "Password updated successfully!";
        $msg_type = "success";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    $reason = $_POST['reset_reason'];
    $stmt = $pdo->prepare("INSERT INTO password_requests (user_id, reason) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $reason]);
    $msg = $lang == 'ar' ? "تم إرسال الطلب بنجاح." : "Request submitted successfully.";
    $msg_type = "success";
}

$stmtReq = $pdo->prepare("SELECT * FROM password_requests WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
$stmtReq->execute([$_SESSION['user_id']]);
$pending_request = $stmtReq->fetch();
?>

<<div class="card-container">
    <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 40px;">
        <div>
            <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;"><?= $t['id_dossier'] ?></h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;"><?= $lang == 'ar' ? 'السجل الجامعي الرسمي وإدارة المصادقة.' : 'Official university record and authentication management.' ?></p>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 40px;">
        
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px 20px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <div style="width: 120px; height: 120px; background: #f1f5f9; border-radius: 50%; margin: 0 auto 20px auto; display: flex; align-items: center; justify-content: center; font-size: 50px; color: #94a3b8; border: 4px solid #fff; box-shadow: 0 0 0 1px #e2e8f0;">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h3 style="margin: 0; color: #1e293b; font-size: 20px; font-weight: 700;"><?= htmlspecialchars($profile['name'] ?? 'N/A') ?></h3>
                <div style="margin-top: 8px;">
                    <span style="background: <?= ($profile['role'] ?? '') == 'admin' ? '#fef2f2' : (($profile['role'] ?? '') == 'teacher' ? '#f0f9ff' : '#f0fdf4') ?>; 
                                color: <?= ($profile['role'] ?? '') == 'admin' ? '#dc2626' : (($profile['role'] ?? '') == 'teacher' ? '#0369a1' : '#15803d') ?>; 
                                padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid currentColor;">
                        <?= htmlspecialchars($profile['role'] ?? 'Registry User') ?>
                    </span>
                </div>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #f1f5f9; font-size: 13px; color: #64748b;">
                    <i class="fas fa-envelope" style="margin-right: 5px;"></i> <?= htmlspecialchars($profile['email'] ?? 'N/A') ?>
                </div>
            </div>

            <?php if(($profile['role'] ?? '') == 'admin'): ?>
                <div style="background: #fff1f2; border: 1px solid #ffe4e6; border-radius: 12px; padding: 20px; font-size: 13px; color: #be123c;">
                    <div style="font-weight: 800; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-shield-alt"></i> <?= $lang == 'ar' ? 'مستوى الأمان' : 'Security Level' ?>: <?= htmlspecialchars($profile['admin_level'] ?? 'REGULAR') ?>
                    </div>
                    <?= $lang == 'ar' ? 'الحساب لديه امتيازات متقدمة. جميع العمليات الإدارية مراقبة وتخضع للمراجعة المؤسسية.' : 'Account has elevated privileges. All administrative operations are monitored and subject to institutional audit.' ?>
                </div>
            <?php endif; ?>
        </div>
        
        
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin: 0 0 25px 0; color: #1e293b; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-id-card-alt" style="color: #3b82f6;"></i> <?= $t['institutional_data'] ?>
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;"><?= $t['full_name'] ?></label>
                        <div style="font-size: 16px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($profile['name'] ?? 'N/A') ?></div>
                    </div>
                    
                    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;"><?= $t['email_addr'] ?></label>
                        <div style="font-size: 16px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($profile['email'] ?? 'N/A') ?></div>
                    </div>

                    <?php if(($profile['role'] ?? '') == 'teacher'): ?>
                        <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;"><?= $lang == 'ar' ? 'رقم سجل الموظف' : 'Employee Registry No.' ?></label>
                            <div style="font-size: 16px; font-weight: 700; color: #0A2B8E;"><?= htmlspecialchars($profile['employee_number'] ?? 'N/A') ?></div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;"><?= $lang == 'ar' ? 'تاريخ التعيين' : 'Appointment Date' ?></label>
                            <div style="font-size: 16px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($profile['hire_date'] ?? 'N/A') ?></div>
                        </div>
                    <?php elseif(($profile['role'] ?? '') == 'student'): ?>
                        <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;"><?= $t['reg_num'] ?></label>
                            <div style="font-size: 18px; font-weight: 800; color: #0A2B8E; font-family: monospace;"><?= htmlspecialchars($profile['student_number'] ?? 'Not Assigned') ?></div>
                        </div>
                        <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;"><?= $t['cycle'] ?></label>
                            <div style="font-size: 16px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($profile['enrollment_year'] ?? 'N/A') ?> <?= $lang == 'ar' ? 'السنة الأكاديمية' : 'Academic Year' ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(($profile['role'] ?? '') != 'admin'): ?>
            
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin: 0 0 25px 0; color: #1e293b; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-key" style="color: #64748b;"></i> <?= $t['security'] ?>
                </h3>

                <?php if($msg): ?>
                    <div style="padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px; background: <?= $msg_type == 'success' ? '#f0fdf4' : '#fef2f2' ?>; color: <?= $msg_type == 'success' ? '#15803d' : '#991b1b' ?>; border: 1px solid <?= $msg_type == 'success' ? '#bbf7d0' : '#fecaca' ?>;">
                        <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <?php if (($profile['password_changed'] ?? 0) == 1): ?>
                    <div style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 25px; border-radius: 12px; text-align: center; margin-top: 20px;">
                        <i class="fas fa-shield-check" style="font-size: 32px; color: #10b981; margin-bottom: 12px;"></i>
                        <p style="margin: 0 0 8px 0; color: #334155; font-size: 15px; font-weight: 600;">
                            <?= $lang == 'ar' ? 'تم تحديث كلمة المرور الخاصة بك' : 'Your password has been updated' ?>
                        </p>
                        <p style="margin: 0 0 15px 0; color: #64748b; font-size: 13px; line-height: 1.5;">
                            <?= $lang == 'ar' ? 'لقد قمت مسبقاً بتغيير كلمة المرور الافتراضية.' : 'You have already changed your default password.' ?>
                        </p>
                        
                        <?php if($pending_request): ?>
                            <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 15px; border-radius: 8px; color: #92400e; font-size: 13px; font-weight: 600;">
                                <i class="fas fa-clock"></i> <?= $lang == 'ar' ? 'طلبك قيد المراجعة من قبل الإدارة.' : 'Your request is currently pending review by the administration.' ?>
                            </div>
                        <?php else: ?>
                            <form method="POST" style="text-align: left; background: white; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 15px;">
                                <label style="display:block; margin-bottom:8px; font-size:13px; font-weight: 600; color:#475569;">
                                    <?= $lang == 'ar' ? 'طلب إعادة تعيين (سبب الطلب):' : 'Request Reset (Reason):' ?>
                                </label>
                                <textarea name="reset_reason" class="form-input" rows="2" placeholder="<?= $lang == 'ar' ? 'مثال: لقد نسيت كلمة المرور الخاصة بي...' : 'e.g. I forgot my password...' ?>" required style="width: 100%; border-radius: 8px; resize: none; margin-bottom: 10px;"></textarea>
                                <button type="submit" name="request_reset" class="btn-primary" style="width: 100%; justify-content: center; background: #0f172a; border-radius: 8px; padding: 10px;">
                                    <i class="fas fa-paper-plane"></i> <?= $lang == 'ar' ? 'إرسال الطلب' : 'Send Request' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form method="POST" style="max-width: 450px;">
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom:8px; font-size:13px; font-weight: 600; color:#475569;"><?= $t['update_pass'] ?></label>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <input type="password" name="new_password" class="form-input" placeholder="<?= $lang == 'ar' ? 'كلمة المرور الجديدة' : 'New password' ?>" required style="padding: 12px;">
                                <input type="password" name="confirm_password" class="form-input" placeholder="<?= $lang == 'ar' ? 'تأكيد كلمة المرور' : 'Confirm password' ?>" required style="padding: 12px;">
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 15px; margin-top: 25px;">
                            <button type="submit" name="change_password" class="btn-primary" style="background: #0A2B8E; padding: 12px 30px; font-weight: 600; border: none; box-shadow: 0 4px 6px -1px rgba(10, 43, 142, 0.2);">
                                <?= $t['commence'] ?>
                            </button>
                            <span style="font-size: 12px; color: #94a3b8; font-style: italic;">
                                <i class="fas fa-lock"></i> <?= $t['encrypted'] ?>
                            </span>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
