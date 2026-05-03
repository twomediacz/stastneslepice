<?php
$isLoggedIn = \App\Core\Auth::check();
?>
<div class="card">
    <div class="card__header card__header--note">
        <span>&#x1F4DD; Deník chovatele</span>
        <?php if ($isLoggedIn): ?>
        <button type="button" class="btn btn--primary btn--round" onclick="App.notes.toggleForm()">Přidat</button>
        <?php endif; ?>
    </div>
    <div class="card__inner">
        <?php if ($isLoggedIn): ?>
        <div id="note-form-wrap" class="egg-form-wrap" style="display:none">
            <form id="note-form" class="egg-form">
                <input type="hidden" name="id" value="">
                <input type="text" id="note-date" name="note_date" placeholder="Datum" required class="egg-form__date" readonly>
                <textarea name="content" placeholder="Poznámka" required class="egg-form__note" rows="1"></textarea>
                <div class="form-buttons">
                    <button type="submit" class="btn btn--primary btn--round btn--small">Uložit</button>
                    <button type="button" class="btn btn--outline btn--round btn--small" onclick="App.notes.hideForm()">Zrušit</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        <div class="maintenance-table-wrap">
            <table class="maintenance-table notes-table">
                <tbody id="notes-table-body">
                    <?php foreach ($notes as $note): ?>
                    <tr data-id="<?= $note['id'] ?>" data-date="<?= $note['note_date'] ?>" data-content="<?= htmlspecialchars($note['content']) ?>">
                        <td>
                            <div class="note-entry">
                                <div class="note-entry__main">
                                    <span class="note-entry__date"><?= date('d.m.Y', strtotime($note['note_date'])) ?></span>
                                    <span class="note-entry__text"><?= htmlspecialchars($note['content']) ?></span>
                                    <div class="note-entry__audio" hidden></div>
                                </div>
                                <button type="button" class="btn btn--outline btn--round btn--small note-entry__speak" onclick="App.notes.toggleSpeech(this.closest('tr'))" title="Přečíst záznam nahlas">Přehrát</button>
                            </div>
                        </td>
                        <?php if ($isLoggedIn): ?>
                        <td class="maintenance-actions">
                            <button class="btn-icon" onclick="App.notes.edit(this.closest('tr'))" title="Upravit">&#x270E;</button>
                            <button class="btn-icon btn-icon--danger" onclick="App.notes.remove(<?= $note['id'] ?>)" title="Smazat">&times;</button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
