<?php

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Core\OEGlobalsBag;

// Translation function
// This is the translation engine
//
//  Note there are cases in installation where this function has already been
//   declared, so check to ensure has not been declared yet.
//

// Returns a reference to the shared translation cache
if (!(function_exists('xlGetCache'))) {
    function &xlGetCache(): array
    {
        static $translationCache = [];
        return $translationCache;
    }
}

// Preloads all translations for the given language into the cache.
// Call this early in the request lifecycle for best performance.
if (!(function_exists('xlWarmCache'))) {
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

        // Skip if already warmed for this language
        if (!empty($cache[$lang_id]['_warmed'])) {
            return;
        }

        // Load all translations for this language in a single query
        $sql = "SELECT lc.constant_name, ld.definition FROM lang_definitions ld " .
            "JOIN lang_constants lc ON ld.cons_id = lc.cons_id " .
            "WHERE ld.lang_id = ?";
        $rows = QueryUtils::fetchRecordsNoLog($sql, [$lang_id]);

        if (!isset($cache[$lang_id])) {
            $cache[$lang_id] = [];
        }

        foreach ($rows as $row) {
            $cache[$lang_id][$row['constant_name']] = $row['definition'];
        }

        // Mark as warmed so we don't repeat
        $cache[$lang_id]['_warmed'] = true;
    }
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

        if ($globals->getBoolean('temp_skip_translations')) {
            return $constant;
        }

        // set language id
        $lang_id = !empty($_SESSION['language_choice']) ? (int) $_SESSION['language_choice'] : 1;

        // Short-circuit for English when configured to skip lookups
        if ($lang_id === 1 && $globals->getBoolean('translate_skip_english_lookup')) {
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
            $string = $cache[$lang_id][$constant];
        } else {
            // second, attempt translation
            $sql = "SELECT ld.definition FROM lang_definitions ld " .
                "JOIN lang_constants lc ON ld.cons_id = lc.cons_id " .
                "WHERE ld.lang_id = ? AND lc.constant_name = ? LIMIT 1";
            $rows = QueryUtils::fetchRecordsNoLog($sql, [$lang_id, $constant]);
            $string = $rows[0]['definition'] ?? '';

            // Cache the result (empty string means no translation found)
            if (!isset($cache[$lang_id])) {
                $cache[$lang_id] = [];
            }
            $cache[$lang_id][$constant] = $string;
        }

        if ($string == '') {
            $string = "$constant";
        }
        // remove dangerous characters and remove comments
        if ($globals->getBoolean('translate_no_safe_apostrophe')) {
            $patterns =  ['/\n/','/\r/','/\{\{.*\}\}/'];
            $replace =  [' ','',''];
            $string = preg_replace($patterns, $replace, (string) $string);
        } else {
            // convert apostrophes and quotes to safe apostrophe
            $patterns =  ['/\n/','/\r/','/"/',"/'/",'/\{\{.*\}\}/'];
            $replace =  [' ','','`','`',''];
            $string = preg_replace($patterns, $replace, (string) $string);
        }

        return $string;
    }
}

// ----------- xl() function wrappers ------------------------------
//
// Use above xl() function the majority of time for translations. The
//  below wrappers are only for specific situations in order to support
//  granular control of translations in certain parts of OpenEMR.
//  Wrappers:
//    xl_list_label()
//    xl_layout_label()
//    xl_gacl_group()
//    xl_form_title()
//    xl_document_category()
//    xl_appt_category()
//
/**
 * Conditionally translates list labels based on global setting
 *
 * Only translates if $GLOBALS['translate_lists'] is set to true.
 * Added 5-09 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_list_label(string $constant): string
{
    return $GLOBALS['translate_lists'] ? xl($constant) : $constant;
}

/**
 * Conditionally translates layout labels based on global setting
 *
 * Only translates if $GLOBALS['translate_layout'] is set to true.
 * Added 5-09 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_layout_label(string $constant): string
{
    return $GLOBALS['translate_layout'] ? xl($constant) : $constant;
}

/**
 * Conditionally translates access control group labels based on global setting
 *
 * Only translates if $GLOBALS['translate_gacl_groups'] is set to true.
 * Added 6-2009 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_gacl_group(string $constant): string
{
    return $GLOBALS['translate_gacl_groups'] ? xl($constant) : $constant;
}

/**
 * Conditionally translates patient form (notes) titles based on global setting
 *
 * Only translates if $GLOBALS['translate_form_titles'] is set to true.
 * Added 6-2009 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_form_title(string $constant): string
{
    return $GLOBALS['translate_form_titles'] ? xl($constant) : $constant;
}

/**
 * Conditionally translates document categories based on global setting
 *
 * Only translates if $GLOBALS['translate_document_categories'] is set to true.
 * Added 6-2009 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_document_category(string $constant): string
{
    return $GLOBALS['translate_document_categories'] ? xl($constant) : $constant;
}

/**
 * Conditionally translates appointment categories based on global setting
 *
 * Only translates if $GLOBALS['translate_appt_categories'] is set to true.
 * Added 6-2009 by BM.
 *
 * @param string $constant The text constant to translate
 * @return string The translated or original string
 */
function xl_appt_category(string $constant): string
{
    return $GLOBALS['translate_appt_categories'] ? xl($constant) : $constant;
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
function getLanguageTitle($val): string
{
    // validate language id
    $lang_id = !empty($val) ? $val : 1;

    // get language title
    $res = sqlStatement("select lang_description from lang_languages where lang_id =?", [$lang_id]);
    for ($iter = 0; $row = sqlFetchArray($res); $iter++) {
        $result[$iter] = $row;
    };
    $languageTitle = $result[0]["lang_description"];
    return $languageTitle;
}

/**
 * Returns language directionality as string 'rtl' or 'ltr'
 *
 * @param int $lang_id language code
 * @return string 'ltr' or 'rtl'
 * @author Amiel <amielel@matrix.co.il>
 */
function getLanguageDir($lang_id): string
{
    // validate language id
    $lang_id = empty($lang_id) ? 1 : $lang_id;
    // get language code
    $row = sqlQuery('SELECT * FROM lang_languages WHERE lang_id = ?', [$lang_id]);

    return !empty($row['lang_is_rtl']) ? 'rtl' : 'ltr';
}
