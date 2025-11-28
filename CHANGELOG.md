# Changelog

## 1.0.1 - 2025-11-28
### Fixed
- Full compatibility with nested and multiple widgets:

  - The widget now prioritizes the `name` attribute passed in `options['name']`, then `$this->name`, and finally model+attribute.
  - This ensures that hidden fields correctly generate indexed/nested names, both initial and dynamic.

## 1.0.0 - 2025-11-26 - Initial Release

### Added
- Initial release of the Yii2 Recurring Date widget/extension.
- Visual UI to configure recurring periods:
  - No expiration
  - Interval (days, months, years)
  - Monthly recurrence by day of month
  - Yearly recurrence by day and month
  - Specific date selection
- JSON configuration persistence through hidden input fields.
- Backend calculation engine (`RecurringDateEngine`) to compute next due dates.
- Handling of edge-case dates (29/30/31, February 29) with configurable `adjust` policies.
- Internationalization (i18n) support with English and Spanish translations.
- Client-side validation for invalid and non-fatal recurrence combinations.
- Customizable UI labels, modal texts, and HTML attributes.
- Usage examples for ActiveForm and standalone widget usage.
- PHPUnit tests for core date-calculation logic.
- Documentation covering installation, usage, configuration, and best practices.
