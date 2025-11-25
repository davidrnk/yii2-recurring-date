<?php

namespace davidrnk\RecurringDate\Widget;

use davidrnk\RecurringDate\assets\RecurringDateAsset;
use davidrnk\RecurringDate\Core\RecurringDateEngine;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\web\View;

/**
 * RecurringDate widget
 *
 * Usage:
 * - With model: ['model' => $model, 'attribute' => 'attribute']
 * - Without model: ['name' => 'recurrence', 'value' => '...']
 */
class RecurringDate extends Widget
{
    /** @var \yii\base\Model|null */
    public $model;

    /** @var string|null attribute when using model */
    public $attribute;

    /** @var string|null name when not using model */
    public $name;

    /** @var mixed initial value (JSON string or array) */
    public $value;

    /** @var array html options for the visible text input */
    public $options = [];

    /** @var array custom labels */
    public $labels = [];

    /** @var string id prefix (auto) */
    protected $prefix;

    public function init()
    {
        parent::init();
        $this->prefix = $this->getId();

        if (!isset($this->options['class'])) {
            $this->options['class'] = 'form-control';

        }

        if (!isset($this->options['readonly'])) {
            $this->options['readonly'] = true;
        }

        if (!isset($this->options['style'])) {
            $this->options['style'] = 'background-color: #e9ecef; cursor: not-allowed;';
        }

        if (!isset($this->options['placeholder'])) {
            $this->options['placeholder'] = 'Configure...';
        }
    }

    public function run()
    {
        $view = $this->getView();
        RecurringDateAsset::register($view);

        $name = $this->name;
        $value = $this->value;

        if ($this->model && $this->attribute) {
            $name = Html::getInputName($this->model, $this->attribute);
            $value = Html::getAttributeValue($this->model, $this->attribute);
        }

        // visible readonly text input
        $textId = $this->prefix . '-text';
        $hiddenId = $this->prefix . '-hidden';
        $btnId = $this->prefix . '-open-btn';

        // render the text input without extra inline JS; view will add rdw-text class
        $visible = Html::textInput(null, $this->formatHumanReadable($value), array_merge($this->options, ['id' => $textId]));

        // hidden input that will hold JSON for backend
        $hidden = Html::hiddenInput($name, $value, ['id' => $hiddenId]);

        // render view
        $out = $this->render('recurring-date', [
            'prefix' => $this->prefix,
            'textInput' => $visible,
            'hiddenInput' => $hidden,
            'button' => null,
            'textId' => $textId,
            'hiddenId' => $hiddenId,
            'btnId' => $btnId,
            'value' => $value,
            'labels' => $this->getLabels(),
            'months' => $this->getMonthsList(),
        ]);

        // Get translations for JavaScript preview
        $translations = $this->getJSTranslations();

        // register initialization script (pass locale + translations)
        $locale = Yii::$app->language ?? 'en-US';

        $translationsJson = json_encode($translations);

        $view->registerJs("jQuery('#{$this->prefix}-widget').recurringDate({prefix: '". $this->prefix ."', locale: '". addslashes($locale) ."', translations: ". $translationsJson ."});", View::POS_READY);

        return $out;
    }

    /**
     * Try to build a human readable label from stored value (if JSON)
     */
    protected function formatHumanReadable($value)
    {
        if (!$value) {
            return '';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return RecurringDateEngine::humanReadable($decoded);
            }
        }

