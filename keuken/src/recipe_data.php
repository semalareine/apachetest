<?php

function recipeCategories()
{
    return array(
        'all'       => 'Alles',
        'breakfast' => 'Ontbijt',
        'lunch'     => 'Lunch',
        'dinner'    => 'Diner',
        'dessert'   => 'Dessert',
        'drinks'    => 'Drankjes',
    );
}

function recipeDifficultyLabel($difficulty)
{
    $labels = array(
        'Easy' => 'Makkelijk',
        'Medium' => 'Gemiddeld',
        'Hard' => 'Moeilijk',
    );

    return isset($labels[$difficulty]) ? $labels[$difficulty] : $difficulty;
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function startAppSession()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(array(
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ));
    session_start();
}

function recipeDb()
{
    static $db = null;

    if ($db instanceof PDO) {
        return $db;
    }

    $dataDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0750, true) && !is_dir($dataDirectory)) {
        throw new RuntimeException('The recipe data directory could not be created.');
    }

    $db = new PDO('sqlite:' . $dataDirectory . DIRECTORY_SEPARATOR . 'recipes.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('PRAGMA busy_timeout = 5000');
    $db->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL COLLATE NOCASE UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT \'user\' CHECK (role IN (\'user\', \'admin\')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS recipes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            category TEXT NOT NULL,
            description TEXT NOT NULL,
            prep_time INTEGER NOT NULL DEFAULT 0,
            cook_time INTEGER NOT NULL DEFAULT 0,
            difficulty TEXT NOT NULL,
            servings INTEGER NOT NULL,
            image TEXT NOT NULL DEFAULT \'\',
            ingredients TEXT NOT NULL DEFAULT \'\',
            instructions TEXT NOT NULL DEFAULT \'\',
            user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $db->exec(
        'CREATE TABLE IF NOT EXISTS recipe_reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recipe_id INTEGER NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
            comment TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (recipe_id, user_id)
        )'
    );

    $columns = $db->query('PRAGMA table_info(recipes)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('user_id', $columns, true)) {
        $db->exec('ALTER TABLE recipes ADD COLUMN user_id INTEGER REFERENCES users(id) ON DELETE SET NULL');
    }

    $db->exec('CREATE INDEX IF NOT EXISTS recipes_category_idx ON recipes(category)');
    $db->exec('CREATE INDEX IF NOT EXISTS recipes_user_id_idx ON recipes(user_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS recipe_reviews_recipe_id_idx ON recipe_reviews(recipe_id)');

    translateSampleRecipes($db);
    seedRecipes($db);
    replaceFirstPlaceholderRecipe($db);
    removeRemainingSampleRecipes($db);
    seedAdeptusTemptation($db);
    seedHoneyfruitSalad($db);
    seedMonsterCake($db);
    seedSuperMushroomPizza($db);
    seedSlurpJuiceSmoothie($db);
    seedJillsSandwich($db);
    seedMoreStandardRecipes($db);
    provisionAdminAccount($db);

    return $db;
}

