<?php

$tasks = [
    [
        'title'             => 'Przygotuj prezentację dla klienta',
        'category'          => 'Praca',
        'priority'          => 'wysoki',
        'status'            => 'w trakcie',
        'estimated_minutes' => 90,
        'tags'              => ['backend', 'pilne'],
    ],
    [
        'title'             => 'Zakupy spożywcze',
        'category'          => 'Dom',
        'priority'          => 'niski',
        'status'            => 'do zrobienia',
        'estimated_minutes' => 30,
        'tags'              => ['dom', 'zakupy'],
    ],
    [
        'title'             => 'Nauka CSS – model pudełkowy',
        'category'          => 'Nauka',
        'priority'          => 'średni',
        'status'            => 'do zrobienia',
        'estimated_minutes' => 60,
        'tags'              => ['frontend'],
    ],
    [
        'title'             => 'Opłacić rachunki',
        'category'          => 'Dom',
        'priority'          => 'wysoki',
        'status'            => 'zakończone',
        'estimated_minutes' => 15,
        'tags'              => ['pilne', 'dom'],
    ],
];

$allowed_categories = ['Praca', 'Dom', 'Nauka', 'Zdrowie', 'Inne'];
$allowed_priorities  = ['niski', 'średni', 'wysoki'];
$allowed_statuses    = ['do zrobienia', 'w trakcie', 'zakończone'];
$allowed_tags        = ['pilne', 'zespół', 'backend', 'frontend', 'dom', 'zakupy'];

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title              = trim($_POST['title'] ?? '');
    $category           = trim($_POST['category'] ?? '');
    $priority           = trim($_POST['priority'] ?? '');
    $status             = trim($_POST['status'] ?? '');
    $estimated_minutes  = trim($_POST['estimated_minutes'] ?? '');
    $tags               = $_POST['tags'] ?? [];

    $old = [
        'title'             => $title,
        'category'          => $category,
        'priority'          => $priority,
        'status'            => $status,
        'estimated_minutes' => $estimated_minutes,
        'tags'              => $tags,
    ];

    if ($title === '') {
        $errors[] = 'Tytuł zadania nie może być pusty.';
    }

    if (!is_numeric($estimated_minutes) || (int)$estimated_minutes <= 0) {
        $errors[] = 'Szacowany czas musi być liczbą dodatnią.';
    }

    if (empty($tags)) {
        $errors[] = 'Musisz wybrać co najmniej jeden tag.';
    }

    if (!in_array($category, $allowed_categories, true)) {
        $errors[] = 'Wybrana kategoria jest nieprawidłowa.';
    }

    if (!in_array($priority, $allowed_priorities, true)) {
        $errors[] = 'Wybrany priorytet jest nieprawidłowy.';
    }

    if (!in_array($status, $allowed_statuses, true)) {
        $errors[] = 'Wybrany status jest nieprawidłowy.';
    }

    if (empty($errors)) {
        $clean_tags = array_filter($tags, fn($t) => $t !== '');
        sort($clean_tags);

        $new_task = [
            'title'             => $title,
            'category'          => $category,
            'priority'          => $priority,
            'status'            => $status,
            'estimated_minutes' => (int)$estimated_minutes,
            'tags'              => $clean_tags,
        ];

        $tasks[] = $new_task;
        $old = [];
    }
}

