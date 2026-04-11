<?php include 'layout_header.php'; 
require_once dirname(__DIR__) . '/config/email_service.php';
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

    if(preg_match('/@gmail\.com$/', $email)) {
        $hashed_pass = password_hash('password', PASSWORD_DEFAULT);
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
            EmailService::sendActivationEmail($user['personal_email'], $user['name'], $official_email);
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

if(isset($_GET['action']) && $_GET['action'] == 'suspend') {
    echo "<script>window.addEventListener('DOMContentLoaded', () => showToast('Account suspended successfully.'));</script>";
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
    <div class="page-actions">
        <h2 class="page-title">User Management</h2>
        <button onclick="document.getElementById('add-user-form').style.display='block'" class="logout-btn" style="background: #0A2B8E; color: white; border: none; box-shadow: none; cursor: pointer;">+ Add User</button>
    </div>
    
    <div id="add-user-form" style="display: none; background: #f8fafc; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
        <h4 style="margin-bottom: 15px; color:#2d3748;">Register New User</h4>
        <form method="POST" class="form-grid">
            <input type="text" name="new_name" class="form-input" placeholder="Full Name" required>
            <input type="email" name="new_email" class="form-input" placeholder="Email (@gmail.com only)" required pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" title="Address must end with @gmail.com">
            
            <select name="new_role" id="role-select" class="form-input" required onchange="toggleStudentFields(this.value)">
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="admin">Administrator</option>
            </select>

            <div id="student-only-fields" style="grid-column: span 2; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <input type="text" name="student_number" class="form-input" placeholder="Student ID" value="<?= $suggested_stu ?>">
                <input type="date" name="birth_date" class="form-input" title="Date of Birth">
                <input type="number" name="enroll_year" class="form-input" placeholder="Enrollment Year" value="<?= date('Y') ?>">
                <select name="section_id" class="form-input">
                    <?php foreach($sections as $sec): ?>
                        <option value="<?= $sec['id'] ?>"><?= htmlspecialchars($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="group_id" class="form-input">
                    <?php foreach($groups as $grp): ?>
                        <option value="<?= $grp['id'] ?>"><?= htmlspecialchars($grp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="teacher-only-fields" style="grid-column: span 2; display: none; grid-template-columns: 1fr; gap: 20px;">
                <input type="text" name="employee_number" class="form-input" placeholder="Employee ID" value="<?= $suggested_emp ?>">
            </div>

            <script>
                function toggleStudentFields(role) {
                    const sFields = document.getElementById('student-only-fields');
                    const tFields = document.getElementById('teacher-only-fields');
                    sFields.style.display = (role === 'student') ? 'grid' : 'none';
                    tFields.style.display = (role === 'teacher') ? 'grid' : 'none';
                }
            </script>
            
            <button type="submit" class="btn-primary" style="width: 100%; grid-column: span 2;">Create Account</button>
        </form>
    </div>

    <script>
        const allGroups = <?= json_encode($groups) ?>;
        
        function filterGroups(sectionSelect, groupSelect) {
            const sectionId = sectionSelect.value;
            const groups = allGroups.filter(g => g.section_id == sectionId);
            
            groupSelect.innerHTML = '';
            groups.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g.id;
                opt.textContent = g.name;
                groupSelect.appendChild(opt);
            });
        }

        document.querySelector('select[name="section_id"]').addEventListener('change', function() {
            filterGroups(this, document.querySelector('select[name="group_id"]'));
        });

        function setupApprovalFilters() {
            document.querySelectorAll('form').forEach(form => {
                const s = form.querySelector('select[name="section_id"]');
                const g = form.querySelector('select[name="group_id"]');
                if(s && g) {
                    s.addEventListener('change', () => filterGroups(s, g));
                    filterGroups(s, g);
                }
            });
        }
        window.onload = setupApprovalFilters;
    </script>

    <p style="color: #666; margin-bottom: 20px;">Manage all students, teachers, and administrators in the system.</p>
    
    <input type="text" id="user-search" onkeyup="filterTable('user-search', 'users-table', 0)" class="form-input" placeholder="Search by Name (e.g. j...)" style="max-width: 350px;">

    <table class="data-table" id="users-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Institutional ID</th>
                <th>Full Name</th>
                <th>Academic Track</th>
                <th>GPA</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($users as $user): 
                $status_label = '<span class="badge badge-success">Active</span>';
                if($user['is_active'] == 2) {
                    $status_label = '<span class="badge badge-info" style="background: #f39c12;">Pending Approval</span>';
                }

                $gpa = '-';
                if($user['role'] == 'student' && $user['student_db_id']) {
                    $stmtG = $pdo->prepare("SELECT SUM(g.grade * c.coefficient) / SUM(c.coefficient) FROM grades g JOIN courses c ON g.course_id = c.id WHERE g.student_id = ?");
                    $stmtG->execute([$user['student_db_id']]);
                    $val = $stmtG->fetchColumn();
                    $gpa = $val ? number_format($val, 2) : '0.00';
                }
            ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><span class="badge badge-info"><?= htmlspecialchars($user['student_number'] ?? ($user['employee_number'] ?? 'N/A')) ?></span></td>
                <td>
                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                    <div style="font-size: 11px; color: #666;"><?= htmlspecialchars($user['email']) ?></div>
                </td>
                <td>
                    <?php if($user['role'] == 'student'): ?>
                        <div style="font-weight: 500; color: #4a5568;"><?= htmlspecialchars($user['section_name'] ?? 'Not set') ?></div>
                        <div style="font-size: 11px; color: #718096;">Group: <?= htmlspecialchars($user['group_name'] ?? '-') ?></div>
                    <?php else: ?>
                        <span class="badge" style="background: #edf2f7; color: #4a5568; text-transform: capitalize;"><?= $user['role'] ?></span>
                    <?php endif; ?>
                </td>
                <td><span style="font-weight: 600; color: #0A2B8E;"><?= $gpa ?></span></td>
                <td><?= $status_label ?></td>
                <td>
                    <?php if($user['is_active'] == 2): ?>
                        <div style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <form method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                                <input type="hidden" name="approve_user_id" value="<?= $user['id'] ?>">
                                <input type="email" name="official_email" class="form-input" style="padding: 5px; margin: 0; font-size: 11px;" 
                                       required value="<?= strtolower(str_replace(' ', '.', $user['name'])) ?>@usthb.dz">
                                <div style="display: flex; gap: 5px;">
                                    <select name="section_id" class="form-input" style="padding: 4px; font-size: 11px; margin:0;">
                                        <?php foreach($sections as $sec): ?>
                                            <option value="<?= $sec['id'] ?>"><?= $sec['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="group_id" class="form-input" style="padding: 4px; font-size: 11px; margin:0;">
                                        <?php foreach($groups as $grp): ?>
                                            <option value="<?= $grp['id'] ?>"><?= $grp['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn-primary" style="padding: 6px; font-size: 11px;">Approve & Assign</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" style="background: none; border: none; cursor: pointer; color: #4a90e2; font-weight: bold; font-size: 14px;">Edit</button>
                            <a href="#" onclick="confirmAction(event, 'Suspend account?', '?action=suspend&id=<?= $user['id'] ?>')" style="color: #e74c3c; text-decoration: none; font-weight: bold; font-size: 14px;">Delete</a>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<div id="edit-user-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 15px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <h3 style="color: #0A2B8E; margin-bottom: 20px;">Edit Account</h3>
        <form method="POST">
            <input type="hidden" name="edit_user_id" id="edit-id">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #666;">Full Name</label>
                <input type="text" name="edit_name" id="edit-name" class="form-input" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #666;">Official Email</label>
                <input type="email" name="edit_email" id="edit-email" class="form-input" required>
            </div>
            <div id="edit-student-fields" style="display: none; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #666;">Section</label>
                    <select name="edit_section" id="edit-section" class="form-input">
                        <?php foreach($sections as $sec): ?>
                            <option value="<?= $sec['id'] ?>"><?= $sec['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-size: 13px; color: #666;">Group</label>
                    <select name="edit_group" id="edit-group" class="form-input">
                        <?php foreach($groups as $grp): ?>
                            <option value="<?= $grp['id'] ?>"><?= $grp['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-primary" style="flex: 1;">Save Changes</button>
                <button type="button" onclick="document.getElementById('edit-user-modal').style.display='none'" class="logout-btn" style="flex: 1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
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
