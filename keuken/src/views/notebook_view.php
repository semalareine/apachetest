<?php
$documentTitle = 'Mijn notitieboek';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($documentTitle) ?></title>
<link rel="stylesheet" href="views/notebook.css">
</head>
<body class="notebook-page">
<main class="notebook" aria-labelledby="notebook-title">
  <a class="notebook-back" href="/">Terug naar keuken</a>
  <header class="notebook-header">
    <p class="notebook-label">Persoonlijke notities</p>
    <h1 id="notebook-title">Mijn notitieboek</h1>
    <div class="profile-name">
      <span>Gebruikersnaam</span>
      <strong><?= e($currentUser['username']) ?></strong>
    </div>
    <p class="profile-role"><?= $currentUser['role'] === 'admin' ? 'Beheerder' : 'Receptliefhebber' ?></p>
  </header>

  <section class="notebook-notes" aria-labelledby="my-recipes-title">
    <div class="section-heading">
      <h2 id="my-recipes-title">Mijn recepten</h2>
      <span><?= count($myRecipes) ?></span>
    </div>
    <?php if (!$myRecipes): ?>
      <p class="notebook-empty">Je hebt nog geen recepten genoteerd.</p>
    <?php else: ?>
      <ul class="note-list">
        <?php foreach ($myRecipes as $recipe): ?>
          <li>
            <span class="note-title"><?= e($recipe['title']) ?></span>
            <span class="note-category"><?= e(isset($categories[$recipe['category']]) ? $categories[$recipe['category']] : 'Recept') ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <form class="notebook-logout" method="post" action="logout.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <button type="submit">Uitloggen</button>
  </form>
</main>
</body>
</html>
