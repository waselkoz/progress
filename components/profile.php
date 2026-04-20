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
    <div class="page-actions">
        <div>
            <h2 class="page-title">Profile Identity</h2>
            <p class="helper-text">Official university dossier & clearance log</p>
        </div>
    </div>
    
    <div style="display: flex; gap: 40px; margin-top: 20px; flex-wrap: wrap;">
        <!-- Left Sidebar: Photo & Active Status -->
        <div style="flex: 0 0 220px; text-align: center; border-right: 1px solid #e2e8f0; padding-right: 40px;">
            <div style="background: white; border-radius: 50%; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 15px; display: inline-block; margin-bottom: 20px;">
                <img src="../img/USTHB.png" alt="Profile" style="width: 140px; height: 140px;">
            </div>
            
            <h3 style="color: #2d3748; font-size: 18px; margin-bottom: 5px;"><?= htmlspecialchars($profile['name'] ?? 'N/A') ?></h3>
            <p style="color: #718096; font-size: 13px; margin-bottom: 15px; word-break: break-all;"><?= htmlspecialchars($profile['email'] ?? 'N/A') ?></p>
            
            <span class="badge <?= $badge_class ?>" style="padding: 8px 20px; font-size: 14px; display: block; text-transform: uppercase; letter-spacing: 1px;">
                <?= htmlspecialchars($profile['role'] ?? 'Unknown User') ?>
            </span>
        </div>
        
        <!-- Right Content: Detailed Stats Grid -->
        <div style="flex: 1; min-width: 300px;">
            <h3 class="sub-header">Institutional Data</h3>
            <div class="two-col-grid" style="gap: 25px;">
                <div class="stat-card">
                    <p class="stat-label">Full Legal Name</p>
                    <p style="color: #0A2B8E; font-size: 18px; font-weight: 600; margin: 0;"><?= htmlspecialchars($profile['name'] ?? 'N/A') ?></p>
                </div>
                
                <div class="stat-card">
                    <p class="stat-label">Official Email</p>
                    <p style="color: #0A2B8E; font-size: 18px; font-weight: 600; margin: 0;"><?= htmlspecialchars($profile['email'] ?? 'N/A') ?></p>
                </div>

                <?php if(($profile['role'] ?? '') == 'admin'): ?>
                    <div class="stat-card-blue full-span">
                        <p class="stat-label" style="color: rgba(10,43,142,0.6);">Clearance Level</p>
                        <p style="color: #e53e3e; font-size: 20px; font-weight: 700; margin: 0; letter-spacing: 1px;"><?= htmlspecialchars(strtoupper($profile['admin_level'] ?? 'REGULAR')) ?> ADMINISTRATOR</p>
                    </div>
                <?php elseif(($profile['role'] ?? '') == 'teacher'): ?>
                    <div class="stat-card-blue">
                        <p class="stat-label" style="color: rgba(10,43,142,0.6);">Employee UID</p>
                        <p style="color: #0A2B8E; font-size: 20px; font-weight: 700; margin: 0;"><?= htmlspecialchars($profile['employee_number'] ?? 'N/A') ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Hire Date</p>
                        <p style="color: #2d3748; font-size: 18px; font-weight: 600; margin: 0;"><?= htmlspecialchars($profile['hire_date'] ?? 'N/A') ?></p>
                    </div>
                <?php else: ?>
                    <div class="stat-card-blue full-span" style="display: flex; gap: 20px;">
                        <div style="flex: 1;">
                            <p class="stat-label" style="color: rgba(10,43,142,0.6);">Student Registration No.</p>
                            <p style="color: #0A2B8E; font-size: 24px; font-weight: 700; margin: 0;"><?= htmlspecialchars($profile['student_number'] ?? 'Not Assigned') ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Birth Date</p>
                        <p style="color: #2d3748; font-size: 18px; font-weight: 600; margin: 0;"><?= htmlspecialchars($profile['birth_date'] ?? 'N/A') ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Enrolled Since</p>
                        <p style="color: #2d3748; font-size: 18px; font-weight: 600; margin: 0;"><?= htmlspecialchars($profile['enrollment_year'] ?? 'N/A') ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if(($profile['role'] ?? '') == 'admin'): ?>
            <div class="alert-banner" style="margin-top: 25px;">
                <p><strong>System Note:</strong> You are logged into a master clearance account. All your actions inside the module and user databases are permanently recorded in the global log.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>
