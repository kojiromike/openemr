<?php

/**
 * CodeSystemsTrait trait
 *
 * Helper methods for navigating to and testing code systems interface
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\E2e\CodeSystems;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use OpenEMR\Tests\E2e\Base\BaseTrait;
use OpenEMR\Tests\E2e\Login\LoginTrait;

trait CodeSystemsTrait
{
    use BaseTrait;

    /**
     * Navigate to the External Data Loads page
     */
    private function navigateToCodeSystemsPage(): void
    {
        // Navigate directly to the code systems page
        $this->crawler = $this->client->request('GET', '/interface/code_systems/dataloads_ajax.php?testing_mode=1');

        // Wait for page to load
        $this->client->waitFor('h4');

        // Verify we're on the right page
        $pageHeading = $this->crawler->filter('h4')->text();
        $this->assertStringContainsString('External Database Import', $pageHeading);
    }

    /**
     * Expand a specific database accordion section
     */
    private function expandDatabaseSection(string $dbType): void
    {
        // Click the button to expand the section
        $buttonId = "#{$dbType} button";
        $button = $this->crawler->filter($buttonId)->first();
        $button->click();

        // Wait for the section to expand and content to load
        $this->client->wait(5)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::id("{$dbType}_install_details")
            )
        );

        // Wait for AJAX content to actually load (check for non-empty content)
        $this->client->wait(15, 500)->until(function ($driver) use ($dbType) {
            try {
                $installDetails = $driver->findElement(WebDriverBy::id("{$dbType}_install_details"));
                $text = $installDetails->getText();

                // Content should be loaded - either "Not installed" or version info
                // But exclude the loading spinner image text
                return !empty($text) && !str_contains($text, 'ajax-loader');
            } catch (\Exception) {
                return false;
            }
        });

        // Also wait for stage details to load
        $this->client->wait(15, 500)->until(function ($driver) use ($dbType) {
            try {
                $stageDetails = $driver->findElement(WebDriverBy::id("{$dbType}_stage_details"));
                $text = $stageDetails->getText();

                // Stage details should have content
                return !empty($text);
            } catch (\Exception) {
                // Stage details might be empty if no files, that's ok
                return true;
            }
        });
    }

    /**
     * Check if a database section shows "Not installed"
     */
    private function isDatabaseNotInstalled(string $dbType): bool
    {
        try {
            // Re-fetch the crawler to get updated DOM
            $this->crawler = $this->client->refreshCrawler();
            $installDetails = $this->crawler->filter("#{$dbType}_install_details")->text();
            return str_contains((string) $installDetails, 'Not installed');
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Check if a database section shows installed version info
     */
    private function isDatabaseInstalled(string $dbType): bool
    {
        try {
            // Re-fetch the crawler to get updated DOM
            $this->crawler = $this->client->refreshCrawler();
            $installDetails = $this->crawler->filter("#{$dbType}_install_details")->text();
            return str_contains((string) $installDetails, 'Name:') ||
                   str_contains((string) $installDetails, 'Revision:') ||
                   str_contains((string) $installDetails, 'Release Date:');
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Check if staged files section shows error messages
     */
    private function hasStageErrors(string $dbType): bool
    {
        try {
            $this->crawler = $this->client->refreshCrawler();
            $stageDetails = $this->crawler->filter("#{$dbType}_stage_details")->text();
            return str_contains((string) $stageDetails, 'ERROR') ||
                   str_contains((string) $stageDetails, 'UNSUPPORTED') ||
                   str_contains((string) $stageDetails, 'installation directory needs to be created') ||
                   str_contains((string) $stageDetails, 'No files staged');
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get the install/upgrade button for a database if present
     */
    private function hasInstallButton(string $dbType): bool
    {
        try {
            $button = $this->crawler->filter("#{$dbType}_install_button");
            return $button->count() > 0;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Verify the overview section is present
     */
    private function verifyOverviewSection(): void
    {
        $overviewText = $this->crawler->filter('#collapseOverview')->text();
        $this->assertStringContainsString('This page allows you to review', $overviewText);
        $this->assertStringContainsString('supported external dataloads', $overviewText);
    }

    /**
     * Get all supported database types shown on the page
     */
    private function getSupportedDatabaseTypes(): array
    {
        // These are the databases listed in dataloads_ajax.php
        return ['ICD10', 'RXNORM', 'SNOMED', 'CQM_VALUESET'];
    }

    /**
     * Verify all expected database sections exist
     */
    private function verifyDatabaseSectionsExist(): void
    {
        $databases = $this->getSupportedDatabaseTypes();

        foreach ($databases as $db) {
            $section = $this->crawler->filter("#collapse{$db}");
            $this->assertGreaterThan(0, $section->count(), "Database section for {$db} should exist");
        }
    }

    /**
     * Check if help instructions link exists
     */
    private function hasInstructionsLink(string $dbType): bool
    {
        try {
            $link = $this->crawler->filter("#{$dbType}_instrmsg");
            return $link->count() > 0;
        } catch (\Exception) {
            return false;
        }
    }
}
