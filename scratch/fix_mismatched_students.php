<?php
require 'config/database.php';
$pdo = getDBConnection();

// Fix wassim selama and others in CS-L2
// CS-L2 is section_id 4
// G1 in section 4 is group_id 5

echo "Fixing student-group alignments...\n";

// Find students in section 4 who have group_id from section 1 (presumably)
$stmt = $pdo->prepare("
    UPDATE student s
    JOIN `group` g_old ON s.group_id = g_old.id
    JOIN `group` g_new ON g_new.name = g_old.name AND g_new.section_id = s.section_id
    SET s.group_id = g_new.id
    WHERE s.section_id != g_old.section_id
");
$stmt->execute();
$count = $stmt->rowCount();

echo "Fixed $count student records.\n";
