<?php
// admin/logout.php
require_once 'super_auth.php';
$superAdmin->logout();
header('Location: login.php');
exit;
?>