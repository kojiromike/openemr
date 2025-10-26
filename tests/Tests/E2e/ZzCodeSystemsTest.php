<?php

/**
 * ZzCodeSystemsTest class
 *
 * E2E tests for the external code systems interface (RXNORM, SNOMED, ICD10, CQM_VALUESET)
 * Tests the web interface at interface/code_systems/
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\E2e;

use OpenEMR\Tests\E2e\Base\BaseTrait;
use OpenEMR\Tests\E2e\CodeSystems\CodeSystemsTrait;
use OpenEMR\Tests\E2e\Login\LoginTestData;
use OpenEMR\Tests\E2e\Login\LoginTrait;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Panther\PantherTestCase;

class ZzCodeSystemsTest extends PantherTestCase
{
    use BaseTrait;
    use LoginTrait;
    use CodeSystemsTrait;

    private $client;
    private $crawler;

    /**
     * Test: Can access the External Data Loads page
     */
    #[Test]
    public function testCanAccessCodeSystemsPage(): void
    {
        $this->base();
        try {
            // Login first
            $this->login(LoginTestData::username, LoginTestData::password);

            // Navigate to code systems page
            $this->navigateToCodeSystemsPage();

            // Verify page title
            $title = $this->client->getTitle();
            $this->assertStringContainsString('External Data Loads', $title);
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: Overview section is visible and contains expected content
     */
    #[Test]
    public function testOverviewSectionExists(): void
    {
        $this->base();
        try {
            $this->login(LoginTestData::username, LoginTestData::password);
            $this->navigateToCodeSystemsPage();

            $this->verifyOverviewSection();
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: All supported database sections exist (ICD10, RXNORM, SNOMED, CQM_VALUESET)
     */
    #[Test]
    public function testAllDatabaseSectionsExist(): void
    {
        $this->base();
        try {
            $this->login(LoginTestData::username, LoginTestData::password);
            $this->navigateToCodeSystemsPage();

            $this->verifyDatabaseSectionsExist();
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: Can expand ICD10 section and view installation status
     */
    #[Test]
    public function testCanExpandICD10Section(): void
    {
        $this->base();
        try {
            $this->login(LoginTestData::username, LoginTestData::password);
            $this->navigateToCodeSystemsPage();

            $this->expandDatabaseSection('ICD10');

            // Should show either "Not installed" or version information
            $hasStatus = $this->isDatabaseNotInstalled('ICD10') ||
                        $this->isDatabaseInstalled('ICD10');

            $this->assertTrue($hasStatus, 'ICD10 section should show installation status');
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: Can expand RXNORM section and view installation status
     */
    #[Test]
    public function testCanExpandRXNORMSection(): void
    {
        $this->base();
        try {
            $this->login(LoginTestData::username, LoginTestData::password);
            $this->navigateToCodeSystemsPage();

            $this->expandDatabaseSection('RXNORM');

            $hasStatus = $this->isDatabaseNotInstalled('RXNORM') ||
                        $this->isDatabaseInstalled('RXNORM');

            $this->assertTrue($hasStatus, 'RXNORM section should show installation status');
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: Can expand SNOMED section and view installation status
     */
    #[Test]
    public function testCanExpandSNOMEDSection(): void
    {
        $this->base();
        try {
            $this->login(LoginTestData::username, LoginTestData::password);
            $this->navigateToCodeSystemsPage();

            $this->expandDatabaseSection('SNOMED');

            $hasStatus = $this->isDatabaseNotInstalled('SNOMED') ||
                        $this->isDatabaseInstalled('SNOMED');

            $this->assertTrue($hasStatus, 'SNOMED section should show installation status');
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: Can expand CQM_VALUESET section and view installation status
     */
    #[Test]
    public function testCanExpandCQMValuesetSection(): void
    {
        $this->base();
        try {
            $this->login(LoginTestData::username, LoginTestData::password);
            $this->navigateToCodeSystemsPage();

            $this->expandDatabaseSection('CQM_VALUESET');

            $hasStatus = $this->isDatabaseNotInstalled('CQM_VALUESET') ||
                        $this->isDatabaseInstalled('CQM_VALUESET');

            $this->assertTrue($hasStatus, 'CQM_VALUESET section should show installation status');
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: Staged files section shows appropriate messages
     *
     * This test verifies that when no files are staged, appropriate error messages are shown
     */
    #[Test]
    public function testStagedFilesShowsMessages(): void
    {
        $this->base();
        try {
            $this->login(LoginTestData::username, LoginTestData::password);
            $this->navigateToCodeSystemsPage();

            // Expand ICD10 as an example
            $this->expandDatabaseSection('ICD10');

            // Wait for stage details to load
            sleep(2);

            // Should either show errors (no files staged) or have files listed
            $stageSection = $this->crawler->filter('#ICD10_stage_details');
            $this->assertGreaterThan(0, $stageSection->count(), 'Stage details section should exist');

            $stageText = $stageSection->text();
            // Should show some content - either error messages or file information
            $this->assertNotEmpty($stageText, 'Stage details should show some content');
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: list_installed.php AJAX endpoint returns data
     *
     * This directly tests the AJAX endpoint that returns installation information
     */
    #[Test]
    public function testListInstalledEndpointReturnsData(): void
    {
        $this->base();
        try {
            $this->login(LoginTestData::username, LoginTestData::password);

            // Directly request the list_installed.php endpoint for each database type
            $databases = ['ICD10', 'RXNORM', 'SNOMED', 'CQM_VALUESET'];

            foreach ($databases as $db) {
                $this->crawler = $this->client->request(
                    'GET',
                    "/interface/code_systems/list_installed.php?db={$db}&testing_mode=1"
                );

                // Should get some response (either "Not installed" or version info)
                $pageContent = $this->crawler->filter('body')->text();
                $this->assertNotEmpty($pageContent, "list_installed.php should return content for {$db}");

                // Should contain either "Not installed" or installation details
                $hasExpectedContent = str_contains($pageContent, 'Not installed') ||
                                     str_contains($pageContent, 'Name:') ||
                                     str_contains($pageContent, 'Revision:');

                $this->assertTrue($hasExpectedContent, "list_installed.php should show status for {$db}");
            }
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: list_staged.php AJAX endpoint returns data
     *
     * This directly tests the AJAX endpoint that validates and lists staged files
     */
    #[Test]
    public function testListStagedEndpointReturnsData(): void
    {
        $this->base();
        try {
            $this->login(LoginTestData::username, LoginTestData::password);

            // Directly request the list_staged.php endpoint for each database type
            $databases = ['ICD10', 'RXNORM', 'SNOMED', 'CQM_VALUESET'];

            foreach ($databases as $db) {
                $this->crawler = $this->client->request(
                    'GET',
                    "/interface/code_systems/list_staged.php?db={$db}&testing_mode=1"
                );

                // Should get some response
                $pageContent = $this->crawler->filter('body')->text();
                $this->assertNotEmpty($pageContent, "list_staged.php should return content for {$db}");

                // Should contain staging information (errors, files, or instructions)
                $hasExpectedContent = str_contains($pageContent, 'No files staged') ||
                                     str_contains($pageContent, 'UNSUPPORTED') ||
                                     str_contains($pageContent, 'installation directory') ||
                                     str_contains($pageContent, '.zip') ||
                                     str_contains($pageContent, 'INSTALL') ||
                                     str_contains($pageContent, 'UPGRADE');

                $this->assertTrue($hasExpectedContent, "list_staged.php should show staging info for {$db}");
            }
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: Accordion sections properly collapse and expand
     */
    #[Test]
    public function testAccordionFunctionality(): void
    {
        $this->base();
        try {
            $this->login(LoginTestData::username, LoginTestData::password);
            $this->navigateToCodeSystemsPage();

            // Initially, database sections should be collapsed
            $icd10Section = $this->crawler->filter('#collapseICD10');
            $this->assertGreaterThan(0, $icd10Section->count(), 'ICD10 collapse section should exist');

            // Expand ICD10
            $this->expandDatabaseSection('ICD10');

            // Give it a moment to expand
            sleep(1);

            // Section should now have content visible
            $installDetails = $this->crawler->filter('#ICD10_install_details');
            $this->assertGreaterThan(0, $installDetails->count(), 'Install details should be visible after expansion');
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }

    /**
     * Test: Page requires admin authentication
     *
     * This test verifies that non-admin users cannot access the code systems page
     */
    #[Test]
    public function testPageRequiresAdminAccess(): void
    {
        $this->base();
        try {
            // Try to access without logging in
            $this->crawler = $this->client->request(
                'GET',
                '/interface/code_systems/dataloads_ajax.php?testing_mode=1'
            );

            // Should either redirect to login or show unauthorized message
            $title = $this->client->getTitle();
            $pageContent = $this->crawler->filter('body')->text();

            $isUnauthorized = $title === 'OpenEMR Login' ||
                             str_contains($pageContent, 'Not Authorized') ||
                             str_contains($pageContent, 'unauthorized');

            $this->assertTrue($isUnauthorized, 'Page should require authentication');
        } catch (\Throwable $e) {
            $this->client->quit();
            throw $e;
        }
        $this->client->quit();
    }
}
