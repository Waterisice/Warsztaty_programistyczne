<?php

$tasks = [
    [
        'title' => 'Zadanie 1',
        'category' => 'Praca',
        'priority' => 'wysoki',
        'status' => 'do zrobienia',
        'estimated_minutes' => 60,
        'tags' => ['backend', 'pilne']
    ],
    [
        'title' => 'Zadanie 2',
        'category' => 'Dom',
        'priority' => 'średni',
        'status' => 'w trakcie',
        'estimated_minutes' => 30,
        'tags' => ['dom']
    ],
    [
        'title' => 'Zadanie 3',
        'category' => 'Nauka',
        'priority' => 'niski',
        'status' => 'zakończone',
        'estimated_minutes' => 120,
        'tags' => ['frontend']
    ],
    [
        'title' => 'Zadanie 4',
        'category' => 'Zdrowie',
        'priority' => 'średni',
        'status' => 'do zrobienia',
        'estimated_minutes' => 45,
        'tags' => ['pilne']
    ]
];

$errors = [];

$title = '';
$category = '';
$priority = '';
$status = '';
$estimated = '';
$tags = [];

$allowedCategories = ['Praca','Dom','Nauka','Zdrowie','Inne'];
$allowedPriorities = ['niski','średni','wysoki'];
$allowedStatuses = ['do zrobienia','w trakcie','zakończone'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $status = $_POST['status'] ?? '';
    $estimated = trim($_POST['estimated_minutes'] ?? '');
    $tags = $_POST['tags'] ?? [];

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

    if (empty($tags)) {
        $errors[] = 'Wybierz przynajmniej jeden tag';
    }

    if (empty($errors)) {

        $tags = array_filter($tags);
        sort($tags);

        $tasks[] = [
            'title' => $title,
            'category' => $category,
            'priority' => $priority,
            'status' => $status,
            'estimated_minutes' => (int)$estimated,
            'tags' => $tags
        ];

        $title = '';
        $category = '';
        $priority = '';
        $status = '';
        $estimated = '';
        $tags = [];
    }
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
                <option <?php if ($c === $category) echo 'selected'; ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
                </select>

                <label>Priorytet</label>
                <select name="priority">
                <?php foreach ($allowedPriorities as $p): ?>
                <option <?php if ($p === $priority) echo 'selected'; ?>><?php echo $p; ?></option>
                <?php endforeach; ?>
                </select>

                <label>Status</label>
                <select name="status">
                <?php foreach ($allowedStatuses as $s): ?>
                <option <?php if ($s === $status) echo 'selected'; ?>><?php echo $s; ?></option>
                <?php endforeach; ?>
                </select>

                <label>Czas (minuty)</label>
                <input type="text" name="estimated_minutes" value="<?php echo htmlspecialchars($estimated); ?>">

                <label>Tagi</label>

                <?php
                $allTags = ['pilne','zespół','backend','frontend','dom','zakupy'];
                foreach ($allTags as $t):
                ?>
                <label>
                <input type="checkbox" name="tags[]" value="<?php echo $t; ?>"
                <?php if (in_array($t, $tags)) echo 'checked'; ?>>
                <?php echo $t; ?>
                </label>
                <?php endforeach; ?>

                <button type="submit">Dodaj</button>

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
                    <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                    <p>Kategoria: <?php echo htmlspecialchars($task['category']); ?></p>
                    <p>Priorytet: <?php echo htmlspecialchars($task['priority']); ?></p>
                    <p>Status: <?php echo htmlspecialchars($task['status']); ?></p>
                    <p>Czas: <?php echo htmlspecialchars($task['estimated_minutes']); ?></p>
                    <p>Tagi: <?php echo htmlspecialchars(implode(', ', $task['tags'])); ?></p>
                </div>

                <?php endforeach; ?>
                </div>
            </main>
        </div>
    </body>
</html>