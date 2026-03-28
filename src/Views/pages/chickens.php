<?php
use App\Models\Chicken;
$statuses = ['active' => 'Aktivní', 'sick' => 'Nemocná', 'deceased' => 'Uhynulá', 'given_away' => 'Darovaná'];
$isLoggedIn = \App\Core\Auth::check();
?>

<div class="chickens-page">

    <!-- Záhlaví -->
    <div class="chickens-header">
        <div class="chickens-header__left">
            <span class="chickens-count">Celkem: <strong><?= $counts['total'] ?></strong> | Aktivní: <strong><?= $counts['active'] ?></strong></span>
        </div>
        <div class="chickens-header__right">
            <div class="view-toggle">
                <button class="btn btn--outline-dark btn--round view-toggle__btn is-active" data-view="cards" onclick="App.chickens.setView('cards')">Karty</button>
                <button class="btn btn--outline-dark btn--round view-toggle__btn" data-view="table" onclick="App.chickens.setView('table')">Tabulka</button>
            </div>
            <?php if ($isLoggedIn): ?>
            <button class="btn btn--primary btn--round" onclick="App.chickens.showForm()">Přidat slepici</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isLoggedIn): ?>
    <!-- Formulář (skrytý) -->
    <div id="chicken-form-wrap" class="card chicken-form-card" style="display:none">
        <h2 id="chicken-form-title">Přidat slepici</h2>
        <div class="card__inner">
            <form id="chicken-form" class="chicken-form">
                <input type="hidden" name="id" value="">
                <div class="chicken-form__grid">
                    <div class="form-group">
                        <label for="ch-name">Jméno *</label>
                        <input type="text" id="ch-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="ch-breed">Plemeno</label>
                        <input type="text" id="ch-breed" name="breed">
                    </div>
                    <div class="form-group">
                        <label for="ch-color">Barva peří</label>
                        <input type="text" id="ch-color" name="color">
                    </div>
                    <div class="form-group">
                        <label for="ch-status">Stav</label>
                        <select id="ch-status" name="status">
                            <?php foreach ($statuses as $val => $label): ?>
                            <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ch-birth">Datum narození</label>
                        <input type="date" id="ch-birth" name="birth_date">
                    </div>
                    <div class="form-group">
                        <label for="ch-acquired">Datum pořízení</label>
                        <input type="date" id="ch-acquired" name="acquired_date">
                    </div>
                    <div class="form-group">
                        <label for="ch-end">Datum ukončení</label>
                        <input type="date" id="ch-end" name="end_date">
                    </div>
                    <div class="form-group form-group--full">
                        <label for="ch-note">Poznámka</label>
                        <input type="text" id="ch-note" name="note">
                    </div>
                </div>
                <div class="chicken-form__actions">
                    <button type="submit" class="btn btn--primary btn--round">Uložit</button>
                    <button type="button" class="btn btn--outline-dark btn--round" onclick="App.chickens.hideForm()">Zrušit</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Zobrazení: Karty -->
    <div id="chickens-cards" class="chickens-cards">
        <?php if (empty($chickens)): ?>
            <p class="text-muted">Zatím nejsou evidovány žádné slepice.</p>
        <?php endif; ?>
        <?php foreach ($chickens as $ch): ?>
        <div class="chicken-card card" data-id="<?= $ch['id'] ?>">
            <div class="chicken-card__photo">
                <?php if ($ch['photo']): ?>
                    <img src="/uploads/<?= htmlspecialchars($ch['photo']) ?>" alt="<?= htmlspecialchars($ch['name']) ?>">
                <?php else: ?>
                    <div class="chicken-card__no-photo">&#x1F414;</div>
                <?php endif; ?>
                <span class="chicken-status chicken-status--overlay" style="background:<?= Chicken::statusColor($ch['status']) ?>"><?= Chicken::statusLabel($ch['status']) ?></span>
                <?php if ($isLoggedIn): ?>
                <label class="chicken-card__upload-btn" title="Nahrát fotku">
                    &#x1F4F7;
                    <input type="file" accept="image/jpeg,image/png,image/webp" onchange="App.chickens.uploadPhoto(<?= $ch['id'] ?>, this)" style="display:none">
                </label>
                <?php endif; ?>
            </div>
            <div class="chicken-card__body">
                <h3><?= htmlspecialchars($ch['name']) ?></h3>
                <?php if ($ch['breed']): ?><p class="chicken-detail">Plemeno: <?= htmlspecialchars($ch['breed']) ?></p><?php endif; ?>
                <?php if ($ch['color']): ?><p class="chicken-detail">Barva: <?= htmlspecialchars($ch['color']) ?></p><?php endif; ?>
                <?php if ($ch['status'] === 'deceased' && $ch['end_date']): ?>
                    <p class="chicken-detail">Uhynulá: <?= date('d.m.Y', strtotime($ch['end_date'])) ?></p>
                <?php elseif ($ch['status'] === 'given_away' && $ch['end_date']): ?>
                    <p class="chicken-detail">Darovaná: <?= date('d.m.Y', strtotime($ch['end_date'])) ?></p>
                <?php elseif ($ch['acquired_date']): ?>
                    <p class="chicken-detail">Pořízena: <?= date('d.m.Y', strtotime($ch['acquired_date'])) ?></p>
                <?php endif; ?>
                <?php if ($ch['note']): ?><p class="chicken-detail chicken-detail--note"><?= htmlspecialchars($ch['note']) ?></p><?php endif; ?>
            </div>
            <?php if ($isLoggedIn): ?>
            <div class="chicken-card__actions">
                <button class="btn-icon" onclick="App.chickens.edit(<?= $ch['id'] ?>)" title="Upravit">&#x270E;</button>
                <button class="btn-icon btn-icon--danger" onclick="App.chickens.remove(<?= $ch['id'] ?>)" title="Smazat">&times;</button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Zobrazení: Tabulka -->
    <div id="chickens-table" class="card" style="display:none">
        <div class="card__inner" style="padding-top:1rem">
            <div class="chickens-table-wrap">
                <table class="chickens-tbl">
                    <thead>
                        <tr>
                            <th>Jméno</th>
                            <th>Plemeno</th>
                            <th>Barva</th>
                            <th>Narozena</th>
                            <th>Pořízena</th>
                            <th>Ukončena</th>
                            <th>Stav</th>
                            <th>Poznámka</th>
                            <?php if ($isLoggedIn): ?><th></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="chickens-tbody">
                        <?php foreach ($chickens as $ch): ?>
                        <tr data-id="<?= $ch['id'] ?>">
                            <td><strong><?= htmlspecialchars($ch['name']) ?></strong></td>
                            <td><?= htmlspecialchars($ch['breed'] ?? '') ?></td>
                            <td><?= htmlspecialchars($ch['color'] ?? '') ?></td>
                            <td><?= $ch['birth_date'] ? date('d.m.Y', strtotime($ch['birth_date'])) : '' ?></td>
                            <td><?= $ch['acquired_date'] ? date('d.m.Y', strtotime($ch['acquired_date'])) : '' ?></td>
                            <td><?= $ch['end_date'] ? date('d.m.Y', strtotime($ch['end_date'])) : '' ?></td>
                            <td><span class="chicken-status chicken-status--sm" style="background:<?= Chicken::statusColor($ch['status']) ?>"><?= Chicken::statusLabel($ch['status']) ?></span></td>
                            <td><?= htmlspecialchars($ch['note'] ?? '') ?></td>
                            <?php if ($isLoggedIn): ?>
                            <td class="egg-actions">
                                <button class="btn-icon" onclick="App.chickens.edit(<?= $ch['id'] ?>)" title="Upravit">&#x270E;</button>
                                <button class="btn-icon btn-icon--danger" onclick="App.chickens.remove(<?= $ch['id'] ?>)" title="Smazat">&times;</button>
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

<script>
window.__chickensData = <?= json_encode($chickens, JSON_UNESCAPED_UNICODE) ?>;
</script>
