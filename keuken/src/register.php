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
    $confirmation = isset($_POST['password_confirmation']) && is_string($_POST['password_confirmation'])
        ? $_POST['password_confirmation']
        : '';

    if (!validCsrfToken()) {
        $errors[] = 'Je sessie is verlopen. Probeer opnieuw.';
    } else {
        if (!preg_match('/\A[A-Za-z0-9_]{3,32}\z/', $username)) {
            $errors[] = 'Kies een gebruikersnaam van 3 tot 32 tekens met alleen letters, cijfers of _ .';
        } elseif (strcasecmp($username, 'alexcaspercrud') === 0) {
            $errors[] = 'Deze gebruikersnaam is gereserveerd.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Je wachtwoord moet minimaal 8 en maximaal 72 tekens bevatten.';
        }
        if ($password !== $confirmation) {
            $errors[] = 'De wachtwoorden komen niet overeen.';
        }

        if (!$errors) {
            $insert = $db->prepare(
                'INSERT OR IGNORE INTO users (username, password_hash)
                 VALUES (:username, :password_hash)'
            );
            $insert->execute(array(
                ':username' => $username,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ));
            if ($insert->rowCount() !== 1) {
                $errors[] = 'Deze gebruikersnaam is al in gebruik.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$db->lastInsertId();
                unset($_SESSION['csrf_token']);
                header('Location: /', true, 303);
                exit;
            }
        }
    }
}

$authMode = 'register';
$documentTitle = 'Account maken';
require __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'auth_view.php';