function provisionAdminAccount(PDO $db)
{
    $password = getenv('ADMIN_PASSWORD');
    if ($password === false || $password === '') {
        return;
    }
    if (strlen($password) < 12 || strlen($password) > 72) {
        throw new RuntimeException('ADMIN_PASSWORD must be between 12 and 72 bytes.');
    }

    $query = $db->prepare('SELECT id, role FROM users WHERE username = :username');
    $query->execute(array(':username' => 'alexcaspercrud'));
    $existingAdmin = $query->fetch();

    if (!$existingAdmin) {
        $insert = $db->prepare(
            'INSERT INTO users (username, password_hash, role)
             VALUES (:username, :password_hash, \'admin\')'
        );
        $insert->execute(array(
            ':username' => 'alexcaspercrud',
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ));
        $adminId = $db->lastInsertId();
    } elseif ($existingAdmin['role'] !== 'admin') {
        throw new RuntimeException('The reserved administrator username is already registered.');
    } else {
        $adminId = $existingAdmin['id'];
    }

    $assignRecipes = $db->prepare('UPDATE recipes SET user_id = :user_id WHERE user_id IS NULL');
    $assignRecipes->execute(array(':user_id' => $adminId));
}

function currentUser(PDO $db)
{
    startAppSession();
    if (!isset($_SESSION['user_id']) || !is_int($_SESSION['user_id'])) {
        return null;
    }

    $query = $db->prepare('SELECT id, username, role FROM users WHERE id = :id');
    $query->execute(array(':id' => $_SESSION['user_id']));
    $user = $query->fetch();
    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $user;
}

function requireLogin(PDO $db)
{
    if (!currentUser($db)) {
        header('Location: /src/login.php', true, 303);
        exit;
    }
}

function csrfToken()
{
    startAppSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validCsrfToken()
{
    startAppSession();
    $submitted = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $submitted);
}

function canManageRecipe($user, $recipe)
{
    return $user && ($user['role'] === 'admin'
        || (int)$recipe['user_id'] === (int)$user['id']);
}

function translateSampleRecipes(PDO $db)
{
    $titles = array(
        'Recipe title one' => 'Voorbeeldrecept 1',
        'Recipe title two' => 'Voorbeeldrecept 2',
        'Recipe title three' => 'Voorbeeldrecept 3',
        'Recipe title four' => 'Voorbeeldrecept 4',
        'Recipe title five' => 'Voorbeeldrecept 5',
        'Recipe title six' => 'Voorbeeldrecept 6',
        'Recipe title seven' => 'Voorbeeldrecept 7',
        'Recipe title eight' => 'Voorbeeldrecept 8',
        'Recipe title nine' => 'Voorbeeldrecept 9',
        'Recipe title ten' => 'Voorbeeldrecept 10',
        'Recipe title eleven' => 'Voorbeeldrecept 11',
    );
    $update = $db->prepare(
        'UPDATE recipes
         SET title = :new_title, description = :new_description
         WHERE title = :old_title
           AND description = :old_description
           AND ingredients = \'\'
           AND instructions = \'\''
    );

    foreach ($titles as $oldTitle => $newTitle) {
        $update->execute(array(
            ':new_title' => $newTitle,
            ':new_description' => 'Een korte beschrijving van dit recept.',
            ':old_title' => $oldTitle,
            ':old_description' => 'Short description of the recipe goes here.',
        ));
    }
}

function replaceFirstPlaceholderRecipe(PDO $db)
{
    $update = $db->prepare(
        'UPDATE recipes
         SET title = :title, category = :category, description = :description,
             prep_time = :prep_time, cook_time = :cook_time, difficulty = :difficulty,
             servings = :servings, image = :image, ingredients = :ingredients,
             instructions = :instructions
         WHERE title = :placeholder_title
           AND description = :placeholder_description
           AND ingredients = \'\'
           AND instructions = \'\''
    );
    $update->execute(array(
        ':title' => 'Nestle Koffie',
        ':category' => 'drinks',
        ':description' => 'Een verse kop slootwater van een mega slop bedrijf die niet geeft om de planeet,',
        ':prep_time' => 0,
        ':cook_time' => 2,
        ':difficulty' => 'Easy',
        ':servings' => 1,
        ':image' => 'https://dehraflicks.com/wp-content/uploads/2025/09/coffee-scaled.png',
        ':ingredients' => "melk\nkoffiebonen\nwater\nje ziel",
        ':instructions' => 'koffieapparaat',
        ':placeholder_title' => 'Voorbeeldrecept 1',
        ':placeholder_description' => 'Een korte beschrijving van dit recept.',
    ));
}

function removeRemainingSampleRecipes(PDO $db)
{
    $titles = array(
        'Voorbeeldrecept 2',
        'Voorbeeldrecept 3',
        'Voorbeeldrecept 4',
        'Voorbeeldrecept 5',
        'Voorbeeldrecept 6',
        'Voorbeeldrecept 7',
        'Voorbeeldrecept 8',
        'Voorbeeldrecept 9',
        'Voorbeeldrecept 10',
        'Voorbeeldrecept 11',
        'Recipe title two',
        'Recipe title three',
        'Recipe title four',
        'Recipe title five',
        'Recipe title six',
        'Recipe title seven',
        'Recipe title eight',
        'Recipe title nine',
        'Recipe title ten',
        'Recipe title eleven',
    );
    $placeholders = implode(', ', array_fill(0, count($titles), '?'));
    $delete = $db->prepare('DELETE FROM recipes WHERE title IN (' . $placeholders . ')');
    $delete->execute($titles);
}

function seedAdeptusTemptation(PDO $db)
{
    $title = "Adeptus' Temptation (Genshin Impact)";
    $exists = $db->prepare('SELECT 1 FROM recipes WHERE title = :title LIMIT 1');
    $exists->execute(array(':title' => $title));
    if ($exists->fetchColumn()) {
        return;
    }

    $insert = $db->prepare(
        'INSERT INTO recipes
            (title, category, description, prep_time, cook_time, difficulty, servings,
             image, ingredients, instructions)
         VALUES
            (:title, :category, :description, :prep_time, :cook_time, :difficulty, :servings,
             :image, :ingredients, :instructions)'
    );
    $insert->execute(array(
        ':title' => $title,
        ':category' => 'dinner',
        ':description' => 'Een luxe gerecht uit Liyue met verschillende soorten vlees, zeevruchten en groenten. Dit beroemde gerecht geeft je een rijke, hartige smaakervaring.',
        ':prep_time' => 25,
        ':cook_time' => 35,
        ':difficulty' => 'Hard',
        ':servings' => 4,
        ':image' => 'https://static.wikia.nocookie.net/gensin-impact/images/5/5e/Item_Adeptus%27_Temptation.png/revision/latest?cb=20210415133724',
        ':ingredients' => "400 g kipfilet\n200 g garnalen\n200 g varkensvlees\n150 g champignons\n1 rode paprika\n2 lente-uitjes\n3 eetlepels sojasaus\n2 eetlepels oestersaus\n2 teentjes knoflook\n1 eetlepel honing\n2 eetlepels plantaardige olie",
        ':instructions' => "Snijd de kip, het varkensvlees en de groenten in kleine stukken.\nMarineer het vlees met sojasaus, honing en knoflook.\nVerhit olie in een grote wok.\nBak het varkensvlees en de kip gaar.\nVoeg de garnalen, champignons en paprika toe.\nRoerbak alles tot de garnalen roze zijn.\nVoeg de oestersaus toe en meng goed.\nGarneer met lente-ui en serveer warm.",
    ));
}

function seedHoneyfruitSalad(PDO $db)
{
    $title = 'Honeyfruit Salad (League of Legends)';
    $exists = $db->prepare('SELECT 1 FROM recipes WHERE title = :title LIMIT 1');
    $exists->execute(array(':title' => $title));
    if ($exists->fetchColumn()) {
        return;
    }

    $insert = $db->prepare(
        'INSERT INTO recipes
            (title, category, description, prep_time, cook_time, difficulty, servings,
             image, ingredients, instructions)
         VALUES
            (:title, :category, :description, :prep_time, :cook_time, :difficulty, :servings,
             :image, :ingredients, :instructions)'
    );
    $insert->execute(array(
        ':title' => $title,
        ':category' => 'dessert',
        ':description' => "Een verfrissende fruitsalade geïnspireerd op de Honeyfruit uit Summoner's Rift. Een combinatie van zoet fruit, honing en frisse yoghurt, perfect als lichte snack na een lange wedstrijd.",
        ':prep_time' => 10,
        ':cook_time' => 0,
        ':difficulty' => 'Easy',
        ':servings' => 2,
        ':image' => 'https://wiki.leagueoflegends.com/en-us/images/thumb/HoneyfruitSquare.png/64px-HoneyfruitSquare.png?37221',
        ':ingredients' => "1 groene appel\n1 peer\n1 kiwi\n100 g blauwe bessen\n150 g Griekse yoghurt\n2 eetlepels honing\n1 eetlepel citroensap\n20 g walnoten",
        ':instructions' => "Was al het fruit grondig.\nSnijd de appel, peer en kiwi in kleine stukjes.\nDoe het fruit in een grote kom.\nVoeg de blauwe bessen toe.\nMeng de yoghurt met honing en citroensap.\nVerdeel de yoghurtsaus over het fruit.\nHak de walnoten fijn en strooi ze eroverheen.\nServeer direct en geniet van je Honeyfruit Salad.",
    ));
}

function seedMonsterCake(PDO $db)
{
    $title = 'Monster Cake (The Legend of Zelda: Breath of the Wild)';
    $exists = $db->prepare('SELECT 1 FROM recipes WHERE title = :title LIMIT 1');
    $exists->execute(array(':title' => $title));
    if ($exists->fetchColumn()) {
        return;
    }

    $insert = $db->prepare(
        'INSERT INTO recipes
            (title, category, description, prep_time, cook_time, difficulty, servings,
             image, ingredients, instructions)
         VALUES
            (:title, :category, :description, :prep_time, :cook_time, :difficulty, :servings,
             :image, :ingredients, :instructions)'
    );
    $insert->execute(array(
        ':title' => $title,
        ':category' => 'dessert',
        ':description' => 'Een mysterieuze paarse cake geïnspireerd op de Monster Cake uit Hyrule. Deze bijzondere taart combineert chocolade, kruiden en een kleurrijke glazuurlaag voor een magisch dessert.',
        ':prep_time' => 20,
        ':cook_time' => 40,
        ':difficulty' => 'Medium',
        ':servings' => 6,
        ':image' => 'https://static.wikia.nocookie.net/zelda_gamepedia_en/images/f/fa/BotW_Monster_Cake_Icon.png/revision/latest?cb=20171227181642&format=original',
        ':ingredients' => "200 g bloem\n150 g suiker\n100 g boter\n2 eieren\n100 ml melk\n2 eetlepels cacaopoeder\n1 theelepel bakpoeder\n1 theelepel vanille-extract\nPaarse voedingskleurstof\n100 g poedersuiker\n2 eetlepels water\n1 snufje zout",
        ':instructions' => "Verwarm de oven voor op 180°C.\nMeng de boter en suiker tot een luchtig mengsel.\nVoeg de eieren één voor één toe.\nMeng de bloem, cacao, bakpoeder en zout in een aparte kom.\nVoeg de droge ingrediënten afwisselend met de melk toe.\nRoer het vanille-extract door het beslag.\nGiet het beslag in een ingevette cakevorm.\nBak de cake ongeveer 35 tot 40 minuten.\nLaat de cake volledig afkoelen.\nMeng de poedersuiker met water en paarse voedingskleurstof.\nVerdeel het paarse glazuur over de cake.\nLaat het glazuur opstijven en serveer.",
    ));
}

function seedSuperMushroomPizza(PDO $db)
{
    $title = 'Super Mushroom Pizza (Super Mario)';
    $exists = $db->prepare('SELECT 1 FROM recipes WHERE title = :title LIMIT 1');
    $exists->execute(array(':title' => $title));
    if ($exists->fetchColumn()) {
        return;
    }

    $insert = $db->prepare(
        'INSERT INTO recipes
            (title, category, description, prep_time, cook_time, difficulty, servings,
             image, ingredients, instructions)
         VALUES
            (:title, :category, :description, :prep_time, :cook_time, :difficulty, :servings,
             :image, :ingredients, :instructions)'
    );
    $insert->execute(array(
        ':title' => $title,
        ':category' => 'dinner',
        ':description' => 'Een kleurrijke pizza geïnspireerd op de iconische Super Mushrooms uit het Mario-universum. Met champignons, tomatensaus en mozzarella is dit een heerlijke maaltijd voor iedere Mario-fan.',
        ':prep_time' => 20,
        ':cook_time' => 15,
        ':difficulty' => 'Easy',
        ':servings' => 2,
        ':image' => 'https://mario.wiki.gallery/images/thumb/4/48/SMP_Dash_Mushroom.png/200px-SMP_Dash_Mushroom.png',
        ':ingredients' => "1 kant-en-klare pizzabodem\n150 g tomatensaus\n150 g mozzarella\n100 g champignons\n1 rode paprika\n50 g cherrytomaten\n1 theelepel oregano\n1 eetlepel olijfolie\nSnufje zout\nSnufje peper",
        ':instructions' => "Verwarm de oven voor op 220°C.\nLeg de pizzabodem op een bakplaat.\nVerdeel de tomatensaus gelijkmatig over de bodem.\nSnijd de mozzarella, champignons en paprika in stukjes.\nVerdeel de mozzarella en champignons over de pizza.\nGebruik de rode paprika en cherrytomaten om kleine Super Mushroom-patronen te maken.\nStrooi oregano, zout en peper over de pizza.\nBesprenkel met olijfolie.\nBak de pizza 12 tot 15 minuten tot de korst goudbruin is.\nLaat kort afkoelen en serveer.",
    ));
}

function seedSlurpJuiceSmoothie(PDO $db)
{
    $title = 'Slurp Juice Smoothie (Fortnite)';
    $exists = $db->prepare('SELECT 1 FROM recipes WHERE title = :title LIMIT 1');
    $exists->execute(array(':title' => $title));
    if ($exists->fetchColumn()) {
        return;
    }

    $insert = $db->prepare(
        'INSERT INTO recipes
            (title, category, description, prep_time, cook_time, difficulty, servings,
             image, ingredients, instructions)
         VALUES
            (:title, :category, :description, :prep_time, :cook_time, :difficulty, :servings,
             :image, :ingredients, :instructions)'
    );
    $insert->execute(array(
        ':title' => $title,
        ':category' => 'drinks',
        ':description' => 'Een felblauwe smoothie geïnspireerd op de iconische Slurp Juice uit Fortnite. Deze verfrissende drank combineert blauwe bessen, banaan en yoghurt tot een zoete, romige smoothie die eruitziet alsof hij rechtstreeks uit een Supply Drop komt.',
        ':prep_time' => 5,
        ':cook_time' => 0,
        ':difficulty' => 'Easy',
        ':servings' => 2,
        ':image' => 'https://static.wikia.nocookie.net/fortnite/images/7/7a/Slurp_Juice_-_Item_-_Fortnite.png/revision/latest?cb=20180917024708',
        ':ingredients' => "150 g blauwe bessen\n1 banaan\n200 ml melk\n100 g Griekse yoghurt\n1 eetlepel honing\n4 ijsblokjes\nBlauwe voedingskleurstof (optioneel)\nSlagroom (optioneel)",
        ':instructions' => "Pel de banaan en snijd deze in stukjes.\nDoe de blauwe bessen en banaan in een blender.\nVoeg de melk, Griekse yoghurt en honing toe.\nVoeg de ijsblokjes toe.\nMix alles tot een gladde smoothie.\nVoeg eventueel een klein beetje blauwe voedingskleurstof toe.\nSchenk de smoothie in twee glazen.\nGarneer eventueel met slagroom.\nServeer direct als een echte Slurp Juice.",
    ));
}

function seedJillsSandwich(PDO $db)
{
    $title = "Jill's Sandwich (Resident Evil)";
    $exists = $db->prepare('SELECT 1 FROM recipes WHERE title = :title LIMIT 1');
    $exists->execute(array(':title' => $title));
    if ($exists->fetchColumn()) {
        return;
    }

    $insert = $db->prepare(
        'INSERT INTO recipes
            (title, category, description, prep_time, cook_time, difficulty, servings,
             image, ingredients, instructions)
         VALUES
            (:title, :category, :description, :prep_time, :cook_time, :difficulty, :servings,
             :image, :ingredients, :instructions)'
    );
    $insert->execute(array(
        ':title' => $title,
        ':category' => 'lunch',
        ':description' => 'Het legendarische broodje uit Resident Evil, bekend van de beroemde uitspraak over Jill Valentine. Een stevig, klassiek broodje met kip, kaas, sla en tomaat. Perfect voor een overlevende in een zombie-apocalyps.',
        ':prep_time' => 10,
        ':cook_time' => 10,
        ':difficulty' => 'Easy',
        ':servings' => 2,
        ':image' => 'https://i.redd.it/3pyx86n5z1o41.png',
        ':ingredients' => "4 sneetjes wit brood\n200 g kipfilet\n2 plakjes cheddar\n2 blaadjes sla\n1 tomaat\n2 eetlepels mayonaise\n1 theelepel mosterd\n1 eetlepel boter\nSnufje zout\nSnufje peper",
        ':instructions' => "Kruid de kipfilet met zout en peper.\nVerhit boter in een koekenpan.\nBak de kipfilet aan beide kanten goudbruin en gaar.\nSnijd de tomaat in dunne plakjes.\nMeng de mayonaise met de mosterd.\nBesmeer de sneetjes brood met het sausmengsel.\nLeg de sla, tomaat, kip en cheddar op twee sneetjes brood.\nPlaats de overige sneetjes brood erbovenop.\nSnijd de sandwiches diagonaal doormidden.\nServeer direct en geniet van Jill's legendarische sandwich!",
    ));
}

function seedMoreStandardRecipes(PDO $db)
{
    $recipes = array(
        array(
            'title' => "Sonic's Famous Chili Dog",
            'category' => 'lunch',
            'description' => 'Het favoriete gerecht van Sonic the Hedgehog! Een sappige hotdog met huisgemaakte pittige gehaktsaus, kidneybonen, tomaten en gesmolten kaas. Een echte klassieker uit het Sonic-universum.',
            'prep_time' => 15,
            'cook_time' => 45,
            'difficulty' => 'Medium',
            'servings' => 4,
            'image' => 'https://media.sonic-city.net/2025/12/5-Sonic-the-Hedgehog-Speed-Cafe-Classic-Chili-Dog-credit-Eugene-Han-2048x1639.jpg',
            'ingredients' => "4 hotdogbroodjes\n4 hotdogs\n300 g rundergehakt\n1 ui\n1 groene paprika\n200 g tomatenblokjes\n100 g kidneybonen\n2 eetlepels barbecuesaus\n1 theelepel chilipoeder\n1 theelepel komijn\n100 g geraspte cheddar\n1 eetlepel olijfolie\nZout en peper",
            'instructions' => "Snipper de ui en snijd de paprika in kleine blokjes.\nVerhit olijfolie in een pan.\nBak het gehakt rul.\nVoeg de ui en paprika toe en bak enkele minuten.\nVoeg de tomatenblokjes, kidneybonen en barbecuesaus toe.\nBreng op smaak met chilipoeder, komijn, zout en peper.\nLaat de saus ongeveer 25 minuten zachtjes koken.\nBereid de hotdogs volgens de verpakking.\nLeg de hotdogs in de broodjes.\nSchep de gehaktsaus over de hotdogs.\nBestrooi met geraspte cheddar en serveer warm.",
        ),
        array(
            'title' => 'Casteliacone Ice Cream',
            'category' => 'dessert',
            'description' => 'Een heerlijke zachte vanille-ijscreatie geïnspireerd op de beroemde Casteliacone uit Pokémon Black and White. Dit iconische dessert uit Castelia City combineert romig softijs met een knapperig hoorntje en zoete toppings.',
            'prep_time' => 15,
            'cook_time' => 0,
            'difficulty' => 'Easy',
            'servings' => 4,
            'image' => 'https://static.wixstatic.com/media/2bedeb_4f59e7709c474931a4690c22fb83958a~mv2.jpg/v1/fill/w_980%2Ch_551%2Cal_c%2Cq_85%2Cusm_0.66_1.00_0.01%2Cenc_avif%2Cquality_auto/2bedeb_4f59e7709c474931a4690c22fb83958a~mv2.jpg',
            'ingredients' => "500 ml vanille-ijs\n4 ijshoorntjes\n100 ml slagroom\n2 eetlepels poedersuiker\n50 g witte chocolade\nBlauwe voedingskleurstof\n1 theelepel vanille-extract\nBlauwe suikerparels (optioneel)",
            'instructions' => "Laat het vanille-ijs enkele minuten zacht worden.\nMeng eventueel een klein beetje blauwe voedingskleurstof door een deel van het ijs.\nSmelt de witte chocolade au bain-marie.\nDoop de randen van de ijshoorntjes in de gesmolten chocolade.\nStrooi blauwe suikerparels over de chocoladerand.\nKlop de slagroom met poedersuiker en vanille-extract stijf.\nSchep het ijs in de hoorntjes.\nGarneer met een toef slagroom.\nServeer direct.",
        ),
        array(
            'title' => "Toriel's Butterscotch-Cinnamon Pie",
            'category' => 'dessert',
            'description' => "De beroemde taart van Toriel uit Undertale! Een warme, zoete taart met butterscotch en kaneel, geïnspireerd op het gerecht dat de speler krijgt in Toriel's huis. Een echte comfortfood-klassieker.",
            'prep_time' => 25,
            'cook_time' => 45,
            'difficulty' => 'Medium',
            'servings' => 8,
            'image' => 'https://stat.ameba.jp/user_images/20220525/14/monravi/ca/6f/j/o1080108015123096155.jpg',
            'ingredients' => "250 g bloem\n125 g koude boter\n75 g suiker\n1 ei\n150 g bruine basterdsuiker\n100 g boter\n200 ml slagroom\n2 eieren\n1 theelepel kaneel\n1 theelepel vanille-extract\n1 eetlepel maïzena\nSnufje zout",
            'instructions' => "Verwarm de oven voor op 180°C.\nMeng de bloem, suiker en koude boter tot een kruimelig deeg.\nVoeg het ei toe en kneed tot een samenhangend deeg.\nBekleed een taartvorm met het deeg.\nBak de taartbodem ongeveer 10 minuten voor.\nSmelt de boter in een pannetje.\nVoeg de basterdsuiker en slagroom toe.\nRoer tot de suiker volledig is opgelost.\nKlop de eieren met kaneel, vanille en maïzena.\nMeng het eimengsel voorzichtig door de butterscotchsaus.\nGiet de vulling in de taartbodem.\nBak de taart ongeveer 30 tot 35 minuten.\nLaat volledig afkoelen voordat je hem aansnijdt.",
        ),
        array(
            'title' => 'Pac-Man Power Pellet Cookies',
            'category' => 'dessert',
            'description' => 'Vrolijke koekjes geïnspireerd op de iconische Power Pellets en Pac-Man uit de klassieke arcadeklassieker. Knapperige suikerkoekjes met gele glazuur en kleurrijke details. Perfect voor een nostalgische gamingsnack.',
            'prep_time' => 20,
            'cook_time' => 12,
            'difficulty' => 'Easy',
            'servings' => 12,
            'image' => 'https://thedecoratedcookie.com/wp-content/uploads/2011/02/arcade-pac-man-cookies-image-4.jpg',
            'ingredients' => "200 g bloem\n100 g boter\n100 g suiker\n1 ei\n1 theelepel vanille-extract\n1 snufje zout\n100 g poedersuiker\nGele voedingskleurstof\n1 eetlepel water\nWitte chocoladedruppels",
            'instructions' => "Verwarm de oven voor op 180°C.\nMeng de boter en suiker tot een romig mengsel.\nVoeg het ei en vanille-extract toe.\nMeng de bloem en het zout erdoor.\nKneed tot een soepel koekjesdeeg.\nRol het deeg uit en steek ronde koekjes uit.\nLeg de koekjes op een bakplaat met bakpapier.\nBak ze ongeveer 10 tot 12 minuten.\nLaat de koekjes volledig afkoelen.\nMeng de poedersuiker met water en gele voedingskleurstof.\nVersier de koekjes met het gele glazuur.\nGebruik witte chocoladedruppels als Power Pellet-decoratie.\nLaat het glazuur uitharden en serveer.",
        ),
        array(
            'title' => "Kirby's Maxim Tomato Soup",
            'category' => 'dinner',
            'description' => 'Een kleurrijke tomatensoep geïnspireerd op de beroemde Maxim Tomato uit de Kirby-games. De soep heeft een rijke tomatensmaak en een romige textuur. Een heerlijke maaltijd die je energie geeft alsof je een Maxim Tomato hebt opgegeten!',
            'prep_time' => 10,
            'cook_time' => 25,
            'difficulty' => 'Easy',
            'servings' => 4,
            'image' => 'https://www.4gamer.net/games/448/G044868/20230522066/SS/008.jpg',
            'ingredients' => "800 g rijpe tomaten\n1 ui\n2 teentjes knoflook\n500 ml groentebouillon\n100 ml kookroom\n1 eetlepel olijfolie\n1 theelepel suiker\n1 theelepel basilicum\nZout en peper\nVerse basilicumblaadjes\n4 kleine tomaten voor garnering",
            'instructions' => "Snipper de ui en hak de knoflook fijn.\nSnijd de tomaten in kleine stukken.\nVerhit olijfolie in een soeppan.\nFruit de ui en knoflook enkele minuten.\nVoeg de tomaten toe en bak kort mee.\nSchenk de groentebouillon erbij.\nVoeg suiker, basilicum, zout en peper toe.\nLaat de soep ongeveer 20 minuten zachtjes koken.\nPureer de soep met een staafmixer.\nRoer de kookroom door de soep.\nVerwarm nog enkele minuten op laag vuur.\nServeer in kommen en garneer met basilicumblaadjes.",
        ),
        array(
            'title' => "Moogle's Kupopo Pancakes",
            'category' => 'breakfast',
            'description' => 'Luchtige, zachte pancakes geïnspireerd op de schattige Moogles uit Final Fantasy. Geserveerd met aardbeien, slagroom en een bolletje ijs dat eruitziet als een Moogle. Een magisch ontbijt waar zelfs een Kupo van zou genieten!',
            'prep_time' => 15,
            'cook_time' => 15,
            'difficulty' => 'Medium',
            'servings' => 2,
            'image' => 'https://www.directoajapon.com/cmsAdmin/uploads/o_1fg6hgfsama518tl1no51um612dlg.jpg',
            'ingredients' => "150 g bloem\n1 ei\n200 ml melk\n2 eetlepels suiker\n1 theelepel bakpoeder\n1 theelepel vanille-extract\n1 eetlepel boter\n100 g aardbeien\n100 ml slagroom\n2 bollen vanille-ijs\n2 rode snoepjes voor de Moogle-pompons\nChocoladesaus\nPoedersuiker",
            'instructions' => "Meng de bloem, suiker en bakpoeder in een kom.\nVoeg het ei, de melk en het vanille-extract toe.\nKlop tot een glad beslag.\nVerhit boter in een koekenpan.\nBak kleine, dikke pancakes aan beide kanten goudbruin.\nSnijd de aardbeien in kleine stukjes.\nKlop de slagroom stijf.\nStapel de pancakes op twee borden.\nLeg een bolletje vanille-ijs boven op iedere stapel.\nGebruik chocoladesaus om ogen en snorharen op het ijs te maken.\nPlaats een rood snoepje bovenop als Moogle-pompon.\nGarneer met aardbeien, slagroom en poedersuiker.\nServeer direct.",
        ),
    );
    $exists = $db->prepare('SELECT 1 FROM recipes WHERE title = :title LIMIT 1');
    $insert = $db->prepare(
        'INSERT INTO recipes
            (title, category, description, prep_time, cook_time, difficulty, servings,
             image, ingredients, instructions)
         VALUES
            (:title, :category, :description, :prep_time, :cook_time, :difficulty, :servings,
             :image, :ingredients, :instructions)'
    );

    foreach ($recipes as $recipe) {
        $exists->execute(array(':title' => $recipe['title']));
        if ($exists->fetchColumn()) {
            continue;
        }
        $insert->execute($recipe);
    }
}

function seedRecipes(PDO $db)
{
    if ((int)$db->query('SELECT COUNT(*) FROM recipes')->fetchColumn() > 0) {
        return;
    }

    $samples = array(
        array('Voorbeeldrecept 1', 'breakfast', '20 min', 'Easy', 2),
        array('Voorbeeldrecept 2', 'breakfast', '15 min', 'Easy', 1),
        array('Voorbeeldrecept 3', 'lunch', '30 min', 'Medium', 2),
        array('Voorbeeldrecept 4', 'lunch', '25 min', 'Easy', 3),
        array('Voorbeeldrecept 5', 'dinner', '60 min', 'Hard', 4),
        array('Voorbeeldrecept 6', 'dinner', '45 min', 'Medium', 4),
        array('Voorbeeldrecept 7', 'dessert', '50 min', 'Medium', 6),
        array('Voorbeeldrecept 8', 'dessert', '35 min', 'Easy', 8),
        array('Voorbeeldrecept 9', 'drinks', '5 min', 'Easy', 1),
        array('Voorbeeldrecept 10', 'dinner', '40 min', 'Medium', 2),
        array('Voorbeeldrecept 11', 'lunch', '20 min', 'Easy', 2),
    );

    $db->beginTransaction();
    try {
        $insert = $db->prepare(
            'INSERT INTO recipes (title, category, description, cook_time, difficulty, servings)
             VALUES (:title, :category, :description, :cook_time, :difficulty, :servings)'
        );

        foreach ($samples as $sample) {
            preg_match('/\d+/', $sample[2], $minutes);
            $insert->execute(array(
                ':title' => $sample[0],
                ':category' => $sample[1],
                ':description' => 'Een korte beschrijving van dit recept.',
                ':cook_time' => isset($minutes[0]) ? (int)$minutes[0] : 0,
                ':difficulty' => $sample[3],
                ':servings' => $sample[4],
            ));
        }

        $db->commit();
    } catch (Exception $exception) {
        $db->rollBack();
        throw $exception;
    }
}

function recipeCounts(PDO $db)
{
    $counts = array_fill_keys(array_keys(recipeCategories()), 0);
    $counts['all'] = (int)$db->query('SELECT COUNT(*) FROM recipes')->fetchColumn();

    foreach ($db->query('SELECT category, COUNT(*) AS amount FROM recipes GROUP BY category') as $row) {
        if (isset($counts[$row['category']])) {
            $counts[$row['category']] = (int)$row['amount'];
        }
    }

    return $counts;
}

function pageUrl($category, $page, $search = '')
{
    $parameters = array('category' => $category, 'page' => $page);
    if ($search !== '') {
        $parameters['search'] = $search;
    }

    return 'recipes.php?' . http_build_query($parameters);
}
