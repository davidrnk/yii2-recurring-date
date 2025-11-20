<?php
/** @var string $prefix */
/** @var string $textInput */
/** @var string $hiddenInput */
/** @var string $button */
/** @var string $textId */
/** @var string $hiddenId */
/** @var string $btnId */
/** @var mixed $value */

use davidrnk\RecurringDate\Core\RecurringDateEngine;
use yii\helpers\Html;

$human = '';
if (is_string($value)) {
    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        // use engine if available; RecurringDate::formatHumanReadable already delegated,
        // but we include data-human for the JS to read if present.
        $human = RecurringDateEngine::humanReadable($decoded);
    } else {
        $human = (string)$value;
    }
}
?>
<div class="recurring-date-widget" id="<?= Html::encode($prefix) ?>-widget" data-prefix="<?= Html::encode($prefix) ?>">
    <div class="input-group">
        <?= Html::button(Yii::t('app', 'Configure'), ['type' => 'button', 'class' => 'btn btn-outline-primary rdw-open-btn', 'id' => Html::encode($btnId)]) ?>
        <?= strtr($textInput, ['id="'.Html::encode($textId).'"' => 'id="'.Html::encode($textId).'" class="form-control rdw-text"']) ?>
    </div>

    <?php
    // ensure hidden input has the class and a data-human attr
    $hiddenInput = str_replace('id="'.Html::encode($hiddenId).'"', 'id="'.Html::encode($hiddenId).'" class="rdw-hidden" data-human="'.Html::encode($human).'"', $hiddenInput);
    echo $hiddenInput;
    ?>

    <!-- Modal (scoped inside container) -->
    <div class="modal fade rdw-modal" id="<?= Html::encode($prefix) ?>-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><?= Yii::t('app', 'Configure recurring period') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <!-- Type -->
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold"><?= Yii::t('app', 'Type') ?></label>
                            <select class="form-select rdw-type">
                                <option value="no_expiration"><?= Yii::t('app', 'No expiration') ?></option>
                                <option value="interval"><?= Yii::t('app', 'Interval') ?></option>
                                <option value="monthly"><?= Yii::t('app', 'Monthly (Day of the month)') ?></option>
                                <option value="yearly"><?= Yii::t('app', 'Yearly (Day and month)') ?></option>
                                <option value="specific_date"><?= Yii::t('app', 'Specific date') ?></option>
                            </select>
                        </div>

                        <!-- Interval -->
                        <div class="col-12 rdw-section-interval">
                            <div class="mb-3 p-3 border rounded" style="background: #f9f9f9;">
                                <h6><i class="bi bi-hourglass-split"></i> <?= Yii::t('app', 'Interval') ?></h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label"><?= Yii::t('app', 'Quantity (N)') ?></label>
                                        <input type="number" class="form-control rdw-interval-value" min="1" max="999" value="1">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><?= Yii::t('app', 'Unit') ?></label>
                                        <select class="form-select rdw-interval-unit">
                                            <option value="days"><?= Yii::t('app', 'Days') ?></option>
                                            <option value="months"><?= Yii::t('app', 'Months') ?></option>
                                            <option value="years"><?= Yii::t('app', 'Years') ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly -->
                        <div class="col-12 d-none rdw-section-monthly">
                            <div class="mb-3 p-3 border rounded" style="background: #f9f9f9;">
                                <h6><i class="bi bi-calendar-month"></i> <?= Yii::t('app', 'Monthly') ?></h6>
                                <label class="form-label"><?= Yii::t('app', 'Month day') ?> (1-31)</label>
                                <input type="number" class="form-control rdw-monthly-day" min="1" max="31" value="1">
                            </div>
                        </div>

                        <!-- Yearly -->
                        <div class="col-12 d-none rdw-section-yearly">
                            <div class="mb-3 p-3 border rounded" style="background: #f9f9f9;">
                                <h6><i class="bi bi-calendar-year"></i> <?= Yii::t('app', 'Yearly') ?></h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label"><?= Yii::t('app', 'Day') ?></label>
                                        <input type="number" class="form-control rdw-yearly-day" min="1" max="31" value="31">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"><?= Yii::t('app', 'Month') ?></label>
                                        <select class="form-select rdw-yearly-month">
                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?= $m ?>"><?= \DateTime::createFromFormat('!m', $m)->format('F') ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Specific date -->
                        <div class="col-12 d-none rdw-section-specific">
                            <div class="mb-3 p-3 border rounded" style="background: #f9f9f9;">
                                <h6><i class="bi bi-calendar-day"></i> <?= Yii::t('app', 'Specific date') ?></h6>
                                <label class="form-label"><?= Yii::t('app', 'Select the date') ?></label>
                                <input type="date" class="form-control rdw-specific-date">
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold"><?= Yii::t('app', 'Preview') ?></label>
                            <div class="form-control rdw-preview" style="min-height:44px;"></div>
                            <div class="form-text text-danger rdw-validation"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= Yii::t('app', 'Cancel') ?></button>
                    <button type="button" class="btn btn-primary rdw-save"><?= Yii::t('app', 'Save') ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
