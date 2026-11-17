<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

$db = recipeDb();
$currentUser = currentUser($db);
requireLogin($db);

$categories = recipeCategories();
$query = $db->prepare(
    'SELECT title, category, created_at FROM recipes WHERE user_id = :user_id ORDER BY id DESC'
);
$query->execute(array(':user_id' => $currentUser['id']));
$myRecipes = $query->fetchAll();

require __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'notebook_view.php';
