<?php
require_once "../src/auth.php";
require_admin();
require_once "../src/users.php";

$id = $_GET["id"];
delete_student($pdo, $id);
header("Location: admin_portal.php");
exit;
