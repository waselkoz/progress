<?php // Wassim Selama / Aissaoui Imededdine / Khettab Imededdine / Temlali Oussama
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();
?>
