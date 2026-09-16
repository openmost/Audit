<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Support;

use Piwik\Plugins\Audit\Checklist\ChecklistItem;

/**
 * Resolves the user-facing strings (title + recommendation template)
 * of a checklist item.
 *
 * Precedence:
 *   1. `lang/<locale>.json`, using a key derived from the check id:
 *      `srv-php-version` → `Audit_SrvPhpVersionTitle` / `Audit_SrvPhpVersionTemplate`.
 *   2. The raw YAML value (kept as a fallback so newly-added items
 *      keep working until their translation lands).
 */
class CheckTranslator
{
    public static function title(ChecklistItem $item): string
    {
        $key = 'Audit_' . self::slug($item->id) . 'Title';
        return self::translateWithFallback($key, (string) $item->title);
    }

    public static function template(ChecklistItem $item): ?string
    {
        $key      = 'Audit_' . self::slug($item->id) . 'Template';
        $fallback = $item->template ?? '';
        $resolved = self::translateWithFallback($key, $fallback);
        return $resolved === '' ? null : $resolved;
    }

    /**
     * Resolves a localised detail message for the given check item.
     *
     * The translation key is `Audit_<ItemSlug>Detail<VariantPascal>` and
     * placeholders of the form `{foo}` inside the translated string are
     * replaced with the matching keys of `$vars`. If the translation is
     * missing, `$fallback` is used as the base string before placeholder
     * substitution — this lets individual checks keep a sensible English
     * default while their translations land.
     */
    public static function detail(
        ChecklistItem $item,
        string $variant,
        array $vars = [],
        string $fallback = ''
    ): string {
        $variantPascal = self::toPascal($variant);
        $key           = 'Audit_' . self::slug($item->id) . 'Detail' . $variantPascal;
        $template      = self::translateWithFallback($key, $fallback);
        return self::substitute($template, $vars);
    }

    /**
     * `srv-php-version` → `SrvPhpVersion`
     */
    public static function slug(string $id): string
    {
        $parts = explode('-', $id);
        return implode('', array_map(static fn(string $p) => ucfirst($p), $parts));
    }

    /**
     * `too-low` → `TooLow` ; `pass` → `Pass` ; `mysql_version` → `MysqlVersion`
     */
    public static function toPascal(string $variant): string
    {
        $variant = str_replace(['_', ' '], '-', $variant);
        $parts   = explode('-', $variant);
        return implode('', array_map(static fn(string $p) => ucfirst($p), $parts));
    }

    /**
     * @param array<string, scalar|null> $vars
     */
    private static function substitute(string $template, array $vars): string
    {
        if (empty($vars)) {
            return $template;
        }
        $pairs = [];
        foreach ($vars as $k => $v) {
            $pairs['{' . $k . '}'] = (string) ($v ?? '');
        }
        return strtr($template, $pairs);
    }

    private static function translateWithFallback(string $key, string $fallback): string
    {
        if (!class_exists(\Piwik\Piwik::class)) {
            return $fallback;
        }

        try {
            $translated = \Piwik\Piwik::translate($key);
        } catch (\Throwable $e) {
            return $fallback;
        }

        // Matomo returns the raw key when the string is unknown.
        if ($translated === $key || $translated === '') {
            return $fallback;
        }
        return $translated;
    }
}
