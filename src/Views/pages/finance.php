<section class="finance">

    <div class="finance__main">

        <!-- LEVÝ SLOUPEC -->
        <div class="finance__left">

            <!-- Ostatní náklady -->
            <div class="card">
                <div class="card__header card__header--finance-expense">
                    <span>&#x1F4B8; Ostatní náklady</span>
                    <button type="button" class="btn btn--primary btn--round" onclick="App.finance.toggleForm('expense')">Přidat</button>
                </div>
                <div class="card__inner">
                    <div id="finance-expense-form-wrap" class="egg-form-wrap" style="display:none">
                        <form id="finance-expense-form" class="feeding-form">
                            <input type="hidden" name="id" value="">
                            <div class="feeding-form__row">
                                <input type="text" id="finance-expense-date" name="expense_date" placeholder="Datum" required class="egg-form__date">
                                <select name="category" required class="egg-form__select">
                                    <option value="bedding">Podestýlka</option>
                                    <option value="vet">Veterina</option>
                                    <option value="equipment">Vybavení</option>
                                    <option value="other">Ostatní</option>
                                </select>
                                <input type="number" name="amount" step="0.01" min="0.01" placeholder="Kč" required class="egg-form__count">
                            </div>
                            <div class="feeding-form__row">
                                <input type="text" name="note" placeholder="Poznámka" class="egg-form__note feeding-form__note">
                                <div class="form-buttons">
                                    <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                                    <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.finance.hideForm('expense')">Zrušit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="maintenance-table-wrap">
                        <table class="maintenance-table feeding-table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Kategorie</th>
                                    <th>Částka</th>
                                    <th>Poznámka</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="finance-expense-table-body">
                                <?php foreach ($expenses as $row): ?>
                                <tr data-id="<?= $row['id'] ?>"
                                    data-date="<?= $row['expense_date'] ?>"
                                    data-category="<?= $row['category'] ?>"
                                    data-amount="<?= $row['amount'] ?>"
                                    data-note="<?= htmlspecialchars($row['note'] ?? '') ?>">
                                    <td><?= date('d.m.Y', strtotime($row['expense_date'])) ?></td>
                                    <td><?= \App\Models\Expense::categoryLabel($row['category']) ?></td>
                                    <td><?= number_format((float)$row['amount'], 0, ',', ' ') ?> Kč</td>
                                    <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
                                    <td class="maintenance-actions">
                                        <button class="btn-icon" onclick="App.finance.editExpense(this.closest('tr'))" title="Upravit">&#x270E;</button>
                                        <button class="btn-icon btn-icon--danger" onclick="App.finance.removeExpense(<?= $row['id'] ?>)" title="Smazat">&times;</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Prodej / darování vajec -->
            <div class="card">
                <div class="card__header card__header--finance-revenue">
                    <span>&#x1F95A; Prodej / darování vajec</span>
                    <button type="button" class="btn btn--primary btn--round" onclick="App.finance.toggleForm('egg-tx')">Přidat</button>
                </div>
                <div class="card__inner">
                    <div id="finance-egg-tx-form-wrap" class="egg-form-wrap" style="display:none">
                        <form id="finance-egg-tx-form" class="feeding-form">
                            <input type="hidden" name="id" value="">
                            <div class="feeding-form__row">
                                <input type="text" id="finance-egg-tx-date" name="transaction_date" placeholder="Datum" required class="egg-form__date">
                                <select name="type" required class="egg-form__select" id="finance-egg-tx-type">
                                    <option value="sale">Prodej</option>
                                    <option value="gift">Darování</option>
                                </select>
                                <input type="number" name="quantity" min="1" placeholder="ks" required class="egg-form__count">
                                <input type="number" name="price_total" step="0.01" min="0" placeholder="Kč celkem" class="egg-form__count" id="finance-egg-tx-price">
                            </div>
                            <div class="feeding-form__row">
                                <input type="text" name="recipient" placeholder="Příjemce" class="egg-form__note" style="flex:0.5; min-width:80px;">
                                <input type="text" name="note" placeholder="Poznámka" class="egg-form__note feeding-form__note">
                                <div class="form-buttons">
                                    <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                                    <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.finance.hideForm('egg-tx')">Zrušit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="maintenance-table-wrap">
                        <table class="maintenance-table feeding-table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Typ</th>
                                    <th>ks</th>
                                    <th>Cena</th>
                                    <th>Příjemce</th>
                                    <th>Poznámka</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="finance-egg-tx-table-body">
                                <?php foreach ($eggTransactions as $row): ?>
                                <tr data-id="<?= $row['id'] ?>"
                                    data-date="<?= $row['transaction_date'] ?>"
                                    data-type="<?= $row['type'] ?>"
                                    data-quantity="<?= $row['quantity'] ?>"
                                    data-price="<?= $row['price_total'] ?>"
                                    data-recipient="<?= htmlspecialchars($row['recipient'] ?? '') ?>"
                                    data-note="<?= htmlspecialchars($row['note'] ?? '') ?>">
                                    <td><?= date('d.m.Y', strtotime($row['transaction_date'])) ?></td>
                                    <td><span class="badge badge--<?= $row['type'] ?>"><?= \App\Models\EggTransaction::typeLabel($row['type']) ?></span></td>
                                    <td><?= $row['quantity'] ?></td>
                                    <td><?= $row['type'] === 'sale' ? number_format((float)$row['price_total'], 0, ',', ' ') . ' Kč' : '–' ?></td>
                                    <td><?= htmlspecialchars($row['recipient'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
                                    <td class="maintenance-actions">
                                        <button class="btn-icon" onclick="App.finance.editEggTransaction(this.closest('tr'))" title="Upravit">&#x270E;</button>
                                        <button class="btn-icon btn-icon--danger" onclick="App.finance.removeEggTransaction(<?= $row['id'] ?>)" title="Smazat">&times;</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Náklady podle kategorie (doughnut) -->
            <div class="card">
                <div class="card__header card__header--finance-category">
                    <span>&#x1F4CA; Náklady podle kategorie</span>
                </div>
                <div class="card__inner">
                    <div class="chart-container">
                        <canvas id="chart-finance-category"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <!-- PRAVÝ SLOUPEC -->
        <div class="finance__right">

            <!-- Přehled -->
            <div class="card">
                <div class="card__header card__header--finance">&#x1F4B0; Finance &ndash; Přehled (12 měsíců)</div>
                <div class="stat-grid2">
                    <div class="stat-card stat-card--finance-expense">
                        <span class="stat-card__value" id="fin-total-costs"><?= number_format($totalCosts, 0, ',', ' ') ?> Kč</span>
                        <span class="stat-card__label">Náklady celkem</span>
                    </div>
                    <div class="stat-card stat-card--finance-revenue">
                        <span class="stat-card__value" id="fin-egg-revenue"><?= number_format($eggRevenue, 0, ',', ' ') ?> Kč</span>
                        <span class="stat-card__label">Příjmy z vajec</span>
                    </div>
                    <div class="stat-card <?= $balance >= 0 ? 'stat-card--finance-positive' : 'stat-card--finance-negative' ?>">
                        <span class="stat-card__value" id="fin-balance"><?= ($balance >= 0 ? '+' : '') . number_format($balance, 0, ',', ' ') ?> Kč</span>
                        <span class="stat-card__label">Bilance</span>
                    </div>
                    <div class="stat-card stat-card--finance-eggs">
                        <span class="stat-card__value"><?= number_format($totalEggs, 0, ',', ' ') ?></span>
                        <span class="stat-card__label">Vajec celkem</span>
                    </div>
                </div>
            </div>

            <!-- Graf měsíčních nákladů a příjmů -->
            <div class="card">
                <div class="card__header card__header--finance-chart">
                    <span>&#x1F4C8; Měsíční přehled</span>
                    <div class="period-toggle" id="finance-period-toggle">
                        <button class="period-toggle__btn" data-period="6">6 měsíců</button>
                        <button class="period-toggle__btn is-active" data-period="12">Rok</button>
                    </div>
                </div>
                <div class="card__inner">
                    <div class="chart-container">
                        <canvas id="chart-finance-monthly"></canvas>
                    </div>
                </div>
            </div>

            <!-- Hodnota vajec -->
            <div class="card">
                <div class="card__header card__header--finance-value">
                    <span>&#x1F95A; Hodnota vajec</span>
                    <button class="btn btn--outline btn--round btn--small" onclick="App.finance.toggleEggPriceForm()" style="color:#fff;border-color:rgba(255,255,255,0.5)">Cena v obchodě</button>
                </div>
                <div class="card__inner">
                    <div id="egg-price-form-wrap" style="display:none; padding-top:0.8rem; padding-bottom:0.8rem;">
                        <form id="egg-price-form" class="feeding-form__row" style="display:flex;gap:0.4rem;align-items:center;">
                            <label style="font-size:0.85rem;white-space:nowrap;">Cena vejce v obchodě:</label>
                            <input type="number" name="egg_market_price" step="0.10" min="0.10" value="<?= $eggMarketPrice ?>" class="egg-form__count" style="width:80px;" required>
                            <span style="font-size:0.85rem;">Kč</span>
                            <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                        </form>
                    </div>
                    <div class="egg-value-grid">
                        <div class="egg-value-row">
                            <span class="egg-value-label">Vlastní náklady / vejce</span>
                            <span class="egg-value-amount"><?= number_format($costPerEgg, 2, ',', ' ') ?> Kč</span>
                        </div>
                        <div class="egg-value-row">
                            <span class="egg-value-label">Cena v obchodě</span>
                            <span class="egg-value-amount"><?= number_format($eggMarketPrice, 2, ',', ' ') ?> Kč</span>
                        </div>
                        <div class="egg-value-row egg-value-row--highlight">
                            <span class="egg-value-label">Tržní hodnota všech vajec</span>
                            <span class="egg-value-amount"><?= number_format($eggMarketValue, 0, ',', ' ') ?> Kč</span>
                        </div>
                        <div class="egg-value-row egg-value-row--result <?= $eggSavings >= 0 ? 'egg-value-row--positive' : 'egg-value-row--negative' ?>">
                            <span class="egg-value-label">Úspora oproti obchodu</span>
                            <span class="egg-value-amount"><?= ($eggSavings >= 0 ? '+' : '') . number_format($eggSavings, 0, ',', ' ') ?> Kč</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Celková bilance -->
            <div class="card">
                <div class="card__header card__header--finance-balance">&#x2696;&#xFE0F; Celková bilance (12 měsíců)</div>
                <div class="card__inner">
                    <div class="balance-table">
                        <div class="balance-row balance-row--expense">
                            <span class="balance-label">Krmivo</span>
                            <span class="balance-amount">-<?= number_format($feedCosts, 0, ',', ' ') ?> Kč</span>
                        </div>
                        <div class="balance-row balance-row--expense">
                            <span class="balance-label">Ostatní náklady</span>
                            <span class="balance-amount">-<?= number_format($otherCosts, 0, ',', ' ') ?> Kč</span>
                        </div>
                        <div class="balance-row balance-row--revenue">
                            <span class="balance-label">Prodej vajec</span>
                            <span class="balance-amount">+<?= number_format($eggRevenue, 0, ',', ' ') ?> Kč</span>
                        </div>
                        <div class="balance-row balance-row--total <?= $balance >= 0 ? 'balance-row--positive' : 'balance-row--negative' ?>">
                            <span class="balance-label">Bilance</span>
                            <span class="balance-amount"><?= ($balance >= 0 ? '+' : '') . number_format($balance, 0, ',', ' ') ?> Kč</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<script>
window.__financeData = {
    expenses: <?= json_encode($expenses) ?>,
    eggTransactions: <?= json_encode($eggTransactions) ?>,
    eggMarketPrice: <?= $eggMarketPrice ?>
};
</script>
