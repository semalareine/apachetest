<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

$db = recipeDb();
$currentUser = currentUser($db);
requireLogin($db);
$bookTitle = 'Het Receptenboek';
$bookNote = 'Bewaar een nieuw recept in het receptenboek.';
$categories = recipeCategories();
$counts = recipeCounts($db);
$editingId = isset($_GET['id']) && is_scalar($_GET['id'])
    ? filter_var($_GET['id'], FILTER_VALIDATE_INT)
    : false;
$editingRecipe = false;
if ($editingId !== false) {
    $query = $db->prepare('SELECT * FROM recipes WHERE id = :id');
    $query->execute(array(':id' => $editingId));
    $editingRecipe = $query->fetch();
    if (!$editingRecipe) {
        http_response_code(404);
        exit('Recipe not found.');
    }
    if (!canManageRecipe($currentUser, $editingRecipe)) {
        http_response_code(403);
        exit('You cannot edit this recipe.');
    }
}
$activePage = $editingRecipe ? '' : 'add';
$documentTitle = $editingRecipe ? 'Recept bewerken' : 'Recept toevoegen';
$errors = array();
$values = array(
    'title' => '',
    'category' => '',
    'description' => '',
    'prep_time' => '0',
    'cook_time' => '0',
    'difficulty' => 'Easy',
    'servings' => '2',
    'image' => '',
    'ingredients' => '',
    'instructions' => '',
);
if ($editingRecipe) {
    foreach ($values as $key => $default) {
        $values[$key] = (string)$editingRecipe[$key];
    }
}
$csrfToken = csrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $values[$key] = trim($_POST[$key]);
        }
    }

    if (!validCsrfToken()) {
        $errors[] = 'Je formulier is verlopen. Verstuur het formulier opnieuw.';
    }

    if ($values['title'] === '' || strlen($values['title']) > 120) {
        $errors[] = 'Vul een titel in van maximaal 120 tekens.';
    }
    if (!isset($categories[$values['category']]) || $values['category'] === 'all') {
        $errors[] = 'Kies een receptcategorie.';
    }
    if ($values['description'] === '' || strlen($values['description']) > 1000) {
        $errors[] = 'Vul een korte beschrijving in van maximaal 1.000 tekens.';
    }

    foreach (array('prep_time' => 'Voorbereidingstijd', 'cook_time' => 'Bereidingstijd') as $field => $label) {
        if ($values[$field] === '' || !ctype_digit($values[$field]) || (int)$values[$field] > 1440) {
            $errors[] = $label . ' moet een geheel getal tussen 0 en 1.440 minuten zijn.';
        }
    }
    if (ctype_digit($values['prep_time']) && ctype_digit($values['cook_time'])
        && (int)$values['prep_time'] + (int)$values['cook_time'] < 1) {
        $errors[] = 'De totale voorbereidingstijd en bereidingstijd moeten minstens één minuut zijn.';
    }
    if (!in_array($values['difficulty'], array('Easy', 'Medium', 'Hard'), true)) {
        $errors[] = 'Kies een geldige moeilijkheidsgraad.';
    }
    if ($values['servings'] === '' || !ctype_digit($values['servings'])
        || (int)$values['servings'] < 1 || (int)$values['servings'] > 100) {
        $errors[] = 'Het aantal porties moet een geheel getal tussen 1 en 100 zijn.';
    }
    if (strlen($values['ingredients']) > 20000 || !preg_match('/\S/', $values['ingredients'])) {
        $errors[] = 'Vul de ingrediënten in, één per regel (maximaal 20.000 tekens).';
    }
    if (strlen($values['instructions']) > 20000 || !preg_match('/\S/', $values['instructions'])) {
        $errors[] = 'Vul de bereidingswijze in, één stap per regel (maximaal 20.000 tekens).';
    }
    if ($values['image'] !== '') {
        $imageParts = parse_url($values['image']);
        if (!filter_var($values['image'], FILTER_VALIDATE_URL) || !is_array($imageParts)
            || !isset($imageParts['scheme']) || !in_array(strtolower($imageParts['scheme']), array('http', 'https'), true)) {
            $errors[] = 'De afbeelding moet een geldige HTTP- of HTTPS-link zijn, of laat dit veld leeg.';
        }
    }

    if (!$errors) {
        if ($editingRecipe) {
            $save = $db->prepare(
                'UPDATE recipes SET
                    title = :title, category = :category, description = :description,
                    prep_time = :prep_time, cook_time = :cook_time, difficulty = :difficulty,
                    servings = :servings, image = :image, ingredients = :ingredients,
                    instructions = :instructions
                 WHERE id = :id'
            );
            $save->execute(array(
                ':title' => $values['title'],
                ':category' => $values['category'],
                ':description' => $values['description'],
                ':prep_time' => (int)$values['prep_time'],
                ':cook_time' => (int)$values['cook_time'],
                ':difficulty' => $values['difficulty'],
                ':servings' => (int)$values['servings'],
                ':image' => $values['image'],
                ':ingredients' => $values['ingredients'],
                ':instructions' => $values['instructions'],
                ':id' => $editingId,
            ));
            $savedId = $editingId;
        } else {
            $save = $db->prepare(
                'INSERT INTO recipes
                (title, category, description, prep_time, cook_time, difficulty, servings,
                 image, ingredients, instructions, user_id)
                 VALUES
                    (:title, :category, :description, :prep_time, :cook_time, :difficulty,
                     :servings, :image, :ingredients, :instructions, :user_id)'
            );
            $save->execute(array(
                ':title' => $values['title'],
                ':category' => $values['category'],
                ':description' => $values['description'],
                ':prep_time' => (int)$values['prep_time'],
                ':cook_time' => (int)$values['cook_time'],
                ':difficulty' => $values['difficulty'],
                ':servings' => (int)$values['servings'],
                ':image' => $values['image'],
                ':ingredients' => $values['ingredients'],
                ':instructions' => $values['instructions'],
                ':user_id' => (int)$currentUser['id'],
            ));
            $savedId = (int)$db->lastInsertId();
        }

        header('Location: recipe.php?id=' . $savedId . ($editingRecipe ? '&updated=1' : '&created=1'), true, 303);
        exit;
    }
}

require __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'add_recipe_view.php';
