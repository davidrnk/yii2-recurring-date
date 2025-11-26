<?php

namespace davidrnk\RecurringDate\assets;

use yii\web\AssetBundle;

/**
 * Class RecurringDateAsset
 *
 * Registers JS and CSS for the RecurringDate widget.
 */
class RecurringDateAsset extends AssetBundle
{
    public $sourcePath = '@davidrnk/RecurringDate/assets';

    public $css = [
        'css/recurring-date.css?v1.0.0',
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css'
    ];
    public $js = [
        'js/recurring-date.js?v1.0.0',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
}
