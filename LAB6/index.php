<?php

$tasks = [
    [
        'title'             => 'Przygotuj prezentację',
        'description'       => 'Sprawdź materiały na stronie https://example.com/slides i skontaktuj się z #backend #pilne. Termin: 2025-06-15 o godzinie 10:00. Tel: 123-456-789.',
        'category'          => 'Praca',
        'priority'          => 'wysoki',
        'status'            => 'w trakcie',
        'estimated_minutes' => 90,
        'tags'              => ['backend', 'pilne'],
        'due_date'          => '2025-06-15',
        'email'             => 'jan.kowalski@firma.pl',
    ],
    [
        'title'             => 'Zakupy spożywcze',
        'description'       => "Lista zakupów:\n- mleko\n- chleb\n- jajka\n- masło\nOdwiedź https://lidl.pl przed 2025-05-20.",
        'category'          => 'Dom',
        'priority'          => 'niski',
        'status'            => 'do zrobienia',
        'estimated_minutes' => 30,
        'tags'              => ['dom', 'zakupy'],
        'due_date'          => '2025-05-20',
        'email'             => '',
    ],
    [
        'title'             => 'Nauka CSS',
        'description'       => 'Przejrzyj dokumentację na https://developer.mozilla.org i zrób notatki. #frontend #nauka\n1. Flexbox\n2. Grid\n3. Animacje',
        'category'          => 'Nauka',
        'priority'          => 'średni',
        'status'            => 'do zrobienia',
        'estimated_minutes' => 60,
        'tags'              => ['frontend'],
        'due_date'          => '',
        'email'             => 'nauka@example.com',
    ],
];

$allowed_categories = ['Praca', 'Dom', 'Nauka', 'Zdrowie', 'Inne'];
$allowed_priorities  = ['niski', 'średni', 'wysoki'];
$allowed_statuses    = ['do zrobienia', 'w trakcie', 'zakończone'];

function validateInput(string $type, string $value): bool
{
    return match ($type) {
        'email' => (bool) preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $value),
        'tag'   => (bool) preg_match('/^[a-zA-Z0-9_]+$/u', $value),
        'date'  => (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $value),
        default => false,
    };
}

function extractTags(string $text): array
{
    preg_match_all('/\#([a-zA-Z0-9_]+)/', $text, $matches);
    return $matches[1] ?? [];
}

function formatTaskDescription(string $description): string
{
    $text = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

    $text = preg_replace(
        '/\b(?:https?|ftp):\/\/[a-z0-9\-+&@#\/%?=~_|!:,.;]*[a-z0-9\-+&@#\/%=~_|]/i',
        '<a href="$0" target="_blank" rel="noopener noreferrer" class="task-link">$0</a>',
        $text
    );

    $text = preg_replace(
        '/\#([a-zA-Z0-9_]+)/',
        '<span class="tag-highlight">#$1</span>',
        $text
    );

    $lines  = explode("\n", $text);
    $output = [];
    $inUl   = false;
    $inOl   = false;

    foreach ($lines as $line) {
        if (preg_match('/^[\s]*[-*+][\s]+(.+)$/', $line, $m)) {
            if ($inOl) { $output[] = '</ol>'; $inOl = false; }
            if (!$inUl) { $output[] = '<ul>'; $inUl = true; }
            $output[] = '<li>' . trim($m[1]) . '</li>';
        }
        elseif (preg_match('/^\d+\.\s+(.+)$/', $line, $m)) {
            if ($inUl) { $output[] = '</ul>'; $inUl = false; }
            if (!$inOl) { $output[] = '<ol>'; $inOl = true; }
            $output[] = '<li>' . trim($m[1]) . '</li>';
        }
        else {
            if ($inUl) { $output[] = '</ul>'; $inUl = false; }
            if ($inOl) { $output[] = '</ol>'; $inOl = false; }
            $output[] = $line;
        }
    }
    if ($inUl) $output[] = '</ul>';
    if ($inOl) $output[] = '</ol>';

    $text = implode("\n", $output);

    $text = preg_replace(
        '/\b(\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))\b/',
        '<span class="highlight-date">$1</span>',
        $text
    );

    $text = preg_replace(
        '/\b(([01]\d|2[0-3]):[0-5]\d)\b/',
        '<span class="highlight-time">$1</span>',
        $text
    );

    $text = preg_replace(
        '/\b(\d{3}[-\s]\d{3}[-\s]\d{3})\b/',
        '<span class="highlight-phone">$1</span>',
        $text
    );

    return nl2br($text);
}

