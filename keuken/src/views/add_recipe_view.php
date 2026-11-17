<?php require __DIR__ . DIRECTORY_SEPARATOR . 'book_layout_start.php'; ?>
      <header class="page-head">
        <h2><?= $editingRecipe ? 'Recept bewerken' : 'Recept toevoegen' ?></h2>
        <p><?= $editingRecipe ? 'Pas deze bladzijde aan' : 'Een nieuwe bladzijde in je boek' ?></p>
      </header>

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

      <form class="recipe-form" method="post"
            action="add_recipe.php<?= $editingRecipe ? '?id=' . (int)$editingId : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <label class="field field--wide">
          <span>Naam van het recept</span>
          <input type="text" name="title" maxlength="120" required value="<?= e($values['title']) ?>">
        </label>

        <label class="field">
          <span>Category</span>
          <select name="category" required>
            <option value="">Kies een categorie</option>
            <?php foreach ($categories as $key => $label): ?>
              <?php if ($key !== 'all'): ?>
                <option value="<?= e($key) ?>" <?= $values['category'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="field">
          <span>Moeilijkheidsgraad</span>
          <select name="difficulty">
            <?php foreach (array('Easy' => 'Makkelijk', 'Medium' => 'Gemiddeld', 'Hard' => 'Moeilijk') as $difficulty => $label): ?>
              <option value="<?= e($difficulty) ?>" <?= $values['difficulty'] === $difficulty ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="field field--wide">
          <span>Beschrijving</span>
          <textarea name="description" rows="2" maxlength="1000" required><?= e($values['description']) ?></textarea>
        </label>

        <label class="field">
          <span>Voorbereidingstijd (minuten)</span>
          <input type="number" name="prep_time" min="0" max="1440" required value="<?= e($values['prep_time']) ?>">
        </label>
        <label class="field">
          <span>Bereidingstijd (minuten)</span>
          <input type="number" name="cook_time" min="0" max="1440" required value="<?= e($values['cook_time']) ?>">
        </label>
        <label class="field">
          <span>Aantal porties</span>
          <input type="number" name="servings" min="1" max="100" required value="<?= e($values['servings']) ?>">
        </label>
        <label class="field">
          <span>Afbeeldingslink <em>(optioneel)</em></span>
          <input type="url" name="image" value="<?= e($values['image']) ?>" placeholder="https://">
        </label>

        <label class="field field--wide">
          <span>Ingrediënten <em>(één per regel)</em></span>
          <textarea name="ingredients" rows="5" maxlength="20000" required><?= e($values['ingredients']) ?></textarea>
        </label>
        <label class="field field--wide">
          <span>Bereidingswijze <em>(één stap per regel)</em></span>
          <textarea name="instructions" rows="6" maxlength="20000" required><?= e($values['instructions']) ?></textarea>
        </label>

        <div class="form-actions field--wide">
          <a class="back-link" href="<?= $editingRecipe ? 'recipe.php?id=' . (int)$editingId : 'recipes.php' ?>">Terug naar het receptenboek</a>
          <button class="button" type="submit"><?= $editingRecipe ? 'Wijzigingen opslaan' : 'Recept opslaan' ?></button>
        </div>
      </form>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'book_layout_end.php'; ?>
