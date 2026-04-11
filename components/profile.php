<?php include 'layout_header.php'; 

$profile = null;
if(isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT u.name, u.email, u.role, 
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
?>

<div class="card-container">
    <h2 style="color: #0A2B8E; margin-bottom: 15px;">My Profile</h2>
    
    <div style="display: flex; gap: 30px; margin-top: 20px;">
        <div style="flex: 0 0 150px; text-align: center;">
            <img src="../img/USTHB.png" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.1); padding: 10px;">
            <span class="badge <?= $badge_class ?>" style="margin-top: 15px; display: inline-block; text-transform: uppercase;">
                <?= htmlspecialchars($profile['role'] ?? 'Unknown User') ?>
            </span>
        </div>
        
        <div style="flex: 1;">
            <table class="data-table">
                <tbody>
                    <tr>
                        <th style="width: 150px;">Full Name</th>
                        <td><strong><?= htmlspecialchars($profile['name'] ?? 'N/A') ?></strong></td>
                    </tr>
                    <tr>
                        <th>Email Address</th>
                        <td><?= htmlspecialchars($profile['email'] ?? 'N/A') ?></td>
                    </tr>
                    
                    <?php if(($profile['role'] ?? '') == 'admin'): ?>
                        <tr>
                            <th>Clearance Level</th>
                            <td><span style="color: #e74c3c; font-weight: bold;"><?= htmlspecialchars(strtoupper($profile['admin_level'] ?? 'REGULAR')) ?> ADMIN</span></td>
                        </tr>
                        <tr>
                            <th>Security Log</th>
                            <td>All actions logged to global database.</td>
                        </tr>
                    <?php elseif(($profile['role'] ?? '') == 'teacher'): ?>
                        <tr>
                            <th>Employee ID</th>
                            <td><?= htmlspecialchars($profile['employee_number'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <th>Hire Date</th>
                            <td><?= htmlspecialchars($profile['hire_date'] ?? 'N/A') ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <th>Student Number</th>
                            <td><?= htmlspecialchars($profile['student_number'] ?? 'Not Assigned') ?></td>
                        </tr>
                        <tr>
                            <th>Birth Date</th>
                            <td><?= htmlspecialchars($profile['birth_date'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <th>Enrolled Since</th>
                            <td><?= htmlspecialchars($profile['enrollment_year'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
