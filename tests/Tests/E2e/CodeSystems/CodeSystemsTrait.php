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
    }

    /**
     * Check if a database section shows "Not installed"
     */
    private function isDatabaseNotInstalled(string $dbType): bool
    {
        $installDetails = $this->crawler->filter("#{$dbType}_install_details")->text();
        return str_contains($installDetails, 'Not installed');
    }

    /**
     * Check if a database section shows installed version info
     */
    private function isDatabaseInstalled(string $dbType): bool
    {
        $installDetails = $this->crawler->filter("#{$dbType}_install_details")->text();
        return str_contains($installDetails, 'Name:') ||
               str_contains($installDetails, 'Revision:') ||
               str_contains($installDetails, 'Release Date:');
    }

    /**
     * Check if staged files section shows error messages
     */
    private function hasStageErrors(string $dbType): bool
    {
        $stageDetails = $this->crawler->filter("#{$dbType}_stage_details")->text();
        return str_contains($stageDetails, 'ERROR') ||
               str_contains($stageDetails, 'UNSUPPORTED') ||
               str_contains($stageDetails, 'installation directory needs to be created') ||
               str_contains($stageDetails, 'No files staged');
    }

    /**
     * Get the install/upgrade button for a database if present
     */
    private function hasInstallButton(string $dbType): bool
    {
        try {
            $button = $this->crawler->filter("#{$dbType}_install_button");
            return $button->count() > 0;
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return false;
        }
    }
}
