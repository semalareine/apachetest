<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

$bookTitle = 'Het Receptenboek';
$bookNote = 'Sla de bladzijden om en ontdek iets lekkers om te maken.';
$categories = recipeCategories();
$db = recipeDb();
$currentUser = currentUser($db);
requireLogin($db);
$counts = recipeCounts($db);
$id = isset($_GET['id']) && is_scalar($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;
$recipe = false;

if ($id !== false && $id > 0) {
    $query = $db->prepare(
        'SELECT recipes.*, users.username AS author
         FROM recipes LEFT JOIN users ON users.id = recipes.user_id
         WHERE recipes.id = :id'
    );
    $query->execute(array(':id' => $id));
    $recipe = $query->fetch();
}

if (!$recipe) {
    http_response_code(404);
    $documentTitle = 'Recept niet gevonden';
    $activeCategory = 'all';
} else {
    $documentTitle = $recipe['title'];
    $activeCategory = $recipe['category'];
}

require __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'recipe_detail_view.php';
