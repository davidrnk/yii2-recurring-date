# yii2-recurring-date

A Yii2 extension/widget that provides a simple and intuitive interface to define and manage recurring date patterns. It is designed to simplify the configuration of renewals, expirations, and periodic events, with clean integration into Yii2 forms and internationalization support.

## Main Features

- Visual interface to configure recurring periods: no expiration, interval (days/months/years), monthly (day of the month), yearly (day + month), and specific date.
- Handling of edge cases (e.g., days 29/30/31 and February 29) with configurable adjustment policy (`previous` | `next`).
- Persistence of the recurrence scheme in a hidden field as JSON ready to be sent to the server.
- Backend function to calculate the next expiration date based on a base date and the configuration.
- Localization (i18n) with translations in English and Spanish.

## Installation

Install the extension with Composer:

```bash
composer require davidrnk/yii2-recurring-date
```

Register the asset (if the widget does not do it automatically) and add the widget to your views/forms according to the usage examples.

## Usage

The extension can be used with Yii2 models (ActiveForm) or independently.

Usage with model (ActiveForm):

```php
use davidrnk\RecurringDate\Widget\RecurringDate;

echo $form->field($model, 'recurrence_config')->widget(RecurringDate::class, [
    // options
    'options' => ['class' => 'form-control my-custom-class'],
    'labels' => [
        'title_modal' => 'Configure recurrence',
        // you can override other labels
    ],
]);
```

Usage without model:

```php
echo davidrnk\RecurringDate\Widget\RecurringDate::widget([
    'name' => 'recurrence',
    'value' => json_encode(['type' => 'monthly', 'day' => 1]),
    'options' => ['class' => 'form-control'],
]);
```

The widget renders a read-only text control with a button to open a modal where recurrence is configured. The resulting JSON is saved in a hidden field (`input.hidden`) and is the content you should store in the database.

## JSON Format (persisted)

The widget persists the configuration in JSON format with the following main structure (examples):

- No expiration

```json
{"type": "no_expiration"}
```

- Interval

```json
{ "type": "interval", "value": 10, "unit": "days" }
```

- Monthly (day of the month) + optional adjustment

```json
{ "type": "monthly", "day": 31, "adjust": "previous" }
```

- Yearly (day + month) + optional adjustment

```json
{ "type": "yearly", "day": 29, "month": 2, "adjust": "previous" }
```

- Specific date

```json
{ "type": "specific_date", "date": "2025-12-31" }
```

Relevant keys:
- `type`: one of `no_expiration`, `interval`, `monthly`, `yearly`, `specific_date`.
- `value`, `unit`: used by `interval` (unit: `days|months|years`).
- `day`: day of the month (1-31).
- `month`: month (1-12).
- `date`: ISO date for `specific_date`.
- `adjust`: policy when the day does not exist in the period (values: `previous` — adjust to the last valid day of the month, or `next` — move to the next day).

## Calculation of the Next Expiration Date

In the backend, the library provides a function to calculate the resulting date based on a base date and the JSON configuration. In the code the function is called:

```php
use davidrnk\RecurringDate\Core\RecurringDateEngine;

$nextDueDate = RecurringDateEngine::calculateExpiration($startDate, $configArray);
// returns DateTime instance or null if configuration is invalid
```

In the documentation and examples of this README, we refer to this date as `nextDueDate`. If `calculateExpiration` returns `null`, the combination of parameters is invalid or could not be calculated.

Quick example:

```php
$start = new \DateTime('2025-01-31');
$cfg = ['type' => 'monthly', 'day' => 31, 'adjust' => 'previous'];
$next = RecurringDateEngine::calculateExpiration($start, $cfg);
echo $next ? $next->format('Y-m-d') : 'invalid';
```

## Configuration and Customization

The widget exposes several ways to adjust its visual and textual behavior:

- `options` (array): HTML attributes for the visible text field (e.g., `class`, `style`, `placeholder`).
- `labels` (array): you can override texts and labels used in the modal. Examples of keys you can customize:
  - `title_modal`, `type`, `configure`, `preview`, `save`, `cancel`, `quantity`, `unit`, `month_day`, `adjust`, `adjust_previous`, `adjust_next`, etc.
- Translations: the extension includes files in `src/messages/en` and `src/messages/es`. The displayed texts are also sent to JavaScript for preview.

Example of label customization:

```php
echo $form->field($model, 'recurrence_config')->widget(RecurringDate::class, [
    'labels' => [
        'title_modal' => 'Schedule repetition',
        'adjust_previous' => 'Adjust to the last day of the month',
    ],
]);
```

## Validations and UX Behavior

- The widget validates on the client side combinations that are clearly invalid (e.g., 31 in months with 30 days, February 31) and blocks saving when the selection is fatal.
- For non-fatal cases (e.g., day >= 29 in monthly or February 29 in yearly), it shows a warning and allows the user to select the `adjust` policy.
- The `adjust` value is persisted in JSON and is considered by `RecurringDateEngine::calculateExpiration`.

## Internationalization (i18n)

The default language of the extension is English. Translations are included in `src/messages/en` and `src/messages/es`. Strings used in views and JavaScript translations are defined and loaded from `RecurringDate::getJSTranslations()`.

If you need to add another language, add a file in `src/messages/XX/davidrnk.recurring.php` with the required keys.

## Tests

Unit tests for PHP are included for the calculation logic (`tests/RecurringDateEngineTest.php`) and should be executed with:

```bash
vendor/bin/phpunit tests/RecurringDateEngineTest.php
```

## Best Practices and Notes

- Save the persisted JSON directly in a text field in the database (e.g., `recurrence_config`), and use `RecurringDateEngine::calculateExpiration` to obtain the next expiration date when needed.
- Decide and document the default `adjust` policy for your domain (by default the extension uses `previous` — clamp to the last valid day). This avoids surprises when calculating next dates.
- Review the locale configuration (`Yii::$app->language`) to ensure the UI displays the desired translations.

## Contributing

Pull requests and issues are welcome. For major changes, first open an issue describing the proposed change.

## License

BSD-3-Clause — see `LICENSE` file.
