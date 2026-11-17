<?php require __DIR__ . DIRECTORY_SEPARATOR . 'book_layout_start.php'; ?>
      <?php if (!$recipe): ?>
        <header class="page-head">
          <h2>Pagina niet gevonden</h2>
          <p>Dit recept bestaat niet</p>
        </header>
        <p class="empty detail-empty">Deze bladzijde ontbreekt in het receptenboek.</p>
        <a class="read" href="recipes.php">Terug naar het receptenboek</a>
      <?php else: ?>
        <header class="page-head">
          <h2><?= e($recipe['category'] && isset($categories[$recipe['category']]) ? $categories[$recipe['category']] : 'Recept') ?></h2>
          <a class="back-link" href="recipes.php?category=<?= e(rawurlencode($recipe['category'])) ?>">Terug naar het receptenboek</a>
        </header>

        <?php if (isset($_GET['created']) && $_GET['created'] === '1'): ?>
          <p class="success-message" role="status">Je recept is toegevoegd aan het receptenboek.</p>
        <?php elseif (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
          <p class="success-message" role="status">Je wijzigingen zijn opgeslagen.</p>
        <?php endif; ?>

        <article class="recipe-detail">
          <div class="detail-title">
            <?php if ($recipe['image'] !== ''): ?>
              <img class="detail-image" src="<?= e($recipe['image']) ?>" alt="<?= e($recipe['title']) ?>">
            <?php endif; ?>
            <h1><?= e($recipe['title']) ?></h1>
            <p class="desc"><?= e($recipe['description']) ?></p>
            <p class="recipe-author">Door <?= e($recipe['author'] ?: 'onbekend') ?></p>
            <dl class="facts detail-facts">
              <div><dt>Voorbereiding</dt><dd><?= (int)$recipe['prep_time'] ?> min</dd></div>
              <div><dt>Bereiding</dt><dd><?= (int)$recipe['cook_time'] ?> min</dd></div>
              <div><dt>Totaal</dt><dd><?= (int)$recipe['prep_time'] + (int)$recipe['cook_time'] ?> min</dd></div>
              <div><dt>Moeilijkheid</dt><dd><?= e(recipeDifficultyLabel($recipe['difficulty'])) ?></dd></div>
              <div><dt>Porties</dt><dd><?= (int)$recipe['servings'] ?></dd></div>
            </dl>
          </div>

          <?php if (canManageRecipe($currentUser, $recipe)): ?>
            <div class="manage-actions">
              <a class="manage-link" href="add_recipe.php?id=<?= (int)$recipe['id'] ?>">Recept bewerken</a>
              <form method="post" action="recipe_delete.php"
                    onsubmit="return confirm('Weet je zeker dat je dit recept wilt verwijderen?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= (int)$recipe['id'] ?>">
                <button class="delete-button" type="submit">Recept verwijderen</button>
              </form>
            </div>
          <?php endif; ?>

          <?php if ($recipe['ingredients'] === '' && $recipe['instructions'] === ''): ?>
            <p class="sample-note">Aan dit voorbeeldrecept zijn nog geen ingrediënten of bereidingsstappen toegevoegd.</p>
          <?php else: ?>
            <div class="detail-columns">
              <section class="detail-section">
                <h2>Ingrediënten</h2>
                <?php if ($recipe['ingredients'] !== ''): ?>
                  <ul class="ingredient-list">
                    <?php foreach (preg_split('/\r\n|\r|\n/', trim($recipe['ingredients'])) as $ingredient): ?>
                      <?php if (trim($ingredient) !== ''): ?><li><?= e(trim($ingredient)) ?></li><?php endif; ?>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <p class="sample-note">Er zijn nog geen ingrediënten ingevuld.</p>
                <?php endif; ?>
              </section>
              <section class="detail-section">
                <h2>Bereidingswijze</h2>
                <?php if ($recipe['instructions'] !== ''): ?>
                  <ol class="instruction-list">
                    <?php foreach (preg_split('/\r\n|\r|\n/', trim($recipe['instructions'])) as $instruction): ?>
                      <?php if (trim($instruction) !== ''): ?><li><?= e(trim($instruction)) ?></li><?php endif; ?>
                    <?php endforeach; ?>
                  </ol>
                <?php else: ?>
                  <p class="sample-note">Er is nog geen bereidingswijze ingevuld.</p>
                <?php endif; ?>
              </section>
            </div>
          <?php endif; ?>
        </article>
      <?php endif; ?>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'book_layout_end.php'; ?>
