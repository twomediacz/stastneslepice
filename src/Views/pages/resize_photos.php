<section class="resize-photos">
    <div class="card">
        <div class="card__header card__header--photo">
            <span>&#x1F4F7; Zmenšení existujících fotek</span>
        </div>
        <div class="card__inner">
            <p class="text-muted">
                <?= $isRun ? 'Ostré spuštění dokončeno.' : 'Suchý běh: zatím se nic neupravilo.' ?>
            </p>

            <form method="post" class="resize-photos__form">
                <input type="hidden" name="confirm" value="1">
                <label>
                    Max. delší strana
                    <input type="number" name="max" value="<?= htmlspecialchars((string) $max) ?>" min="1">
                </label>
                <label class="resize-photos__check">
                    <input type="hidden" name="backup" value="0">
                    <input type="checkbox" name="backup" value="1" <?= $backup ? 'checked' : '' ?>>
                    Vytvořit .bak zálohy
                </label>
                <button type="submit" class="btn btn--primary btn--round">Spustit zmenšení</button>
                <a href="/nastaveni/resize-fotky?max=<?= urlencode((string) $max) ?>&backup=<?= $backup ? '1' : '0' ?>" class="btn btn--outline btn--round">Znovu suchý běh</a>
            </form>

            <pre class="resize-photos__output"><?= htmlspecialchars(implode("\n", $result['lines'])) ?></pre>
        </div>
    </div>
</section>