        return is_string($value) ? $value : '';
    }

    /**
     * Get merged labels with defaults for customization
     * @return array
     */
    protected function getLabels()
    {
        $defaults = [
            // Main labels
            'title_modal' => Yii::t('davidrnk.recurring', 'Configure recurring period'),
            'type' => Yii::t('davidrnk.recurring', 'Type'),
            'configure' => Yii::t('davidrnk.recurring', 'Configure'),
            'preview' => Yii::t('davidrnk.recurring', 'Preview'),
            'save' => Yii::t('davidrnk.recurring', 'Save'),
            'cancel' => Yii::t('davidrnk.recurring', 'Cancel'),

            // Type options
            'interval' => Yii::t('davidrnk.recurring', 'Interval'),

            // Interval labels
            'quantity' => Yii::t('davidrnk.recurring', 'Quantity (N)'),
            'unit' => Yii::t('davidrnk.recurring', 'Unit'),

            // Monthly labels
            'month_day' => Yii::t('davidrnk.recurring', 'Month day'),
            // Adjustment option
            'adjust' => Yii::t('davidrnk.recurring', 'Adjustment'),
            'adjust_previous' => Yii::t('davidrnk.recurring', 'Previous (clamp to last day)'),
            'adjust_next' => Yii::t('davidrnk.recurring', 'Next (advance one day)'),
        ];

        return array_merge($defaults, $this->labels);
    }

    /**
     * Get translations for JavaScript preview humanization
     * @return array
     */
    protected function getJSTranslations()
    {
        return [
            'no_expiration' => Yii::t('davidrnk.recurring', 'No expiration'),
            'every' => Yii::t('davidrnk.recurring', 'Every'),
            'day' => Yii::t('davidrnk.recurring', 'day'),
            'days' => Yii::t('davidrnk.recurring', 'days'),
            'month' => Yii::t('davidrnk.recurring', 'month'),
            'months' => Yii::t('davidrnk.recurring', 'months'),
            'year' => Yii::t('davidrnk.recurring', 'year'),
            'years' => Yii::t('davidrnk.recurring', 'years'),
            'every_month_day' => Yii::t('davidrnk.recurring', 'Every month, day {day}.'),
            'every_year_day' => Yii::t('davidrnk.recurring', 'Every year, {day} of {month}.'),
            'on_date' => Yii::t('davidrnk.recurring', 'Expires on {date}.'),
            // notes for UI warnings about adjustments
            'adjust_months_note' => Yii::t('davidrnk.recurring', 'If the selected day does not exist in a month, the widget will follow the Adjustment setting: "Previous" clamps to the month\'s last valid day; "Next" advances to the following day.'),
            'feb29_note' => Yii::t('davidrnk.recurring', 'Note: if the year is not leap the date will be adjusted to 28; in leap years 29 will be used.'),
            'months_list' => [
                1 => Yii::t('davidrnk.recurring', 'January'),
                2 => Yii::t('davidrnk.recurring', 'February'),
                3 => Yii::t('davidrnk.recurring', 'March'),
                4 => Yii::t('davidrnk.recurring', 'April'),
                5 => Yii::t('davidrnk.recurring', 'May'),
                6 => Yii::t('davidrnk.recurring', 'June'),
                7 => Yii::t('davidrnk.recurring', 'July'),
                8 => Yii::t('davidrnk.recurring', 'August'),
                9 => Yii::t('davidrnk.recurring', 'September'),
                10 => Yii::t('davidrnk.recurring', 'October'),
                11 => Yii::t('davidrnk.recurring', 'November'),
                12 => Yii::t('davidrnk.recurring', 'December'),
            ],
        ];
    }

    /**
     * Get month names translated for the yearly month selector
     * @return array
     */
    protected function getMonthsList()
    {
        return [
            1 => Yii::t('davidrnk.recurring', 'January'),
            2 => Yii::t('davidrnk.recurring', 'February'),
            3 => Yii::t('davidrnk.recurring', 'March'),
            4 => Yii::t('davidrnk.recurring', 'April'),
            5 => Yii::t('davidrnk.recurring', 'May'),
            6 => Yii::t('davidrnk.recurring', 'June'),
            7 => Yii::t('davidrnk.recurring', 'July'),
            8 => Yii::t('davidrnk.recurring', 'August'),
            9 => Yii::t('davidrnk.recurring', 'September'),
            10 => Yii::t('davidrnk.recurring', 'October'),
            11 => Yii::t('davidrnk.recurring', 'November'),
            12 => Yii::t('davidrnk.recurring', 'December'),
        ];
    }
}
