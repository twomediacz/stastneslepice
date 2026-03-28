<section class="almanach">

    <!-- Vyhledávání -->
    <div class="almanach__search-wrap">
        <div class="almanach__search">
            <span class="almanach__search-icon">&#x1F50D;</span>
            <input type="text" id="almanach-search" placeholder="Hledat v radách..." autocomplete="off">
            <span class="almanach__search-count" id="almanach-search-count"></span>
        </div>
    </div>

    <div class="almanach__layout">

        <!-- Sticky sidebar s obsahem -->
        <aside class="almanach__sidebar" id="almanach-sidebar">
            <div class="almanach__toc-header">Obsah</div>
            <nav class="almanach__toc">
                <?php foreach ($sections as $section): ?>
                <a href="#<?= $section['id'] ?>" class="almanach__toc-link" data-section="<?= $section['id'] ?>">
                    <span class="almanach__toc-icon"><?= $section['icon'] ?></span>
                    <?= htmlspecialchars($section['title']) ?>
                </a>
                <?php foreach ($section['subsections'] as $sub): ?>
                <a href="#<?= $sub['id'] ?>" class="almanach__toc-link almanach__toc-link--sub" data-section="<?= $sub['id'] ?>">
                    <?= htmlspecialchars($sub['title']) ?>
                </a>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- Obsah – sekce jako karty -->
        <div class="almanach__content">
            <?php
            $totalTips = 0;
            foreach ($sections as $s) {
                $totalTips += count($s['tips']);
                foreach ($s['subsections'] as $sub) {
                    $totalTips += count($sub['tips']);
                }
            }
            ?>
            <div class="almanach__intro card">
                <div class="card__body">
                    <strong><?= $totalTips ?> praktických rad</strong> pro domácí chov slepic &ndash; od výběru plemene přes stavbu kurníku až po zimní péči.
                </div>
            </div>

            <?php foreach ($sections as $section): ?>
            <div class="card almanach__section" id="<?= $section['id'] ?>" data-section-id="<?= $section['id'] ?>">
                <div class="card__header card__header--<?= $section['color'] ?>">
                    <?= $section['icon'] ?> <?= htmlspecialchars($section['title']) ?>
                </div>
                <div class="card__inner">
                    <?php if (!empty($section['tips'])): ?>
                    <ul class="almanach__tips">
                        <?php foreach ($section['tips'] as $tip): ?>
                        <li class="almanach__tip" data-num="<?= $tip['num'] ?>">
                            <span class="almanach__tip-num"><?= $tip['num'] ?></span>
                            <span class="almanach__tip-text"><?= htmlspecialchars($tip['text']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php foreach ($section['subsections'] as $sub): ?>
                    <div class="almanach__subsection" id="<?= $sub['id'] ?>">
                        <h3 class="almanach__subsection-title"><?= htmlspecialchars($sub['title']) ?></h3>
                        <ul class="almanach__tips">
                            <?php foreach ($sub['tips'] as $tip): ?>
                            <li class="almanach__tip" data-num="<?= $tip['num'] ?>">
                                <span class="almanach__tip-num"><?= $tip['num'] ?></span>
                                <span class="almanach__tip-text"><?= htmlspecialchars($tip['text']) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <p class="almanach__footer">Sestaveno z odborných chovatelských zdrojů (chovatelské portály, výrobci krmiv, drůbežářské společnosti). Při jakémkoliv zdravotním problému u slepic se vždy obraťte na veterinárního lékaře.</p>
</section>
