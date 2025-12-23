<?php

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Core\OEGlobalsBag;

if (!(function_exists('xlGetCache'))) {
    /**
     * Returns a reference to the shared translation cache
     */
    function &xlGetCache(): array
    {
        static $translationCache = [];
        return $translationCache;
    }
}

if (!(function_exists('xlWarmCache'))) {
    /**
     * Preloads all translations for the given language into the cache.
     * Call this early in the request lifecycle for best performance.
     */
    function xlWarmCache(): void
    {
        $globals = OEGlobalsBag::getInstance();
        $lang_id = !empty($_SESSION['language_choice']) ? (int) $_SESSION['language_choice'] : 1;

        // Check for conflicting settings
        if ($lang_id === 1 && $globals->getBoolean('translate_skip_english_lookup')) {
            (new SystemLogger())->warning(
                "Both 'translate_preload_cache' and 'translate_skip_english_lookup' are enabled. " .
                "These settings are mutually exclusive for English. Using 'translate_skip_english_lookup' (skipping cache warm)."
            );
            return;
        }

        $cache = &xlGetCache();
        if (isset($cache[$lang_id])) {
            // Skip if already warmed (or marked as empty) for this language
            return;
        }

        // Load all translations for this language in a single query
        $sql = <<<'SQL'
            SELECT lang_constants.constant_name,
                   lang_definitions.definition
              FROM lang_definitions
              JOIN lang_constants
                ON lang_definitions.cons_id = lang_constants.cons_id
             WHERE lang_definitions.lang_id = ?;
        SQL;
        $rows = QueryUtils::fetchRecordsNoLog($sql, [$lang_id]);
        $cache[$lang_id] = array_column($rows, 'definition', 'constant_name');
    }
}

/**
 * Checks if there are any translations for the given language.
 * Caches the result: empty array means "checked, no translations exist".
 */
function xlLangHasTranslations(int $lang_id): bool
{
    if ($lang_id === 1 && OEGlobalsBag::getInstance()->getBoolean('translate_skip_english_lookup')) {
        return false;
    }

    $cache = &xlGetCache();

    // Already have cached translations
    if (!empty($cache[$lang_id])) {
        return true;
    }

    // Already checked and found none
    if (isset($cache[$lang_id])) {
        return false;
    }

    // Check database for any translations
    $sql = <<<'SQL'
        SELECT 1
          FROM lang_definitions
          JOIN lang_constants
            ON lang_definitions.cons_id = lang_constants.cons_id
         WHERE lang_definitions.lang_id = ?
         LIMIT 1;
    SQL;
    $rows = QueryUtils::fetchRecordsNoLog($sql, [$lang_id]);

    if (empty($rows)) {
        // Mark as "no translations" so future lookups short-circuit
        $cache[$lang_id] = [];
        return false;
    }

    return true;
}

/**
 * Determine if we should skip translation and just return the input.
 *
 * When 'temp_skip_translations' is set
 * When 'translate_skip_english_lookup' is set and the language is English
 * When cache is empty (no translations)
 * When first access to database finds there are no translations for that language.
 */
function xlskip(int $lang_id): bool
{
    $cache = &xlGetCache();
    $globals = OEGlobalsBag::getInstance();
    return ($globals->getBoolean('temp_skip_translations')
        || ($lang_id === 1 && $globals->getBoolean('translate_skip_english_lookup'))
        || (isset($cache[$lang_id]) ? empty($cache[$lang_id]) : !xlLangHasTranslations($lang_id))
    );
}


