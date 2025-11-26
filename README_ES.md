# yii2-recurring-date

Una extensión / widget de Yii2 que proporciona una interfaz sencilla e intuitiva para definir y gestionar patrones de fechas recurrentes. Está diseñada para facilitar la configuración de renovaciones, vencimientos y eventos periódicos, con integración limpia en formularios de Yii2 y soporte para internacionalización.

## Características principales

- Interfaz visual para configurar periodos recurrentes: sin vencimiento, intervalo (días/meses/años), mensual (día del mes), anual (día + mes) y fecha específica.
- Manejo de casos límite (p. ej. días 29/30/31 y 29 de febrero) con política configurable de ajuste (`previous` | `next`).
- Persistencia del esquema de recurrencia en un campo oculto como JSON listo para enviar al servidor.
- Función backend para calcular la próxima fecha de vencimiento a partir de una fecha base y la configuración.
- Localización (i18n) con traducciones en inglés y español.

## Instalación

Instala la extensión con Composer:

```bash
composer require davidrnk/yii2-recurring-date
```

Registra el asset (si no lo hace el widget automáticamente) y añade el widget en tus vistas/formularios según los ejemplos de uso.

## Uso

La extensión puede usarse con modelos de Yii2 (ActiveForm) o de forma independiente.

Uso con modelo (ActiveForm):

```php
use davidrnk\RecurringDate\Widget\RecurringDate;

echo $form->field($model, 'recurrence_config')->widget(RecurringDate::class, [
    // opciones
    'options' => ['class' => 'form-control my-custom-class'],
    'labels' => [
        'title_modal' => 'Configurar recurrencia',
        // puedes sobrescribir otros labels
    ],
]);
```

Uso sin modelo:

```php
echo davidrnk\RecurringDate\Widget\RecurringDate::widget([
    'name' => 'recurrence',
    'value' => json_encode(['type' => 'monthly', 'day' => 1]),
    'options' => ['class' => 'form-control'],
]);
```

El widget renderiza un control de texto de sólo lectura con un botón para abrir un modal donde se configura la recurrencia. El JSON resultante se guarda en un campo oculto (`input.hidden`) y es el contenido que debes almacenar en la base de datos.

## Formato JSON (persistido)

El widget persiste la configuración en formato JSON con la siguiente estructura principal (ejemplos):

- Sin vencimiento

```json
{"type": "no_expiration"}
```

- Intervalo

```json
{ "type": "interval", "value": 10, "unit": "days" }
```

- Mensual (día del mes) + ajuste opcional

```json
{ "type": "monthly", "day": 31, "adjust": "previous" }
```

- Anual (día + mes) + ajuste opcional

```json
{ "type": "yearly", "day": 29, "month": 2, "adjust": "previous" }
```

- Fecha específica

```json
{ "type": "specific_date", "date": "2025-12-31" }
```

Claves relevantes:
- `type`: uno de `no_expiration`, `interval`, `monthly`, `yearly`, `specific_date`.
- `value`, `unit`: utilizados por `interval` (unit: `days|months|years`).
- `day`: día del mes (1-31).
- `month`: mes (1-12).
- `date`: fecha ISO para `specific_date`.
- `adjust`: política cuando el día no existe en el periodo (valores: `previous` — ajustar al último día válido del mes, o `next` — avanzar al siguiente día).

## Cálculo de la siguiente fecha de vencimiento

En backend la librería proporciona una función para calcular la fecha resultante a partir de una fecha base y la configuración JSON. En el código la función se llama:

```php
use davidrnk\RecurringDate\Core\RecurringDateEngine;

$nextDueDate = RecurringDateEngine::calculateExpiration($startDate, $configArray);
// devuelve instancia DateTime o null si la configuración es inválida
```

En la documentación y ejemplos de esta README, nos referimos a esa fecha como `nextDueDate` (fecha siguiente de vencimiento). Si `calculateExpiration` devuelve `null`, la combinación de parámetros es inválida o no pudo calcularse.

Ejemplo rápido:

```php
$start = new \DateTime('2025-01-31');
$cfg = ['type' => 'monthly', 'day' => 31, 'adjust' => 'previous'];
$next = RecurringDateEngine::calculateExpiration($start, $cfg);
echo $next ? $next->format('Y-m-d') : 'invalid';
```

## Configuración y personalización

El widget expone varias formas de ajustar su comportamiento visual y textual:

- `options` (array): atributos HTML para el campo de texto visible (p.ej. `class`, `style`, `placeholder`).
- `labels` (array): puedes sobrescribir textos y labels usados en el modal. Ejemplos de claves que puedes personalizar:
  - `title_modal`, `type`, `configure`, `preview`, `save`, `cancel`, `quantity`, `unit`, `month_day`, `adjust`, `adjust_previous`, `adjust_next`, etc.
- Traducciones: la extensión incluye archivos en `src/messages/en` y `src/messages/es`. Los textos mostrados se envían también al JavaScript para la vista previa.

Ejemplo de personalización de labels:

```php
echo $form->field($model, 'recurrence_config')->widget(RecurringDate::class, [
    'labels' => [
        'title_modal' => 'Programar repetición',
        'adjust_previous' => 'Ajustar al último día del mes',
    ],
]);
```

## Validaciones y comportamiento UX

- El widget valida en cliente combinaciones evidentemente inválidas (p.ej. 31 en meses de 30 días, 31 Febrero) y bloquea el guardado cuando la selección es fatal.
- Para casos no fatales (p.ej. día >= 29 en mensual o 29 Feb en anual), muestra una advertencia y permite al usuario seleccionar la política `adjust`.
- El valor `adjust` se persiste en el JSON y es tenido en cuenta por `RecurringDateEngine::calculateExpiration`.

## Internacionalización (i18n)

El idioma por defecto de la extensión es inglés. Se incluyen traducciones en `src/messages/en` y `src/messages/es`. Las cadenas usadas en las vistas y en las traducciones JavaScript están definidas y cargadas desde `RecurringDate::getJSTranslations()`.

Si necesitas añadir otro idioma, añade un archivo en `src/messages/XX/davidrnk.recurring.php` con las claves necesarias.

## Tests

Se incluyen tests unitarios de PHP para la lógica de cálculo (`tests/RecurringDateEngineTest.php`) y conviene ejecutar:

```bash
vendor/bin/phpunit tests/RecurringDateEngineTest.php
```

## Buenas prácticas y notas

- Guarda el JSON persistido directamente en un campo de texto en la base de datos (por ejemplo `recurrence_config`), y usa `RecurringDateEngine::calculateExpiration` para obtener la próxima fecha de vencimiento cuando la necesites.
- Decide y documenta la política por defecto de `adjust` para tu dominio (por defecto la extensión usa `previous` — clampa al último día válido). Esto evita sorpresas al calcular fechas próximas.
- Revisa la configuración regional (`Yii::$app->language`) para asegurar que la IU muestre las traducciones deseadas.

## Contribuir

Pull requests y issues son bienvenidos. Para cambios mayores abre primero un issue describiendo el cambio propuesto.

## Licencia

BSD-3-Clause — ver archivo `LICENSE`.