function searchTasks(array $tasks, string $pattern): array
{
    if (empty($pattern)) return $tasks;
    if (!preg_match('/^[\/\#\~\!].+[\/\#\~\!][a-z]*$/i', $pattern)) {
        $pattern = '/' . preg_quote($pattern, '/') . '/i';
    }

    return array_filter($tasks, function ($task) use ($pattern) {
        $haystack = ($task['title'] ?? '') . ' ' . ($task['description'] ?? '');
        return @preg_match($pattern, $haystack) === 1;
    });
}

function filterTasksByTag(array $tasks, string $tag): array
{
    if (empty($tag)) return $tasks;
    $tag = strtolower(trim($tag));
    return array_filter($tasks, function ($task) use ($tag) {
        $inArray = in_array($tag, array_map('strtolower', $task['tags'] ?? []), true);
        $inDesc  = in_array($tag, array_map('strtolower', extractTags($task['description'] ?? '')), true);
        return $inArray || $inDesc;
    });
}

function highlightMatch(string $text, string $pattern): string
{
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    if (empty($pattern)) return $safe;
    if (!preg_match('/^[\/\#\~\!].+[\/\#\~\!][a-z]*$/i', $pattern)) {
        $pattern = '/' . preg_quote($pattern, '/') . '/i';
    }
    $result = @preg_replace($pattern, '<span class="highlight">$0</span>', $safe);
    return $result ?? $safe;
}

$errors      = [];
$old         = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'add_task') {
    $title              = trim($_POST['title'] ?? '');
    $description        = trim($_POST['description'] ?? '');
    $category           = trim($_POST['category'] ?? '');
    $priority           = trim($_POST['priority'] ?? '');
    $status             = trim($_POST['status'] ?? '');
    $estimated_minutes  = trim($_POST['estimated_minutes'] ?? '');
    $tags_raw           = trim($_POST['tags_text'] ?? '');
    $due_date           = trim($_POST['due_date'] ?? '');
    $email              = trim($_POST['email'] ?? '');

    $old = compact('title', 'description', 'category', 'priority', 'status', 'estimated_minutes', 'tags_raw', 'due_date', 'email');

    if ($title === '') $errors[] = 'Tytuł zadania nie może być pusty.';
    if (!is_numeric($estimated_minutes) || (int)$estimated_minutes <= 0) $errors[] = 'Szacowany czas musi być liczbą dodatnią.';
    if (!in_array($category, $allowed_categories, true)) $errors[] = 'Wybrana kategoria jest nieprawidłowa.';
    if (!in_array($priority, $allowed_priorities, true)) $errors[] = 'Wybrany priorytet jest nieprawidłowy.';
    if (!in_array($status, $allowed_statuses, true)) $errors[] = 'Wybrany status jest nieprawidłowy.';
    if ($email !== '' && !validateInput('email', $email)) $errors[] = 'Podany e-mail jest nieprawidłowy.';
    if ($due_date !== '' && !validateInput('date', $due_date)) $errors[] = 'Data musi mieć format RRRR-MM-DD.';

    $tags_arr = [];
    if ($tags_raw !== '') {
        $raw_parts = preg_split('/\s+/', $tags_raw);
        foreach ($raw_parts as $t) {
            $t = trim($t);
            if ($t === '') continue;
            if (!validateInput('tag', $t)) $errors[] = "Tag „{$t}” jest nieprawidłowy.";
            else $tags_arr[] = $t;
        }
    } else {
        $errors[] = 'Pole tagów nie może być puste.';
    }

    if (empty($errors)) {
        sort($tags_arr);
        $tasks[] = [
            'title' => $title, 'description' => $description, 'category' => $category,
            'priority' => $priority, 'status' => $status, 'estimated_minutes' => (int)$estimated_minutes,
            'tags' => $tags_arr, 'due_date' => $due_date, 'email' => $email
        ];
        $old = [];
        $success_msg = 'Zadanie zostało dodane pomyślnie.';
    }
}

