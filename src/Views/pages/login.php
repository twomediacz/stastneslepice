<section class="auth-page">
    <div class="auth-card">
        <h2>Přihlášení</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/login">
            <div class="form-group">
                <label for="username">Uživatelské jméno</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($username ?? '') ?>"
                       required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Heslo</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn--primary btn--full">Přihlásit se</button>
        </form>

    </div>
</section>
