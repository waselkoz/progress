<?php
require 'config/database.php';
$pdo = getDBConnection();

echo "STUDENTS:\n";
$students = $pdo->query("SELECT s.id, u.name, s.section_id, s.group_id, sec.name as sec_name, g.name as grp_name 
                        FROM student s 
                        JOIN users u ON s.user_id = u.id 
                        JOIN section sec ON s.section_id = sec.id 
                        JOIN `group` g ON s.group_id = g.id")->fetchAll(PDO::FETCH_ASSOC);
print_r($students);

echo "\nCOURSE ASSIGNMENTS:\n";
$assignments = $pdo->query("SELECT ca.id, c.name, sec.name as sec, g.name as grp, t.id as tid 
                           FROM course_assignment ca 
                           JOIN courses c ON ca.course_id = c.id 
                           JOIN section sec ON ca.section_id = sec.id 
                           JOIN `group` g ON ca.group_id = g.id")->fetchAll(PDO::FETCH_ASSOC);
print_r($assignments);
