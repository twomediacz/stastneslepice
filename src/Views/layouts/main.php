<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Chov slepic – Doloplazy') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/css/style.css?v=<?= filemtime(__DIR__ . '/../../../public/css/style.css') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
</head>
<body>
    <div class="site-header-wrap">
    <header class="site-header">
        <a href="/" class="header-brand">
            <img src="/img/chicken-logo.png" alt="Slepice" class="header-logo">
        </a>
        <div class="header-content">
            <div class="header-top-row">
                <div class="header-left">
                    <a href="/" class="header-brand"><h1>Chov slepic &ndash; Doloplazy</h1></a>
                </div>
            </div>
            <?php $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
            <button class="hamburger" id="hamburger-btn" aria-label="Menu" aria-expanded="false">
                <span class="hamburger__line"></span>
                <span class="hamburger__line"></span>
                <span class="hamburger__line"></span>
            </button>
            <nav class="site-nav" id="site-nav">
                <a href="/" class="site-nav__tab <?= ($currentPath === '/' || $currentPath === '') ? 'is-active' : '' ?>">N&aacute;stěnka</a>
                <a href="/zive" class="site-nav__tab <?= $currentPath === '/zive' ? 'is-active' : '' ?>">Kamery</a>
                <a href="/slepice" class="site-nav__tab <?= $currentPath === '/slepice' ? 'is-active' : '' ?>">Slepice</a>
                <?php if (\App\Core\Auth::check()): ?>
                <a href="/krmeni" class="site-nav__tab <?= $currentPath === '/krmeni' ? 'is-active' : '' ?>">Krmen&iacute;</a>
                <a href="/finance" class="site-nav__tab <?= $currentPath === '/finance' ? 'is-active' : '' ?>">Finance</a>
                <?php endif; ?>
                <a href="/udrzba" class="site-nav__tab <?= $currentPath === '/udrzba' ? 'is-active' : '' ?>">&Uacute;držba</a>
                <a href="/almanach/pokrocily" class="site-nav__tab <?= $currentPath === '/almanach/pokrocily' ? 'is-active' : '' ?>">Almanach</a>
                <!-- <div class="site-nav__dropdown <?= str_starts_with($currentPath, '/almanach') ? 'is-active' : '' ?>">
                    <button class="site-nav__tab site-nav__dropdown-toggle <?= str_starts_with($currentPath, '/almanach') ? 'is-active' : '' ?>" type="button">
                        Almanach <span class="site-nav__dropdown-arrow">&#x25BE;</span>
                    </button>
                    <div class="site-nav__dropdown-menu">
                        <a href="/almanach" class="site-nav__dropdown-item <?= $currentPath === '/almanach' ? 'is-active' : '' ?>">Z&aacute;kladn&iacute; rady</a>
                        <a href="/almanach/pokrocily" class="site-nav__dropdown-item <?= $currentPath === '/almanach/pokrocily' ? 'is-active' : '' ?>">Pokročil&yacute; průvodce</a>
                    </div>
                </div> -->
            </nav>
        </div>
        <img src="/img/hens.png" alt="" class="header-hens">
    </header>
    </div>

    <main class="site-content">
        <?= $content ?>
    </main>

    <button class="scroll-top" id="scroll-top-btn" aria-label="Nahoru">&#x25B2;</button>

    <footer class="site-footer">
        <p>Two Media, s.r.o. &copy; <?= date('Y') ?>
        <?php if (\App\Core\Auth::check()): ?>
            | <a href="/uzivatele">Uživatelé</a>
            | <a href="/logout">Odhlásit se</a>
        <?php else: ?>
            | <a href="/login">Přihlášení</a>
        <?php endif; ?>
        </p>
    </footer>

    <script>window.__isLoggedIn = <?= \App\Core\Auth::check() ? 'true' : 'false' ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/cs.js"></script>
    <script src="/js/app.js?v=<?= filemtime(__DIR__ . '/../../../public/js/app.js') ?>"></script>
</body>
</html>
