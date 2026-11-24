<section class="auth-page">
    <div class="auth-card">
        <h2>Registrace</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/register">
            <div class="form-group">
                <label for="username">Uživatelské jméno</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($username ?? '') ?>"
                       required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Heslo</label>
                <input type="password" id="password" name="password"
                       required minlength="6">
            </div>
            <div class="form-group">
                <label for="password_confirm">Heslo znovu</label>
                <input type="password" id="password_confirm" name="password_confirm"
                       required minlength="6">
            </div>
            <button type="submit" class="btn btn--primary btn--full">Zaregistrovat se</button>
        </form>

        <p class="auth-link">Máte účet? <a href="/login">Přihlaste se</a></p>
    </div>
</section>
