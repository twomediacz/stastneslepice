<div class="live-page">
    <?php foreach ($streams as $stream): ?>
    <div class="live-card card">
        <div class="card__header card__header--<?= $stream['color'] ?>">&#x1F4F9; <?= htmlspecialchars($stream['label']) ?></div>
        <div class="card__inner">
            <div class="live-video">
                <?php if ($stream['embedUrl']): ?>
                    <iframe src="<?= htmlspecialchars($stream['embedUrl']) ?>" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                <?php else: ?>
                    <div class="livestream-placeholder">
                        <p class="text-muted">Živý přenos není nastaven.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
