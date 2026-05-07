<?php

session_start();

$defaultTasks = [
    [
        'title' => 'Zadanie 1',
        'category' => 'Praca',
        'priority' => 'wysoki',
        'status' => 'do zrobienia',
        'estimated_minutes' => 60,
        'description' => 'Kontakt test@test.pl https://google.com #backend',
        'date' => '2026-05-10',
        'tags' => ['backend', 'pilne']
    ],
    [
        'title' => 'Zadanie 2',
        'category' => 'Dom',
        'priority' => 'średni',
        'status' => 'w trakcie',
        'estimated_minutes' => 30,
        'description' => '- kup mleko',
        'date' => '2026-05-11',
        'tags' => ['dom']
    ],
    [
        'title' => 'Zadanie 3',
        'category' => 'Nauka',
        'priority' => 'niski',
        'status' => 'zakończone',
        'estimated_minutes' => 120,
        'description' => 'Telefon 123-456-789 #frontend',
        'date' => '2026-05-12',
        'tags' => ['frontend']
    ],
    [
        'title' => 'Zadanie 4',
        'category' => 'Zdrowie',
        'priority' => 'średni',
        'status' => 'do zrobienia',
        'estimated_minutes' => 45,
        'description' => 'Wizyta 12:30 #pilne',
        'date' => '2026-05-15',
        'tags' => ['pilne']
    ]
];

if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = $defaultTasks;
}

$tasks = $_SESSION['tasks'];

$errors = [];

$title = '';
$category = '';
$priority = '';
$status = '';
$estimated = '';
$description = '';
$date = '';
$tags = '';

$search = $_GET['search'] ?? '';
$filterTag = $_GET['filter_tag'] ?? '';
$filterPriority = $_GET['filter_priority'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';

$allowedCategories = ['Praca','Dom','Nauka','Zdrowie','Inne'];
$allowedPriorities = ['niski','średni','wysoki'];
$allowedStatuses = ['do zrobienia','w trakcie','zakończone'];

function validateInput($input) {
    return htmlspecialchars(trim($input));
}

function extractTags($text) {

    // Wykrywanie tagów #tag
    preg_match_all('/\#([a-zA-Z0-9_]+)/', $text, $matches);

    return $matches[1];
}

function formatTaskDescription($description) {

    $description = htmlspecialchars($description);

    // Zamiana URL na linki HTML
    $description = preg_replace(
        '/\b(?:https?|ftp):\/\/[a-z0-9-+&@#\/%?=~_|!:,.;]*[a-z0-9-+&@#\/%=~_|]/i',
        '<a href="$0" target="_blank">$0</a>',
        $description
    );

    // Formatowanie tagów
    $description = preg_replace(
        '/#([a-zA-Z0-9_]+)/',
        '<span class="tag">#$1</span>',
        $description
    );

    // Formatowanie godzin
    $description = preg_replace(
        '/\b\d{2}:\d{2}\b/',
        '<span class="important">$0</span>',
        $description
    );

    // Formatowanie telefonów
    $description = preg_replace(
        '/\b\d{3}-\d{3}-\d{3}\b/',
        '<span class="important">$0</span>',
        $description
    );

    // Formatowanie dat
    $description = preg_replace(
        '/\b\d{4}-\d{2}-\d{2}\b/',
        '<span class="important">$0</span>',
        $description
    );

    // Wykrywanie list
    $description = preg_replace(
        '/^[\s]*[-*+][\s]+(.+)$/m',
        '<li>$1</li>',
        $description
    );

    if (strpos($description, '<li>') !== false) {
        $description = '<ul>' . $description . '</ul>';
        $description = str_replace('</ul><ul>', '', $description);
    }

    return nl2br($description);
}

function searchTasks($tasks, $pattern) {

    if ($pattern === '') return $tasks;

    $results = [];

    foreach ($tasks as $task) {

        $text = $task['title'] . ' ' . $task['description'];

        if (@preg_match($pattern, $text)) {
            $results[] = $task;
        }
    }

    return $results;
}

function filterTasksByTag($tasks, $tag) {

    if ($tag === '') return $tasks;

    return array_filter($tasks, function($task) use ($tag) {
        return in_array($tag, $task['tags']);
    });
}

function highlightText($text, $pattern) {

    if ($pattern === '') return $text;

    return @preg_replace($pattern, '<span class="highlight">$0</span>', $text);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = validateInput($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $status = $_POST['status'] ?? '';
    $estimated = trim($_POST['estimated_minutes'] ?? '');
    $description = validateInput($_POST['description'] ?? '');
    $date = validateInput($_POST['date'] ?? '');
    $tags = explode(' ', validateInput($_POST['tags'] ?? ''));

    if ($title === '') {
        $errors[] = 'Tytuł jest wymagany';
    }

    if (!is_numeric($estimated) || (int)$estimated <= 0) {
        $errors[] = 'Czas musi być dodatnią liczbą';
    }

    if (!in_array($category, $allowedCategories)) {
        $errors[] = 'Niepoprawna kategoria';
    }

    if (!in_array($priority, $allowedPriorities)) {
        $errors[] = 'Niepoprawny priorytet';
    }

    if (!in_array($status, $allowedStatuses)) {
        $errors[] = 'Niepoprawny status';
    }

    // Walidacja daty RRRR-MM-DD
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors[] = 'Niepoprawna data';
    }

    foreach ($tags as $tag) {

        if ($tag === '') continue;

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tag)) {
            $errors[] = 'Niepoprawny tag: ' . htmlspecialchars($tag);
        }
    }

    preg_match_all(
        '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
        $description,
        $emails
    );

    foreach ($emails[0] as $email) {

        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            $errors[] = 'Niepoprawny email';
        }
    }

    if (empty($errors)) {

        $tasks[] = [
            'title' => $title,
            'category' => $category,
            'priority' => $priority,
            'status' => $status,
            'estimated_minutes' => (int)$estimated,
            'description' => $description,
            'date' => $date,
            'tags' => array_filter($tags)
        ];

        $_SESSION['tasks'] = $tasks;

        $title = '';
        $category = '';
        $priority = '';
        $status = '';
        $estimated = '';
        $description = '';
        $date = '';
        $tags = '';
    }
}

