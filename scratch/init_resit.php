<?php
require 'config/database.php';
$pdo = getDBConnection();
$pdo->query("INSERT INTO system_settings (setting_key, setting_value) VALUES ('resit_period_open', '0') ON DUPLICATE KEY UPDATE setting_key=setting_key");
echo "Resit period initialized.";
