<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
 include 'layout_header.php'; 
if(($_SESSION['user_role'] ?? '') !== 'admin') die("Access Denied.");

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_name'])) {
    $name = $_POST['new_name'];
    $email = $_POST['new_email'];
    $role = $_POST['new_role'] ?? 'student';
    $enroll_year = $_POST['enroll_year'] ?? date('Y');
    $section_id = $_POST['section_id'] ?? 1;
    $group_id = $_POST['group_id'] ?? 1;
    
    $student_number = $_POST['student_number'] ?? '';
    $employee_number = $_POST['employee_number'] ?? '';
    
    if (empty($student_number) && $role == 'student') {
        $stmtCount = $pdo->prepare("SELECT MAX(SUBSTRING(student_number, 8)) FROM student WHERE enrollment_year = ?");
        $stmtCount->execute([$enroll_year]);
        $maxVal = (int)$stmtCount->fetchColumn();
        $student_number = 'STU' . $enroll_year . str_pad($maxVal + 1, 4, '0', STR_PAD_LEFT);
    }
    
    if (empty($employee_number) && $role == 'teacher') {
        $stmtCount = $pdo->prepare("SELECT MAX(SUBSTRING(employee_number, 8)) FROM teacher WHERE employee_number LIKE ?");
        $stmtCount->execute(['EMP' . date('Y') . '%']);
        $maxVal = (int)$stmtCount->fetchColumn();
        $employee_number = 'EMP' . date('Y') . str_pad($maxVal + 1, 4, '0', STR_PAD_LEFT);
    }

    $hashed_pass = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, personal_email, password, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([$name, $email, $email, $hashed_pass, $role]);
    $new_user_id = $pdo->lastInsertId();
    
    if($role == 'student') {
        $pdo->prepare("INSERT INTO student (user_id, section_id, group_id, student_number, birth_date, enrollment_year) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$new_user_id, $section_id, $group_id, $student_number, $_POST['birth_date'] ?? null, $enroll_year]);
    } elseif ($role == 'teacher') {
        $pdo->prepare("INSERT INTO teacher (user_id, employee_number, hire_date) VALUES (?, ?, ?)")
            ->execute([$new_user_id, $employee_number, date('Y-m-d')]);
    }
    echo "<script>window.onload = () => showToast('New account created and assigned!');</script>";
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_user_id'])) {
    $uid = $_POST['approve_user_id'];
    $official_email = $_POST['official_email'];
    $sid = $_POST['section_id'] ?? 1;
    $gid = $_POST['group_id'] ?? 1;
    
    if(preg_match('/@usthb\.dz$/', $official_email)) {
        $stmt = $pdo->prepare("SELECT name, personal_email FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        if($user) {
            $pdo->prepare("UPDATE users SET email = ?, is_active = 1 WHERE id = ?")->execute([$official_email, $uid]);
            $pdo->prepare("UPDATE student SET section_id = ?, group_id = ? WHERE user_id = ?")->execute([$sid, $gid, $uid]);
            echo "<script>window.onload = () => showToast('Approved and assigned to track!');</script>";
        }
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user_id'])) {
    $uid = $_POST['edit_user_id'];
    $name = $_POST['edit_name'];
    $email = $_POST['edit_email'];
    $sid = $_POST['edit_section'] ?? null;
    $gid = $_POST['edit_group'] ?? null;
    
    $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?")->execute([$name, $email, $uid]);
    if($sid && $gid) {
        $pdo->prepare("UPDATE student SET section_id = ?, group_id = ? WHERE user_id = ?")->execute([$sid, $gid, $uid]);
    }
    echo "<script>window.onload = () => showToast('Account details updated!');</script>";
}

if(isset($_GET['action']) && $_GET['action'] == 'suspend' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_GET['id']]);
    echo "<script>window.addEventListener('DOMContentLoaded', () => showToast('Account deleted successfully.'));</script>";
}

