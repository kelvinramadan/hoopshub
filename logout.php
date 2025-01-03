<!--logout.php-->
<?php
require_once 'config.php';

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>