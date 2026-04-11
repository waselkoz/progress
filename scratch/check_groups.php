<?php
require 'config/database.php';
$pdo = getDBConnection();

echo "GROUPS:\n";
$groups = $pdo->query("SELECT id, name, section_id FROM `group`")->fetchAll(PDO::FETCH_ASSOC);
print_r($groups);
