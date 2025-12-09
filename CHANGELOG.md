# Changelog

## 1.0.3 - 2025-12-09
### Fixed
- Fixed the recurring date input visual update; the widget now updates the visible text input when a user modifies a preloaded value.

## 1.0.2 - 2025-12-08
### Fixed
- Corrected case-sensitivity issue in production environments by renaming the directory `core` to `Core` to fully align with PSR-4 autoloading rules.
- Resolved autoloading failures for `RecurringDateEngine` in Linux deployments.

### Improved
- Updated internal paths and namespaces to ensure consistent class discovery across Windows and Linux.
- Minor refactor in widget initialization to strengthen cross-environment compatibility.

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
