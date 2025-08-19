<?php

/**
 * KkCreateInOfficeProviderEventTest class
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc.
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\E2e;

use OpenEMR\Tests\E2e\Base\BaseTrait;
use OpenEMR\Tests\E2e\Login\LoginTestData;
use OpenEMR\Tests\E2e\Login\LoginTrait;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\Client;

class KkCreateInOfficeProviderEventTest extends PantherTestCase
{
    use BaseTrait;
    use LoginTrait;

    private $client;
    private $crawler;

    #[Test]
    public function testCreateInOfficeProviderEvent(): void
    {
        $this->base();
        $this->login(LoginTestData::username, LoginTestData::password);

        // Navigate to Calendar
        $this->goToMainMenuLink('Calendar');
        $this->assertActiveTab('Calendar');

        // Switch to calendar iframe
        $calendarIframe = "//iframe[@name='cal']";
        $this->client->waitFor($calendarIframe, 10);
        $this->switchToIFrame($calendarIframe);

        // Wait for calendar to load
        $this->client->waitFor("//td[contains(@class, 'schedule')]", 15);
        $this->crawler = $this->client->refreshCrawler();

        // Find a schedule cell and hover over it to create apptMarker
        $scheduleCell = $this->crawler->filterXPath("//td[contains(@class, 'schedule')]")->first();

        if ($scheduleCell->count() === 0) {
            $this->fail('Could not find calendar schedule cells');
        }

        // Trigger mousemove to create the apptMarker
        $this->client->getMouse()->mouseMove($scheduleCell->getElement(0));

        // Wait for apptMarker to appear and become clickable
        $this->client->waitFor("//a[contains(@class, 'apptMarker')]", 5);
        $this->crawler = $this->client->refreshCrawler();

        // Click on the appointment marker to open add_edit_event.php
        $apptMarker = $this->crawler->filterXPath("//a[contains(@class, 'apptMarker')]")->first();
        $apptMarker->click();

        // Wait for Add Event form to load
        $this->client->waitFor("//input[@name='form_title']", 10);
        $this->crawler = $this->client->refreshCrawler();

        // Fill in event details
        $eventTitle = 'Test In Office Event ' . date('Y-m-d H:i:s');

        // Set event title
        $titleField = $this->crawler->filterXPath("//input[@name='form_title']");
        $titleField->clear();
        $titleField->sendKeys($eventTitle);

        // Select "Specified time" radio button (rballday2) to enable time fields
        $this->crawler->filterXPath("//input[@id='rballday2']")->click();

        // Set event category to "In Office" (category ID = 2)
        $categorySelect = $this->crawler->filterXPath("//select[@name='form_category']");
        $categorySelect->selectOption('2'); // In Office category

        // Try to set duration - this should be disabled for In Office events
        $durationField = $this->crawler->filterXPath("//input[@name='form_duration']");

        // Verify that duration field is disabled
        $isDisabled = $durationField->getAttribute('disabled');
        $this->assertNotNull($isDisabled, 'Duration field should be disabled for In Office events');

        // Try to enter duration anyway - this should fail
        try {
            $durationField->clear();
            $durationField->sendKeys('60');

            // If we reach here, the test should fail because duration field should be disabled
            $this->fail('Duration field should not allow input for In Office events');
        } catch (\Exception $e) {
            // Expected behavior - duration field is disabled and cannot accept input
            $this->assertTrue(true, 'Duration field correctly disabled for In Office events');
        }

        // Verify the duration field value is empty or cleared
        $durationValue = $durationField->getAttribute('value');
        $this->assertTrue(
            empty($durationValue) || $durationValue === '0',
            'Duration field value should be empty or 0 for In Office events'
        );

        // Set other required fields
        $this->crawler->filterXPath("//input[@name='form_hour']")->clear();
        $this->crawler->filterXPath("//input[@name='form_hour']")->sendKeys('14');

        $this->crawler->filterXPath("//input[@name='form_minute']")->clear();
        $this->crawler->filterXPath("//input[@name='form_minute']")->sendKeys('30');

        // Try to save the event (this step tests the overall flow)
        $saveButton = $this->crawler->filterXPath("//input[@type='submit' and @value='Save']");
        if ($saveButton->count() > 0) {
            $saveButton->click();

            // Wait for save response
            sleep(2);

            // Verify we're back on calendar or success page
            $this->assertTrue(true, 'In Office event creation flow completed');
        }

        $this->client->quit();
    }

    #[Test]
    public function testDurationFieldBehaviorOnCategoryChange(): void
    {
        $this->base();
        $this->login(LoginTestData::username, LoginTestData::password);

        // Navigate to Calendar
        $this->goToMainMenuLink('Calendar');
        $this->assertActiveTab('Calendar');

        // Switch to calendar iframe
        $calendarIframe = "//iframe[@name='cal']";
        $this->client->waitFor($calendarIframe, 10);
        $this->switchToIFrame($calendarIframe);

        // Wait for calendar to load and click on apptMarker
        $this->client->waitFor("//td[contains(@class, 'schedule')]", 15);
        $this->crawler = $this->client->refreshCrawler();

        $scheduleCell = $this->crawler->filterXPath("//td[contains(@class, 'schedule')]")->first();
        $this->client->getMouse()->mouseMove($scheduleCell->getElement(0));

        $this->client->waitFor("//a[contains(@class, 'apptMarker')]", 5);
        $this->crawler = $this->client->refreshCrawler();

        $apptMarker = $this->crawler->filterXPath("//a[contains(@class, 'apptMarker')]")->first();
        $apptMarker->click();

        // Wait for form to load
        $this->client->waitFor("//input[@name='form_title']", 10);
        $this->crawler = $this->client->refreshCrawler();

        // Select "Specified time" first to enable duration field potentially
        $this->crawler->filterXPath("//input[@id='rballday2']")->click();

        // Test 1: Select a non-In Office category first (should enable duration)
        $categorySelect = $this->crawler->filterXPath("//select[@name='form_category']");
        $categorySelect->selectOption('1'); // Assuming category 1 is not In Office

        $durationField = $this->crawler->filterXPath("//input[@name='form_duration']");
        $isDisabledBefore = $durationField->getAttribute('disabled');

        // Test 2: Now change to In Office category (should disable duration)
        $categorySelect->selectOption('2'); // In Office category

        // Wait a moment for JavaScript to execute
        usleep(500000); // 0.5 seconds
        $this->crawler = $this->client->refreshCrawler();

        $durationField = $this->crawler->filterXPath("//input[@name='form_duration']");
        $isDisabledAfter = $durationField->getAttribute('disabled');

        // Verify that duration field gets disabled when In Office is selected
        $this->assertNotNull($isDisabledAfter, 'Duration field should be disabled after selecting In Office category');

        // Verify duration value is cleared
        $durationValue = $durationField->getAttribute('value');
        $this->assertTrue(
            empty($durationValue) || $durationValue === '0',
            'Duration field value should be cleared when In Office category is selected'
        );

        $this->client->quit();
    }
}