if (!(function_exists('xl'))) {
    /**
     * Translation function - the translation engine for OpenEMR
     *
     * Translates a given constant string into the current session language.
     * Note: In some installation scenarios this function may already be declared,
     * so we check to ensure it hasn't been declared yet.
     *
     * @param string $constant The text constant to translate
     * @return string The translated string
     */
    function xl(string $constant): string
    {
        $cache = &xlGetCache();
        $globals = OEGlobalsBag::getInstance();
        $lang_id = (int) ($_SESSION['language_choice'] ?? 1);

        if (xlskip($lang_id)) {
            return $constant;
        }

        // TRANSLATE
        // first, clean lines
        // convert new lines to spaces and remove windows end of lines
        $patterns =  ['/\n/','/\r/'];
        $replace =  [' ',''];
        $constant = preg_replace($patterns, $replace, $constant);

        // Check cache first
        if (isset($cache[$lang_id][$constant])) {
            $string = (string) $cache[$lang_id][$constant];
        } else {
            // second, attempt translation
            $sql = <<<'SQL'
                SELECT definition
                  FROM lang_definitions
                  JOIN lang_constants
                    ON lang_definitions.cons_id = lang_constants.cons_id
                 WHERE lang_definitions.lang_id = ?
                   AND lang_constants.constant_name = ?
                   LIMIT 1;
            SQL;
            $rows = QueryUtils::fetchRecordsNoLog($sql, [$lang_id, $constant]);
            $string = (string) ($rows[0]['definition'] ?? '');

            // Cache the result (empty string means no translation found)
            $cache[$lang_id] ??= [];
            $cache[$lang_id][$constant] = $string;
        }

        $string = $string ?: $constant;
        // remove dangerous characters and remove comments
        if ($globals->getBoolean('translate_no_safe_apostrophe')) {
            $patterns =  ['/\n/','/\r/','/\{\{.*\}\}/'];
            $replace =  [' ','',''];
        } else {
            // convert apostrophes and quotes to safe apostrophe
            $patterns =  ['/\n/','/\r/','/"/',"/'/",'/\{\{.*\}\}/'];
            $replace =  [' ','','`','`',''];
        }
        $string = preg_replace($patterns, $replace, (string) $string);
        return $string;
    }
}

/**
 * xl() function wrappers
 *
 * Use the above xl() function the majority of time for translations. The
 * below wrappers are only for specific situations in order to support
 * granular control of translations in certain parts of OpenEMR.
 * Wrappers:
 *   xl_list_label()
 *   xl_layout_label()
 *   xl_gacl_group()
 *   xl_form_title()
 *   xl_document_category()
 *   xl_appt_category()
 */
function xlw(string $globalFlag, string $constant): string
{
    return OEGlobalsBag::getInstance()->getBoolean($globalFlag) ? xl($constant) : $constant;
}

/**
 * Conditionally translates list labels based on global setting
 *
 * Only translates if 'translate_lists' is set to true.
 * Added 5-09 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_list_label(string $constant): string
{
    return xlw('translate_lists', $constant);
}

/**
 * Conditionally translates layout labels based on global setting
 *
 * Only translates if 'translate_layout' is set to true.
 * Added 5-09 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_layout_label(string $constant): string
{
    return xlw('translate_layout', $constant);
}

/**
 * Conditionally translates access control group labels based on global setting
 *
 * Only translates if 'translate_gacl_groups' is set to true.
 * Added 6-2009 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_gacl_group(string $constant): string
{
    return xlw('translate_gacl_groups', $constant);
}

/**
 * Conditionally translates patient form (notes) titles based on global setting
 *
 * Only translates if 'translate_form_titles' is set to true.
 * Added 6-2009 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_form_title(string $constant): string
{
    return xlw('translate_form_titles', $constant);
}

/**
 * Conditionally translates document categories based on global setting
 *
 * Only translates if 'translate_document_categories' is set to true.
 * Added 6-2009 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_document_category(string $constant): string
{
    return xlw('translate_document_categories', $constant);
}

/**
 * Conditionally translates appointment categories based on global setting
 *
 * Only translates if 'translate_appt_categories' is set to true.
 * Added 6-2009 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_appt_category(string $constant): string
{
    return xlw('translate_appt_categories', $constant);
}
// ---------------------------------------------------------------------------

// ---------------------------------
// Miscellaneous language translation functions

/**
 * Returns the title/description of a language from its ID
 *
 * @param int|string $val The language ID
 * @return string The language description/title
 */
function getLanguageTitle(int|string $lang_id): string
{
    $lang_id = (int) ($lang_id ?: 1);
    $sql = <<<'SQL'
        SELECT lang_description
          FROM lang_languages
         WHERE lang_id = ?
         LIMIT 1;
    SQL;
    $rows = QueryUtils::fetchRecordsNoLog($sql, [$lang_id]);
    return $rows[0]['lang_description'] ?? '';
}

/**
 * Returns language directionality as string 'rtl' or 'ltr'
 *
 * @param int|string $lang_id language code
 * @return string 'ltr' or 'rtl'
 * @author Amiel <amielel@matrix.co.il>
 */
function getLanguageDir(int|string $lang_id): string
{
    $lang_id = empty($lang_id) ? 1 : $lang_id;
    $rows = QueryUtils::fetchRecordsNoLog(
        'SELECT lang_is_rtl FROM lang_languages WHERE lang_id = ? LIMIT 1',
        [$lang_id]
    );
    return empty($rows[0]['lang_is_rtl']) ? 'ltr' : 'rtl';
}
