<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$user = currentUser(recipeDb());
echo json_encode(array('authenticated' => (bool)$user));
