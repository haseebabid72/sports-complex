<?php
// Simple authentication helper functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}
function require_login() {
    if (!is_logged_in()) {
        header('Location: /sports-complex/user/login.php');
        exit;
    }
}
function require_role($role) {
    if (!is_logged_in() || ($_SESSION['user_role'] ?? null) !== $role) {
        header('Location: /sports-complex/user/login.php');
        exit;
    }
}
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}
function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}
function get_user_name() {
    return $_SESSION['user_name'] ?? null;
}
?>
