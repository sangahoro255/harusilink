<div class="form-row">
  <div>
    <label for="bride_name"><?= e(t('wedding.field.bride')) ?></label>
    <input id="bride_name" name="bride_name" type="text" maxlength="100" value="<?= e($old['bride_name'] ?? '') ?>" required>
  </div>
  <div>
    <label for="groom_name"><?= e(t('wedding.field.groom')) ?></label>
    <input id="groom_name" name="groom_name" type="text" maxlength="100" value="<?= e($old['groom_name'] ?? '') ?>" required>
  </div>
</div>

<div class="form-row">
  <div>
    <label for="wedding_date"><?= e(t('wedding.field.date')) ?></label>
    <input id="wedding_date" name="wedding_date" type="date" value="<?= e($old['wedding_date'] ?? '') ?>" required>
  </div>
  <div>
    <label for="wedding_time"><?= e(t('wedding.field.time_optional')) ?></label>
    <input id="wedding_time" name="wedding_time" type="time" value="<?= e($old['wedding_time'] ?? '') ?>">
  </div>
</div>

<label for="venue"><?= e(t('wedding.field.venue')) ?></label>
<input id="venue" name="venue" type="text" maxlength="255" value="<?= e($old['venue'] ?? '') ?>" required>

<label for="description"><?= e(t('wedding.field.description_optional')) ?></label>
<textarea id="description" name="description" maxlength="2000" rows="4"><?= e($old['description'] ?? '') ?></textarea>