$tasks = searchTasks($tasks, $search);
$tasks = filterTasksByTag($tasks, $filterTag);

if ($filterPriority !== '') {
    $tasks = array_filter($tasks, fn($t) => $t['priority'] === $filterPriority);
}

if ($filterStatus !== '') {
    $tasks = array_filter($tasks, fn($t) => $t['status'] === $filterStatus);
}

$total = count($tasks);

$todo = 0;
$done = 0;
$minutes = [];

foreach ($tasks as $t) {

    if ($t['status'] === 'do zrobienia') $todo++;
    if ($t['status'] === 'zakończone') $done++;

    $minutes[] = $t['estimated_minutes'];
}

$sumMinutes = array_sum($minutes);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Menedżer zadań</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <header>
        <h1>Menedżer Zadań</h1>
    </header>

    <aside>

        <?php if (!empty($errors)): ?>
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <form method="POST">

            <label>Tytuł</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>">

            <label>Kategoria</label>
            <select name="category">
                <?php foreach ($allowedCategories as $c): ?>
                <option value="<?php echo $c; ?>" <?php if ($c === $category) echo 'selected'; ?>>
                    <?php echo $c; ?>
                </option>
                <?php endforeach; ?>
            </select>

            <label>Priorytet</label>
            <select name="priority">
                <?php foreach ($allowedPriorities as $p): ?>
                <option value="<?php echo $p; ?>" <?php if ($p === $priority) echo 'selected'; ?>>
                    <?php echo $p; ?>
                </option>
                <?php endforeach; ?>
            </select>

            <label>Status</label>
            <select name="status">
                <?php foreach ($allowedStatuses as $s): ?>
                <option value="<?php echo $s; ?>" <?php if ($s === $status) echo 'selected'; ?>>
                    <?php echo $s; ?>
                </option>
                <?php endforeach; ?>
            </select>

            <label>Czas (minuty)</label>
            <input type="text" name="estimated_minutes" value="<?php echo htmlspecialchars($estimated); ?>">

            <label>Data</label>
            <input type="text" name="date" placeholder="2026-05-10">

            <label>Opis</label>
            <textarea name="description"></textarea>

            <label>Tagi</label>
            <input type="text" name="tags" placeholder="backend pilne">

            <button type="submit">Dodaj</button>

        </form>

        <hr>

        <form method="GET">

            <label>Regex wyszukiwania</label>
            <input type="text" name="search" placeholder="/^Z/i">

            <label>Filtr tagu</label>
            <input type="text" name="filter_tag">

            <label>Priorytet</label>
            <select name="filter_priority">
                <option value="">Wszystkie</option>
                <?php foreach ($allowedPriorities as $p): ?>
                    <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                <?php endforeach; ?>
            </select>

            <label>Status</label>
            <select name="filter_status">
                <option value="">Wszystkie</option>
                <?php foreach ($allowedStatuses as $s): ?>
                    <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Szukaj</button>

        </form>

    </aside>

    <main>

        <div class="stats">
            <div><?php echo $total; ?><br>Wszystkie</div>
            <div><?php echo $todo; ?><br>Do zrobienia</div>
            <div><?php echo $done; ?><br>Zakończone</div>
            <div><?php echo $sumMinutes; ?><br>Minut</div>
        </div>

        <div class="tasks">

        <?php foreach ($tasks as $task): ?>
            <div class="task">

                <h3>
                    <?php echo highlightText(htmlspecialchars($task['title']), $search); ?>
                </h3>

                <p>Kategoria: <?php echo htmlspecialchars($task['category']); ?></p>
                <p>Priorytet: <?php echo htmlspecialchars($task['priority']); ?></p>
                <p>Status: <?php echo htmlspecialchars($task['status']); ?></p>
                <p>Czas: <?php echo htmlspecialchars($task['estimated_minutes']); ?></p>
                <p>Data: <?php echo htmlspecialchars($task['date']); ?></p>

                <p>
                    Tagi:
                    <?php echo htmlspecialchars(implode(', ', $task['tags'])); ?>
                </p>

                <p>
                    <?php echo highlightText(formatTaskDescription($task['description']), $search); ?>
                </p>

                <p>
                    Tagi z opisu:
                    <?php echo htmlspecialchars(implode(', ', extractTags($task['description']))); ?>
                </p>

            </div>
        <?php endforeach; ?>

        </div>

    </main>

</div>

</body>
</html>