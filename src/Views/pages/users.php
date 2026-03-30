<?php
$roles = ['admin' => 'Administrátor'];
$currentUserId = \App\Core\Auth::user()['id'] ?? 0;
?>

<section class="maintenance">
    <div class="maintenance__grid">
        <div class="card">
            <div class="card__header card__header--blue">
                <span>&#x1F4F9; Kamery – YouTube streamy</span>
            </div>
            <div class="card__inner">
                <form id="streams-form" class="streams-form">
                    <div class="streams-form__field">
                        <label for="stream-url-1">Kurník – YouTube URL</label>
                        <input type="url" id="stream-url-1" name="youtube_livestream_url" placeholder="https://www.youtube.com/watch?v=..." value="<?= htmlspecialchars($settings['youtube_livestream_url'] ?? '') ?>">
                    </div>
                    <div class="streams-form__field">
                        <label for="stream-url-2">Výběh – YouTube URL</label>
                        <input type="url" id="stream-url-2" name="youtube_livestream_url_2" placeholder="https://www.youtube.com/watch?v=..." value="<?= htmlspecialchars($settings['youtube_livestream_url_2'] ?? '') ?>">
                    </div>
                    <div class="form-buttons">
                        <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card__header card__header--brown">
                <span>&#x1F465; Správa uživatelů</span>
                <button type="button" class="btn btn--primary btn--round" onclick="App.users.toggleForm()">Přidat uživatele</button>
            </div>
            <div class="card__inner">
                <div id="user-form-wrap" class="egg-form-wrap" style="display:none">
                    <form id="user-form" class="egg-form">
                        <input type="hidden" name="id" value="">
                        <input type="text" name="username" placeholder="Uživatelské jméno" required class="egg-form__note">
                        <input type="password" name="password" placeholder="Heslo (min. 6 znaků)" class="egg-form__note">
                        <div class="form-buttons">
                            <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                            <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.users.hideForm()">Zrušit</button>
                        </div>
                    </form>
                </div>
                <div class="maintenance-table-wrap">
                    <table class="maintenance-table">
                        <thead>
                            <tr>
                                <th>Uživatelské jméno</th>
                                <th>Role</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <?php foreach ($users as $u): ?>
                            <tr data-id="<?= $u['id'] ?>" data-username="<?= htmlspecialchars($u['username']) ?>" data-role="<?= htmlspecialchars($u['role']) ?>">
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= $roles[$u['role']] ?? htmlspecialchars($u['role']) ?></td>
                                <td class="maintenance-actions">
                                    <button class="btn-icon" onclick="App.users.edit(this.closest('tr'))" title="Upravit">&#x270E;</button>
                                    <?php if ($u['id'] != $currentUserId): ?>
                                    <button class="btn-icon btn-icon--danger" onclick="App.users.remove(<?= $u['id'] ?>)" title="Smazat">&times;</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