$search_pattern = trim($_GET['search'] ?? '');
$filter_priority = trim($_GET['filter_priority'] ?? '');
$filter_status = trim($_GET['filter_status'] ?? '');
$filter_tag = trim($_GET['filter_tag'] ?? '');

$search_pattern_valid = true;
if ($search_pattern !== '') {
    $test = $search_pattern;
    if (!preg_match('/^[\/\#\~\!].+[\/\#\~\!][a-z]*$/i', $test)) $test = '/' . preg_quote($test, '/') . '/i';
    if (@preg_match($test, '') === false) $search_pattern_valid = false;
}

$displayed_tasks = $tasks;
if ($search_pattern !== '' && $search_pattern_valid) $displayed_tasks = searchTasks($displayed_tasks, $search_pattern);
if ($filter_priority !== '') $displayed_tasks = array_filter($displayed_tasks, fn($t) => $t['priority'] === $filter_priority);
if ($filter_status !== '') $displayed_tasks = array_filter($displayed_tasks, fn($t) => $t['status'] === $filter_status);
if ($filter_tag !== '') $displayed_tasks = filterTasksByTag($displayed_tasks, $filter_tag);

$count_all = count($tasks);
$count_todo = count(array_filter($tasks, fn($t) => $t['status'] === 'do zrobienia'));
$count_done = count(array_filter($tasks, fn($t) => $t['status'] === 'zakończone'));
$sum_minutes = array_sum(array_column($tasks, 'estimated_minutes'));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menadżer Zadań Pro</title>
    <style>
        body { font-family: sans-serif; background: #eee; margin: 0; }
        header { background: #333; color: #fff; padding: 10px 20px; }
        .wrapper { display: flex; align-items: flex-start; }
        aside { width: 300px; background: #fff; padding: 20px; border-right: 1px solid #ccc; min-height: 100vh; }
        main { flex: 1; padding: 20px; }
        label { display: block; margin: 10px 0 3px; font-size: 13px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ccc; margin-bottom: 5px; }
        button { width: 100%; padding: 10px; background: #25c; color: #fff; border: none; cursor: pointer; }
        .stats { display: flex; gap: 10px; margin-bottom: 20px; }
        .stat-block { flex: 1; background: #fff; padding: 15px; text-align: center; border: 1px solid #ccc; }
        .task-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; }
        .task-card { background: #fff; padding: 15px; border: 1px solid #ccc; }
        .wysoki { border-left: 5px solid #c00; }
        .sredni { border-left: 5px solid #c70; }
        .niski { border-left: 5px solid #070; }
        .tag-highlight { background: #eef; color: #55a; padding: 2px 4px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .highlight { background: yellow; }
        .highlight-date { background: #cce; font-weight: bold; }
        .highlight-phone { background: #fec; font-weight: bold; }
        .errors { background: #fee; color: #c00; padding: 10px; border: 1px solid #c00; margin-bottom: 15px; }
        .success-msg { background: #efe; color: #060; padding: 10px; border: 1px solid #060; margin-bottom: 15px; }
        .task-description { font-size: 13px; background: #fafafa; padding: 10px; border: 1px solid #eee; margin: 10px 0; }
    </style>
</head>
<body>
<header><h1>Menadżer Zadań</h1></header>
<div class="wrapper">
    <aside>
        <form method="post">
            <input type="hidden" name="form_type" value="add_task">
            <h3>Nowe zadanie</h3>
            <label>Tytuł *</label>
            <input type="text" name="title" value="<?= htmlspecialchars($old['title'] ?? '') ?>">
            <label>Opis</label>
            <textarea name="description"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
            <label>Kategoria</label>
            <select name="category">
                <?php foreach($allowed_categories as $c): ?>
                    <option <?= ($old['category']??'')==$c?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
            <label>Priorytet</label>
            <select name="priority">
                <?php foreach($allowed_priorities as $p): ?>
                    <option <?= ($old['priority']??'')==$p?'selected':'' ?>><?= $p ?></option>
                <?php endforeach; ?>
            </select>
            <label>Status</label>
            <select name="status">
                <?php foreach($allowed_statuses as $s): ?>
                    <option <?= ($old['status']??'')==$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <label>Czas (min) *</label>
            <input type="text" name="estimated_minutes" value="<?= htmlspecialchars($old['estimated_minutes'] ?? '') ?>">
            <label>Tagi * (spacje)</label>
            <input type="text" name="tags_text" value="<?= htmlspecialchars($old['tags_raw'] ?? '') ?>">
            <label>Termin (RRRR-MM-DD)</label>
            <input type="text" name="due_date" value="<?= htmlspecialchars($old['due_date'] ?? '') ?>">
            <label>E-mail</label>
            <input type="text" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
            <button type="submit">Dodaj</button>
        </form>
        <hr>
        <form method="get">
            <h3>Szukaj</h3>
            <input type="text" name="search" placeholder="Regex / tekst" value="<?= htmlspecialchars($search_pattern) ?>">
            <select name="filter_priority">
                <option value="">Wszystkie priorytety</option>
                <?php foreach($allowed_priorities as $p): ?>
                    <option value="<?= $p ?>" <?= $filter_priority==$p?'selected':'' ?>><?= $p ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="filter_tag" placeholder="Tag" value="<?= htmlspecialchars($filter_tag) ?>">
            <button type="submit" style="background:#444">Filtruj</button>
            <a href="index.php" style="display:block; text-align:center; font-size:12px; margin-top:10px;">Resetuj</a>
        </form>
    </aside>
    <main>
        <?php if($errors): ?><div class="errors"><ul><?php foreach($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if($success_msg): ?><div class="success-msg"><?= $success_msg ?></div><?php endif; ?>

        <div class="stats">
            <div class="stat-block"><b><?= $count_all ?></b><br><small>Wszystkie</small></div>
            <div class="stat-block"><b><?= $count_todo ?></b><br><small>Do zrobienia</small></div>
            <div class="stat-block"><b><?= $count_done ?></b><br><small>Zakończone</small></div>
            <div class="stat-block"><b><?= $sum_minutes ?></b><br><small>Suma min.</small></div>
        </div>

        <div class="task-list">
            <?php foreach ($displayed_tasks as $task): 
                $p_class = match($task['priority']) { 'wysoki'=>'wysoki', 'średni'=>'sredni', default=>'niski' };
            ?>
            <div class="task-card <?= $p_class ?>">
                <div style="font-weight:bold; margin-bottom:10px;"><?= highlightMatch($task['title'], $search_pattern) ?></div>
                <div style="font-size:11px; color:#666;">
                    <?= $task['category'] ?> | <?= $task['status'] ?> | <?= $task['estimated_minutes'] ?> min
                </div>
                <?php if($task['description']): ?>
                    <div class="task-description"><?= formatTaskDescription($task['description']) ?></div>
                <?php endif; ?>
                <div style="margin-top:10px;">
                    <?php 
                    $combined_tags = array_unique(array_merge($task['tags'], extractTags($task['description'])));
                    foreach($combined_tags as $t): ?>
                        <span class="tag-highlight">#<?= htmlspecialchars($t) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php if($task['due_date'] || $task['email']): ?>
                <div style="font-size:11px; margin-top:10px; border-top:1px solid #eee; pt:5px;">
                    <?= $task['due_date'] ? "📅 ".$task['due_date'] : "" ?> 
                    <?= $task['email'] ? " ✉️ ".$task['email'] : "" ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>