<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'recipe_data.php';

$db = recipeDb();
$currentUser = currentUser($db);
requireLogin($db);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$categories = recipeCategories();
$errors = array();
$values = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipeId = isset($_POST['recipe_id']) && is_string($_POST['recipe_id'])
        ? filter_var($_POST['recipe_id'], FILTER_VALIDATE_INT)
        : false;

    if ($recipeId === false || $recipeId < 1) {
        $errors['form'] = 'Kies een geldig recept om te beoordelen.';
    } else {
        $recipeQuery = $db->prepare(
            'SELECT id FROM recipes
             WHERE id = :id AND (user_id IS NULL OR user_id != :user_id)'
        );
        $recipeQuery->execute(array(
            ':id' => $recipeId,
            ':user_id' => (int)$currentUser['id'],
        ));
        if (!$recipeQuery->fetch()) {
            http_response_code(404);
            exit('Dit recept kun je niet beoordelen.');
        }

        $rating = isset($_POST['rating']) && is_string($_POST['rating']) ? $_POST['rating'] : '';
        $comment = isset($_POST['comment']) && is_string($_POST['comment'])
            ? trim($_POST['comment'])
            : '';
        $values[$recipeId] = array('rating' => $rating, 'comment' => $comment);

        if (!validCsrfToken()) {
            $errors[$recipeId] = 'Je formulier is verlopen. Verstuur het opnieuw.';
        } elseif (!ctype_digit($rating) || (int)$rating < 1 || (int)$rating > 5) {
            $errors[$recipeId] = 'Kies een beoordeling van 1 tot en met 5 sterren.';
        } elseif ($comment === '' || preg_match_all('/./us', $comment, $commentCharacters) === false
            || count($commentCharacters[0]) > 1500) {
            $errors[$recipeId] = 'Schrijf een reactie van maximaal 1.500 tekens.';
        } else {
            $save = $db->prepare(
                'INSERT INTO recipe_reviews (recipe_id, user_id, rating, comment)
                 VALUES (:recipe_id, :user_id, :rating, :comment)
                 ON CONFLICT (recipe_id, user_id) DO UPDATE SET
                    rating = excluded.rating,
                    comment = excluded.comment,
                    updated_at = CURRENT_TIMESTAMP'
            );
            $save->execute(array(
                ':recipe_id' => $recipeId,
                ':user_id' => (int)$currentUser['id'],
                ':rating' => (int)$rating,
                ':comment' => $comment,
            ));

            header('Location: reviewboard.php?posted=1#recipe-' . $recipeId, true, 303);
            exit;
        }
    }
}

$recipes = $db->prepare(
    'SELECT recipes.id, recipes.title, recipes.category, recipes.description, recipes.user_id,
            users.username AS author,
            COUNT(recipe_reviews.id) AS review_count,
            AVG(recipe_reviews.rating) AS average_rating
     FROM recipes
     LEFT JOIN users ON users.id = recipes.user_id
     LEFT JOIN recipe_reviews ON recipe_reviews.recipe_id = recipes.id
     GROUP BY recipes.id
     ORDER BY recipes.id DESC'
);
$recipes->execute();
$availableRecipes = $recipes->fetchAll();

$myReviewsQuery = $db->prepare(
    'SELECT recipe_id, rating, comment
     FROM recipe_reviews
     WHERE user_id = :user_id'
);
$myReviewsQuery->execute(array(':user_id' => (int)$currentUser['id']));
$myReviews = array();
foreach ($myReviewsQuery->fetchAll() as $myReview) {
    $myReviews[(int)$myReview['recipe_id']] = $myReview;
}

$reviewsByRecipe = array();
$reviews = $db->prepare(
    'SELECT recipe_reviews.recipe_id, recipe_reviews.rating, recipe_reviews.comment,
            recipe_reviews.updated_at, users.username
     FROM recipe_reviews
     JOIN recipes ON recipes.id = recipe_reviews.recipe_id
     JOIN users ON users.id = recipe_reviews.user_id
     ORDER BY recipe_reviews.updated_at DESC, recipe_reviews.id DESC'
);
$reviews->execute();
foreach ($reviews->fetchAll() as $review) {
    $recipeId = (int)$review['recipe_id'];
    if (!isset($reviewsByRecipe[$recipeId])) {
        $reviewsByRecipe[$recipeId] = array();
    }
    $reviewsByRecipe[$recipeId][] = $review;
}

require __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'reviewboard_view.php';
