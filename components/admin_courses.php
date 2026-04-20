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
    <div class="page-actions">
        <h2 class="page-title">Module Management</h2>
        <button onclick="document.getElementById('add-curr-form').style.display='block'" class="btn-primary">+ New Module</button>
    </div>
    
    <div id="add-curr-form" style="display: none; background: #f8fafc; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
        <h4 style="margin-bottom: 15px; color:#2d3748;">Add New Module</h4>
        <form method="POST" class="form-grid">
            <input type="text" name="c_name" class="form-input" placeholder="Module Name" required>
            <input type="text" name="c_code" class="form-input" placeholder="Code (e.g. CS101)" required>
            <input type="number" name="c_credits" class="form-input" placeholder="Credits" required>
            <input type="number" name="c_coef" class="form-input" placeholder="Coefficient" required>
            <select name="c_spec" class="form-input" required>
                <?php foreach($specialities as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="c_year" class="form-input" required>
                <?php foreach($years as $y): ?>
                    <option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="c_sem" class="form-input" required>
                <option value="S1">Semester 1 (S1)</option>
                <option value="S2">Semester 2 (S2)</option>
            </select>
            <button type="submit" class="btn-primary" style="width: 100%; grid-column: span 2;">Save Module</button>
        </form>
    </div>

    <div style="background: #eef2ff; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #c3dafe;">
        <h4 style="margin-bottom: 15px; color:#1e429f;">Bulk Assign Teacher to Sections</h4>
        <form method="POST" class="form-grid">
            <input type="hidden" name="assign_teacher" value="1">
            <div style="grid-column: span 1;">
                <label style="display:block; margin-bottom:8px; font-weight:600; color:#4a5568;">1. Select Teacher</label>
                <select name="teacher_id" class="form-input" required>
                    <option value="">Select...</option>
                    <?php foreach($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="grid-column: span 1;">
                <label style="display:block; margin-bottom:8px; font-weight:600; color:#4a5568;">2. Select Module</label>
                <select name="course_id" class="form-input" required>
                    <option value="">Select...</option>
                    <?php foreach($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="grid-column: span 2; margin-top: 15px;">
                <label style="display:block; margin-bottom:12px; font-weight:600; color:#4a5568;">3. Target Sections (Multiple Choice)</label>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; max-height: 150px; overflow-y: auto; background: white; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <?php foreach($sections as $s): ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
                            <input type="checkbox" name="section_ids[]" value="<?= $s['id'] ?>"> <?= htmlspecialchars($s['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; grid-column: span 2; margin-top: 20px;">Assign to Selected Sections</button>
        </form>
    </div>

    <p class="helper-text">Manage the module catalog, coefficients, and responsible teachers.</p>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Coef</th>
                <th>Credits</th>
                <th>Semester</th>
                <th>Responsible</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($courses as $c): ?>
            <tr>
                <td><span class="badge badge-info"><?= htmlspecialchars($c['code']) ?></span></td>
                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                <td style="text-align: center;"><?= htmlspecialchars($c['coefficient']) ?></td>
                <td><?= htmlspecialchars($c['credits']) ?> Cr</td>
                <td><?= htmlspecialchars($c['semester']) ?></td>
                <td><span style="font-size: 14px; font-weight: 500; color: #4a5568;"><?= htmlspecialchars($c['teacher_names'] ?? 'None') ?></span></td>
                <td style="display:flex; gap:10px; justify-content:flex-end; align-items: center; border-bottom: none; border-top: none;">
                    <button onclick='openEditModModal(<?= json_encode($c) ?>)' style="background:none; border:none; color:#4a90e2; font-weight:bold; cursor:pointer; font-size:13px;">Edit</button>
                    <form method="POST" onsubmit="return confirm('Delete this module completely? This will also unassign all teachers and delete associated grades!');" style="margin:0;">
                        <input type="hidden" name="delete_course_id" value="<?= $c['id'] ?>">
                        <button type="submit" style="background:none; border:none; color:#e53e3e; font-weight:bold; cursor:pointer; font-size:13px;">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 style="color: #0A2B8E; margin: 40px 0 20px 0;">Active Teacher Assignments</h2>
    <div style="background: #e1effe; padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #0A2B8E;">
        <p style="margin:0; font-size: 14px; color: #0A2B8E;"><strong>Note:</strong> Assignments are grouped by section. Removing an assignment will revoke the teacher's access to all groups within that section for the selected module.</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Teacher</th>
                <th>Module</th>
                <th>Target Section</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($active_assignments)): ?>
                <tr><td colspan="4">No active teacher-module pairings found.</td></tr>
            <?php else: ?>
                <?php foreach($active_assignments as $aa): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($aa['teacher_name']) ?></strong></td>
                    <td><?= htmlspecialchars($aa['course_name']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($aa['section_name']) ?></span></td>
                    <td style="text-align: right;">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove this teacher from this section?');">
                            <input type="hidden" name="delete_assignment" value="1">
                            <input type="hidden" name="teacher_id" value="<?= $aa['teacher_id'] ?>">
                            <input type="hidden" name="course_id" value="<?= $aa['course_id'] ?>">
                            <input type="hidden" name="section_id" value="<?= $aa['section_id'] ?>">
                            <button type="submit" style="background:none; border:none; color:#e53e3e; font-weight:600; cursor:pointer; font-size:13px;">Remove Assignment</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
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
