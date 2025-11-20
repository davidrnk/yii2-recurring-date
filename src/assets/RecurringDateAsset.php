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
        'css/recurring-date.css',
    ];
    public $js = [
        'js/recurring-date.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
}
