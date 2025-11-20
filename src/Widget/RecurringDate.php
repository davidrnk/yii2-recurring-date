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
        ]);

        // register initialization script (pass locale)
        $locale = Yii::$app->language ?? 'en-US';
        $view->registerJs("jQuery('#{$this->prefix}-widget').recurringDate({prefix: '". $this->prefix ."', locale: '". addslashes($locale) ."'});", View::POS_READY);

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
}
