<?php

namespace davidrnk\RecurringDate\Core;

use Yii;
use DateTime;
use Exception;

/**
 * Simple engine to calculate the next expiration date based on a config
 * Format expected: same as widget JSON.
 */
class RecurringDateEngine
{
    /**
     * Calculate next expiration from a given start date (string or DateTime) and config array.
     * Returns DateTime or null on failure.
     */
    public static function calculateExpiration($startDate, array $config): ?DateTime
    {
        try {
            $dt = $startDate instanceof DateTime ? clone $startDate : new DateTime($startDate);

            if (empty($config['type'])) {
                return null;
            }

            switch ($config['type']) {
                case 'interval':
                    $value = max(1, (int)($config['value'] ?? 1));
                    $unit = $config['unit'] ?? 'days';

                    if ($unit === 'days') {
                        $dt->modify('+' . $value . ' days');
                    } elseif ($unit === 'months') {
                        $dt->modify('+' . $value . ' months');
                    } elseif ($unit === 'years') {
                        $dt->modify('+' . $value . ' years');
                    }

                    return $dt;

                case 'monthly':
                    $day = max(1, min(31, (int)($config['day'] ?? 1)));
                    $adjust = ($config['adjust'] ?? 'previous'); // 'previous' (default) or 'next'

                    // move to next month where day exists
                    $dt->modify('first day of next month');
                    $year = (int)$dt->format('Y');
                    $month = (int)$dt->format('m');
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

                    if ($day <= $daysInMonth) {
                        $dt->setDate($year, $month, $day);
                    } else {
                        if ($adjust === 'next') {
                            // advance to first day of following month
                            $dt->modify('+1 month');
                            $dt->setDate((int)$dt->format('Y'), (int)$dt->format('m'), 1);
                        } else {
                            // default: clamp to last day of the month
                            $dt->setDate($year, $month, $daysInMonth);
                        }
                    }

                    return $dt;

                case 'yearly':
                    $day = max(1, min(31, (int)($config['day'] ?? 1)));
                    $month = max(1, min(12, (int)($config['month'] ?? 1)));
                    $adjust = ($config['adjust'] ?? 'previous'); // 'previous' or 'next'

                    // invalidate obviously impossible combos:
                    // - February > 29 is impossible
                    if ($month === 2 && $day > 29) {
                        return null;
                    }

                    // - months with 30 days cannot have day 31
                    if (in_array($month, [4, 6, 9, 11], true) && $day === 31) {
                        return null;
                    }

                    $year = (int)$dt->format('Y');
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

                    if ($day <= $daysInMonth) {
                        $cand = DateTime::createFromFormat('!Y-n-j', $year . '-' . $month . '-' . $day);
                        if ($cand === false) return null;
                        return $cand;
                    }

                    // day > daysInMonth (e.g., 29 Feb in non-leap year)
                    if ($adjust === 'next') {
                        // advance to first day of following month
                        $cand = DateTime::createFromFormat('!Y-n-j', $year . '-' . $month . '-' . $daysInMonth);
                        if ($cand === false) return null;
                        $cand->modify('+1 day');
                        return $cand;
                    }

                    // default: clamp to last day of the month (previous)
                    $dayAdj = $daysInMonth;
                    $cand = DateTime::createFromFormat('!Y-n-j', $year . '-' . $month . '-' . $dayAdj);
                    if ($cand === false) return null;
                    return $cand;

                case 'specific_date':
                    if (empty($config['date'])) return null;
                    return new DateTime($config['date']);

                case 'no_expiration':
                    return null;
                default:
                    return null;
            }
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * humanReadable
     * Build a human readable string for a config.
     * Default language is Yii::$app->language (falls back to English).
     */
    public static function humanReadable(array $cfg): string
    {
        if (empty($cfg['type'])) return '';

        // month names map via translations (fallback to english)
        $months = [
            1 => Yii::t('davidrnk.recurring','January'),
            2 => Yii::t('davidrnk.recurring','February'),
            3 => Yii::t('davidrnk.recurring','March'),
            4 => Yii::t('davidrnk.recurring','April'),
            5 => Yii::t('davidrnk.recurring','May'),
            6 => Yii::t('davidrnk.recurring','June'),
            7 => Yii::t('davidrnk.recurring','July'),
            8 => Yii::t('davidrnk.recurring','August'),
            9 => Yii::t('davidrnk.recurring','September'),
            10 => Yii::t('davidrnk.recurring','October'),
            11 => Yii::t('davidrnk.recurring','November'),
            12 => Yii::t('davidrnk.recurring','December'),
        ];

        switch ($cfg['type']) {
            case 'no_expiration':
                return Yii::t('davidrnk.recurring', 'No expiration');
            case 'interval':
                $v = (int)($cfg['value'] ?? 1);
                $u = $cfg['unit'] ?? 'days';
                $unitLabels = [
                    'days' => $v === 1 ? Yii::t('davidrnk.recurring', 'day') : Yii::t('davidrnk.recurring', 'days'),
                    'months' => $v === 1 ? Yii::t('davidrnk.recurring', 'month') : Yii::t('davidrnk.recurring', 'months'),
                    'years' => $v === 1 ? Yii::t('davidrnk.recurring', 'year') : Yii::t('davidrnk.recurring', 'years'),
                ];
                $unitLabel = $unitLabels[$u] ?? $u;
                return Yii::t('davidrnk.recurring', 'Every {n} {unit}.', ['n' => $v, 'unit' => $unitLabel]);
            case 'monthly':
                $day = (int)($cfg['day'] ?? 1);
                return Yii::t('davidrnk.recurring', 'Every month, day {day}.', ['day' => $day]);
            case 'yearly':
                $day = (int)($cfg['day'] ?? 1);
                $month = (int)($cfg['month'] ?? 1);
                $monthName = $months[$month] ?? $month;
                return Yii::t('davidrnk.recurring', 'Every year, {day} of {month}.', ['day' => $day, 'month' => $monthName]);
            case 'specific_date':
                if (empty($cfg['date'])) return '';
                $d = new DateTime($cfg['date']);
                return Yii::t('davidrnk.recurring', 'Expires on {date}.', ['date' => $d->format('Y-m-d')]);
            default:
                return '';
        }
    }
}