if(isset($_GET['action']) && $_GET['action'] == 'reset_pass' && isset($_GET['id'])) {
    $hashed = password_hash('USTHB2026!', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ?, password_changed = 0 WHERE id = ?")->execute([$hashed, $_GET['id']]);
    echo "<script>window.addEventListener('DOMContentLoaded', () => showToast('Password reset to default (USTHB2026!)'));</script>";
}

if(isset($_GET['action']) && $_GET['action'] == 'approve_req' && isset($_GET['req_id']) && isset($_GET['user_id'])) {
    $hashed = password_hash('123456', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ?, password_changed = 0 WHERE id = ?")->execute([$hashed, $_GET['user_id']]);
    $pdo->prepare("UPDATE password_requests SET status = 'approved' WHERE id = ?")->execute([$_GET['req_id']]);
    echo "<script>window.addEventListener('DOMContentLoaded', () => showToast('Request approved. Password reset to 123456.'));</script>";
}

if(isset($_GET['action']) && $_GET['action'] == 'reject_req' && isset($_GET['req_id'])) {
    $pdo->prepare("UPDATE password_requests SET status = 'rejected' WHERE id = ?")->execute([$_GET['req_id']]);
    echo "<script>window.addEventListener('DOMContentLoaded', () => showToast('Request rejected.'));</script>";
}

$users = $pdo->query("
    SELECT u.id, u.name, u.email, u.personal_email, u.role, u.is_active, u.created_at, 
           s.student_number, t.employee_number, s.id as student_db_id,
           sec.name as section_name, g.name as group_name,
           s.section_id, s.group_id
    FROM users u
    LEFT JOIN student s ON u.id = s.user_id
    LEFT JOIN teacher t ON u.id = t.user_id
    LEFT JOIN section sec ON s.section_id = sec.id
    LEFT JOIN `group` g ON s.group_id = g.id
    WHERE u.is_active != 0
    ORDER BY FIELD(u.is_active, 2, 1), u.role, u.name
")->fetchAll();

$requests = $pdo->query("
    SELECT pr.*, u.name, u.email, u.role
    FROM password_requests pr
    JOIN users u ON pr.user_id = u.id
    WHERE pr.status = 'pending'
    ORDER BY pr.created_at DESC
")->fetchAll();

$sections = $pdo->query("SELECT id, name FROM section ORDER BY name")->fetchAll();
$groups = $pdo->query("SELECT id, name, section_id FROM `group` ORDER BY name")->fetchAll();

$stmtNextSTU = $pdo->prepare("SELECT MAX(SUBSTRING(student_number, 8)) FROM student WHERE enrollment_year = ?");
$stmtNextSTU->execute([date('Y')]);
$nextSTUseq = (int)$stmtNextSTU->fetchColumn() + 1;
$suggested_stu = 'STU' . date('Y') . str_pad($nextSTUseq, 4, '0', STR_PAD_LEFT);

$stmtNextEMP = $pdo->prepare("SELECT MAX(SUBSTRING(employee_number, 8)) FROM teacher WHERE employee_number LIKE ?");
$stmtNextEMP->execute(['EMP' . date('Y') . '%']);
$nextEMPseq = (int)$stmtNextEMP->fetchColumn() + 1;
$suggested_emp = 'EMP' . date('Y') . str_pad($nextEMPseq, 4, '0', STR_PAD_LEFT);
?>

<div class="card-container">
        <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
            <div>
                <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;"><?= $lang == 'ar' ? 'المستخدمين' : 'Users' ?></h2>
            </div>
            <button onclick="document.getElementById('add-user-form').style.display='block'" class="btn-primary" style="background: #0A2B8E; display: flex; align-items: center; gap: 8px; padding: 10px 20px;">
                <i class="fas fa-user-plus"></i> <?= $lang == 'ar' ? 'إضافة' : 'Add' ?>
            </button>
        </div>
    
    
    <div id="add-user-form" style="display: none; background: #ffffff; padding: 30px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0; color:#1e293b; font-size: 18px;"><?= $lang == 'ar' ? 'مستخدم جديد' : 'New User' ?></h4>
            <button onclick="document.getElementById('add-user-form').style.display='none'" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:20px;">&times;</button>
        </div>
        <form method="POST" class="form-grid">
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $t['full_name'] ?></label>
                <input type="text" name="new_name" class="form-input" placeholder="<?= $lang == 'ar' ? 'الاسم الكامل' : 'Full Name' ?>" required>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $t['email_addr'] ?></label>
                <input type="email" name="new_email" class="form-input" placeholder="e.g. name@usthb.dz" required>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $lang == 'ar' ? 'الدور في النظام' : 'System Role' ?></label>
                <select name="new_role" id="role-select" class="form-input" required onchange="toggleStudentFields(this.value)">
                    <option value="student"><?= $lang == 'ar' ? 'طالب' : 'Student' ?></option>
                    <option value="teacher"><?= $lang == 'ar' ? 'أستاذ' : 'Teacher' ?></option>
                    <option value="admin"><?= $lang == 'ar' ? 'مسؤول' : 'Administrator' ?></option>
                </select>
            </div>

            <div id="student-only-fields" style="grid-column: span 2; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $t['reg_num'] ?></label>
                    <input type="text" name="student_number" class="form-input" value="<?= $suggested_stu ?>" readonly style="background: #f8fafc; cursor: not-allowed;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $lang == 'ar' ? 'تاريخ الميلاد' : 'Date of Birth' ?></label>
                    <input type="date" name="birth_date" class="form-input">
                </div>
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $lang == 'ar' ? 'سنة الالتحاق' : 'Enrollment Year' ?></label>
                    <input type="number" name="enroll_year" class="form-input" value="<?= date('Y') ?>">
                </div>
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $lang == 'ar' ? 'الدفعة' : 'Section' ?></label>
                    <select name="section_id" class="form-input">
                        <?php foreach($sections as $sec): ?>
                            <option value="<?= $sec['id'] ?>"><?= htmlspecialchars($sec['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $lang == 'ar' ? 'الفوج' : 'Group' ?></label>
                    <select name="group_id" class="form-input">
                        <?php foreach($groups as $grp): ?>
                            <option value="<?= $grp['id'] ?>"><?= htmlspecialchars($grp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="teacher-only-fields" style="grid-column: span 2; display: none; grid-template-columns: 1fr; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $lang == 'ar' ? 'رقم الموظف' : 'Employee ID' ?></label>
                    <input type="text" name="employee_number" class="form-input" value="<?= $suggested_emp ?>" readonly style="background: #f8fafc; cursor: not-allowed;">
                </div>
            </div>
            
            <div style="grid-column: span 2; margin-top: 10px;">
                <button type="submit" class="btn-primary" style="width: 200px; background: #059669;"><?= $lang == 'ar' ? 'تأكيد' : 'Confirm' ?></button>
            </div>
        </form>
    </div>

    
    <div style="display: flex; gap: 10px; margin-bottom: 25px; background: #f1f5f9; padding: 5px; border-radius: 10px; width: fit-content;">
        <button onclick="switchTab('students')" class="tab-btn active-tab" id="tab-students">
            <i class="fas fa-user-graduate"></i> <?= $lang == 'ar' ? 'الطلاب' : 'Students' ?>
        </button>
        <button onclick="switchTab('teachers')" class="tab-btn" id="tab-teachers">
            <i class="fas fa-chalkboard-teacher"></i> <?= $lang == 'ar' ? 'الأساتذة' : 'Teachers' ?>
        </button>
        <button onclick="switchTab('admins')" class="tab-btn" id="tab-admins">
            <i class="fas fa-user-shield"></i> <?= $lang == 'ar' ? 'المسؤولون' : 'Administrators' ?>
        </button>
        <button onclick="switchTab('requests')" class="tab-btn" id="tab-requests">
            <i class="fas fa-envelope-open-text"></i> <?= $lang == 'ar' ? 'الطلبات' : 'Requests' ?>
            <?php if(count($requests) > 0): ?>
                <span style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;"><?= count($requests) ?></span>
            <?php endif; ?>
        </button>
    </div>

    <style>
        .tab-btn {
            padding: 10px 25px;
            border: none;
            background: none;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tab-btn:hover {
            color: #1e293b;
            background: #e2e8f0;
        }
        .tab-btn.active-tab {
            background: #ffffff;
            color: #0A2B8E;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .user-tab-content {
            display: none;
        }
        .user-tab-content.active-content {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .action-icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .action-icon-btn:hover {
            background: #f1f5f9;
        }
    </style>

    <div style="margin-bottom: 25px;">
        <div style="position: relative; max-width: 400px;">
            <i class="fas fa-search" style="position: absolute; left: 12px; top: 12px; color: #94a3b8;"></i>
            <input type="text" id="user-search" onkeyup="globalSearch()" class="form-input" placeholder="<?= $lang == 'ar' ? 'البحث بالاسم أو البريد...' : 'Search by name or email...' ?>" style="padding-left: 40px; margin: 0;">
        </div>
    </div>

    
    <div id="content-students" class="user-tab-content active-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= $lang == 'ar' ? 'رقم التسجيل' : 'Registration' ?></th>
                    <th><?= $t['full_name'] ?></th>
                    <th><?= $lang == 'ar' ? 'التخصص والفوج' : 'Speciality & Group' ?></th>
                    <th><?= $lang == 'ar' ? 'المعدل' : 'Average' ?></th>
                    <th><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></th>
                    <th style="text-align: right;"><?= $lang == 'ar' ? 'الإجراءات' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): if($user['role'] !== 'student') continue; 
                    $gpa = '-';
                    if($user['student_db_id']) {
                        $stmtG = $pdo->prepare("SELECT SUM(g.grade * c.coefficient) / SUM(c.coefficient) FROM grades g JOIN courses c ON g.course_id = c.id WHERE g.student_id = ?");
                        $stmtG->execute([$user['student_db_id']]);
                        $val = $stmtG->fetchColumn();
                        $gpa = $val ? number_format($val, 2) : '0.00';
                    }
                ?>
                <tr class="user-row">
                    <td><span style="font-family: monospace; color: #475569; font-weight: 600;"><?= htmlspecialchars($user['student_number'] ?? 'N/A') ?></span></td>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($user['name']) ?></div>
                        <div style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($user['email']) ?></div>
                    </td>
                    <td>
                        <div style="font-size: 13px; font-weight: 500; color: #0A2B8E;"><?= htmlspecialchars($user['section_name'] ?? ($lang == 'ar' ? 'غير محدد' : 'Not set')) ?></div>
                        <div style="font-size: 11px; color: #94a3b8;"><?= $lang == 'ar' ? 'الفوج' : 'Group' ?> <?= htmlspecialchars($user['group_name'] ?? '-') ?></div>
                    </td>
                    <td><span style="font-weight: 700; color: <?= (float)$gpa >= 10 ? '#059669' : '#dc2626' ?>;"><?= $gpa ?></span></td>
                    <td><span class="badge badge-success" style="background: #ecfdf5; color: #059669; border: 1px solid #d1fae5;"><?= $lang == 'ar' ? 'نشط' : 'Active' ?></span></td>
                    <td style="text-align: right;">
                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" class="action-icon-btn" title="<?= $lang == 'ar' ? 'تعديل المستخدم' : 'Edit User' ?>">
                            <i class="fas fa-edit" style="color: #3b82f6;"></i>
                        </button>
                        <button onclick="confirmAction(event, '<?= $lang == 'ar' ? 'حذف حساب الطالب؟' : 'Delete student account?' ?>', '?action=suspend&id=<?= $user['id'] ?>')" class="action-icon-btn" title="<?= $lang == 'ar' ? 'حذف الحساب' : 'Delete Account' ?>">
                            <i class="fas fa-trash-alt" style="color: #ef4444;"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    
    <div id="content-teachers" class="user-tab-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= $lang == 'ar' ? 'رقم الموظف' : 'Employee ID' ?></th>
                    <th><?= $t['full_name'] ?></th>
                    <th><?= $lang == 'ar' ? 'البريد المؤسسي' : 'Institutional Email' ?></th>
                    <th><?= $lang == 'ar' ? 'الحالة' : 'Status' ?></th>
                    <th style="text-align: right;"><?= $lang == 'ar' ? 'الإجراءات' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): if($user['role'] !== 'teacher') continue; ?>
                <tr class="user-row">
                    <td><span style="font-family: monospace; color: #475569; font-weight: 600;"><?= htmlspecialchars($user['employee_number'] ?? 'N/A') ?></span></td>
                    <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($user['name']) ?></td>
                    <td style="color: #64748b;"><?= htmlspecialchars($user['email']) ?></td>
                    <td><span class="badge badge-success" style="background: #f0f9ff; color: #0369a1; border: 1px solid #e0f2fe;"><?= $lang == 'ar' ? 'أستاذ' : 'Faculty' ?></span></td>
                    <td style="text-align: right;">
                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" class="action-icon-btn">
                            <i class="fas fa-edit" style="color: #3b82f6;"></i>
                        </button>
                        <button onclick="confirmAction(event, '<?= $lang == 'ar' ? 'حذف حساب الأستاذ؟' : 'Delete teacher account?' ?>', '?action=suspend&id=<?= $user['id'] ?>')" class="action-icon-btn">
                            <i class="fas fa-trash-alt" style="color: #ef4444;"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    
    <div id="content-admins" class="user-tab-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= $lang == 'ar' ? 'مستوى الوصول' : 'Access Level' ?></th>
                    <th><?= $t['full_name'] ?></th>
                    <th><?= $lang == 'ar' ? 'البريد الإلكتروني' : 'Account Email' ?></th>
                    <th><?= $lang == 'ar' ? 'تاريخ الإنشاء' : 'Created At' ?></th>
                    <th style="text-align: right;"><?= $lang == 'ar' ? 'الإجراءات' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): if($user['role'] !== 'admin') continue; ?>
                <tr class="user-row">
                    <td><span class="badge" style="background: #fff1f2; color: #be123c; border: 1px solid #ffe4e6; text-transform: uppercase; font-size: 10px;"><?= $lang == 'ar' ? 'جذر الأمان' : 'Security Root' ?></span></td>
                    <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($user['name']) ?></td>
                    <td style="color: #64748b;"><?= htmlspecialchars($user['email']) ?></td>
                    <td style="font-size: 13px; color: #94a3b8;"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                    <td style="text-align: right;">
                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" class="action-icon-btn">
                            <i class="fas fa-edit" style="color: #3b82f6;"></i>
                        </button>
                        <button onclick="confirmAction(event, '<?= $lang == 'ar' ? 'إلغاء صلاحيات المسؤول؟' : 'Revoke admin access?' ?>', '?action=suspend&id=<?= $user['id'] ?>')" class="action-icon-btn">
                            <i class="fas fa-user-slash" style="color: #ef4444;"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    
    <div id="content-requests" class="user-tab-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= $lang == 'ar' ? 'المستخدم' : 'User' ?></th>
                    <th><?= $lang == 'ar' ? 'الدور' : 'Role' ?></th>
                    <th><?= $lang == 'ar' ? 'سبب الطلب' : 'Reason' ?></th>
                    <th><?= $lang == 'ar' ? 'تاريخ الطلب' : 'Requested At' ?></th>
                    <th style="text-align: right;"><?= $lang == 'ar' ? 'الإجراءات' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($requests)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px; color: #94a3b8;"><?= $lang == 'ar' ? 'لا توجد طلبات معلقة.' : 'No pending requests.' ?></td></tr>
                <?php else: ?>
                    <?php foreach($requests as $req): ?>
                    <tr class="user-row">
                        <td>
                            <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($req['name']) ?></div>
                            <div style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($req['email']) ?></div>
                        </td>
                        <td>
                            <span class="badge" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; text-transform: uppercase; font-size: 11px;">
                                <?= htmlspecialchars($req['role']) ?>
                            </span>
                        </td>
                        <td style="color: #475569; font-size: 13px; max-width: 300px; word-wrap: break-word;">
                            <?= nl2br(htmlspecialchars($req['reason'])) ?>
                        </td>
                        <td style="font-size: 13px; color: #94a3b8;"><?= date('M d, Y H:i', strtotime($req['created_at'])) ?></td>
                        <td style="text-align: right;">
                            <button onclick="confirmAction(event, '<?= $lang == 'ar' ? 'الموافقة على إعادة تعيين كلمة المرور؟' : 'Approve password reset?' ?>', '?action=approve_req&req_id=<?= $req['id'] ?>&user_id=<?= $req['user_id'] ?>')" class="action-icon-btn" title="<?= $lang == 'ar' ? 'موافقة' : 'Approve' ?>">
                                <i class="fas fa-check-circle" style="color: #10b981; font-size: 18px;"></i>
                            </button>
                            <button onclick="confirmAction(event, '<?= $lang == 'ar' ? 'رفض الطلب؟' : 'Reject request?' ?>', '?action=reject_req&req_id=<?= $req['id'] ?>')" class="action-icon-btn" title="<?= $lang == 'ar' ? 'رفض' : 'Reject' ?>">
                                <i class="fas fa-times-circle" style="color: #ef4444; font-size: 18px;"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<div id="edit-user-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; padding: 40px; border-radius: 16px; width: 100%; max-width: 550px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="color: #1e293b; margin: 0; font-size: 20px;"><?= $lang == 'ar' ? 'تعديل الحساب' : 'Edit Account' ?></h3>
            <button onclick="document.getElementById('edit-user-modal').style.display='none'" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:24px;">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="edit_user_id" id="edit-id">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #64748b;">Full Legal Name</label>
                <input type="text" name="edit_name" id="edit-name" class="form-input" required>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #64748b;">Official University Email</label>
                <input type="email" name="edit_email" id="edit-email" class="form-input" required>
            </div>
            <div id="edit-student-fields" style="display: none; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #64748b;">Academic Section</label>
                    <select name="edit_section" id="edit-section" class="form-input">
                        <?php foreach($sections as $sec): ?>
                            <option value="<?= $sec['id'] ?>"><?= $sec['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #64748b;">Assigned Group</label>
                    <select name="edit_group" id="edit-group" class="form-input">
                        <?php foreach($groups as $grp): ?>
                            <option value="<?= $grp['id'] ?>"><?= $grp['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <button type="submit" class="btn-primary" style="flex: 2; padding: 12px;">Save Profile Changes</button>
                <button type="button" onclick="document.getElementById('edit-user-modal').style.display='none'" class="logout-btn" style="flex: 1; border: 1px solid #e2e8f0; background: white; color: #64748b;">Discard</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tab) {
        document.querySelectorAll('.user-tab-content').forEach(c => c.classList.remove('active-content'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active-tab'));
        
        document.getElementById('content-' + tab).classList.add('active-content');
        document.getElementById('tab-' + tab).classList.add('active-tab');
    }

    function globalSearch() {
        const input = document.getElementById('user-search');
        const filter = input.value.toUpperCase();
        const rows = document.querySelectorAll('.user-row');

        rows.forEach(row => {
            const text = row.textContent || row.innerText;
            if (text.toUpperCase().indexOf(filter) > -1) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    function toggleStudentFields(role) {
        const sFields = document.getElementById('student-only-fields');
        const tFields = document.getElementById('teacher-only-fields');
        sFields.style.display = (role === 'student') ? 'grid' : 'none';
        tFields.style.display = (role === 'teacher') ? 'grid' : 'none';
    }

    const allGroups = <?= json_encode($groups) ?>;
    function filterGroups(sectionSelect, groupSelect) {
        const sectionId = sectionSelect.value;
        const groups = allGroups.filter(g => g.section_id == sectionId);
        groupSelect.innerHTML = '';
        groups.forEach(g => {
            const opt = document.createElement('option');
            opt.value = g.id; opt.textContent = g.name;
            groupSelect.appendChild(opt);
        });
    }

    document.querySelector('select[name="section_id"]').addEventListener('change', function() {
        filterGroups(this, document.querySelector('select[name="group_id"]'));
    });

    function openEditModal(user) {
        document.getElementById('edit-id').value = user.id;
        document.getElementById('edit-name').value = user.name;
        document.getElementById('edit-email').value = user.email;
        const studentFields = document.getElementById('edit-student-fields');
        if(user.role === 'student') {
            studentFields.style.display = 'grid';
            document.getElementById('edit-section').value = user.section_id || 1;
            document.getElementById('edit-group').value = user.group_id || 1;
        } else {
            studentFields.style.display = 'none';
        }
        document.getElementById('edit-user-modal').style.display = 'flex';
    }
</script>

<?php include 'layout_footer.php'; ?>
