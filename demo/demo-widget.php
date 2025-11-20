<?php
/**
 * Demo Standalone: Widget RecurringDate
 * 
 * Este archivo demuestra cómo funciona el widget RecurringDate sin necesidad
 * de un proyecto Yii2 completo. Puedes abrirlo en el navegador con:
 * 
 *   php -S localhost:8000
 * 
 * Luego accede a: http://localhost:8000/demo/demo-widget.php
 */

// Definir rutas base
define('BASE_PATH', dirname(dirname(__FILE__)));
define('SRC_PATH', BASE_PATH . '/src');

// Importar el motor (no requiere Yii2 completo)
require_once SRC_PATH . '/Core/RecurringDateEngine.php';

use davidrnk\RecurringDate\Core\RecurringDateEngine;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo: Widget Vigencia Recurrente</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .demo-card { box-shadow: 0 4px 15px rgba(0,0,0,0.2); border: none; }
        .demo-card .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .widget-section { margin-top: 30px; }
        .rdw-preview { 
            min-height: 50px; 
            padding: 12px; 
            background: #f8f9fa; 
            border: 1px solid #e9ecef; 
            border-radius: 6px; 
            display: flex;
            align-items: center;
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }
        .rdw-hidden { display: none !important; }
        .section-box { 
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
        }
        .json-display {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 300px;
            overflow-y: auto;
        }
        .rdw-error { color: #dc3545; font-weight: 500; margin-top: 10px; }
        .rdw-success { color: #198754; font-weight: 500; margin-top: 10px; }
        .button-group { gap: 10px; }
        input[readonly] { background-color: #e9ecef; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card demo-card">
            <div class="card-header text-white">
                <h3 class="mb-0"><i class="bi bi-calendar-check"></i> Demo: Widget de Vigencia Recurrente</h3>
                <small>Prueba completa del widget sin un proyecto Yii2</small>
            </div>
            
            <div class="card-body">

                <!-- Sección 1: Control del Widget -->
                <div class="widget-section">
                    <h5>1. Control del Widget</h5>
                    <div class="section-box">
                        <label class="form-label fw-bold">Vigencia del Documento</label>
                        <div class="input-group">
                            <input 
                                id="vigencia-text" 
                                type="text" 
                                class="form-control" 
                                readonly 
                                placeholder="Seleccione la vigencia..."
                                style="background-color: #e9ecef;"
                            >
                            <button 
                                id="open-modal-btn" 
                                class="btn btn-primary" 
                                type="button"
                            >
                                <i class="bi bi-pencil-square"></i> Configurar
                            </button>
                        </div>
                        <input id="vigencia-config-hidden" type="hidden" value="">
                        <small class="form-text text-muted d-block mt-2">
                            Campo readonly. Pulsa "Configurar" para abrir el modal.
                        </small>
                    </div>
                </div>

                <!-- Sección 2: JSON Almacenado -->
                <div class="widget-section">
                    <h5>2. Configuración JSON Guardada</h5>
                    <div class="section-box">
                        <div id="json-display" class="json-display">{}</div>
                    </div>
                </div>

                <!-- Sección 3: Calcular Expiración -->
                <div class="widget-section">
                    <h5>3. Calcular Fecha de Vencimiento</h5>
                    <div class="section-box">
                        <label class="form-label fw-bold">Fecha del Documento</label>
                        <div class="input-group">
                            <input 
                                id="doc-date-input" 
                                type="date" 
                                class="form-control"
                            >
                            <button 
                                id="calc-expiration-btn" 
                                class="btn btn-success" 
                                type="button"
                            >
                                <i class="bi bi-calculator"></i> Calcular Vencimiento
                            </button>
                        </div>
                        <div id="expiration-result"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal: Configurar Vigencia -->
    <div class="modal fade" id="vigenciaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title"><i class="bi bi-gear"></i> Configurar Vigencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    
                    <!-- Selector de Tipo -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Vigencia</label>
                        <select id="rdw-type" class="form-select">
                            <option value="no_expiration">No vence</option>
                            <option value="interval">Cada N (Intervalo)</option>
                            <option value="monthly">Mensual (Día del mes)</option>
                            <option value="yearly">Anual (Día y mes)</option>
                            <option value="specific_date">Fecha Específica</option>
                        </select>
                    </div>

                    <!-- Sección: Intervalo -->
                    <div id="section-interval" class="mb-3 p-3 border rounded rdw-hidden" style="background: #f9f9f9;">
                        <h6><i class="bi bi-hourglass-split"></i> Intervalo</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Cantidad (N)</label>
                                <input 
                                    id="rdw-interval-value" 
                                    type="number" 
                                    min="1" 
                                    max="999" 
                                    class="form-control" 
                                    value="1"
                                >
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unidad</label>
                                <select id="rdw-interval-unit" class="form-select">
                                    <option value="day">Días</option>
                                    <option value="month">Meses</option>
                                    <option value="year">Años</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Mensual -->
                    <div id="section-monthly" class="mb-3 p-3 border rounded rdw-hidden" style="background: #f9f9f9;">
                        <h6><i class="bi bi-calendar-month"></i> Mensual</h6>
                        <label class="form-label">Día del mes (1-31)</label>
                        <input 
                            id="rdw-day-of-month" 
                            type="number" 
                            min="1" 
                            max="31" 
                            class="form-control" 
                            value="1"
                        >
                    </div>

                    <!-- Sección: Anual -->
                    <div id="section-yearly" class="mb-3 p-3 border rounded rdw-hidden" style="background: #f9f9f9;">
                        <h6><i class="bi bi-calendar-year"></i> Anual</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Día</label>
                                <input 
                                    id="rdw-yearly-day" 
                                    type="number" 
                                    min="1" 
                                    max="31" 
                                    class="form-control" 
                                    value="1"
                                >
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mes</label>
                                <select id="rdw-yearly-month" class="form-select">
                                    <option value="1">Enero</option><option value="2">Febrero</option>
                                    <option value="3">Marzo</option><option value="4">Abril</option>
                                    <option value="5">Mayo</option><option value="6">Junio</option>
                                    <option value="7">Julio</option><option value="8">Agosto</option>
                                    <option value="9">Septiembre</option><option value="10">Octubre</option>
                                    <option value="11">Noviembre</option><option value="12">Diciembre</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Fecha Específica -->
                    <div id="section-specific-date" class="mb-3 p-3 border rounded rdw-hidden" style="background: #f9f9f9;">
                        <h6><i class="bi bi-calendar-day"></i> Fecha Específica</h6>
                        <label class="form-label">Selecciona la fecha</label>
                        <input id="rdw-specific-date" type="date" class="form-control">
                    </div>

                    <!-- Vista Previa -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vista Previa (Texto Legible)</label>
                        <div id="rdw-preview" class="rdw-preview">—</div>
                        <div id="rdw-validation-error" class="rdw-error"></div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button id="rdw-save-btn" type="button" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Guardar Configuración
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        /**
         * Demo: Vigencia Widget
         * Simulación del comportamiento del widget RecurringDate
         */
        (function() {
            'use strict';

            // Meses en español
            const MONTHS_ES = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

            // Elementos del DOM
            const modal = new bootstrap.Modal(document.getElementById('vigenciaModal'));
            const $typeSelect = $('#rdw-type');
            const $vigenciaText = $('#vigencia-text');
            const $vigenciaHidden = $('#vigencia-config-hidden');
            const $jsonDisplay = $('#json-display');
            const $previewDiv = $('#rdw-preview');
            const $errorDiv = $('#rdw-validation-error');

            // === Eventos ===

            $('#open-modal-btn').on('click', function() {
                modal.show();
            });

            $typeSelect.on('change', function() {
                updateVisibleSections();
                updatePreview();
            });

            $('#rdw-interval-value, #rdw-interval-unit, #rdw-day-of-month, ' +
             '#rdw-yearly-day, #rdw-yearly-month, #rdw-specific-date')
                .on('input change', updatePreview);

            $('#rdw-save-btn').on('click', saveConfiguration);

            $('#calc-expiration-btn').on('click', calculateExpiration);

            // === Funciones ===

            function updateVisibleSections() {
                const type = $typeSelect.val();
                $('#section-interval').toggleClass('rdw-hidden', type !== 'interval');
                $('#section-monthly').toggleClass('rdw-hidden', type !== 'monthly');
                $('#section-yearly').toggleClass('rdw-hidden', type !== 'yearly');
                $('#section-specific-date').toggleClass('rdw-hidden', type !== 'specific_date');
                $errorDiv.html('');
            }

            function buildConfigFromForm() {
                const type = $typeSelect.val();
                let config = { type: type };

                if (type === 'interval') {
                    config.interval = {
                        value: parseInt($('#rdw-interval-value').val() || 1, 10),
                        unit: $('#rdw-interval-unit').val()
                    };
                } else if (type === 'monthly') {
                    config.monthly = {
                        dayOfMonth: parseInt($('#rdw-day-of-month').val() || 1, 10)
                    };
                } else if (type === 'yearly') {
                    config.yearly = {
                        day: parseInt($('#rdw-yearly-day').val() || 1, 10),
                        month: parseInt($('#rdw-yearly-month').val() || 1, 10)
                    };
                } else if (type === 'specific_date') {
                    config.specific_date = {
                        date: $('#rdw-specific-date').val() || null
                    };
                }

                return config;
            }

            function toHumanReadable(config) {
                if (!config || !config.type) return '—';

                switch (config.type) {
                    case 'no_expiration':
                        return 'No vence';

                    case 'interval': {
                        const v = config.interval?.value || 0;
                        const unit = config.interval?.unit || 'day';
                        const unitLabels = {
                            day: v === 1 ? 'día' : 'días',
                            month: v === 1 ? 'mes' : 'meses',
                            year: v === 1 ? 'año' : 'años'
                        };
                        return `Cada ${v} ${unitLabels[unit] || unit}`;
                    }

                    case 'monthly': {
                        const day = config.monthly?.dayOfMonth || '?';
                        return `Cada mes, día ${day}`;
                    }

                    case 'yearly': {
                        const day = config.yearly?.day || '?';
                        const month = MONTHS_ES[config.yearly?.month] || '?';
                        return `Cada año, ${day} de ${month}`;
                    }

                    case 'specific_date': {
                        const dateStr = config.specific_date?.date;
                        if (!dateStr) return 'Fecha no válida';
                        const date = new Date(dateStr);
                        return `Vence el ${date.toLocaleDateString('es-ES')}`;
                    }

                    default:
                        return 'Tipo desconocido';
                }
            }

            function updatePreview() {
                const config = buildConfigFromForm();
                $previewDiv.text(toHumanReadable(config));
            }

            function validateConfiguration() {
                const config = buildConfigFromForm();

                if (config.type === 'interval') {
                    const v = config.interval.value;
                    if (!Number.isInteger(v) || v < 1) {
                        return 'El intervalo debe ser un número entero ≥ 1';
                    }
                }

                if (config.type === 'monthly') {
                    const d = config.monthly.dayOfMonth;
                    if (!Number.isInteger(d) || d < 1 || d > 31) {
                        return 'Día del mes debe estar entre 1 y 31';
                    }
                }

                if (config.type === 'yearly') {
                    const d = config.yearly.day;
                    const m = config.yearly.month;
                    if (!Number.isInteger(d) || d < 1 || d > 31) {
                        return 'Día debe estar entre 1 y 31';
                    }
                    if (!Number.isInteger(m) || m < 1 || m > 12) {
                        return 'Mes debe estar entre 1 y 12';
                    }
                }

                if (config.type === 'specific_date') {
                    if (!config.specific_date.date) {
                        return 'Debes seleccionar una fecha específica';
                    }
                }

                return null;
            }

            function saveConfiguration() {
                const error = validateConfiguration();

                if (error) {
                    $errorDiv.html(`<i class="bi bi-exclamation-circle"></i> <strong>Error:</strong> ${error}`);
                    return;
                }

                const config = buildConfigFromForm();
                const jsonStr = JSON.stringify(config);

                $vigenciaHidden.val(jsonStr);
                $vigenciaText.val(toHumanReadable(config));
                $jsonDisplay.text(JSON.stringify(config, null, 2));

                modal.hide();

                // Mostrar notificación
                showNotification('✓ Configuración guardada correctamente', 'success');
            }

            function calculateExpiration() {
                const docDateStr = $('#doc-date-input').val();
                const configJson = $vigenciaHidden.val();
                const $resultDiv = $('#expiration-result');

                if (!configJson) {
                    $resultDiv.html('<div class="rdw-error"><i class="bi bi-exclamation-circle"></i> Debes configurar la vigencia primero</div>');
                    return;
                }

                if (!docDateStr) {
                    $resultDiv.html('<div class="rdw-error"><i class="bi bi-exclamation-circle"></i> Selecciona una fecha de documento</div>');
                    return;
                }

                let config;
                try {
                    config = JSON.parse(configJson);
                } catch (e) {
                    $resultDiv.html('<div class="rdw-error"><i class="bi bi-exclamation-circle"></i> Configuración JSON inválida</div>');
                    return;
                }

                const docDate = new Date(docDateStr);
                if (isNaN(docDate)) {
                    $resultDiv.html('<div class="rdw-error"><i class="bi bi-exclamation-circle"></i> Fecha de documento inválida</div>');
                    return;
                }

                let expirationDate = null;
                let resultText = '';

                if (config.type === 'no_expiration') {
                    resultText = '📅 El documento <strong>no vence</strong>';
                } else if (config.type === 'interval') {
                    expirationDate = new Date(docDate);
                    const v = config.interval.value;
                    const unit = config.interval.unit;

                    if (unit === 'day') expirationDate.setDate(expirationDate.getDate() + v);
                    else if (unit === 'month') expirationDate.setMonth(expirationDate.getMonth() + v);
                    else if (unit === 'year') expirationDate.setFullYear(expirationDate.getFullYear() + v);

                    resultText = `📅 Vence el <strong>${expirationDate.toLocaleDateString('es-ES')}</strong>`;
                } else if (config.type === 'monthly') {
                    expirationDate = new Date(docDate);
                    expirationDate.setMonth(expirationDate.getMonth() + 1);
                    expirationDate.setDate(config.monthly.dayOfMonth);

                    resultText = `📅 Vence el <strong>${expirationDate.toLocaleDateString('es-ES')}</strong>`;
                } else if (config.type === 'yearly') {
                    expirationDate = new Date(docDate);
                    expirationDate.setFullYear(expirationDate.getFullYear() + 1);
                    expirationDate.setMonth(config.yearly.month - 1);
                    expirationDate.setDate(config.yearly.day);

                    resultText = `📅 Vence el <strong>${expirationDate.toLocaleDateString('es-ES')}</strong>`;
                } else if (config.type === 'specific_date') {
                    expirationDate = new Date(config.specific_date.date);
                    if (isNaN(expirationDate)) {
                        $resultDiv.html('<div class="rdw-error"><i class="bi bi-exclamation-circle"></i> Fecha específica inválida</div>');
                        return;
                    }

                    resultText = `📅 Vence el <strong>${expirationDate.toLocaleDateString('es-ES')}</strong>`;
                }

                $resultDiv.html(`<div class="rdw-success">${resultText}</div>`);
            }

            function showNotification(msg, type = 'info') {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-' + type;
                const $alert = $(`<div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    ${msg}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`);
                $('body').prepend($alert);
                setTimeout(() => $alert.fadeOut(300, () => $alert.remove()), 3000);
            }

            // Inicialización
            updateVisibleSections();
            updatePreview();

        })();
    </script>

</body>
</html>
