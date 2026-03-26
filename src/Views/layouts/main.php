<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Šťastné slepice') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
</head>
<body>
    <div class="site-header-wrap">
    <header class="site-header">
        <img src="/img/chicken-logo.png" alt="Slepice" class="header-logo">
        <div class="header-content">
            <div class="header-top-row">
                <div class="header-left">
                    <h1>Chov slepic &ndash; Doloplazy</h1>
                </div>
            </div>
            <?php if (\App\Core\Auth::check()): ?>
            <?php $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
            <nav class="site-nav">
                <a href="/" class="site-nav__tab <?= ($currentPath === '/' || $currentPath === '') ? 'is-active' : '' ?>">N&aacute;stěnka</a>
                <a href="/slepice" class="site-nav__tab <?= $currentPath === '/slepice' ? 'is-active' : '' ?>">Slepice</a>
                <a href="/zive" class="site-nav__tab <?= $currentPath === '/zive' ? 'is-active' : '' ?>">Živě</a>
            </nav>
            <?php endif; ?>
        </div>
    </header>
    </div>

    <main class="site-content">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <p>Two Media, s.r.o. &copy; <?= date('Y') ?></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/cs.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
