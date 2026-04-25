<?php include 'layout_header.php'; 
if(($_SESSION['user_role'] ?? '') !== 'admin') die("Access Denied.");

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['c_code'])) {
    $stmt = $pdo->prepare("INSERT INTO courses (name, code, credits, coefficient, hours, semester, speciality_id, year_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['c_name'], $_POST['c_code'], $_POST['c_credits'], $_POST['c_coef'], 45, $_POST['c_sem'], $_POST['c_spec'], $_POST['c_year']]);
    echo "<script>window.onload = () => showToast('Module added successfully!');</script>";
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_course_id'])) {
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$_POST['delete_course_id']]);
    echo "<script>window.onload = () => showToast('Module deleted successfully!');</script>";
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_course_action'])) {
    $stmt = $pdo->prepare("UPDATE courses SET name=?, code=?, credits=?, coefficient=?, semester=? WHERE id=?");
    $stmt->execute([$_POST['e_name'], $_POST['e_code'], $_POST['e_credits'], $_POST['e_coef'], $_POST['e_sem'], $_POST['e_id']]);
    echo "<script>window.onload = () => showToast('Module updated successfully!');</script>";
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_teacher'])) {
    $tid = $_POST['teacher_id'];
    $cid = $_POST['course_id'];
    $sids = $_POST['section_ids'] ?? [];
    
    foreach($sids as $sid) {
        $groups = $pdo->prepare("SELECT id FROM `group` WHERE section_id = ?");
        $groups->execute([$sid]);
        $group_list = $groups->fetchAll(PDO::FETCH_COLUMN);
        
        foreach($group_list as $gid) {
            $stmt = $pdo->prepare("INSERT INTO course_assignment (teacher_id, course_id, section_id, group_id, academic_year, semester, teaching_type) VALUES (?, ?, ?, ?, ?, 'S2', 'C') ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id)");
            $stmt->execute([$tid, $cid, $sid, $gid, date('Y')]);
        }
    }
    echo "<script>window.onload = () => showToast('Teacher assigned to all selected sections/groups!');</script>";
}
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_assignment'])) {
    $tid = $_POST['teacher_id'];
    $cid = $_POST['course_id'];
    $sid = $_POST['section_id'];
    $stmt = $pdo->prepare("DELETE FROM course_assignment WHERE teacher_id = ? AND course_id = ? AND section_id = ?");
    $stmt->execute([$tid, $cid, $sid]);
    echo "<script>window.onload = () => showToast('Assignment removed successfully.');</script>";
}

