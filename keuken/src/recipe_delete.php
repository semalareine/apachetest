<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

$db = recipeDb();
$currentUser = currentUser($db);
requireLogin($db);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validCsrfToken()) {
    http_response_code(400);
    exit('Invalid delete request.');
}

$id = isset($_POST['id']) && is_string($_POST['id'])
    ? filter_var($_POST['id'], FILTER_VALIDATE_INT)
    : false;
if ($id === false || $id < 1) {
    http_response_code(400);
    exit('Invalid recipe ID.');
}

$query = $db->prepare('SELECT id, user_id FROM recipes WHERE id = :id');
$query->execute(array(':id' => $id));
$recipe = $query->fetch();
if (!$recipe) {
    http_response_code(404);
    exit('Recipe not found.');
}
if (!canManageRecipe($currentUser, $recipe)) {
    http_response_code(403);
    exit('You cannot delete this recipe.');
}

$delete = $db->prepare('DELETE FROM recipes WHERE id = :id');
$delete->execute(array(':id' => $id));
header('Location: recipes.php', true, 303);
