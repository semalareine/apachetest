<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

startAppSession();
$db = recipeDb();
if (currentUser($db)) {
    header('Location: /', true, 303);
    exit;
}

$errors = array();
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) && is_string($_POST['username'])
        ? trim($_POST['username'])
        : '';
    $password = isset($_POST['password']) && is_string($_POST['password'])
        ? $_POST['password']
        : '';

    if (!validCsrfToken()) {
        $errors[] = 'Je sessie is verlopen. Probeer opnieuw.';
    } elseif (!preg_match('/\A[A-Za-z0-9_]{3,32}\z/', $username)
        || strlen($password) < 1 || strlen($password) > 72) {
        $errors[] = 'Gebruikersnaam of wachtwoord is onjuist.';
    } else {
        $query = $db->prepare('SELECT id, password_hash FROM users WHERE username = :username');
        $query->execute(array(':username' => $username));
        $account = $query->fetch();
        if (!$account || !password_verify($password, $account['password_hash'])) {
            $errors[] = 'Gebruikersnaam of wachtwoord is onjuist.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$account['id'];
            unset($_SESSION['csrf_token']);
            header('Location: /', true, 303);
            exit;
        }
    }
}

$authMode = 'login';
$documentTitle = 'Inloggen';
require __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'auth_view.php';
