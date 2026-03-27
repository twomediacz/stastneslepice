<?php
$coopTemp = $climateCoop['temperature'] ?? '–';
$coopHum = $climateCoop['humidity'] ?? '–';
$outTemp = $climateOutdoor['temperature'] ?? '–';
$outHum = $climateOutdoor['humidity'] ?? '–';
$todayCount = $todayEggs['egg_count'] ?? 0;
$todayNote = $todayEggs['note'] ?? '';
?>

<section class="dashboard">

    <!-- Klimatické karty -->
    <!-- <div class="card dashboard__climate-card">
        <div class="dashboard__climate">
            <div class="climate-section climate-section--coop">
                <div class="climate-section__header climate-section__header--blue">Kurník</div>
                <div class="climate-body">
                    <div class="climate-values">
                        <span class="climate-val"><span class="climate-icon">&#x1F321;&#xFE0F;</span> <strong><?= $coopTemp ?>&deg;C</strong></span>
                        <span class="climate-val"><span class="climate-icon">&#x1F4A7;</span> <strong><?= $coopHum ?>%</strong></span>
                    </div>
                    </div>
            </div>

            <div class="climate-section climate-section--outdoor">
                <div class="climate-section__header climate-section__header--green">Venku</div>
                <div class="climate-body">
                    <div class="climate-values">
                        <span class="climate-val"><span class="climate-icon">&#x1F321;&#xFE0F;</span> <strong><?= $outTemp ?>&deg;C</strong></span>
                        <span class="climate-val"><span class="climate-icon">&#x1F4A7;</span> <strong><?= $outHum ?>%</strong></span>
                    </div>
                    </div>
            </div>
        </div>
    </div> -->

    <!-- Hlavní obsah: 2 sloupce -->
    <div class="dashboard__main">

        <!-- LEVÝ SLOUPEC -->
        <div class="dashboard__left">

            <!-- Zápis vajec -->
            <div class="card">
                <div class="card__header card__header--egg">
                    <span>&#x1F95A; Zápis vajec</span>
                    <button type="button" id="egg-add-btn" class="btn btn--primary btn--round" onclick="App.eggs.toggleForm()">Přidat</button>
                </div>
                <div class="card__inner">
                    <div id="egg-form-wrap" class="egg-form-wrap" style="display:none">
                        <form id="egg-form" class="egg-form">
                            <input type="text" id="egg-date" name="date" value="<?= date('Y-m-d') ?>" class="egg-form__date" readonly>
                            <input type="number" name="egg_count" placeholder="Počet" min="0" required class="egg-form__count">
                            <input type="text" name="note" placeholder="Poznámka" class="egg-form__note">
                            <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                            <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.eggs.toggleForm(false)">Zrušit</button>
                        </form>
                    </div>
                    <div class="egg-table-header-wrap">
                        <table class="egg-table egg-table--header">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Počet</th>
                                    <th>Poznámka</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="egg-table-wrap">
                        <table class="egg-table">
                            <tbody id="egg-table-body">
                                <?php foreach ($recentEggs as $egg): ?>
                                <tr data-id="<?= $egg['id'] ?>" data-date="<?= $egg['record_date'] ?>" data-count="<?= $egg['egg_count'] ?>" data-note="<?= htmlspecialchars($egg['note'] ?? '') ?>">
                                    <td><?= date('d.m.Y', strtotime($egg['record_date'])) ?></td>
                                    <td><span class="egg-count-badge"><?= $egg['egg_count'] ?></span></td>
                                    <td><?= htmlspecialchars($egg['note'] ?? '') ?></td>
                                    <td class="egg-actions">
                                        <button class="btn-icon" onclick="App.eggs.edit(this.closest('tr'))" title="Upravit">&#x270E;</button>
                                        <button class="btn-icon btn-icon--danger" onclick="App.eggs.remove(<?= $egg['id'] ?>)" title="Smazat">&times;</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Graf: Teplota & Vlhkost -->
            <div class="card">
                <div class="card__header card__header--blue">&#x1F414; Teplota &amp; Vlhkost</div>
                <div class="chart-container">
                    <canvas id="chart-climate"></canvas>
                </div>
            </div>

            <!-- Graf: Snáška vajec -->
            <div class="card">
                <div class="card__header card__header--red">&#x1F95A; Snáška vajec</div>
                <div class="chart-container">
                    <canvas id="chart-eggs"></canvas>
                </div>
            </div>

            <!-- Poznámky -->
            <div class="card">
                <div class="card__header card__header--note">
                    <span>&#x1F4DD; Poznámky</span>
                    <button type="button" class="btn btn--primary btn--round" onclick="App.notes.toggleForm()">Přidat</button>
                </div>
                <div class="card__inner">
                    <div id="note-form-wrap" class="egg-form-wrap" style="display:none">
                        <form id="note-form" class="egg-form">
                            <input type="hidden" name="id" value="">
                            <input type="text" id="note-date" name="note_date" placeholder="Datum" required class="egg-form__date" readonly>
                            <input type="text" name="content" placeholder="Poznámka" required class="egg-form__note">
                            <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                            <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.notes.hideForm()">Zrušit</button>
                        </form>
                    </div>
                    <div class="maintenance-table-wrap">
                        <table class="maintenance-table">
                            <tbody id="notes-table-body">
                                <?php foreach ($notes as $note): ?>
                                <tr data-id="<?= $note['id'] ?>" data-date="<?= $note['note_date'] ?>" data-content="<?= htmlspecialchars($note['content']) ?>">
                                    <td><?= date('d.m.Y', strtotime($note['note_date'])) ?></td>
                                    <td><?= htmlspecialchars($note['content']) ?></td>
                                    <td class="maintenance-actions">
                                        <button class="btn-icon" onclick="App.notes.edit(this.closest('tr'))" title="Upravit">&#x270E;</button>
                                        <button class="btn-icon btn-icon--danger" onclick="App.notes.remove(<?= $note['id'] ?>)" title="Smazat">&times;</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- PRAVÝ SLOUPEC -->
        <div class="dashboard__right">

            <!-- Kurník -->
            <div class="card">
                <div class="card__header card__header--brown">Kurník</div>
                <div class="stat-grid2">
                    <div class="stat-card stat-card--brown">
                        <span class="stat-card__value"><span class="climate-icon">&#x1F321;&#xFE0F;</span> <?= $coopTemp ?>&thinsp;&deg;C</span>
                        <span class="stat-card__label">Teplota</span>
                    </div>
                    <div class="stat-card stat-card--brown">
                        <span class="stat-card__value"><span class="climate-icon">&#x1F4A7;</span> <?= $coopHum ?>&thinsp;%</span>
                        <span class="stat-card__label">Vlhkost</span>
                    </div>
                </div>
            </div>

            <!-- Výběh -->
            <div class="card">
                <div class="card__header card__header--teal">Výběh</div>
                <div class="stat-grid2">
                    <div class="stat-card stat-card--teal">
                        <span class="stat-card__value"><span class="climate-icon">&#x1F321;&#xFE0F;</span> <?= $outTemp ?>&thinsp;&deg;C</span>
                        <span class="stat-card__label">Teplota</span>
                    </div>
                    <div class="stat-card stat-card--teal">
                        <span class="stat-card__value"><span class="climate-icon">&#x1F4A7;</span> <?= $outHum ?>&thinsp;%</span>
                        <span class="stat-card__label">Vlhkost</span>
                    </div>
                </div>
            </div>

            <!-- Statistiky -->
            <div class="card">
                <div class="card__header card__header--green">Statistika</div>
                <div class="stat-grid">
                    <div class="stat-card">
                        <span class="stat-card__value">&#x1F414; <?= $chickenCount['active'] ?></span>
                        <span class="stat-card__label">Slepic</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-card__value" id="stat-total">&#x1F95A; <?= number_format($totalEggs, 0, ',', ' ') ?></span>
                        <span class="stat-card__label">Vajec celkem</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-card__value" id="stat-avg">&#x1F95A; <?= number_format($dailyAvg, 1, ',', ' ') ?></span>
                        <span class="stat-card__label">Prům. vajec/den</span>
                    </div>
                </div>
            </div>

            <!-- Podestýlka -->
            <?php
            function dnyTextHome(int $n): string {
                $abs = abs($n);
                if ($abs === 1) return $abs . ' den';
                if ($abs >= 2 && $abs <= 4) return $abs . ' dny';
                return $abs . ' dní';
            }
            $dbStatus = 'ok';
            $dbDaysLeft = null;
            if ($nextBeddingDate) {
                $dbNow = new DateTime('today');
                $dbNext = new DateTime($nextBeddingDate);
                $dbDiff = (int) $dbNow->diff($dbNext)->format('%r%a');
                $dbDaysLeft = $dbDiff;
                if ($dbDiff < 0) $dbStatus = 'overdue';
                elseif ($dbDiff <= 3) $dbStatus = 'warning';
            }
            ?>
            <div class="card">
                <div class="card__header card__header--maintenance">
                    <span>Výměna podestýlky</span>
                    <button type="button" class="btn btn--primary btn--round" onclick="App.maintenance.beddingQuickLog()">Vyměněno</button>
                </div>
                <div class="stat-grid2">
                    <div class="stat-card">
                        <span class="stat-card__value" id="dashboard-bedding-last"><?= $lastBeddingDate ? date('d.m.Y', strtotime($lastBeddingDate)) : '–' ?></span>
                        <span class="stat-card__label">Poslední výměna</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-card__value bedding-status__value--<?= $dbStatus ?>" id="dashboard-bedding-next"><?php if ($dbDaysLeft !== null): ?><?= $dbDaysLeft < 0 ? dnyTextHome($dbDaysLeft) . ' po termínu' : ($dbDaysLeft === 0 ? 'dnes' : 'za ' . dnyTextHome($dbDaysLeft)) ?><?php else: ?>–<?php endif; ?></span>
                        <span class="stat-card__label">Příští výměna</span>
                    </div>
                </div>
            </div>

            <!-- Počasí -->
            <div class="card">
                <div class="card__header card__header--blue">Předpověď počasí Doloplazy</div>
                <div id="weather-content" class="weather-list">
                    <p class="text-muted">Načítám předpověď...</p>
                </div>
            </div>

            <!-- Galerie fotek -->
            <div class="card">
                <div class="card__header card__header--photo">
                    <span>&#x1F4F7; Fotky</span>
                    <label class="btn btn--primary btn--round" for="photo-upload">Přidat</label>
                    <input type="file" id="photo-upload" accept="image/jpeg,image/png,image/webp" style="display:none">
                </div>
                <div class="card__inner">
                    <div id="gallery-grid" class="gallery-grid">
                        <?php foreach ($photos as $photo): ?>
                        <div class="gallery-item" data-id="<?= $photo['id'] ?>">
                            <img src="/uploads/thumbs/<?= htmlspecialchars($photo['filename']) ?>"
                                 alt="<?= htmlspecialchars($photo['caption'] ?? '') ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null;this.src='/uploads/<?= htmlspecialchars($photo['filename']) ?>'">
                            <button class="gallery-item__delete" onclick="event.stopPropagation();App.gallery.remove(<?= $photo['id'] ?>)">&times;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>

    </div>

</section>

<!-- Lightbox -->
<div id="lightbox" class="lightbox" onclick="App.gallery.closeLightbox()">
    <button class="lightbox__close" onclick="App.gallery.closeLightbox()">&times;</button>
    <img id="lightbox-img" class="lightbox__img" src="" alt="">
</div>

<!-- Data pro JS -->
<script>
window.__dashboardData = {
    recentEggs: <?= json_encode($recentEggs, JSON_UNESCAPED_UNICODE) ?>,
    totalEggs: <?= $totalEggs ?>,
    dailyAvg: <?= $dailyAvg ?>
};
</script>
