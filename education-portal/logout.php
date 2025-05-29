<?php
require 'config.php';
unset($_SESSION['admin_panel_authenticated']); // Clear admin panel access
session_destroy();
header("Location: login.php");
exit;
?>