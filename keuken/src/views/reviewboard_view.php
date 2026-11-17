<?php
$documentTitle = 'Recentiebord';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($documentTitle) ?></title>
<link rel="stylesheet" href="views/reviewboard.css">
</head>
<body class="reviewboard-page">
<main class="pinboard" aria-labelledby="reviewboard-title">
  <header class="pinboard-header">
    <a class="kitchen-link" href="/">Terug naar de keuken</a>
    <p class="board-kicker">Regel 1: Nestle = poo</p>
    <h1 id="reviewboard-title">Het prikbord</h1>
    <p>Een recept geprobeerd? Geef het sterren en vertel hoe het ging.</p>
  </header>

  <?php if (isset($_GET['posted']) && $_GET['posted'] === '1'): ?>
    <p class="board-notice" role="status">Je briefje hangt erbij!</p>
  <?php endif; ?>
  <?php if (isset($errors['form'])): ?>
    <p class="board-error" role="alert"><?= e($errors['form']) ?></p>
  <?php endif; ?>

  <?php if (!$availableRecipes): ?>
    <p class="board-empty">Er staan nog geen recepten op het bord. Kom later nog eens kijken!</p>
  <?php else: ?>
    <div class="pinboard-grid">
      <?php foreach ($availableRecipes as $recipe): ?>
        <?php $recipeId = (int)$recipe['id']; ?>
        <?php $cardColor = array('red', 'blue', 'green')[abs(crc32((string)$recipeId)) % 3]; ?>
        <?php $myReview = isset($myReviews[$recipeId]) ? $myReviews[$recipeId] : null; ?>
        <?php $formValues = isset($values[$recipeId]) ? $values[$recipeId] : $myReview; ?>
        <article class="recipe-pin recipe-pin--<?= e($cardColor) ?>" id="recipe-<?= $recipeId ?>">
          <span class="pin" aria-hidden="true"></span>
          <header class="recipe-pin-header">
            <p class="recipe-category"><?= e(isset($categories[$recipe['category']]) ? $categories[$recipe['category']] : 'Recept') ?></p>
            <h2><?= e($recipe['title']) ?></h2>
            <p class="pin-author"><?= (int)$recipe['user_id'] === (int)$currentUser['id'] ? 'Jouw recept' : 'Van ' . e($recipe['author'] ?: 'een thuiskok') ?></p>
          </header>
          <p class="pin-description"><?= e($recipe['description']) ?></p>
          <p class="rating-summary">
            <?php if ((int)$recipe['review_count'] > 0): ?>
              <span class="rating-stars" aria-label="Gemiddeld <?= number_format((float)$recipe['average_rating'], 1, ',', '') ?> van 5 sterren"><?= str_repeat('★', (int)round((float)$recipe['average_rating'])) ?><?= str_repeat('☆', 5 - (int)round((float)$recipe['average_rating'])) ?></span>
              <span><?= number_format((float)$recipe['average_rating'], 1, ',', '') ?>/5 · <?= (int)$recipe['review_count'] ?> <?= (int)$recipe['review_count'] === 1 ? 'reactie' : 'reacties' ?></span>
            <?php else: ?>
              <span class="rating-stars" aria-hidden="true">☆☆☆☆☆</span>
              <span>Nog geen sterren. Wees de eerste!</span>
            <?php endif; ?>
          </p>

          <?php if ((int)$recipe['user_id'] !== (int)$currentUser['id']): ?>
          <form class="review-form" method="post" action="reviewboard.php#recipe-<?= $recipeId ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="recipe_id" value="<?= $recipeId ?>">
            <fieldset class="star-picker">
              <legend>Hoeveel sterren geef je het?</legend>
              <?php for ($star = 5; $star >= 1; $star--): ?>
                <label class="star-choice">
                  <input type="radio" name="rating" value="<?= $star ?>"
                         aria-label="<?= $star ?> <?= $star === 1 ? 'ster' : 'sterren' ?>"
                         <?= $formValues && (int)$formValues['rating'] === $star ? 'checked' : '' ?> required>
                  <span aria-hidden="true">★</span>
                </label>
              <?php endfor; ?>
            </fieldset>
            <label class="comment-label" for="comment-<?= $recipeId ?>">Vertel ’t erbij</label>
            <textarea id="comment-<?= $recipeId ?>" name="comment" rows="3" maxlength="1500"
                      placeholder="Lekker? Laat een berichtje achter choom…" required><?= e($formValues ? $formValues['comment'] : '') ?></textarea>
            <?php if (isset($errors[$recipeId])): ?>
              <p class="form-error" role="alert"><?= e($errors[$recipeId]) ?></p>
            <?php endif; ?>
            <div class="review-form-footer">
              <?php if ($myReview): ?><span class="edit-hint">Je kunt je briefje altijd aanpassen.</span><?php endif; ?>
              <button type="submit"><?= $myReview ? 'Bewaar beoordeling' : 'Plaats beoordeling' ?></button>
            </div>
          </form>
          <?php else: ?>
            <p class="own-recipe-note">Je eigen recept kan je niet beoordelen.</p>
          <?php endif; ?>

          <section class="pinned-reviews" aria-label="Reacties op <?= e($recipe['title']) ?>">
            <h3>Briefjes van anderen</h3>
            <?php if (empty($reviewsByRecipe[$recipeId])): ?>
              <p class="no-reviews">Nog niemand heeft iets opgehangen.</p>
            <?php else: ?>
              <ul>
                <?php foreach ($reviewsByRecipe[$recipeId] as $review): ?>
                  <li>
                    <p class="review-meta">
                      <strong><?= e($review['username']) ?></strong>
                      <span class="rating-stars" aria-label="<?= (int)$review['rating'] ?> van 5 sterren"><?= str_repeat('★', (int)$review['rating']) ?><?= str_repeat('☆', 5 - (int)$review['rating']) ?></span>
                    </p>
                    <p class="review-comment"><?= nl2br(e($review['comment'])) ?></p>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </section>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body>
</html>