$courses = $pdo->query("
    SELECT c.id, c.code, c.name, c.credits, c.coefficient, c.semester, sp.name as speciality_name, y.name as year_name,
           (SELECT GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') FROM users u JOIN teacher t ON u.id = t.user_id JOIN course_assignment ca ON t.id = ca.teacher_id WHERE ca.course_id = c.id) as teacher_names
    FROM courses c
    JOIN speciality sp ON c.speciality_id = sp.id
    JOIN years y ON c.year_id = y.id
    ORDER BY sp.name, y.year_number, c.semester
")->fetchAll();

$teachers = $pdo->query("SELECT t.id, u.name FROM teacher t JOIN users u ON t.user_id = u.id ORDER BY u.name")->fetchAll();
$sections = $pdo->query("SELECT id, name FROM section ORDER BY name")->fetchAll();
$specialities = $pdo->query("SELECT id, name FROM speciality")->fetchAll();
$years = $pdo->query("SELECT id, name FROM years")->fetchAll();

$active_assignments = $pdo->query("
    SELECT ca.teacher_id, ca.course_id, ca.section_id, u.name as teacher_name, c.name as course_name, sec.name as section_name
    FROM course_assignment ca
    JOIN teacher t ON ca.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN courses c ON ca.course_id = c.id
    JOIN section sec ON ca.section_id = sec.id
    GROUP BY ca.teacher_id, ca.course_id, ca.section_id
    ORDER BY u.name, sec.name
")->fetchAll();
?>

<div class="card-container">
        <div class="page-actions" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px;">
            <div>
                <h2 class="page-title" style="margin: 0; color: #1e293b; font-size: 24px;"><?= $lang == 'ar' ? 'المقاييس والأساتذة' : 'Modules & Faculty' ?></h2>
            </div>
            <button onclick="document.getElementById('add-curr-form').style.display='block'" class="btn-primary" style="background: #0A2B8E; display: flex; align-items: center; gap: 8px; padding: 10px 20px;">
                <i class="fas fa-plus-circle"></i> <?= $lang == 'ar' ? 'إضافة' : 'Add' ?>
            </button>
        </div>
    
    <!-- Add Module Form (Refined) -->
    <div id="add-curr-form" style="display: none; background: #ffffff; padding: 30px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0; color:#1e293b; font-size: 18px;"><?= $lang == 'ar' ? 'مقياس جديد' : 'New Module' ?></h4>
            <button onclick="document.getElementById('add-curr-form').style.display='none'" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:20px;">&times;</button>
        </div>
        <form method="POST" class="form-grid">
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $t['module_name'] ?? ($lang == 'ar' ? 'اسم الوحدة' : 'Module Name') ?></label>
                <input type="text" name="c_name" class="form-input" placeholder="e.g. Data Structures" required>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $lang == 'ar' ? 'رمز المقرر' : 'Course Code' ?></label>
                <input type="text" name="c_code" class="form-input" placeholder="e.g. CS101" required>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $t['credits'] ?></label>
                <input type="number" name="c_credits" class="form-input" placeholder="6" required>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $t['coefficient'] ?></label>
                <input type="number" name="c_coef" class="form-input" placeholder="3" required>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $lang == 'ar' ? 'التخصص' : 'Speciality' ?></label>
                <select name="c_spec" class="form-input" required>
                    <?php foreach($specialities as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $lang == 'ar' ? 'السنة الأكاديمية' : 'Academic Year' ?></label>
                <select name="c_year" class="form-input" required>
                    <?php foreach($years as $y): ?>
                        <option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 13px; color: #64748b; font-weight: 600;"><?= $t['semester'] ?></label>
                <select name="c_sem" class="form-input" required>
                    <option value="S1"><?= $lang == 'ar' ? 'السداسي 1 (S1)' : 'Semester 1 (S1)' ?></option>
                    <option value="S2"><?= $lang == 'ar' ? 'السداسي 2 (S2)' : 'Semester 2 (S2)' ?></option>
                </select>
            </div>
            <div style="grid-column: span 3; margin-top: 10px;">
                <button type="submit" class="btn-primary" style="width: 200px; background: #059669;"><?= $lang == 'ar' ? 'تأكيد' : 'Confirm' ?></button>
            </div>
        </form>
    </div>

    <!-- Assignments Section (Formal Card) -->
    <div style="background: #f8fafc; padding: 30px; border-radius: 12px; margin-bottom: 40px; border: 1px solid #e2e8f0; border-left: 5px solid #3b82f6;">
        <h4 style="margin: 0 0 10px 0; color:#1e293b; font-size: 18px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-link" style="color: #3b82f6;"></i> <?= $lang == 'ar' ? 'تعيين الأساتذة' : 'Faculty Assignment' ?>
        </h4>
        <p style="color: #64748b; font-size: 14px; margin-bottom: 25px;"><?= $lang == 'ar' ? 'ربط موظف تعليمي بعدة دفعات لوحدة معينة.' : 'Link a teaching staff member to multiple sections for a specific module.' ?></p>
        
        <form method="POST" class="form-grid">
            <input type="hidden" name="assign_teacher" value="1">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-size: 13px; font-weight: 600; color:#475569;">1. <?= $lang == 'ar' ? 'الطاقم التعليمي' : 'Teaching Staff' ?></label>
                <select name="teacher_id" class="form-input" required>
                    <option value=""><?= $lang == 'ar' ? 'اختر الموظف...' : 'Select Personnel...' ?></option>
                    <?php foreach($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-size: 13px; font-weight: 600; color:#475569;">2. <?= $lang == 'ar' ? 'الوحدة المستهدفة' : 'Target Module' ?></label>
                <select name="course_id" class="form-input" required>
                    <option value=""><?= $lang == 'ar' ? 'اختر الوحدة...' : 'Select Module...' ?></option>
                    <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="grid-column: span 2; margin-top: 15px;">
                <label style="display:block; margin-bottom:12px; font-weight:600; color:#475569;">3. <?= $lang == 'ar' ? 'المهام الأكاديمية' : 'Academic Sections' ?></label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; max-height: 180px; overflow-y: auto; background: white; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                    <?php foreach($sections as $s): ?>
                        <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer; color: #334155; padding: 5px; border-radius: 4px; transition: background 0.2s;">
                            <input type="checkbox" name="section_ids[]" value="<?= $s['id'] ?>" style="width: 16px; height: 16px;"> <?= htmlspecialchars($s['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="grid-column: span 2; margin-top: 15px;">
                <button type="submit" class="btn-primary" style="width: 250px; background: #0A2B8E;"><?= $lang == 'ar' ? 'تصريح التعيينات' : 'Authorize Assignments' ?></button>
            </div>
        </form>
    </div>

    <!-- Module Catalog -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="color: #1e293b; margin: 0; font-size: 20px;"><?= $lang == 'ar' ? 'دليل الوحدات الرسمي' : 'Official Module Catalog' ?></h3>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th><?= $lang == 'ar' ? 'الرمز' : 'Code' ?></th>
                <th><?= $lang == 'ar' ? 'الوحدة المؤسسية' : 'Institutional Module' ?></th>
                <th><?= $lang == 'ar' ? 'الإحصائيات' : 'Stats' ?></th>
                <th><?= $lang == 'ar' ? 'الطاقم' : 'Staff' ?></th>
                <th style="text-align: right;"><?= $lang == 'ar' ? 'العمليات' : 'Operations' ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($courses as $c): ?>
            <tr>
                <td><span style="font-family: monospace; color: #0A2B8E; font-weight: 700; background: #eff6ff; padding: 4px 10px; border-radius: 4px; border: 1px solid #dbeafe;"><?= htmlspecialchars($c['code']) ?></span></td>
                <td>
                    <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($c['name']) ?></div>
                    <div style="font-size: 12px; color: #94a3b8;"><?= htmlspecialchars($c['speciality_name']) ?> &bull; <?= htmlspecialchars($c['year_name']) ?></div>
                </td>
                <td>
                    <div style="font-size: 13px; color: #475569;">
                        <strong><?= htmlspecialchars($c['coefficient']) ?></strong> <?= $lang == 'ar' ? 'معامل' : 'Coef' ?> &bull; 
                        <strong><?= htmlspecialchars($c['credits']) ?></strong> <?= $lang == 'ar' ? 'رصيد' : 'Credits' ?>
                    </div>
                </td>
                <td>
                    <div style="font-size: 13px; color: #64748b; font-style: italic;">
                        <i class="fas fa-user-tie" style="margin-right: 5px; color: #94a3b8;"></i>
                        <?= htmlspecialchars($c['teacher_names'] ?? ($lang == 'ar' ? 'لم يتم تعيين طاقم' : 'No Staff Assigned')) ?>
                    </div>
                </td>
                <td style="text-align: right;">
                    <div style="display: flex; gap: 15px; justify-content: flex-end;">
                        <button onclick='openEditModModal(<?= json_encode($c) ?>)' style="background:none; border:none; color:#3b82f6; font-weight:600; cursor:pointer; font-size:14px; display: flex; align-items: center; gap: 5px;">
                            <i class="fas fa-edit"></i> <?= $lang == 'ar' ? 'تعديل' : 'Edit' ?>
                        </button>
                        <form method="POST" onsubmit="return confirm('<?= $lang == 'ar' ? 'تحذير: سيؤدي حذف هذه الوحدة إلى إزالة جميع العلامات والمهام المرتبطة بها نهائياً. هل ترغب في الاستمرار؟' : 'WARNING: Deleting this module will PERMANENTLY remove all associated grades and assignments. Proceed?' ?>');" style="margin:0;">
                            <input type="hidden" name="delete_course_id" value="<?= $c['id'] ?>">
                            <button type="submit" style="background:none; border:none; color:#ef4444; font-weight:600; cursor:pointer; font-size:14px; display: flex; align-items: center; gap: 5px;">
                                <i class="fas fa-trash-alt"></i> <?= $lang == 'ar' ? 'تطهير' : 'Purge' ?>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 50px;">
        <h3 style="color: #1e293b; margin-bottom: 20px; font-size: 20px; border-bottom: 2px solid #0A2B8E; display: inline-block; padding-bottom: 5px;"><?= $lang == 'ar' ? 'تعيينات الطاقم النشطة' : 'Active Staff Assignments' ?></h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= $lang == 'ar' ? 'الطاقم الأكاديمي' : 'Academic Staff' ?></th>
                    <th><?= $lang == 'ar' ? 'عنوان الوحدة' : 'Module Title' ?></th>
                    <th><?= $lang == 'ar' ? 'الدفعة المستهدفة' : 'Target Section' ?></th>
                    <th style="text-align: right;"><?= $lang == 'ar' ? 'إلغاء' : 'Revoke' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($active_assignments)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 40px;"><?= $lang == 'ar' ? 'لم يتم العثور على تعيينات هيئة التدريس في الدليل الحالي.' : 'No faculty mappings found in the current directory.' ?></td></tr>
                <?php else: ?>
                    <?php foreach($active_assignments as $aa): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($aa['teacher_name']) ?></strong></td>
                        <td><?= htmlspecialchars($aa['course_name']) ?></td>
                        <td><span class="badge badge-info" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;"><?= htmlspecialchars($aa['section_name']) ?></span></td>
                        <td style="text-align: right;">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?= $lang == 'ar' ? 'إلغاء وصول عضو هيئة التدريس هذا إلى الدفعة المختارة؟' : 'Revoke this staff member\'s access to the selected section?' ?>');">
                                <input type="hidden" name="delete_assignment" value="1">
                                <input type="hidden" name="teacher_id" value="<?= $aa['teacher_id'] ?>">
                                <input type="hidden" name="course_id" value="<?= $aa['course_id'] ?>">
                                <input type="hidden" name="section_id" value="<?= $aa['section_id'] ?>">
                                <button type="submit" style="background:none; border:none; color:#ef4444; font-weight:600; cursor:pointer; font-size:13px; display: flex; align-items: center; gap: 5px; justify-content: flex-end;">
                                    <i class="fas fa-unlink"></i> <?= $lang == 'ar' ? 'إلغاء' : 'Revoke' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<div id="edit-mod-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 15px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="color: #0A2B8E; margin-bottom: 20px;">Edit Module</h3>
        <form method="POST">
            <input type="hidden" name="edit_course_action" value="1">
            <input type="hidden" name="e_id" id="em-id">
            <div style="margin-bottom: 10px;">
                <label style="font-size: 13px; color: #666;">Module Name</label>
                <input type="text" name="e_name" id="em-name" class="form-input" required>
            </div>
            <div style="margin-bottom: 10px;">
                <label style="font-size: 13px; color: #666;">Code (e.g. CS101)</label>
                <input type="text" name="e_code" id="em-code" class="form-input" required>
            </div>
            <div style="display:flex; gap:10px; margin-bottom: 10px;">
                <div style="flex:1;">
                    <label style="font-size: 13px; color: #666;">Credits</label>
                    <input type="number" name="e_credits" id="em-credits" class="form-input" required>
                </div>
                <div style="flex:1;">
                    <label style="font-size: 13px; color: #666;">Coefficient</label>
                    <input type="number" name="e_coef" id="em-coef" class="form-input" required>
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="font-size: 13px; color: #666;">Semester</label>
                <select name="e_sem" id="em-sem" class="form-input" required>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-primary" style="flex: 1;">Save Changes</button>
                <button type="button" onclick="document.getElementById('edit-mod-modal').style.display='none'" class="logout-btn" style="flex: 1; border: 1px solid #cbd5e0; color: #4a5568;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModModal(c) {
    document.getElementById('em-id').value = c.id;
    document.getElementById('em-name').value = c.name;
    document.getElementById('em-code').value = c.code;
    document.getElementById('em-credits').value = c.credits;
    document.getElementById('em-coef').value = c.coefficient;
    document.getElementById('em-sem').value = c.semester;
    document.getElementById('edit-mod-modal').style.display = 'flex';
}
</script>

<?php include 'layout_footer.php'; ?>
