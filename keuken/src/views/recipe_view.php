<?php require __DIR__ . DIRECTORY_SEPARATOR . 'book_layout_start.php'; ?>
      <header class="page-head">
        <h2><?= e($categories[$cat]) ?></h2>
        <p><?= $total ?> <?= $total === 1 ? 'recept' : 'recepten' ?></p>
      </header>

      <?php if (!$visible): ?>
        <p class="empty">
          <?php if ($search !== ''): ?>
            Geen recepten gevonden met de naam "<?= e($search) ?>".
          <?php else: ?>
            Er staan nog geen recepten in deze categorie.
          <?php endif; ?>
        </p>
      <?php else: ?>
        <ul class="recipes">
          <?php foreach ($visible as $r): ?>
            <li class="recipe">
              <div class="plate">
                <?php if (!empty($r['image'])): ?>
                  <img src="<?= e($r['image']) ?>" alt="<?= e($r['title']) ?>">
                <?php else: ?>
                  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M7 3v7a2 2 0 0 0 4 0V3M9 12v9M17 21V3c-2 1.5-3 4-3 7s1 3 3 3"/>
                  </svg>
                <?php endif; ?>
              </div>

              <div class="recipe-body">
                <h3><?= e($r['title']) ?></h3>
                <p class="desc"><?= e($r['description']) ?></p>
                <p class="recipe-author">Door <?= e($r['author'] ?: 'onbekend') ?></p>
                <dl class="facts">
                  <div><dt>Tijd</dt><dd><?= (int)$r['prep_time'] + (int)$r['cook_time'] ?> min</dd></div>
                  <div><dt>Moeilijkheid</dt><dd><?= e(recipeDifficultyLabel($r['difficulty'])) ?></dd></div>
                  <div><dt>Porties</dt><dd><?= e($r['servings']) ?></dd></div>
                </dl>
                <a class="read" href="recipe.php?id=<?= (int)$r['id'] ?>">Bekijk recept</a>
                <?php if (canManageRecipe($currentUser, $r)): ?>
                  <a class="manage-link" href="add_recipe.php?id=<?= (int)$r['id'] ?>">Bewerken</a>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <nav class="pager" aria-label="Boekpagina's">
        <?php if ($page > 1): ?>
          <a href="<?= e(pageUrl($cat, $page - 1, $search)) ?>">Vorige pagina</a>
        <?php else: ?>
          <span class="is-off">Vorige pagina</span>
        <?php endif; ?>

        <span class="where">Pagina <?= $page ?> van <?= $totalPages ?></span>

        <?php if ($page < $totalPages): ?>
          <a href="<?= e(pageUrl($cat, $page + 1, $search)) ?>">Volgende pagina</a>
        <?php else: ?>
          <span class="is-off">Volgende pagina</span>
        <?php endif; ?>
      </nav>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'book_layout_end.php'; ?>
