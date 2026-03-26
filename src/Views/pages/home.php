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
                <div class="card__title-row">
                    <h2>&#x1F95A; Zápis vajec</h2>
                    <button type="button" id="egg-add-btn" class="btn btn--primary btn--round" onclick="App.eggs.toggleForm()">Přidat</button>
                </div>
                <div class="card__inner">
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
                    <div id="egg-form-wrap" class="egg-form-wrap" style="display:none">
                        <form id="egg-form" class="egg-form">
                            <input type="text" id="egg-date" name="date" value="<?= date('Y-m-d') ?>" class="egg-form__date" readonly>
                            <input type="number" name="egg_count" placeholder="Počet" min="0" required class="egg-form__count">
                            <input type="text" name="note" placeholder="Poznámka" class="egg-form__note">
                            <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Graf: Teplota & Vlhkost -->
            <div class="card">
                <h2>&#x1F414; Teplota &amp; Vlhkost</h2>
                <div class="chart-container">
                    <canvas id="chart-climate"></canvas>
                </div>
            </div>

            <!-- Graf: Snáška vajec -->
            <div class="card">
                <h2>&#x1F95A; Snáška vajec</h2>
                <div class="chart-container">
                    <canvas id="chart-eggs"></canvas>
                </div>
            </div>

            <!-- Poznámky -->
            <div class="card">
                <h2>&#x1F4DD; Poznámky</h2>
                <div class="card__inner">
                    <ul id="notes-list" class="notes-list">
                        <?php foreach ($notes as $note): ?>
                        <li data-id="<?= $note['id'] ?>" data-content="<?= htmlspecialchars($note['content']) ?>">
                            <strong><?= date('d.m.Y', strtotime($note['note_date'])) ?>&nbsp;</strong>
                            <span class="note-text"><?= htmlspecialchars($note['content']) ?></span>
                            <span class="note-actions">
                                <button class="btn-icon" onclick="App.notes.edit(this.closest('li'))" title="Upravit">&#x270E;</button>
                                <button class="btn-icon btn-icon--danger" onclick="App.notes.remove(<?= $note['id'] ?>)" title="Smazat">&times;</button>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <form id="note-form" class="note-form">
                        <input type="text" name="content" placeholder="Nová poznámka" required>
                        <button type="submit" class="btn btn--primary btn--round">Přidat</button>
                    </form>
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

            <!-- Počasí -->
            <div class="card">
                <div class="card__header card__header--blue">Předpověď počasí Doloplazy</div>
                <div id="weather-content" class="weather-list">
                    <p class="text-muted">Načítám předpověď...</p>
                </div>
            </div>

            <!-- Galerie fotek -->
            <div class="card">
                <div class="card__title-row">
                    <h2>&#x1F4F7; Fotky</h2>
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
