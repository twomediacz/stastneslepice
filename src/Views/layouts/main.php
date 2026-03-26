<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Šťastné slepice') ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header class="site-header">
        <h1>Chov slepic &ndash; Doloplazy</h1>
    </header>

    <main class="site-content">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> Chov slepic | Kontakt / Autor stránky</p>
    </footer>

    <script src="/js/app.js"></script>
</body>
</html>
