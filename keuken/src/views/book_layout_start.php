<?php
$documentTitle = isset($documentTitle) ? $documentTitle : $bookTitle;
$activeCategory = isset($activeCategory) ? $activeCategory : null;
$activePage = isset($activePage) ? $activePage : '';
$currentUser = isset($currentUser) ? $currentUser : currentUser($db);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($documentTitle) ?> · <?= e($bookTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IM+Fell+English:ital@0;1&family=Pinyon+Script&display=swap" rel="stylesheet">
<link rel="stylesheet" href="views/recipe.css">
</head>
<body>
<div class="stage">
  <span class="ribbon" aria-hidden="true"></span>
  <main class="book">
    <aside class="page page--left">
      <header class="intro">
        <h1 class="title"><a class="title-link" href="recipes.php"><?= e($bookTitle) ?></a></h1>
        <p class="note"><?= e($bookNote) ?></p>
        <a class="home-link" href="/">Terug naar de keuken</a>
        <form class="logout-form" method="post" action="logout.php">
          <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
          <button type="submit">Uitloggen</button>
        </form>
      </header>

      <nav class="contents" aria-label="Receptcategorieën">
        <h2>Inhoud</h2>
        <form class="recipe-search" method="get" action="recipes.php">
          <label for="recipe-search">Zoek op receptnaam</label>
          <div class="recipe-search-controls">
            <input id="recipe-search" type="search" name="search" value="<?= e(isset($search) ? $search : '') ?>"
                   placeholder="Bijv. nestle koffie">
            <input type="hidden" name="category"
                   value="<?= e(isset($activeCategory) && isset($categories[$activeCategory]) ? $activeCategory : 'all') ?>">
            <button type="submit">Zoek</button>
          </div>
        </form>
        <ul>
          <?php foreach ($categories as $key => $label): ?>
            <li>
              <a href="<?= e(pageUrl($key, 1)) ?>"
                 class="<?= $key === $activeCategory ? 'is-active' : '' ?>"
                 <?= $key === $activeCategory ? 'aria-current="page"' : '' ?>>
                <span class="entry-name"><?= e($label) ?></span>
                <span class="leader" aria-hidden="true"></span>
                <span class="entry-count"><?= (int)(isset($counts[$key]) ? $counts[$key] : 0) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <a class="add-recipe <?= $activePage === 'add' ? 'is-active' : '' ?>"
           href="add_recipe.php" <?= $activePage === 'add' ? 'aria-current="page"' : '' ?>>+ Recept toevoegen</a>
      </nav>
    </aside>
    <section class="page page--right">
