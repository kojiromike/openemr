<?php

/**
 * CcdaTemplateCodesTest.php - Unit tests for CcdaTemplateCodes
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\Carecoordination\PhpCcdaBuilder\CodeSystems;

use PHPUnit\Framework\TestCase;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\CodeSystems\CcdaTemplateCodes;

class CcdaTemplateCodesTest extends TestCase
{
    // ========== Document Level Codes ==========

    public function testGetCcdCode(): void
    {
        $result = CcdaTemplateCodes::get('CCD');

        $this->assertEquals('34133-9', $result['code']);
        $this->assertEquals('Summarization of Episode Note', $result['name']);
        $this->assertEquals('2.16.840.1.113883.6.1', $result['code_system']);
        $this->assertEquals('LOINC', $result['code_system_name']);
    }

    // ========== Section Codes ==========

    public function testGetAllergiesSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('AllergiesSection');

        $this->assertEquals('48765-2', $result['code']);
        $this->assertEquals('Allergies, adverse reactions, alerts', $result['name']);
        $this->assertEquals('LOINC', $result['code_system_name']);
    }

    public function testGetMedicationsSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('MedicationsSection');

        $this->assertEquals('10160-0', $result['code']);
        $this->assertEquals('History of medication use', $result['name']);
    }

    public function testGetProblemsSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('ProblemsSection');

        $this->assertEquals('11450-4', $result['code']);
        $this->assertEquals('Problem list', $result['name']);
    }

    public function testGetProceduresSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('ProceduresSection');

        $this->assertEquals('47519-4', $result['code']);
        $this->assertEquals('History of procedures', $result['name']);
    }

    public function testGetResultsSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('ResultsSection');

        $this->assertEquals('30954-2', $result['code']);
        $this->assertEquals('Relevant diagnostic tests and/or laboratory data', $result['name']);
    }

    public function testGetEncountersSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('EncountersSection');

        $this->assertEquals('46240-8', $result['code']);
        $this->assertEquals('History of encounters', $result['name']);
    }

    public function testGetImmunizationsSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('ImmunizationsSection');

        $this->assertEquals('11369-6', $result['code']);
        $this->assertEquals('History of immunizations', $result['name']);
    }

    public function testGetVitalSignsSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('VitalSignsSection');

        $this->assertEquals('8716-3', $result['code']);
        $this->assertEquals('Vital signs', $result['name']);
    }

    public function testGetSocialHistorySectionCode(): void
    {
        $result = CcdaTemplateCodes::get('SocialHistorySection');

        $this->assertEquals('29762-2', $result['code']);
        $this->assertEquals('Social history', $result['name']);
    }

    public function testGetPlanOfCareSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('PlanOfCareSection');

        $this->assertEquals('18776-5', $result['code']);
        $this->assertEquals('Plan of care note', $result['name']);
    }

    public function testGetGoalsSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('GoalsSection');

        $this->assertEquals('61146-7', $result['code']);
        $this->assertEquals('Goals', $result['name']);
    }

    public function testGetHealthConcernsSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('HealthConcernsSection');

        $this->assertEquals('75310-3', $result['code']);
        $this->assertEquals('Health concerns document', $result['name']);
    }

    public function testGetCareTeamSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('CareTeamSection');

        $this->assertEquals('85847-2', $result['code']);
        $this->assertEquals('Patient Care team information', $result['name']);
    }

    public function testGetPayersSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('PayersSection');

        $this->assertEquals('48768-6', $result['code']);
        $this->assertEquals('Payment sources', $result['name']);
    }

    public function testGetAdvanceDirectivesSectionCode(): void
    {
        $result = CcdaTemplateCodes::get('AdvanceDirectivesSection');

        $this->assertEquals('42348-3', $result['code']);
        $this->assertEquals('Advance directives', $result['name']);
    }

    // ========== Entry Level Codes ==========

    public function testGetAllergyProblemActCode(): void
    {
        $result = CcdaTemplateCodes::get('AllergyProblemAct');

        $this->assertEquals('48765-2', $result['code']);
    }

    public function testGetSmokingStatusObservationCode(): void
    {
        $result = CcdaTemplateCodes::get('SmokingStatusObservation');

        $this->assertEquals('72166-2', $result['code']);
        $this->assertEquals('Tobacco smoking status NHIS', $result['name']);
    }

    public function testGetVitalSignsOrganizerCode(): void
    {
        $result = CcdaTemplateCodes::get('VitalSignsOrganizer');

        $this->assertEquals('46680005', $result['code']);
        $this->assertEquals('Vital signs', $result['name']);
        $this->assertEquals('SNOMED CT', $result['code_system_name']);
    }

    public function testGetCareTeamOrganizerCode(): void
    {
        $result = CcdaTemplateCodes::get('CareTeamOrganizer');

        $this->assertEquals('86744-0', $result['code']);
        $this->assertEquals('Care Team', $result['name']);
    }

    // ========== Utility Methods ==========

    public function testExistsWithValidCode(): void
    {
        $this->assertTrue(CcdaTemplateCodes::exists('CCD'));
        $this->assertTrue(CcdaTemplateCodes::exists('AllergiesSection'));
        $this->assertTrue(CcdaTemplateCodes::exists('SmokingStatusObservation'));
    }

    public function testExistsWithInvalidCode(): void
    {
        $this->assertFalse(CcdaTemplateCodes::exists('NonExistentCode'));
        $this->assertFalse(CcdaTemplateCodes::exists(''));
    }

    public function testGetWithUnknownCodeReturnsDefault(): void
    {
        $result = CcdaTemplateCodes::get('UnknownCode');

        $this->assertNull($result['code']);
        $this->assertEquals('UnknownCode', $result['name']);
        $this->assertNull($result['code_system']);
        $this->assertNull($result['code_system_name']);
    }

    public function testSetAddsNewCode(): void
    {
        CcdaTemplateCodes::set('CustomCode', [
            'code' => '99999-9',
            'name' => 'Custom Test Code',
            'code_system' => '2.16.840.1.113883.6.1',
            'code_system_name' => 'LOINC',
        ]);

        $this->assertTrue(CcdaTemplateCodes::exists('CustomCode'));

        $result = CcdaTemplateCodes::get('CustomCode');
        $this->assertEquals('99999-9', $result['code']);
        $this->assertEquals('Custom Test Code', $result['name']);
    }

    public function testAllReturnsAllCodes(): void
    {
        $all = CcdaTemplateCodes::all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('CCD', $all);
        $this->assertArrayHasKey('AllergiesSection', $all);
        $this->assertArrayHasKey('MedicationsSection', $all);
        $this->assertArrayHasKey('ProblemsSection', $all);

        // Should have multiple codes
        $this->assertGreaterThan(20, count($all));
    }

    public function testAllCodesHaveRequiredKeys(): void
    {
        $all = CcdaTemplateCodes::all();

        foreach ($all as $name => $code) {
            $this->assertArrayHasKey('code', $code, "Code '{$name}' missing 'code' key");
            $this->assertArrayHasKey('name', $code, "Code '{$name}' missing 'name' key");
            $this->assertArrayHasKey('code_system', $code, "Code '{$name}' missing 'code_system' key");
            $this->assertArrayHasKey('code_system_name', $code, "Code '{$name}' missing 'code_system_name' key");
        }
    }

    // ========== LOINC Code Verification ==========

    public function testLoincCodesHaveCorrectCodeSystem(): void
    {
        $loincSections = [
            'AllergiesSection',
            'MedicationsSection',
            'ProblemsSection',
            'ResultsSection',
            'VitalSignsSection',
            'SocialHistorySection',
        ];

        foreach ($loincSections as $section) {
            $result = CcdaTemplateCodes::get($section);
            $this->assertEquals('2.16.840.1.113883.6.1', $result['code_system'], "Section '{$section}' should have LOINC code system");
            $this->assertEquals('LOINC', $result['code_system_name'], "Section '{$section}' should have LOINC code system name");
        }
    }

    // ========== SNOMED CT Code Verification ==========

    public function testSnomedCodesHaveCorrectCodeSystem(): void
    {
        $snomedCodes = [
            'VitalSignsOrganizer',
            'AgeObservation',
        ];

        foreach ($snomedCodes as $codeName) {
            $result = CcdaTemplateCodes::get($codeName);
            $this->assertEquals('2.16.840.1.113883.6.96', $result['code_system'], "Code '{$codeName}' should have SNOMED CT code system");
            $this->assertEquals('SNOMED CT', $result['code_system_name'], "Code '{$codeName}' should have SNOMED CT code system name");
        }
    }
}
