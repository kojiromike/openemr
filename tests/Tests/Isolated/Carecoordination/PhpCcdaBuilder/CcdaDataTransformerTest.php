<?php

/**
 * CcdaDataTransformerTest.php - Unit tests for CcdaDataTransformer
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc <https://opencoreemr.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\Carecoordination\PhpCcdaBuilder;

use PHPUnit\Framework\TestCase;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\CcdaDataTransformer;

class CcdaDataTransformerTest extends TestCase
{
    private CcdaDataTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new CcdaDataTransformer();
    }

    public function testTransformReturnsArrayWithDataAndMeta(): void
    {
        $input = $this->getMinimalPatientData();
        $result = $this->transformer->transform($input);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
    }

    public function testTransformIncludesDemographics(): void
    {
        $input = $this->getMinimalPatientData();
        $result = $this->transformer->transform($input);

        $this->assertArrayHasKey('demographics', $result['data']);
    }

    public function testTransformHandlesEmptyAllergies(): void
    {
        $input = $this->getMinimalPatientData();
        $result = $this->transformer->transform($input);

        $this->assertArrayHasKey('allergies', $result['data']);
        $this->assertIsArray($result['data']['allergies']);
    }

    public function testTransformHandlesSingleAllergy(): void
    {
        $input = $this->getMinimalPatientData();
        $input['allergies'] = [
            'allergy' => [
                'title' => 'Penicillin',
                'rxnorm_drugcode' => '7984',
                'begdate' => '2020-01-15',
            ],
        ];
        $result = $this->transformer->transform($input);

        $this->assertCount(1, $result['data']['allergies']);
    }

    public function testTransformHandlesMultipleAllergies(): void
    {
        $input = $this->getMinimalPatientData();
        $input['allergies'] = [
            'allergy' => [
                ['title' => 'Penicillin', 'rxnorm_drugcode' => '7984'],
                ['title' => 'Aspirin', 'rxnorm_drugcode' => '1191'],
            ],
        ];
        $result = $this->transformer->transform($input);

        $this->assertCount(2, $result['data']['allergies']);
    }

    public function testTransformHandlesMedications(): void
    {
        $input = $this->getMinimalPatientData();
        $input['medications'] = [
            'medication' => [
                'drug' => 'Lisinopril',
                'rxnorm' => '197884',
                'start_date' => '2024-01-01',
            ],
        ];
        $result = $this->transformer->transform($input);

        $this->assertCount(1, $result['data']['medications']);
        $this->assertEquals('Lisinopril', $result['data']['medications'][0]['product']['unencoded_name']);
    }

    public function testTransformHandlesProblems(): void
    {
        $input = $this->getMinimalPatientData();
        $input['problem_lists'] = [
            'problem' => [
                'title' => 'Hypertension',
                'code' => '38341003',
                'code_text' => 'Hypertensive disorder',
            ],
        ];
        $result = $this->transformer->transform($input);

        $this->assertCount(1, $result['data']['problems']);
    }

    public function testTransformHandlesVitals(): void
    {
        $input = $this->getMinimalPatientData();
        $input['vitals'] = [
            'vital' => [
                'date' => '2024-01-15',
                'height' => '180',
                'weight' => '75',
                'bps' => '120',
                'bpd' => '80',
            ],
        ];
        $result = $this->transformer->transform($input);

        $this->assertArrayHasKey('vitals', $result['data']);
    }

    public function testTransformUnstructuredReturnsMinimalDocument(): void
    {
        $input = $this->getMinimalPatientData();
        $result = $this->transformer->transformUnstructured($input);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('demographics', $result['data']);
    }

    public function testTransformIncludesCcdaHeader(): void
    {
        $input = $this->getMinimalPatientData();
        $result = $this->transformer->transform($input);

        $this->assertArrayHasKey('ccda_header', $result['meta']);
    }

    /**
     * Provide minimal patient data for testing
     * @codeCoverageIgnore Data provider runs before instrumentation
     */
    private function getMinimalPatientData(): array
    {
        return [
            'patient' => [
                'id' => '1',
                'uuid' => 'test-uuid-1234',
                'fname' => 'John',
                'lname' => 'Doe',
                'dob' => '1990-01-15',
                'gender' => 'Male',
                'gender_code' => 'M',
                'race' => 'White',
                'race_code' => '2106-3',
                'ethnicity' => 'Not Hispanic or Latino',
                'ethnicity_code' => '2186-5',
                'street' => '123 Main St',
                'city' => 'Boston',
                'state' => 'MA',
                'postalCode' => '02101',
                'author' => [
                    'fname' => 'Jane',
                    'lname' => 'Doctor',
                    'npi' => '1234567890',
                    'facility_oid' => '2.16.840.1.113883.4.6',
                ],
            ],
            'created_time' => '20240115120000',
            'timezone_local_offset' => '+0000',
        ];
    }
}