$count_all      = count($tasks);
$count_todo     = count(array_filter($tasks, fn($t) => $t['status'] === 'do zrobienia'));
$count_done     = count(array_filter($tasks, fn($t) => $t['status'] === 'zakończone'));
$sum_minutes    = array_sum(array_column($tasks, 'estimated_minutes'));

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menadżer Zadań</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eeeeee;
            margin: 0;
        }

        header {
            background-color: #333333;
            padding: 10px 20px;
        }

        header h1 {
            color: white;
            font-size: 20px;
        }

        .wrapper {
            display: flex;
        }

        aside {
            width: 260px;
            background-color: #ffffff;
            border-right: 2px solid #cccccc;
            padding: 16px;
            min-height: calc(100vh - 42px);
        }

        aside h2 {
            font-size: 15px;
            margin-bottom: 12px;
        }

        label {
            display: block;
            margin-top: 10px;
            margin-bottom: 3px;
            font-size: 13px;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 5px;
            font-size: 13px;
            border: 1px solid #aaaaaa;
        }

        .tags-group {
            margin-top: 4px;
        }

        .tags-group label {
            margin-top: 3px;
            font-weight: normal;
        }

        button[type="submit"] {
            margin-top: 14px;
            width: 100%;
            padding: 8px;
            background-color: #2255cc;
            color: white;
            border: none;
            font-size: 14px;
            cursor: pointer;
        }

        main {
            flex: 1;
            padding: 16px 20px;
        }

        .errors {
            background-color: #ffdddd;
            border: 1px solid #cc0000;
            padding: 10px;
            margin-bottom: 14px;
        }

        .errors p {
            color: #cc0000;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .errors li {
            color: #cc0000;
            font-size: 13px;
        }

        .success-msg {
            background-color: #ddffdd;
            border: 1px solid #009900;
            padding: 8px 12px;
            margin-bottom: 14px;
            color: #006600;
            font-size: 13px;
        }

        .stats {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
        }

        .stat-block {
            flex: 1;
            background-color: #ffffff;
            border: 1px solid #cccccc;
            padding: 10px;
            text-align: center;
        }

        .stat-num {
            display: block;
            font-size: 22px;
            font-weight: bold;
        }

        .stat-label {
            font-size: 12px;
            color: #555555;
        }

        .task-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 12px;
        }

        .task-card {
            background-color: #ffffff;
            border: 1px solid #cccccc;
            padding: 12px;
        }

        .task-card.wysoki { background-color: #ffe0e0; }
        .task-card.sredni { background-color: #fff8cc; }
        .task-card.niski  { background-color: #e0ffe0; }

        .task-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .task-meta {
            font-size: 12px;
            color: #555555;
            margin-bottom: 6px;
        }

        .task-tags {
            font-size: 12px;
            color: #444444;
            margin-bottom: 8px;
        }

        .task-footer {
            font-size: 12px;
            color: #333333;
        }

        .badge-priority {
            font-size: 11px;
            font-weight: bold;
            padding: 2px 6px;
            color: white;
        }

        .badge-priority.wysoki { background-color: #cc0000; }
        .badge-priority.sredni { background-color: #cc7700; }
        .badge-priority.niski  { background-color: #007700; }

        .badge-status {
            font-size: 11px;
            padding: 2px 6px;
        }

        .badge-status.do-zrobienia { background-color: #cce0ff; color: #003399; }
        .badge-status.w-trakcie    { background-color: #fff0cc; color: #664400; }
        .badge-status.zakonczone   { background-color: #ccffcc; color: #004400; }
    </style>
</head>
<body>

<header>
    <h1>Menadżer Zadań</h1>
</header>

<div class="wrapper">

<aside>
    <h2>Dodaj zadanie</h2>

    <form method="post" action="">

        <label for="title">Tytuł zadania</label>
        <input
            type="text"
            id="title"
            name="title"
            placeholder="Wpisz tytuł..."
            value="<?= htmlspecialchars($old['title'] ?? '') ?>">

        <label for="category">Kategoria</label>
        <select id="category" name="category">
            <option value="">-- wybierz --</option>
            <?php foreach ($allowed_categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"
                    <?= (($old['category'] ?? '') === $cat) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="priority">Priorytet</label>
        <select id="priority" name="priority">
            <option value="">-- wybierz --</option>
            <?php foreach ($allowed_priorities as $pri): ?>
                <option value="<?= htmlspecialchars($pri) ?>"
                    <?= (($old['priority'] ?? '') === $pri) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(ucfirst($pri)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">-- wybierz --</option>
            <?php foreach ($allowed_statuses as $st): ?>
                <option value="<?= htmlspecialchars($st) ?>"
                    <?= (($old['status'] ?? '') === $st) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(ucfirst($st)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="estimated_minutes">Szacowany czas (minuty)</label>
        <input
            type="text"
            id="estimated_minutes"
            name="estimated_minutes"
            placeholder="np. 30"
            value="<?= htmlspecialchars($old['estimated_minutes'] ?? '') ?>">

        <label>Tagi</label>
        <div class="tags-group">
            <?php foreach ($allowed_tags as $tag): ?>
                <label>
                    <input
                        type="checkbox"
                        name="tags[]"
                        value="<?= htmlspecialchars($tag) ?>"
                        <?= in_array($tag, $old['tags'] ?? [], true) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($tag) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <button type="submit">Dodaj zadanie</button>

    </form>
</aside>

<main>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <p>Formularz zawiera błędy:</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="success-msg">Zadanie zostało dodane pomyślnie.</div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-block">
            <span class="stat-num"><?= $count_all ?></span>
            <span class="stat-label">Wszystkie</span>
        </div>
        <div class="stat-block">
            <span class="stat-num"><?= $count_todo ?></span>
            <span class="stat-label">Do zrobienia</span>
        </div>
        <div class="stat-block">
            <span class="stat-num"><?= $count_done ?></span>
            <span class="stat-label">Zakończone</span>
        </div>
        <div class="stat-block">
            <span class="stat-num"><?= $sum_minutes ?></span>
            <span class="stat-label">Łączne minuty</span>
        </div>
    </div>

    <div class="task-list">
        <?php foreach ($tasks as $task):
            $priority_class = match($task['priority']) {
                'wysoki' => 'wysoki',
                'średni' => 'sredni',
                default  => 'niski',
            };
            $status_class = match($task['status']) {
                'do zrobienia' => 'do-zrobienia',
                'w trakcie'    => 'w-trakcie',
                default        => 'zakonczone',
            };
        ?>
            <div class="task-card <?= $priority_class ?>">
                <div class="task-title">
                    <?= htmlspecialchars($task['title']) ?>
                    <span class="badge-priority <?= $priority_class ?>"><?= htmlspecialchars(ucfirst($task['priority'])) ?></span>
                </div>
                <div class="task-meta">
                    Kategoria: <strong><?= htmlspecialchars($task['category']) ?></strong>
                </div>
                <div class="task-tags">
                    Tagi: <?= htmlspecialchars(implode(', ', $task['tags'])) ?>
                </div>
                <div class="task-footer">
                    <span class="badge-status <?= $status_class ?>"><?= htmlspecialchars(ucfirst($task['status'])) ?></span>
                    &nbsp; <?= (int)$task['estimated_minutes'] ?> min
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</main>

</div>

</body>
</html>