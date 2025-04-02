<?php
session_start();
session_unset();
session_destroy();
setcookie("session", "", time() - 3600, "/", "", true, true);
header("Location: home.html");
exit();
?>
