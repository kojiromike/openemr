<?php

/**
 * This file is part of OpenEMR.
 *
 * @link https://github.com/openemr/openemr/tree/master
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Services\Globals;

/**
 * Service for pre-loading data needed by the globals editor screen.
 * This class eliminates N+1 query problems by loading all necessary data
 * in a minimal number of database queries and file system operations.
 *
 * @package OpenEMR\Services
 * @subpackage Globals
 */
class GlobalsEditorDataLoader
{
    private array $globalsValues = [];
    private array $languages = [];
    private array $userSettings = [];
    private array $dashboardCards = [];
    private array $calendarCategories = [];
    private array $cssFiles = [];
    private array $tabsCssFiles = [];
    private bool $isLoaded = false;

    /**
     * Load all data needed for rendering the globals editor.
     * This performs all database queries and file system operations upfront.
     *
     * @param bool $userMode Whether we're in user settings mode
     * @param int|null $userId User ID for loading user-specific settings
     * @param string $themesDir Path to themes directory
     */
    public function loadAll(bool $userMode = false, ?int $userId = null, string $themesDir = ''): void
    {
        if ($this->isLoaded) {
            return; // Already loaded, skip redundant work
        }

        $this->loadGlobalsValues();
        $this->loadLanguages();
        $this->loadDashboardCards();
        $this->loadCalendarCategories();

        if ($userMode && $userId !== null) {
            $this->loadUserSettings($userId);
        }

        if (!empty($themesDir)) {
            $this->loadCssFiles($themesDir);
        }

        $this->isLoaded = true;
    }

    /**
     * Load all globals values from the database.
     * Organizes them by gl_name for O(1) lookup.
     */
    private function loadGlobalsValues(): void
    {
        $res = sqlStatement("SELECT gl_name, gl_index, gl_value FROM globals ORDER BY gl_name, gl_index");
        while ($row = sqlFetchArray($res)) {
            $gl_name = $row['gl_name'];
            if (!isset($this->globalsValues[$gl_name])) {
                $this->globalsValues[$gl_name] = [];
            }
            $this->globalsValues[$gl_name][] = $row;
        }
    }

    /**
     * Load all available languages from the database.
     */
    private function loadLanguages(): void
    {
        $res = sqlStatement("SELECT * FROM lang_languages ORDER BY lang_description");
        while ($row = sqlFetchArray($res)) {
            $this->languages[] = $row;
        }
    }

    /**
     * Load user-specific settings for a given user.
     *
     * @param int $userId The user ID to load settings for
     */
    private function loadUserSettings(int $userId): void
    {
        $res = sqlStatement(
            "SELECT setting_label, setting_value FROM user_settings
             WHERE setting_user = ? AND setting_label LIKE 'global:%'",
            [$userId]
        );
        while ($row = sqlFetchArray($res)) {
            $this->userSettings[$row['setting_label']] = $row['setting_value'];
        }
    }

    /**
     * Load dashboard cards configuration.
     */
    private function loadDashboardCards(): void
    {
        $res = sqlStatement("SELECT gl_value FROM globals WHERE gl_name = 'hide_dashboard_cards'");
        while ($row = sqlFetchArray($res)) {
            $this->dashboardCards[] = $row['gl_value'];
        }
    }

    /**
     * Load calendar categories.
     */
    private function loadCalendarCategories(): void
    {
        $res = sqlStatement(
            "SELECT pc_catid, pc_catname, pc_cattype
             FROM openemr_postcalendar_categories
             WHERE pc_active = 1
             ORDER BY pc_seq"
        );
        while ($row = sqlFetchArray($res)) {
            $this->calendarCategories[] = $row;
        }
    }

    /**
     * Load and cache CSS theme files from the themes directory.
     *
     * @param string $themesDir Path to the themes directory
     */
    private function loadCssFiles(string $themesDir): void
    {
        if (!is_dir($themesDir)) {
            return;
        }

        $dh = opendir($themesDir);
        if (!$dh) {
            return;
        }

        while (false !== ($filename = readdir($dh))) {
            // Cache regular CSS files
            if (
                preg_match("/^style_.*\.css$/", $filename) &&
                $filename != 'style_blue.css' &&
                $filename != 'style_pdf.css'
            ) {
                $displayName = $this->formatCssDisplayName($filename, 6);
                $this->cssFiles[$filename] = $displayName;
            }

            // Cache tabs CSS files
            if (preg_match("/^tabs_style_.*\.css$/", $filename)) {
                $displayName = $this->formatCssDisplayName($filename, 11);
                $this->tabsCssFiles[$filename] = $displayName;
            }
        }

        closedir($dh);

        // Alphabetize for consistent display
        asort($this->cssFiles);
        asort($this->tabsCssFiles);
    }

    /**
     * Format CSS filename for display.
     *
     * @param string $filename The CSS filename
     * @param int $prefixLength Length of prefix to remove (6 for "style_", 11 for "tabs_style_")
     * @return string Formatted display name
     */
    private function formatCssDisplayName(string $filename, int $prefixLength): string
    {
        $displayName = substr($filename, $prefixLength);
        $displayName = str_replace("_", " ", $displayName);
        $displayName = str_replace(".css", "", $displayName);
        return ucfirst($displayName);
    }

    /**
     * Get globals values for a specific global setting.
     *
     * @param string $globalName The global setting name
     * @return array Array of globals rows for this setting
     */
    public function getGlobalsValues(string $globalName): array
    {
        return $this->globalsValues[$globalName] ?? [];
    }

    /**
     * Get all languages.
     *
     * @return array Array of language rows
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * Get user setting value.
     *
     * @param string $settingLabel The setting label (e.g., "global:setting_name")
     * @return string The setting value, or empty string if not found
     */
    public function getUserSetting(string $settingLabel): string
    {
        return $this->userSettings[$settingLabel] ?? '';
    }

    /**
     * Get dashboard cards configuration.
     *
     * @return array Array of hidden dashboard card values
     */
    public function getDashboardCards(): array
    {
        return $this->dashboardCards;
    }

    /**
     * Get calendar categories.
     *
     * @return array Array of calendar category rows
     */
    public function getCalendarCategories(): array
    {
        return $this->calendarCategories;
    }

    /**
     * Get CSS files list.
     *
     * @param bool $tabsCss Whether to get tabs CSS files (true) or regular CSS files (false)
     * @return array Associative array of filename => display name
     */
    public function getCssFiles(bool $tabsCss = false): array
    {
        return $tabsCss ? $this->tabsCssFiles : $this->cssFiles;
    }
}
