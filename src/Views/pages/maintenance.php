<?php
$isLoggedIn = \App\Core\Auth::check();
// Skloňování "den/dny/dní"
function dnyText(int $n): string {
    $abs = abs($n);
    if ($abs === 1) return $abs . ' den';
    if ($abs >= 2 && $abs <= 4) return $abs . ' dny';
    return $abs . ' dní';
}

// Výpočet stavu příští výměny
$beddingStatus = 'ok';
$beddingDaysLeft = null;
if ($nextBeddingDate) {
    $now = new DateTime('today');
    $next = new DateTime($nextBeddingDate);
    $diff = (int) $now->diff($next)->format('%r%a');
    $beddingDaysLeft = $diff;
    if ($diff < 0) {
        $beddingStatus = 'overdue';
    } elseif ($diff <= 3) {
        $beddingStatus = 'warning';
    }
}
?>
<section class="maintenance">
    <div class="maintenance__grid">

        <!-- Výměna podestýlky -->
        <div class="card">
            <div class="card__header card__header--maintenance">
                <span>&#x1FAA3; Výměna podestýlky</span>
            </div>
            <div class="card__inner">

                <!-- Status panel -->
                <div class="bedding-status" id="bedding-status" style="padding-bottom: 0rem;">
                    
                        <div class="bedding-status__item">
                            <span class="bedding-status__label">Příští výměna <?php if ($isLoggedIn): ?><button class="btn-icon" onclick="App.maintenance.toggleInterval()" title="Změnit interval">&#x270E;</button><?php endif; ?></span>
                            <span class="bedding-status__value bedding-status__value--<?= $beddingStatus ?>" id="bedding-next-date"><?= $nextBeddingDate ? date('d.m.Y', strtotime($nextBeddingDate)) : '–' ?><?php if ($beddingDaysLeft !== null): ?> <small>(<?= $beddingDaysLeft < 0 ? dnyText($beddingDaysLeft) . ' po termínu' : ($beddingDaysLeft === 0 ? 'dnes' : 'za ' . dnyText($beddingDaysLeft)) ?>)</small><?php endif; ?></span>
                        </div>

                    <?php if ($isLoggedIn): ?>
                    <!-- Interval nastavení (skrytý) -->
                    <div class="bedding-interval" id="bedding-interval-wrap" style="display:none">
                        <form id="bedding-interval-form" class="bedding-interval__form">
                            <label for="bedding-interval-days">Interval:</label>
                            <input type="number" id="bedding-interval-days" name="interval_days" value="<?= $intervalDays ?>" min="1" max="365" class="bedding-interval__input">
                            <span class="bedding-interval__unit">dn&iacute;</span>
                            <button type="submit" class="btn btn--outline btn--round btn--small">Uložit</button>
                        </form>
                    </div>

                    <!-- Tlačítko pro rychlý záznam -->
                    <div class="bedding-actions">
                        <button type="button" class="btn btn--primary btn--round" id="bedding-quick-log-btn" onclick="App.maintenance.beddingQuickLog()">Podestýlka vyměněna</button>
                        <button type="button" class="btn btn--primary btn--round" onclick="App.maintenance.toggleForm('bedding')">Zadat výměnu podrobně</button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($isLoggedIn): ?>
                <!-- Skrytý formulář pro ruční zadání -->
                <div id="bedding-form-wrap" class="egg-form-wrap" style="display:none">
                    <form id="bedding-form" class="egg-form">
                        <input type="hidden" name="id" value="">
                        <input type="text" id="bedding-date" name="changed_at" placeholder="Datum" required class="egg-form__date">
                        <textarea name="note" placeholder="Poznámka" class="egg-form__note" rows="1"></textarea>
                        <div class="form-buttons">
                            <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                            <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.maintenance.hideForm('bedding')">Zrušit</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Historie -->
                <div class="maintenance-table-wrap">
                    <table class="maintenance-table">
                        <tbody id="bedding-table-body">
                            <?php foreach ($beddingChanges as $row): ?>
                            <tr data-id="<?= $row['id'] ?>" data-datetime="<?= $row['changed_at'] ?>" data-note="<?= htmlspecialchars($row['note'] ?? '') ?>">
                                <td><?= date('d.m.Y', strtotime($row['changed_at'])) ?></td>
                                <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
                                <?php if ($isLoggedIn): ?>
                                <td class="maintenance-actions">
                                    <button class="btn-icon" onclick="App.maintenance.edit('bedding', this.closest('tr'))" title="Upravit">&#x270E;</button>
                                    <button class="btn-icon btn-icon--danger" onclick="App.maintenance.remove('bedding', <?= $row['id'] ?>)" title="Smazat">&times;</button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Opravy -->
        <div class="card">
            <div class="card__header card__header--repair">
                <span>&#x1F6E0; Opravy</span>
                <?php if ($isLoggedIn): ?>
                <button type="button" class="btn btn--primary btn--round" onclick="App.maintenance.toggleForm('repair')">Přidat</button>
                <?php endif; ?>
            </div>
            <div class="card__inner">
                <?php if ($isLoggedIn): ?>
                <div id="repair-form-wrap" class="egg-form-wrap" style="display:none">
                    <form id="repair-form" class="egg-form">
                        <input type="hidden" name="id" value="">
                        <input type="text" id="repair-date" name="repaired_at" placeholder="Datum" required class="egg-form__date">
                        <textarea name="note" placeholder="Poznámka" class="egg-form__note" rows="1"></textarea>
                        <div class="form-buttons">
                            <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                            <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.maintenance.hideForm('repair')">Zrušit</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                <div class="maintenance-table-wrap">
                    <table class="maintenance-table">
                        <tbody id="repair-table-body">
                            <?php foreach ($repairs as $row): ?>
                            <tr data-id="<?= $row['id'] ?>" data-datetime="<?= $row['repaired_at'] ?>" data-note="<?= htmlspecialchars($row['note'] ?? '') ?>">
                                <td><?= date('d.m.Y', strtotime($row['repaired_at'])) ?></td>
                                <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
                                <?php if ($isLoggedIn): ?>
                                <td class="maintenance-actions">
                                    <button class="btn-icon" onclick="App.maintenance.edit('repair', this.closest('tr'))" title="Upravit">&#x270E;</button>
                                    <button class="btn-icon btn-icon--danger" onclick="App.maintenance.remove('repair', <?= $row['id'] ?>)" title="Smazat">&times;</button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>
