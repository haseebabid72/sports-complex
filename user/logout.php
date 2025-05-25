<?php
session_start();
session_unset();
session_destroy();
header('Location: /sports-complex/user/login.php');
exit;
