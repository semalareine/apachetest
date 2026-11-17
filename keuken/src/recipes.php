<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

$bookTitle = 'Het Receptenboek';
$bookNote  = 'Bekijk alle recepten. Kies links een categorie en sla de bladzijden om.';
$categories = recipeCategories();
$db = recipeDb();
$currentUser = currentUser($db);
requireLogin($db);
$counts = recipeCounts($db);
$perPage = 3;

$cat = isset($_GET['category']) && is_string($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';

if (!isset($categories[$cat])) {
    $cat = 'all';
}

$conditions = array();
$parameters = array();
if ($cat !== 'all') {
    $conditions[] = 'category = :category';
    $parameters[':category'] = $cat;
}
if ($search !== '') {
    $conditions[] = "title LIKE :search ESCAPE '!'";
    $escapedSearch = strtr($search, array('!' => '!!', '%' => '!%', '_' => '!_'));
    $parameters[':search'] = '%' . $escapedSearch . '%';
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

$countQuery = $db->prepare('SELECT COUNT(*) FROM recipes' . $where);
$countQuery->execute($parameters);
$total = (int)$countQuery->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$pageInput = isset($_GET['page']) && is_scalar($_GET['page']) ? (int)$_GET['page'] : 1;
$page       = min($totalPages, max(1, $pageInput));
$offset = ($page - 1) * $perPage;

$query = $db->prepare(
    'SELECT recipes.*, users.username AS author
     FROM recipes LEFT JOIN users ON users.id = recipes.user_id'
     . $where . ' ORDER BY recipes.id DESC LIMIT :limit OFFSET :offset'
);
foreach ($parameters as $name => $value) {
    $query->bindValue($name, $value, PDO::PARAM_STR);
}
$query->bindValue(':limit', $perPage, PDO::PARAM_INT);
$query->bindValue(':offset', $offset, PDO::PARAM_INT);
$query->execute();
$visible = $query->fetchAll();
$activeCategory = $cat;
$documentTitle = $categories[$cat];

require __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'recipe_view.php';
