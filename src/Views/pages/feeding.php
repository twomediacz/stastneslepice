<section class="feeding">

    <div class="feeding__main">

        <!-- LEVÝ SLOUPEC -->
        <div class="feeding__left">

            <!-- Záznamy krmení -->
            <div class="card">
                <div class="card__header card__header--teal">
                    <span>&#x1F33E; Záznam krmení</span>
                    <button type="button" class="btn btn--primary btn--round" onclick="App.feeding.toggleForm('record')">Přidat</button>
                </div>
                <div class="card__inner">
                    <div id="feeding-record-form-wrap" class="egg-form-wrap" style="display:none">
                        <form id="feeding-record-form" class="feeding-form">
                            <input type="hidden" name="id" value="">
                            <div class="feeding-form__row">
                                <input type="text" id="feeding-record-date" name="record_date" placeholder="Datum" required class="egg-form__date">
                                <select name="feed_type_id" required class="egg-form__select">
                                    <option value="">-- Typ krmiva --</option>
                                    <?php foreach ($feedTypes as $ft): ?>
                                        <?php if ($ft['is_active']): ?>
                                        <option value="<?= $ft['id'] ?>"><?= htmlspecialchars($ft['name']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="amount_kg" step="0.01" min="0.01" placeholder="kg" required class="egg-form__count">
                            </div>
                            <div class="feeding-form__row">
                                <textarea name="note" placeholder="Poznámka" class="egg-form__note feeding-form__note" rows="1"></textarea>
                                <div class="form-buttons">
                                    <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                                    <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.feeding.hideForm('record')">Zrušit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="maintenance-table-wrap">
                        <table class="maintenance-table feeding-table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Krmivo</th>
                                    <th>kg</th>
                                    <th>Cena</th>
                                    <th>Poznámka</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="feeding-record-table-body">
                                <?php foreach ($recentRecords as $row): ?>
                                <tr data-id="<?= $row['id'] ?>"
                                    data-date="<?= $row['record_date'] ?>"
                                    data-feed-type-id="<?= $row['feed_type_id'] ?>"
                                    data-amount="<?= $row['amount_kg'] ?>"
                                    data-note="<?= htmlspecialchars($row['note'] ?? '') ?>">
                                    <td><?= date('d.m.Y', strtotime($row['record_date'])) ?></td>
                                    <td><?= htmlspecialchars($row['feed_type_name']) ?></td>
                                    <td><?= number_format((float)$row['amount_kg'], 2, ',', ' ') ?></td>
                                    <td><?= number_format((float)$row['amount_kg'] * (float)$row['price_per_kg'], 0, ',', ' ') ?> Kč</td>
                                    <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
                                    <td class="maintenance-actions">
                                        <button class="btn-icon" onclick="App.feeding.editRecord(this.closest('tr'))" title="Upravit">&#x270E;</button>
                                        <button class="btn-icon btn-icon--danger" onclick="App.feeding.removeRecord(<?= $row['id'] ?>)" title="Smazat">&times;</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Typy krmiva -->
            <div class="card">
                <div class="card__header card__header--feeding-type">
                    <span>&#x1F4CB; Typy krmiva</span>
                    <button type="button" class="btn btn--primary btn--round" onclick="App.feeding.toggleForm('type')">Přidat</button>
                </div>
                <div class="card__inner">
                    <div id="feeding-type-form-wrap" class="egg-form-wrap" style="display:none">
                        <form id="feeding-type-form" class="feeding-form">
                            <input type="hidden" name="id" value="">
                            <div class="feeding-form__row">
                                <input type="text" name="name" placeholder="Název krmiva" required class="egg-form__note">
                                <input type="number" name="price_per_kg" step="0.01" min="0" placeholder="Kč/kg" required class="egg-form__count">
                                <select name="palatability" class="egg-form__select">
                                    <option value="">-- Chutnost --</option>
                                    <option value="1">1 – Nechutná</option>
                                    <option value="2">2 – Spíše ne</option>
                                    <option value="3">3 – Nevadí</option>
                                    <option value="4">4 – Chutná</option>
                                    <option value="5">5 – Oblíbené</option>
                                </select>
                            </div>
                            <div class="feeding-form__row">
                                <textarea name="note" placeholder="Poznámka" class="egg-form__note feeding-form__note" rows="1"></textarea>
                                <div class="form-buttons">
                                    <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                                    <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.feeding.hideForm('type')">Zrušit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="maintenance-table-wrap">
                        <table class="maintenance-table feeding-table">
                            <thead>
                                <tr>
                                    <th>Název</th>
                                    <th>Kč/kg</th>
                                    <th>Chutnost</th>
                                    <th>Poznámka</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="feeding-type-table-body">
                                <?php foreach ($feedTypes as $ft): ?>
                                <tr data-id="<?= $ft['id'] ?>"
                                    data-name="<?= htmlspecialchars($ft['name']) ?>"
                                    data-price="<?= $ft['price_per_kg'] ?>"
                                    data-palatability="<?= $ft['palatability'] ?? '' ?>"
                                    data-note="<?= htmlspecialchars($ft['note'] ?? '') ?>"
                                    data-active="<?= $ft['is_active'] ?>"
                                    class="<?= !$ft['is_active'] ? 'row--inactive' : '' ?>">
                                    <td><?= htmlspecialchars($ft['name']) ?><?= !$ft['is_active'] ? ' <small>(neaktivní)</small>' : '' ?></td>
                                    <td><?= number_format((float)$ft['price_per_kg'], 2, ',', ' ') ?></td>
                                    <td class="palatability-stars"><?php
                                        $p = (int)($ft['palatability'] ?? 0);
                                        if ($p > 0) {
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $p ? '&#9733;' : '&#9734;';
                                            }
                                        } else {
                                            echo '–';
                                        }
                                    ?></td>
                                    <td><?= htmlspecialchars($ft['note'] ?? '') ?></td>
                                    <td class="maintenance-actions">
                                        <button class="btn-icon" onclick="App.feeding.editType(this.closest('tr'))" title="Upravit">&#x270E;</button>
                                        <button class="btn-icon btn-icon--danger" onclick="App.feeding.removeType(<?= $ft['id'] ?>)" title="Smazat">&times;</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Nákupy krmiva -->
            <div class="card">
                <div class="card__header card__header--feeding-purchase">
                    <span>&#x1F6CD; Nákupy krmiva</span>
                    <button type="button" class="btn btn--primary btn--round" onclick="App.feeding.toggleForm('purchase')">Přidat</button>
                </div>
                <div class="card__inner">
                    <div id="feeding-purchase-form-wrap" class="egg-form-wrap" style="display:none">
                        <form id="feeding-purchase-form" class="feeding-form">
                            <input type="hidden" name="id" value="">
                            <div class="feeding-form__row">
                                <input type="text" id="feeding-purchase-date" name="purchased_at" placeholder="Datum" required class="egg-form__date">
                                <select name="feed_type_id" required class="egg-form__select">
                                    <option value="">-- Typ krmiva --</option>
                                    <?php foreach ($feedTypes as $ft): ?>
                                        <?php if ($ft['is_active']): ?>
                                        <option value="<?= $ft['id'] ?>"><?= htmlspecialchars($ft['name']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="quantity_kg" step="0.01" min="0.01" placeholder="kg" required class="egg-form__count">
                                <input type="number" name="total_price" step="0.01" min="0" placeholder="Celková cena (Kč)" required class="egg-form__count">
                            </div>
                            <div class="feeding-form__row">
                                <textarea name="note" placeholder="Poznámka" class="egg-form__note feeding-form__note" rows="1"></textarea>
                                <div class="form-buttons">
                                    <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                                    <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.feeding.hideForm('purchase')">Zrušit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="maintenance-table-wrap">
                        <table class="maintenance-table feeding-table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Krmivo</th>
                                    <th>kg</th>
                                    <th>Cena</th>
                                    <th>Kč/kg</th>
                                    <th>Poznámka</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="feeding-purchase-table-body">
                                <?php foreach ($purchases as $row): ?>
                                <tr data-id="<?= $row['id'] ?>"
                                    data-date="<?= $row['purchased_at'] ?>"
                                    data-feed-type-id="<?= $row['feed_type_id'] ?>"
                                    data-quantity="<?= $row['quantity_kg'] ?>"
                                    data-price="<?= $row['total_price'] ?>"
                                    data-note="<?= htmlspecialchars($row['note'] ?? '') ?>">
                                    <td><?= date('d.m.Y', strtotime($row['purchased_at'])) ?></td>
                                    <td><?= htmlspecialchars($row['feed_type_name']) ?></td>
                                    <td><?= number_format((float)$row['quantity_kg'], 1, ',', ' ') ?></td>
                                    <td><?= number_format((float)$row['total_price'], 0, ',', ' ') ?> Kč</td>
                                    <td><?= $row['quantity_kg'] > 0 ? number_format((float)$row['total_price'] / (float)$row['quantity_kg'], 1, ',', ' ') : '–' ?></td>
                                    <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
                                    <td class="maintenance-actions">
                                        <button class="btn-icon btn-icon--danger" onclick="App.feeding.removePurchase(<?= $row['id'] ?>)" title="Smazat">&times;</button>
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
        <div class="feeding__right">

            <!-- Přehled statistik -->
            <div class="card">
                <div class="card__header card__header--feeding">&#x1F4CA; Přehled (za 30 dní)</div>
                <div class="stat-grid">
                    <div class="stat-card stat-card--feeding">
                        <span class="stat-card__value" id="stat-total-kg"><?= number_format($totalKgMonth, 1, ',', ' ') ?> kg</span>
                        <span class="stat-card__label">Spotřeba</span>
                    </div>
                    <div class="stat-card stat-card--feeding">
                        <span class="stat-card__value" id="stat-total-cost"><?= number_format($totalCostMonth, 0, ',', ' ') ?> Kč</span>
                        <span class="stat-card__label">Náklady</span>
                    </div>
                    <div class="stat-card stat-card--feeding">
                        <span class="stat-card__value" id="stat-daily-avg"><?= number_format($dailyAvg, 1, ',', ' ') ?> kg</span>
                        <span class="stat-card__label">Průměr / den</span>
                    </div>
                </div>
            </div>

            <!-- Graf spotřeby -->
            <div class="card">
                <div class="card__header card__header--red">
                    <span>&#x1F4C9; Spotřeba</span>
                    <div class="period-toggle" id="consumption-period-toggle">
                        <button class="period-toggle__btn is-active" data-period="week">Týden</button>
                        <button class="period-toggle__btn" data-period="month">Měsíc</button>
                        <button class="period-toggle__btn" data-period="year">Rok</button>
                    </div>
                </div>
                <div class="card__inner">
                    <div class="chart-container">
                        <canvas id="chart-feeding-consumption"></canvas>
                    </div>
                </div>
            </div>

            <!-- Graf nákladů -->
            <div class="card">
                <div class="card__header card__header--feeding-cost">
                    <span>&#x1F4B0; Náklady</span>
                    <div class="period-toggle" id="cost-period-toggle">
                        <button class="period-toggle__btn" data-period="6">6 měsíců</button>
                        <button class="period-toggle__btn is-active" data-period="12">Rok</button>
                    </div>
                </div>
                <div class="card__inner">
                    <div class="chart-container">
                        <canvas id="chart-feeding-cost"></canvas>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<script>
window.__feedingData = {
    feedTypes: <?= json_encode($feedTypes) ?>,
    recentRecords: <?= json_encode($recentRecords) ?>,
    purchases: <?= json_encode($purchases) ?>
};
</script>
