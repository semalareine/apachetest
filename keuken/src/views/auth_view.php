<?php
$isLogin = $authMode === 'login';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($documentTitle) ?> · Het Receptenboek</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IM+Fell+English:ital@0;1&family=Pinyon+Script&display=swap" rel="stylesheet">
<link rel="stylesheet" href="views/recipe.css">
</head>
<body>
<main class="auth-stage">
  <section class="auth-panel" aria-labelledby="auth-title">
    <p class="auth-kicker">Welkom in de keuken</p>
    <h1 class="title" id="auth-title"><?= $isLogin ? 'Schuif aan' : 'Een plek aan tafel' ?></h1>
    <p class="note"><?= $isLogin ? 'Log in om de keuken en alle recepten te bekijken.' : 'Maak een account en deel je recepten met iedereen.' ?></p>

    <?php if ($errors): ?>
      <div class="form-errors" role="alert">
        <p>Controleer het volgende:</p>
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form class="auth-form" method="post" action="<?= $isLogin ? 'login.php' : 'register.php' ?>">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
      <label class="field">
        <span>Gebruikersnaam</span>
        <input type="text" name="username" required minlength="3" maxlength="32"
               pattern="[A-Za-z0-9_]+" autocomplete="username" value="<?= e($username) ?>">
      </label>
      <label class="field">
        <span>Wachtwoord<?= $isLogin ? '' : ' (8–72 tekens)' ?></span>
        <input type="password" name="password" required
               <?= $isLogin ? 'maxlength="72"' : 'minlength="8" maxlength="72"' ?>
               autocomplete="<?= $isLogin ? 'current-password' : 'new-password' ?>">
      </label>
      <?php if (!$isLogin): ?>
        <label class="field">
          <span>Herhaal wachtwoord</span>
          <input type="password" name="password_confirmation" required minlength="8" maxlength="72"
                 autocomplete="new-password">
        </label>
      <?php endif; ?>
      <button class="button" type="submit"><?= $isLogin ? 'Naar de keuken' : 'Account maken' ?></button>
    </form>

    <p class="auth-switch">
      <?php if ($isLogin): ?>
        Nog geen account? <a href="register.php">Maak er een aan</a>
      <?php else: ?>
        Al een account? <a href="login.php">Log in</a>
      <?php endif; ?>
    </p>
  </section>
</main>
</body>
</html>
