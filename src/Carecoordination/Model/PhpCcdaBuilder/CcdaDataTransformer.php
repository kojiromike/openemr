<?php

/**
 * CcdaDataTransformer.php - Data Transformation for CCDA Generation
 *
 * This class replaces the populate*() functions from serveccda.js,
 * transforming the raw XML data from EncounterccdadispatchTable into
 * the structured format required by CCDA templates.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2025 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Carecoordination\Model\PhpCcdaBuilder;

use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\CodeSystems\CcdaTemplateCodes;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Utils\DateFormatter;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Utils\CodeCleaner;

class CcdaDataTransformer
{
    // Global context variables (mirrors serveccda.js globals)
    /** @var array<string, mixed> */
    private array $all = [];
    private string $oidFacility = '';
    private string $npiProvider = '';
    private string $npiFacility = '';
    private string $authorDateTime = '';

    /**
     * Route code mapping (from serveccda.js mapRouteCode)
     */
    private const ROUTE_MAP = [
        'PO' => 'C38288',
        'ORAL' => 'C38288',
        'IV' => 'C38276',
        'IM' => 'C28161',
        'SC' => 'C38299',
        'SUBCUT' => 'C38299',
        'SQ' => 'C38299',
        'TOP' => 'C38304',
        'TOPICAL' => 'C38304',
        'INH' => 'C38216',
        'NASAL' => 'C38284',
        'OPTH' => 'C38287',
        'OTIC' => 'C38192',
        'RECTAL' => 'C38295',
        'VAGINAL' => 'C38313',
        'SL' => 'C38300',
        'BUCCAL' => 'C38193',
        'TD' => 'C38305',
    ];

    /**
     * Main transform method - converts raw CCDA data to template format
     *
     * @param array<string, mixed> $pd The parsed CCDA data array
     * @return array<string, mixed> Transformed data ready for template engine
     * @return array<string, mixed>
     */
    public function transform(array $pd): array
    {
        // Initialize global context
        $this->initializeContext($pd);

        $doc = [];
        $data = [];

        // Demographics (required for all documents)
        $data['demographics'] = $this->populateDemographics($pd);

        // Providers
        if (!empty($pd['primary_care_provider'])) {
            $providers = $this->populateProviders();
            $data['demographics'] = array_merge($data['demographics'], $providers);
        }

        // Process each section based on available data
        $data['allergies'] = $this->processSection($pd, 'allergies', 'allergy', 'populateAllergy');
        $data['medications'] = $this->processSection($pd, 'medications', 'medication', 'populateMedication');
        $data['problems'] = $this->processSection($pd, 'problem_lists', 'problem', 'populateProblem');
        $data['procedures'] = $this->processSection($pd, 'procedures', 'procedure', 'populateProcedure');
        // NOTE: Node.js doesn't have a 'results' section - lab results may be in other sections
        // $data['results'] = $this->processSection($pd, 'results', 'result', 'populateResult');
        // Vitals may be at top level or nested in history_physical->vitals_list
        $vitalsSource = $pd;
        if (empty($pd['vitals'])) {
            $historyPhysicalVitals = $this->arr($pd['history_physical'] ?? []);
            $vitalsList = $this->arr($historyPhysicalVitals['vitals_list'] ?? []);
            if (!empty($vitalsList['vitals'])) {
                // Wrap the vitals data in the expected structure for processSection
                $vitalsData = $this->arr($vitalsList['vitals']);
                // If it's a single vitals record, wrap in array
                if (!array_key_exists(0, $vitalsData)) {
                    $vitalsData = [$vitalsData];
                }
                $vitalsSource = ['vitals' => ['vital' => $vitalsData]];
            }
        }
        $data['vitals'] = $this->processSection($vitalsSource, 'vitals', 'vital', 'populateVital');
        $data['immunizations'] = $this->processSection($pd, 'immunizations', 'immunization', 'populateImmunization');
        $data['encounters'] = $this->processSection($pd, 'encounter_list', 'encounter', 'populateEncounter');
        $data['plan_of_care'] = $this->processSection($pd, 'planofcare', 'item', 'populatePlanOfCare');
        $data['goals'] = $this->processSection($pd, 'goals', 'goal', 'populateGoal');
        $data['health_concerns'] = $this->processSection($pd, 'health_concerns', 'concern', 'populateHealthConcern');
        $data['medical_devices'] = $this->processSection($pd, 'medical_devices', 'device', 'populateMedicalDevice');

        // Social History
        $historyPhysical = $this->arr($pd['history_physical'] ?? []);
        if (!empty($historyPhysical)) {
            $data['social_history'] = $this->populateSocialHistory($historyPhysical);
        }

        // Care Team
        $careTeam = $this->arr($pd['care_team'] ?? []);
        if ($this->str($careTeam['is_active'] ?? null) === 'active') {
            $data['care_team'] = $this->populateCareTeamMembers($pd);
        }

        // Payers
        if (!empty($pd['payers']) && is_array($pd['payers'])) {
            $data['payers'] = $this->populatePayer($pd['payers']);
        }

        // Advance Directives
        $advanceDirectives = $this->arr($pd['advance_directives'] ?? []);
        $directives = $this->arr($advanceDirectives['directive'] ?? []);
        if (!empty($directives)) {
            $data['advance_directives'] = $this->processAdvanceDirectives($directives);
        }

        // Clinical Notes sections
        $noteSections = [
            'progress_note', 'hospital_course', 'discharge_summary',
            'discharge_diagnosis', 'discharge_medications', 'complications',
            'postprocedure_diagnosis', 'postoperative_diagnosis', 'preoperative_diagnosis',
            'procedure_description', 'procedure_indications', 'anesthesia',
            'estimated_blood_loss', 'procedure_findings', 'procedure_specimens',
            'assessment_plan', 'chief_complaint', 'physical_exam',
            'review_of_systems', 'general_status', 'history_past_illness'
        ];

        foreach ($noteSections as $noteSection) {
            $noteData = $this->arr($pd[$noteSection] ?? []);
            if (!empty($noteData)) {
                $data[$noteSection] = $this->populateNote($noteData);
            }
        }

        // Assemble document
        $doc['data'] = $data;
        $doc['meta'] = $this->getMeta($pd);
        $doc['meta']['ccda_header'] = $this->populateHeader($pd);

        // Apply timezone
        $timezoneOffset = $this->str($pd['timezone_local_offset'] ?? null);
        if ($timezoneOffset !== '') {
            $this->applyTimezones($doc, $timezoneOffset);
        }

        /** @var array<string, mixed> $doc */
        return $doc;
    }

    /**
     * Transform data for unstructured document
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    public function transformUnstructured(array $pd): array
    {
        $this->initializeContext($pd);
        $pd['doc_type'] = 'unstructured';

        $doc = [];
        $data = [];

        // Only demographics needed for unstructured
        $data['demographics'] = $this->populateDemographics($pd);

        if (!empty($pd['primary_care_provider'])) {
            $providers = $this->populateProviders();
            $data['demographics'] = array_merge($data['demographics'], $providers);
        }

        $doc['data'] = $data;
        $doc['meta'] = $this->getMeta($pd);
        $doc['meta']['ccda_header'] = $this->populateHeader($pd);

        $timezoneOffset = $this->str($pd['timezone_local_offset'] ?? null);
        if ($timezoneOffset !== '') {
            $this->applyTimezones($doc, $timezoneOffset);
        }

        /** @var array<string, mixed> $doc */
        return $doc;
    }

    /**
     * Initialize context variables from input data
     */
    /**
     * @param array<string, mixed> $pd
     */
    private function initializeContext(array $pd): void
    {
        $this->all = $pd;

        $primaryCareProvider = $this->arr($pd['primary_care_provider'] ?? []);
        $primaryProvider = $this->arr($primaryCareProvider['provider'] ?? []);
        $this->npiProvider = $this->str($primaryProvider['npi'] ?? null, 'NI');

        $encounterProvider = $this->arr($pd['encounter_provider'] ?? []);
        $this->oidFacility = $this->str($encounterProvider['facility_oid'] ?? null, '2.16.840.1.113883.19.5.99999.1');
        $this->npiFacility = $this->getNpiFacility($pd);

        // Determine author datetime
        $this->authorDateTime = $this->str($pd['created_time_timezone'] ?? null);
        $author = $this->arr($pd['author'] ?? []);
        $authorTime = $author['time'] ?? null;
        if (is_string($authorTime) && strlen($authorTime) > 7) {
            $this->authorDateTime = $authorTime;
        } else {
            $encounterList = $this->arr($pd['encounter_list'] ?? []);
            $encounterData = $encounterList['encounter'] ?? [];
            if (!empty($encounterData) && is_array($encounterData)) {
                // Check if it's a list of encounters or a single encounter
                if (array_key_exists(0, $encounterData) && is_array($encounterData[0])) {
                    $this->authorDateTime = $this->str($encounterData[0]['date'] ?? null);
                } else {
                    $this->authorDateTime = $this->str($encounterData['date'] ?? null);
                }
            }
        }
        $this->authorDateTime = $this->fDate($this->authorDateTime);
    }

    /**
     * Get facility NPI
     *
     * @param array<string, mixed> $pd
     */
    private function getNpiFacility(array $pd, bool $returnNi = false): string
    {
        $encProvider = $this->arr($pd['encounter_provider'] ?? []);
        $npi = $this->str($encProvider['facility_npi'] ?? null);
        if ($npi === '') {
            $primaryCareProvider = $this->arr($pd['primary_care_provider'] ?? []);
            $primaryProvider = $this->arr($primaryCareProvider['provider'] ?? []);
            $npi = $this->str($primaryProvider['facility_npi'] ?? null);
        }
        if ($npi === '' && $returnNi) {
            return 'NI';
        }
        return $npi;
    }

    /**
     * Process a section with multiple items
     * Handles various data structures from OpenEMR/serveccda.js
     *
     * @param array<string, mixed> $pd
     * @return list<array<string, mixed>>
     */
    private function processSection(array $pd, string $sectionKey, string $itemKey, string $populateMethod): array
    {
        /** @var list<array<string, mixed>> $result */
        $result = [];

        // Check if section exists at all
        if (empty($pd[$sectionKey])) {
            return $result;
        }

        // Get the section data
        $sectionData = $pd[$sectionKey];
        if (!is_array($sectionData)) {
            return $result;
        }

        // Handle nested structure: $pd['medications']['medication'][]
        if (isset($sectionData[$itemKey])) {
            $items = $sectionData[$itemKey];
        } elseif (array_key_exists(0, $sectionData)) {
            // Already an array at top level
            $items = $sectionData;
        } else {
            // Single object or empty
            $items = $sectionData;
        }

        // Ensure we have something to process
        if (empty($items) || !is_array($items)) {
            return $result;
        }

        // Handle single item vs array
        if (!array_key_exists(0, $items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (method_exists($this, $populateMethod)) {
                /** @var array<string, mixed> $populated */
                $populated = $this->$populateMethod($item);
                if (!empty($populated)) {
                    $result[] = $populated;
                }
            }
        }

        return $result;
    }

    /**
     * Count entities (single object vs array)
     */
    private function countEntities(mixed $data): int
    {
        if (empty($data)) {
            return 0;
        }
        if (is_array($data) && isset($data[0])) {
            return count($data);
        }
        if (is_array($data) || is_object($data)) {
            return 1;
        }
        return 0;
    }

    /**
     * Safe get - get nested array value with default
     *
     * @param array<string, mixed> $arr
     */
    private function safeGet(array $arr, string $path, mixed $default = ''): mixed
    {
        $keys = explode('.', $path);
        $value = $arr;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Safely extract a string value from mixed data
     */
    private function str(mixed $value, string $default = ''): string
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        return $default;
    }

    /**
     * Safely extract an array value from mixed data
     *
     * @return array<int|string, mixed>
     */
    private function arr(mixed $value): array
    {
        if (is_array($value)) {
            /** @var array<int|string, mixed> $value */
            return $value;
        }
        return [];
    }

    /**
     * Safely extract a float value from mixed data
     */
    private function num(mixed $value, float $default = 0.0): float
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        return $default;
    }

    /**
     * Format a date value from mixed data
     */
    private function fDate(mixed $value): string
    {
        return DateFormatter::fDate($this->str($value));
    }

    /**
     * Clean a code value from mixed data
     */
    private function cleanCode(mixed $value): string
    {
        return CodeCleaner::clean($this->str($value));
    }

    /**
     * Map route code to NCI Thesaurus code
     */
    private function mapRouteCode(?string $routeCode): string
    {
        if (empty($routeCode)) {
            return '';
        }

        $cleaned = $this->cleanCode($routeCode);

        // Already a valid NCI code
        if (preg_match('/^C\d+$/', $cleaned)) {
            return $cleaned;
        }

        $upper = strtoupper($cleaned);
        return self::ROUTE_MAP[$upper] ?? $cleaned;
    }

    /**
     * Build a race/ethnicity code object for CCDA output
     *
     * @param string $name Display name (e.g., "White", "Not Hispanic or Latino")
     * @param string $code Code value (e.g., "2106-3", "2186-5")
     * @return array{code?: string, name?: string, code_system?: string, code_system_name?: string}|string
     * @return array<string, mixed>
     */
    private function buildRaceEthnicityCode(string $name, string $code): array|string
    {
        if ($name === '' || $name === 'declined_to_specify') {
            return 'null_flavor';
        }

        if ($code === '') {
            // No code provided, return name only for fallback lookup
            return $name;
        }

        return [
            'code' => $code,
            'name' => $name,
            'code_system' => '2.16.840.1.113883.6.238',
            'code_system_name' => 'Race and Ethnicity - CDC',
        ];
    }

    // =========================================================================
    // Population Methods (mirrors serveccda.js)
    // =========================================================================

    /**
     * Populate demographics data - exactly matches populate-demographics.js
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateDemographics(array $pd): array
    {
        /** @var array<string, mixed> $patient */
        $patient = $this->arr($pd['patient'] ?? $pd);
        /** @var array<string, mixed> $guardian */
        $guardian = $this->arr($pd['guardian'] ?? []);
        /** @var array<string, mixed> $encounterProvider */
        $encounterProvider = $this->arr($pd['encounter_provider'] ?? []);

        // Build race/ethnicity code objects with code, name, and code system
        $race = $this->buildRaceEthnicityCode(
            $this->str($patient['race'] ?? ''),
            $this->str($patient['race_code'] ?? '')
        );
        $raceGroup = $this->buildRaceEthnicityCode(
            $this->str($patient['race_group'] ?? ''),
            $this->str($patient['race_group_code'] ?? '')
        );
        $ethnicity = $this->buildRaceEthnicityCode(
            $this->str($patient['ethnicity'] ?? ''),
            $this->str($patient['ethnicity_code'] ?? '')
        );

        return [
            'name' => [
                'prefix' => $this->str($patient['prefix'] ?? ''),
                'suffix' => $this->str($patient['suffix'] ?? ''),
                'middle' => [$this->str($patient['mname'] ?? '')],
                'last' => $this->str($patient['lname'] ?? ''),
                'first' => $this->str($patient['fname'] ?? ''),
            ],
            'birth_name' => [
                'middle' => $this->str($patient['birth_mname'] ?? ''),
                'last' => $this->str($patient['birth_lname'] ?? ''),
                'first' => $this->str($patient['birth_fname'] ?? ''),
            ],
            'dob' => [
                'point' => [
                    // Birth date should not include time component
                    'date' => DateFormatter::fDate($this->str($patient['dob'] ?? ''), false),
                    'precision' => 'day',
                ],
            ],
            'gender' => strtoupper($this->str($patient['gender'] ?? '')) ?: 'null_flavor',
            'identifiers' => [
                [
                    'identifier' => $this->oidFacility ?: $this->npiFacility,
                    'extension' => $this->str($patient['uuid'] ?? ''),
                ],
            ],
            'marital_status' => strtoupper($this->str($patient['status'] ?? '')),
            'addresses' => $this->fetchPreviousAddresses($patient),
            'phone' => [
                ['number' => $this->str($patient['phone_home'] ?? ''), 'type' => 'primary home'],
                ['number' => $this->str($patient['phone_mobile'] ?? ''), 'type' => 'primary mobile'],
                ['number' => $this->str($patient['phone_work'] ?? ''), 'type' => 'work place'],
                ['number' => $this->str($patient['phone_emergency'] ?? ''), 'type' => 'emergency contact'],
                ['email' => $this->str($patient['email'] ?? ''), 'type' => 'contact_email'],
            ],
            'ethnicity' => $ethnicity,
            'race' => $race,
            'race_additional' => $raceGroup,
            'languages' => [
                [
                    'language' => $this->getLanguageCode($patient),
                    'preferred' => true,
                    'mode' => 'Expressed spoken',
                    'proficiency' => 'Good',
                ],
            ],
            'attributed_provider' => [
                'identity' => [
                    [
                        'root' => '2.16.840.1.113883.4.6',
                        'extension' => $this->npiFacility ?: '',
                    ],
                ],
                'phone' => [
                    ['number' => $this->str($encounterProvider['facility_phone'] ?? '')],
                ],
                'name' => [
                    ['full' => $this->str($encounterProvider['facility_name'] ?? '')],
                ],
                'address' => [
                    [
                        'street_lines' => [$this->str($encounterProvider['facility_street'] ?? '')],
                        'city' => $this->str($encounterProvider['facility_city'] ?? ''),
                        'state' => $this->str($encounterProvider['facility_state'] ?? ''),
                        'zip' => $this->str($encounterProvider['facility_postal_code'] ?? ''),
                        'country' => $this->str($encounterProvider['facility_country_code'] ?? '', 'US'),
                        'use' => 'work place',
                    ],
                ],
            ],
            'guardians' => $this->getGuardianInfo($guardian),
        ];
    }

    /**
     * Get language code - matches populate-demographics.js
     *
     * @param array<string, mixed> $patient
     */
    private function getLanguageCode(array $patient): string
    {
        $lang = $this->str($patient['language'] ?? '');
        $langCode = $this->str($patient['language_code'] ?? 'en-US');
        return match ($lang) {
            'English' => 'en-US',
            'Spanish' => 'sp-US',
            default => $langCode,
        };
    }

    /**
     * Fetch previous addresses - matches previous-addresses.js
     *
     * @param array<string, mixed> $patient
     * @return list<array<string, mixed>>
     */
    private function fetchPreviousAddresses(array $patient): array
    {
        $addresses = [];

        // Build street lines helper
        $buildStreetLines = function($streets) {
            if (!is_array($streets)) {
                return [$streets];
            }
            $streetLines = [$streets[0] ?? ''];
            if (!empty($streets[1])) {
                $streetLines[] = $streets[1];
            }
            return $streetLines;
        };

        // Current address
        $addresses[] = [
            'use' => 'HP',
            'street_lines' => $buildStreetLines($patient['street'] ?? ''),
            'city' => $patient['city'] ?? '',
            'state' => $patient['state'] ?? '',
            'zip' => $patient['postalCode'] ?? '',
            'country' => $patient['country'] ?? 'US',
            'date_time' => [
                'low' => [
                    'date' => $this->fDate(''),
                    'precision' => 'day',
                ],
            ],
        ];

        // Previous addresses
        $previousAddresses = $this->arr($patient['previous_addresses'] ?? []);
        $prevAddresses = $this->arr($previousAddresses['address'] ?? []);
        if (!array_key_exists(0, $prevAddresses) && !empty($prevAddresses)) {
            $prevAddresses = [$prevAddresses];
        }
        foreach ($prevAddresses as $addr) {
            if (!is_array($addr)) {
                continue;
            }
                $addresses[] = [
                    'use' => $addr['use'] ?? 'BAD',
                    'street_lines' => $buildStreetLines($addr['street'] ?? ''),
                    'city' => $addr['city'] ?? '',
                    'state' => $addr['state'] ?? '',
                    'zip' => $addr['postalCode'] ?? '',
                    'country' => $addr['country'] ?? 'US',
                    'date_time' => [
                        'low' => [
                            'date' => $this->fDate($addr['period_start'] ?? ''),
                            'precision' => 'day',
                        ],
                        'high' => [
                            'date' => $this->fDate($addr['period_end'] ?? '') ?: $this->fDate(''),
                            'precision' => 'day',
                        ],
                    ],
                ];
        }

        return $addresses;
    }

    /**
     * Get guardian info - matches populate-demographics.js
     *
     * @param array<string, mixed> $guardian
     * @return list<array<string, mixed>>
     */
    private function getGuardianInfo(array $guardian): array
    {
        $displayName = $this->str($guardian['display_name'] ?? null);
        if ($displayName === '') {
            return [];
        }

        // Parse display name into first/last
        $parts = explode(' ', $displayName);
        $names = count($parts) === 3
            ? [['first' => $parts[0], 'last' => $parts[2]]]
            : (count($parts) === 2
                ? [['first' => $parts[0], 'last' => $parts[1]]]
                : [['first' => 'Not Informed', 'last' => 'Not Informed']]);

        return [[
            'relation' => $guardian['relation'] ?? '',
            'addresses' => [[
                'street_lines' => [$guardian['address'] ?? $guardian['street'] ?? ''],
                'city' => $guardian['city'] ?? '',
                'state' => $guardian['state'] ?? '',
                'zip' => $guardian['postalCode'] ?? '',
                'country' => $guardian['country'] ?? 'US',
                'use' => 'primary home',
            ]],
            'names' => $names,
            'phone' => [['number' => $guardian['telecom'] ?? '', 'type' => 'primary home']],
        ]];
    }

    /**
     * Populate provider - matches providers.js populateProvider()
     *
     * @param array<int|string, mixed> $provider
     * @return array<string, mixed>
     */
    private function populateProvider(array $provider): array
    {
        $encounterProvider = $this->arr($this->all['encounter_provider'] ?? []);

        return [
            'function_code' => !empty($provider['physician_type']) ? 'PP' : '',
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($provider['provider_since'] ?? ''),
                    'precision' => 'tz',
                ],
            ],
            'identity' => [
                [
                    'root' => !empty($provider['npi']) ? '2.16.840.1.113883.4.6' : $this->oidFacility,
                    'extension' => $provider['npi'] ?? $provider['table_id'] ?? 'NI',
                ],
            ],
            'type' => [
                [
                    'name' => $provider['taxonomy_description'] ?? '',
                    'code' => $this->cleanCode($provider['taxonomy'] ?? ''),
                    'code_system' => '2.16.840.1.113883.6.101',
                    'code_system_name' => 'NUCC Health Care Provider Taxonomy',
                ],
            ],
            'name' => [
                [
                    'last' => $provider['lname'] ?? '',
                    'first' => $provider['fname'] ?? '',
                ],
            ],
            'address' => [
                [
                    'street_lines' => [$this->str($encounterProvider['facility_street'] ?? null)],
                    'city' => $this->str($encounterProvider['facility_city'] ?? null),
                    'state' => $this->str($encounterProvider['facility_state'] ?? null),
                    'zip' => $this->str($encounterProvider['facility_postal_code'] ?? null),
                    'country' => $this->str($encounterProvider['facility_country_code'] ?? null, 'US'),
                ],
            ],
            'phone' => [
                ['number' => $this->str($encounterProvider['facility_phone'] ?? null)],
            ],
        ];
    }

    /**
     * Populate previous names
     *
     * @return list<array<string, mixed>>
     */
    private function populatePreviousNames(mixed $names): array
    {
        if (empty($names) || !is_array($names)) {
            return [];
        }

        // Ensure array
        if (!isset($names[0])) {
            $names = [$names];
        }

        $result = [];
        foreach ($names as $name) {
            if (!is_array($name)) {
                continue;
            }
            $result[] = [
                'first' => $this->str($name['previous_name_first'] ?? ''),
                'middle' => $this->str($name['previous_name_middle'] ?? ''),
                'last' => $this->str($name['previous_name_last'] ?? $name['formatted_name'] ?? ''),
                'prefix' => $this->str($name['previous_name_prefix'] ?? ''),
                'suffix' => $name['previous_name_suffix'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * Populate phone numbers (legacy - keeping for compatibility)
     *
     * @param array<string, mixed> $pd
     * @return list<array<string, mixed>>
     */
    private function populatePhones(array $pd): array
    {
        $phones = [];

        if (!empty($pd['phone_home'])) {
            $phones[] = [
                'number' => $pd['phone_home'],
                'type' => 'primary home',
            ];
        }
        if (!empty($pd['phone_mobile'])) {
            $phones[] = [
                'number' => $pd['phone_mobile'],
                'type' => 'mobile contact',
            ];
        }
        if (!empty($pd['phone_contact'])) {
            $phones[] = [
                'number' => $pd['phone_contact'],
                'type' => 'contact',
            ];
        }
        if (!empty($pd['email'])) {
            $phones[] = [
                'email' => $pd['email'],
                'type' => 'contact',
            ];
        }

        return $phones;
    }

    /**
     * Populate medication data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateMedication(array $pd): array
    {
        $pd['status'] = 'Completed'; // @todo handle prescribed status

        $author = $this->arr($pd['author'] ?? []);
        $extension = $this->str($pd['extension'] ?? null);

        return [
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($pd['start_date'] ?? ''),
                    'precision' => 'day',
                ],
                'high' => [
                    'date' => $this->fDate($pd['end_date'] ?? ''),
                    'precision' => 'day',
                ],
            ],
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? '',
                    'extension' => $extension,
                ],
            ],
            'status' => $pd['status'],
            'sig' => $pd['direction'] ?? '',
            'product' => [
                'identifiers' => [
                    [
                        'identifier' => $pd['sha_extension'] ?? '2a620155-9d11-439e-92b3-5d9815ff4ee8',
                        'extension' => $extension !== '' ? $extension . '_1' : '',
                    ],
                ],
                'unencoded_name' => $pd['drug'] ?? '',
                'product' => [
                    'name' => $pd['drug'] ?? '',
                    'code' => $this->cleanCode($pd['rxnorm'] ?? ''),
                    'code_system_name' => 'RXNORM',
                ],
            ],
            'author' => $this->buildAuthorBlock($author),
            'administration' => [
                'route' => [
                    'name' => $pd['route'] ?? '',
                    'code' => $this->mapRouteCode($this->str($pd['route_code'] ?? null)),
                    'code_system_name' => 'Medication Route FDA',
                ],
                'form' => [
                    'name' => $pd['form'] ?? '',
                    'code' => $this->cleanCode($pd['form_code'] ?? ''),
                    'code_system_name' => 'Medication Route FDA',
                ],
                'dose' => [
                    'value' => !empty($pd['size']) ? $this->num($pd['size']) : null,
                    'unit' => $pd['unit'] ?? '',
                ],
                'interval' => [
                    'period' => [
                        'value' => !empty($pd['dosage']) ? $this->num($pd['dosage']) : null,
                        'unit' => $pd['interval'] ?? null,
                    ],
                    'frequency' => true,
                ],
            ],
        ];
    }

    /**
     * Populate allergy data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateAllergy(array $pd): array
    {
        $author = $this->arr($pd['author'] ?? []);
        $extension = $this->str($pd['extension'] ?? null);

        return [
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? '',
                    'extension' => $extension,
                ],
            ],
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($pd['begdate'] ?? ''),
                    'precision' => 'day',
                ],
                'high' => [
                    'date' => $this->fDate($pd['enddate'] ?? ''),
                    'precision' => 'day',
                ],
            ],
            'observation' => [
                'identifiers' => [
                    [
                        'identifier' => $pd['sha_extension'] ?? '',
                        'extension' => $extension !== '' ? $extension . '_1' : '',
                    ],
                ],
                // intolerance is used for the <value> element
                'intolerance' => [
                    'name' => $pd['intolerance_title'] ?? $pd['type_title'] ?? '',
                    'code' => $this->cleanCode($pd['intolerance_code'] ?? $pd['type_code'] ?? ''),
                    'code_system' => '2.16.840.1.113883.6.96',
                    'code_system_name' => 'SNOMED CT',
                ],
                // allergen is used for the participant/playingEntity
                'allergen' => [
                    'name' => $pd['title'] ?? '',
                    'code' => $this->cleanCode($pd['rxnorm_drugcode'] ?? $pd['snomed_code'] ?? ''),
                    'code_system_name' => $pd['code_type'] ?? 'RXNORM',
                ],
                'status' => [
                    'name' => $pd['status'] ?? '',
                    'code' => $pd['status_code'] ?? '',
                ],
                'severity' => [
                    'code' => [
                        'name' => $pd['severity_al'] ?? '',
                        'code' => $pd['severity_al_code'] ?? '',
                        'code_system_name' => 'SNOMED CT',
                    ],
                ],
                'reactions' => [
                    [
                        'reaction' => [
                            'name' => $pd['reaction'] ?? '',
                            'code' => $this->cleanCode($pd['reaction_code'] ?? ''),
                            'code_system_name' => 'SNOMED CT',
                        ],
                    ],
                ],
            ],
            'author' => $this->buildAuthorBlock($author),
        ];
    }

    /**
     * Populate problem data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateProblem(array $pd): array
    {
        $author = $this->arr($pd['author'] ?? []);
        $extension = $this->str($pd['extension'] ?? null);

        return [
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? '',
                    'extension' => $extension,
                ],
            ],
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($pd['begdate'] ?? ''),
                    'precision' => 'day',
                ],
                'high' => [
                    'date' => $this->fDate($pd['enddate'] ?? ''),
                    'precision' => 'day',
                ],
            ],
            'problem' => [
                'identifiers' => [
                    [
                        'identifier' => $pd['sha_extension'] ?? '',
                        'extension' => $extension !== '' ? $extension . '_1' : '',
                    ],
                ],
                'code' => [
                    'name' => $pd['title'] ?? '',
                    'code' => $this->cleanCode($pd['code'] ?? ''),
                    'code_system_name' => $pd['code_type'] ?? 'SNOMED CT',
                ],
                'status' => [
                    'name' => $pd['status'] ?? '',
                    'code' => $pd['status_code'] ?? '',
                ],
            ],
            'author' => $this->buildAuthorBlock($author),
        ];
    }

    /**
     * Build author block (shared structure)
     *
     * @param array<int|string, mixed> $author
     * @return array<string, mixed>
     */
    private function buildAuthorBlock(array $author): array
    {
        return [
            'code' => [
                'name' => $author['physician_type'] ?? '',
                'code' => $author['physician_type_code'] ?? '',
                'code_system' => $author['physician_type_system'] ?? '',
                'code_system_name' => $author['physician_type_system_name'] ?? '',
            ],
            'date_time' => [
                'point' => [
                    'date' => $this->fDate($author['time'] ?? '') ?: $this->fDate(''),
                    'precision' => 'tz',
                ],
            ],
            'identifiers' => [
                [
                    'identifier' => !empty($author['npi']) ? '2.16.840.1.113883.4.6' : ($author['id'] ?? ''),
                    'extension' => !empty($author['npi']) ? $author['npi'] : 'NI',
                ],
            ],
            'address' => [
                'street_lines' => [$author['streetAddressLine'] ?? ''],
                'city' => $author['city'] ?? '',
                'state' => $author['state'] ?? '',
                'zip' => $author['postalCode'] ?? '',
                'country' => $author['country'] ?? 'US',
                'use' => 'WP',
            ],
            'phone' => [
                'value' => 'tel:' . $this->str($author['telecom'] ?? null),
                'use' => 'HP',
            ],
            'name' => [
                'last' => $author['lname'] ?? '',
                'first' => $author['fname'] ?? '',
            ],
            'organization' => [
                'identity' => [
                    'root' => $author['facility_oid'] ?? '2.16.840.1.113883.4.6',
                    'extension' => $author['facility_npi'] ?? 'NI',
                ],
                'name' => [$author['facility_name'] ?? ''],
            ],
        ];
    }

    /**
     * Populate providers
     * @return array<string, mixed>
     */
    private function populateProviders(): array
    {
        $providerArray = [];

        // Primary provider
        $primaryCareProvider = $this->arr($this->all['primary_care_provider'] ?? []);
        $primaryProviderData = $this->arr($primaryCareProvider['provider'] ?? []);
        if (!empty($primaryProviderData)) {
            $providerArray[] = $this->populateProvider($primaryProviderData);
        }

        // Care team providers
        $careTeam = $this->arr($this->all['care_team'] ?? []);
        $providers = $this->arr($careTeam['provider'] ?? []);

        if (!empty($providers)) {
            if (!array_key_exists(0, $providers)) {
                $providers = [$providers];
            }
            foreach ($providers as $provider) {
                $providerArray[] = $this->populateProvider($this->arr($provider));
            }
        }

        $primaryDiagnosis = $this->arr($this->all['primary_diagnosis'] ?? []);

        return [
            'providers' => [
                'date_time' => [
                    'low' => [
                        'date' => $this->fDate($this->all['time_start'] ?? ''),
                        'precision' => 'tz',
                    ],
                    'high' => [
                        'date' => $this->fDate($this->all['time_end'] ?? ''),
                        'precision' => 'tz',
                    ],
                ],
                'code' => [
                    'name' => $this->str($primaryDiagnosis['text'] ?? null),
                    'code' => $this->cleanCode($primaryDiagnosis['code'] ?? ''),
                    'code_system_name' => $this->str($primaryDiagnosis['code_type'] ?? null),
                ],
                'provider' => $providerArray,
            ],
        ];
    }

    /**
     * Populate header data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateHeader(array $pd): array
    {
        $encounterProvider = $this->arr($pd['encounter_provider'] ?? []);
        $author = $this->arr($pd['author'] ?? []);
        $custodian = $this->arr($pd['custodian'] ?? []);

        // Get default document code from CCD template
        $ccdCode = CcdaTemplateCodes::get('CCD');

        // Extract values using type-safe helpers
        $facilityName = $this->str($encounterProvider['facility_name'] ?? null) ?: $this->str($custodian['name'] ?? null);
        $facilityStreet = $this->str($encounterProvider['facility_street'] ?? null) ?: $this->str($custodian['streetAddressLine'] ?? null);
        $facilityCity = $this->str($encounterProvider['facility_city'] ?? null) ?: $this->str($custodian['city'] ?? null);
        $facilityState = $this->str($encounterProvider['facility_state'] ?? null) ?: $this->str($custodian['state'] ?? null);
        $facilityZip = $this->str($encounterProvider['facility_postal_code'] ?? null) ?: $this->str($custodian['postalCode'] ?? null);
        $facilityCountry = $this->str($encounterProvider['facility_country_code'] ?? null) ?: $this->str($custodian['country'] ?? null, 'US');
        $facilityPhone = $this->str($encounterProvider['facility_phone'] ?? null) ?: $this->str($custodian['telecom'] ?? null);

        return [
            'identifiers' => [
                [
                    'identifier' => $pd['document_uuid'] ?? ($this->oidFacility . '.' . time()),
                    'extension' => $pd['doc_extension'] ?? 'OE-DOC-0001',
                ],
            ],
            'code' => [
                'name' => $pd['doc_code_name'] ?? $ccdCode['name'],
                'code' => $pd['doc_code'] ?? $ccdCode['code'],
                'code_system_name' => $ccdCode['code_system_name'],
            ],
            'template' => [
                'root' => $this->getDocumentTemplateId($this->str($pd['doc_type'] ?? null, 'ccd')),
                'extension' => '2015-08-01',
            ],
            'title' => $pd['doc_title'] ?? $ccdCode['name'],
            'date_time' => [
                'point' => [
                    'date' => $this->authorDateTime ?: $this->fDate(''),
                    'precision' => 'tz',
                ],
            ],
            'author' => $this->buildAuthorBlock($author),
            'custodian' => [
                'identity' => [
                    'root' => $this->oidFacility ?: '2.16.840.1.113883.4.6',
                    'extension' => $this->npiFacility,
                ],
                'name' => [$facilityName],
                'address' => [
                    'street_lines' => [$facilityStreet],
                    'city' => $facilityCity,
                    'state' => $facilityState,
                    'zip' => $facilityZip,
                    'country' => $facilityCountry,
                    'use' => 'work place',
                ],
                'phone' => [
                    'value' => 'tel:' . $facilityPhone,
                    'use' => 'WP',
                ],
            ],
            'informant' => $this->buildInformant($pd),
            'information_recipient' => $this->buildInformationRecipient($pd),
            'component_of' => $this->buildComponentOf($pd),
        ];
    }

    /**
     * Get document template ID based on doc type
     */
    private function getDocumentTemplateId(string $docType): string
    {
        return match ($docType) {
            'referral' => '2.16.840.1.113883.10.20.22.1.14',
            'ccd' => '2.16.840.1.113883.10.20.22.1.2',
            'unstructured' => '2.16.840.1.113883.10.20.22.1.10',
            default => '2.16.840.1.113883.10.20.22.1.2',
        };
    }

    /**
     * Build informant block
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>|null
     */
    private function buildInformant(array $pd): ?array
    {
        $informer = $this->arr($pd['informer'] ?? []);
        if (empty($informer)) {
            return null;
        }

        return [
            'identifiers' => [['identifier' => $this->oidFacility]],
            'name' => [
                'organization' => $this->str($informer['organization'] ?? null),
            ],
        ];
    }

    /**
     * Build information recipient block
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>|null
     */
    private function buildInformationRecipient(array $pd): ?array
    {
        $recipient = $this->arr($pd['information_recipient'] ?? []);
        $fname = $this->str($recipient['fname'] ?? null);
        $lname = $this->str($recipient['lname'] ?? null);
        if ($fname === '' && $lname === '') {
            return null;
        }

        return [
            'name' => [
                'first' => $fname,
                'last' => $lname,
                'prefix' => $this->str($recipient['prefix'] ?? null),
                'suffix' => $this->str($recipient['suffix'] ?? null),
            ],
            'organization' => $this->str($recipient['organization'] ?? null),
        ];
    }

    /**
     * Build componentOf (encompassingEncounter) block
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>|null
     */
    private function buildComponentOf(array $pd): ?array
    {
        $primaryDiagnosis = $this->arr($pd['primary_diagnosis'] ?? []);
        $primaryCareProvider = $this->arr($pd['primary_care_provider'] ?? []);
        $primaryProvider = $this->arr($primaryCareProvider['provider'] ?? []);

        if (empty($primaryDiagnosis) && empty($primaryProvider)) {
            return null;
        }

        $author = $this->arr($pd['author'] ?? []);
        $patient = $this->arr($pd['patient'] ?? []);
        $encounterProvider = $this->arr($pd['encounter_provider'] ?? []);

        return [
            'identifiers' => [
                [
                    'identifier' => $this->oidFacility,
                    'extension' => 'PT-' . $this->str($patient['id'] ?? null),
                ],
            ],
            'code' => [
                'name' => $this->str($primaryDiagnosis['text'] ?? null),
                'code' => $this->str($primaryDiagnosis['code'] ?? null),
                'code_system_name' => $this->str($primaryDiagnosis['code_type'] ?? null),
            ],
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($primaryDiagnosis['encounter_date'] ?? ''),
                    'precision' => 'tz',
                ],
                'high' => [
                    'date' => $this->fDate($primaryDiagnosis['encounter_end_date'] ?? ''),
                    'precision' => 'tz',
                ],
            ],
            'responsible_party' => [
                'root' => $this->oidFacility,
                'name' => [
                    'last' => $this->str($author['lname'] ?? null),
                    'first' => $this->str($author['fname'] ?? null),
                ],
            ],
            'encounter_participant' => [
                'root' => $this->oidFacility,
                'name' => [
                    'last' => $this->str($primaryProvider['lname'] ?? null),
                    'first' => $this->str($primaryProvider['fname'] ?? null),
                ],
                'address' => [
                    'street_lines' => [$this->str($encounterProvider['facility_street'] ?? null)],
                    'city' => $this->str($encounterProvider['facility_city'] ?? null),
                    'state' => $this->str($encounterProvider['facility_state'] ?? null),
                    'zip' => $this->str($encounterProvider['facility_postal_code'] ?? null),
                    'country' => $this->str($encounterProvider['facility_country_code'] ?? null, 'US'),
                    'use' => 'work place',
                ],
                'phone' => [[
                    'value' => 'tel:' . $this->str($encounterProvider['facility_phone'] ?? null),
                    'use' => 'WP',
                ]],
            ],
        ];
    }

    /**
     * Get document metadata
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function getMeta(array $pd): array
    {
        $docExtension = $this->str($pd['doc_extension'] ?? null, 'OE-DOC-0001');

        return [
            'type' => $pd['doc_type'] ?? 'ccd',
            'identifiers' => [
                [
                    'identifier' => $pd['document_uuid'] ?? $this->oidFacility,
                    'extension' => $docExtension,
                ],
            ],
            'set_id' => [
                'identifier' => $pd['document_uuid'] ?? $this->oidFacility,
                'extension' => 's' . $docExtension,
            ],
        ];
    }

    /**
     * Apply timezone offsets
     *
     * @param array<string, mixed> $doc
     */
    private function applyTimezones(array &$doc, string $offset): void
    {
        // This would recursively apply timezone offset to all date fields
        // Implementation depends on how dates are stored in the structure
    }

    /**
     * Populate procedure data - matches serveccda.js populateProcedure()
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateProcedure(array $pd): array
    {
        $author = $this->arr($pd['author'] ?? []);

        return [
            'procedure' => [
                'name' => $pd['code_text'] ?? $pd['description'] ?? '',
                'code' => $this->cleanCode($pd['code'] ?? ''),
                'code_system' => $pd['code_type'] === 'SNOMED-CT' ? '2.16.840.1.113883.6.96' :
                    ($pd['code_type'] === 'CPT4' ? '2.16.840.1.113883.6.12' : ''),
                'code_system_name' => $pd['code_type'] ?? 'CPT4',
                'translations' => !empty($pd['code2']) ? [
                    [
                        'code' => $this->cleanCode($pd['code2']),
                        'code_system_name' => $pd['code_type2'] ?? '',
                    ]
                ] : [],
            ],
            'identifiers' => [
                [
                    'identifier' => 'd68b7e32-7810-4f5b-9cc2-acd54b0fd85d',
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'status' => 'completed',
            'date_time' => [
                'point' => [
                    'date' => $this->fDate($pd['date'] ?? $pd['encounter'] ?? ''),
                    'precision' => 'day',
                ],
            ],
            'performers' => [  // FIXED: Changed from 'performer' to 'performers' (plural)
                [
                    'identifiers' => [
                        [
                            'identifier' => '2.16.840.1.113883.4.6',
                            'extension' => $pd['npi'] ?? $this->npiProvider,
                        ],
                    ],
                    'name' => [
                        [
                            'last' => $pd['provider_lname'] ?? '',
                            'first' => $pd['provider_fname'] ?? '',
                        ],
                    ],
                    'address' => [
                        [
                            'street_lines' => [$pd['address'] ?? ''],
                            'city' => $pd['city'] ?? '',
                            'state' => $pd['state'] ?? '',
                            'zip' => $pd['zip'] ?? '',
                            'country' => 'US',
                        ],
                    ],
                    'phone' => [
                        [
                            'number' => $pd['work_phone'] ?? '',
                            'type' => 'work place',
                        ],
                    ],
                    'organization' => [
                        [
                            'identifiers' => [
                                [
                                    'identifier' => $pd['facility_sha_extension'] ?? '',
                                    'extension' => $pd['facility_extension'] ?? '',
                                ],
                            ],
                            'name' => [$this->str($pd['facility_name'] ?? null) ?: $this->str($this->arr($this->all['encounter_provider'] ?? [])['facility_name'] ?? null)],
                            'address' => [
                                [
                                    'street_lines' => [$pd['facility_address'] ?? ''],
                                    'city' => $pd['facility_city'] ?? '',
                                    'state' => $pd['facility_state'] ?? '',
                                    'zip' => $pd['facility_zip'] ?? '',
                                    'country' => $pd['facility_country'] ?? 'US',
                                ],
                            ],
                            'phone' => [
                                [
                                    'number' => $pd['facility_phone'] ?? '',
                                    'type' => 'work place',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'author' => $this->buildAuthorBlock($author),
            'procedure_type' => 'procedure',  // FIXED: Added required field for template existsWhen condition
        ];
    }

    /**
     * Populate result data - matches serveccda.js populateResult()
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateResult(array $pd): array
    {
        // Results come as sets with individual results inside
        $results = [];
        $resultList = $this->arr($pd['result'] ?? []);
        if (!empty($resultList) && !isset($resultList[0])) {
            $resultList = [$resultList];
        }

        foreach ($resultList as $result) {
            $result = $this->arr($result);
            $results[] = [
                'identifiers' => [
                    [
                        'identifier' => $result['sha_extension'] ?? $this->oidFacility,
                        'extension' => $result['extension'] ?? '',
                    ],
                ],
                'result' => [
                    'name' => $result['result_text'] ?? $result['title'] ?? '',
                    'code' => $this->cleanCode($result['result_code'] ?? $result['code'] ?? ''),
                    'code_system' => '2.16.840.1.113883.6.1',
                    'code_system_name' => 'LOINC',
                ],
                'date_time' => [
                    'point' => [
                        'date' => $this->fDate($result['result_date'] ?? $result['date'] ?? ''),
                        'precision' => 'tz',
                    ],
                ],
                'status' => 'completed',
                'value' => $result['result_value'] ?? '',
                'unit' => $result['result_unit'] ?? '',
                'reference_range' => [
                    'low' => $result['range_low'] ?? '',
                    'high' => $result['range_high'] ?? '',
                    'text' => $result['range'] ?? '',
                ],
                'interpretation' => [
                    'code' => $result['abnormal_flag'] ?? '',
                    'name' => $result['abnormal_flag'] === 'H' ? 'High' :
                        ($result['abnormal_flag'] === 'L' ? 'Low' : 'Normal'),
                ],
            ];
        }

        return [
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'result_set' => [
                'name' => $pd['title'] ?? $pd['result_text'] ?? '',
                'code' => $this->cleanCode($pd['code'] ?? ''),
                'code_system' => '2.16.840.1.113883.6.1',
                'code_system_name' => 'LOINC',
            ],
            'date_time' => [
                'point' => [
                    'date' => $this->fDate($pd['date'] ?? ''),
                    'precision' => 'tz',
                ],
            ],
            'status' => 'completed',
            'results' => $results,
        ];
    }

    /**
     * Populate vital signs data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateVital(array $pd): array
    {
        $vitalList = [];
        $author = $this->arr($pd['author'] ?? []);

        // Map common vital signs (some have aliases for different input formats)
        $vitalsMap = [
            'bps' => ['code' => '8480-6', 'name' => 'Systolic Blood Pressure', 'unit' => 'mm[Hg]'],
            'bpd' => ['code' => '8462-4', 'name' => 'Diastolic Blood Pressure', 'unit' => 'mm[Hg]'],
            'pulse' => ['code' => '8867-4', 'name' => 'Heart Rate', 'unit' => '/min'],
            'temperature' => ['code' => '8310-5', 'name' => 'Body Temperature', 'unit' => 'Cel'],
            'respiration' => ['code' => '9279-1', 'name' => 'Respiratory Rate', 'unit' => '/min'],
            'breath' => ['code' => '9279-1', 'name' => 'Respiratory Rate', 'unit' => '/min'],
            'height' => ['code' => '8302-2', 'name' => 'Body Height', 'unit' => 'cm'],
            'weight' => ['code' => '29463-7', 'name' => 'Body Weight', 'unit' => 'kg'],
            'BMI' => ['code' => '39156-5', 'name' => 'Body Mass Index', 'unit' => 'kg/m2'],
            'oxygen_saturation' => ['code' => '2708-6', 'name' => 'Oxygen Saturation', 'unit' => '%'],
            'head_circ' => ['code' => '9843-4', 'name' => 'Head Circumference', 'unit' => 'cm'],
        ];

        foreach ($vitalsMap as $key => $info) {
            if (!empty($pd[$key])) {
                $vitalList[] = [
                    'identifiers' => [
                        [
                            'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                            'extension' => $pd['extension_' . $key] ?? $pd['extension'] ?? '',
                        ],
                    ],
                    'vital' => [
                        'name' => $info['name'],
                        'code' => $info['code'],
                        'code_system' => '2.16.840.1.113883.6.1',
                        'code_system_name' => 'LOINC',
                    ],
                    'status' => 'completed',
                    'date_time' => [
                        'point' => [
                            'date' => $this->fDate($pd['effectivetime'] ?? $pd['date'] ?? ''),
                            'precision' => 'day',
                        ],
                    ],
                    'interpretations' => ['N'],
                    'value' => $this->num($pd[$key]),
                    'unit' => $pd[$key . '_unit'] ?? $pd['unit_' . $key] ?? $info['unit'],
                    'author' => $this->buildAuthorBlock($author),
                ];
            }
        }

        return [
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'status' => 'completed',
            'date_time' => [
                'point' => [
                    'date' => $this->fDate($pd['effectivetime'] ?? $pd['date'] ?? ''),
                    'precision' => 'day',
                ],
            ],
            'vital_list' => $vitalList,  // Already correct!
        ];
    }

    /**
     * Populate immunization data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateImmunization(array $pd): array
    {
        $author = $this->arr($pd['author'] ?? []);

        return [
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($pd['administered_date'] ?? $pd['administered_on'] ?? $pd['date'] ?? ''),
                    'precision' => 'day',
                ],
            ],
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'status' => !empty($pd['completion_status']) ? $pd['completion_status'] : 'complete',
            'product' => [
                'product' => [
                    'name' => $pd['cvx_code_text'] ?? $pd['code_text'] ?? $pd['title'] ?? '',
                    'code' => $this->cleanCode($pd['cvx_code'] ?? ''),
                    'code_system_name' => 'CVX',
                    'lot_number' => '',
                ],
                'lot_number' => $pd['lot_number'] ?? '',
                'manufacturer' => $pd['manufacturer'] ?? '',
            ],
            'administration' => [
                'route' => [
                    'name' => $pd['route'] ?? $pd['route_of_administration'] ?? '',
                    'code' => $this->mapRouteCode($this->str($pd['route_code'] ?? null)),
                    'code_system_name' => 'Medication Route FDA',
                ],
            ],
            'performer' => [
                'identifiers' => [
                    [
                        'identifier' => '2.16.840.1.113883.4.6',
                        'extension' => $pd['npi'] ?? $this->npiProvider,
                    ],
                ],
                'name' => [
                    [
                        'last' => $pd['provider_lname'] ?? $pd['lname'] ?? '',
                        'first' => $pd['provider_fname'] ?? $pd['fname'] ?? '',
                    ],
                ],
                'address' => [
                    [
                        'street_lines' => [$pd['address'] ?? ''],
                        'city' => $pd['city'] ?? '',
                        'state' => $pd['state'] ?? '',
                        'zip' => $pd['zip'] ?? '',
                        'country' => 'US',
                    ],
                ],
                'organization' => [
                    [
                        'identifiers' => [
                            [
                                'identifier' => '2.16.840.1.113883.4.6',
                                'extension' => $this->npiFacility,
                            ],
                        ],
                        'name' => [$pd['facility_name'] ?? ''],
                    ],
                ],
            ],
            'instructions' => [
                'code' => [
                    'name' => 'immunization education',
                    'code' => '171044003',
                    'code_system_name' => 'SNOMED CT',
                ],
                'free_text' => 'Needs Attention for more data.',
            ],
            'author' => $this->buildAuthorBlock($author),
        ];
    }

    /**
     * Populate encounter data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateEncounter(array $pd): array
    {
        $author = $this->arr($pd['author'] ?? []);

        // Process findings/diagnoses
        $findings = [];
        $diagnoses = $this->arr($pd['encounter_diagnosis'] ?? []);
        if (!empty($diagnoses)) {
            if (!isset($diagnoses[0])) {
                $diagnoses = [$diagnoses];
            }
            foreach ($diagnoses as $dx) {
                $dx = $this->arr($dx);
                $findings[] = [
                    'value' => [
                        'name' => $dx['diagnosis'] ?? $dx['title'] ?? '',
                        'code' => $this->cleanCode($dx['code'] ?? ''),
                        'code_system' => $dx['code_type'] === 'SNOMED-CT' ? '2.16.840.1.113883.6.96' : '2.16.840.1.113883.6.103',
                        'code_system_name' => $dx['code_type'] ?? 'ICD-10-CM',
                    ],
                ];
            }
        }

        // ADDED: Process locations (required by template)
        $locations = [];
        if (!empty($pd['facility_name'])) {
            $locations[] = [
                'name' => $pd['facility_name'],
                'location_type' => [
                    'name' => 'General Acute Care Hospital',
                    'code' => '1118-9',
                    'code_system_name' => 'HealthcareServiceLocation',
                ],
                'address' => [
                    [
                        'street_lines' => [$pd['facility_address'] ?? ''],
                        'city' => $pd['facility_city'] ?? '',
                        'state' => $pd['facility_state'] ?? '',
                        'zip' => $pd['facility_zip'] ?? '',
                        'country' => 'US',
                    ],
                ],
                'telecom' => [
                    [
                        'number' => $pd['facility_phone'] ?? '',
                        'type' => 'work place',
                    ],
                ],
            ];
        }

        return [
            'encounter' => [
                'name' => $pd['pc_catname'] ?? $pd['code_text'] ?? '',
                'code' => $this->cleanCode($pd['code'] ?? ''),
                'code_system' => '2.16.840.1.113883.6.12',
                'code_system_name' => 'CPT4',
                'translations' => [],
            ],
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($pd['date'] ?? ''),
                    'precision' => 'tz',
                ],
                'high' => [
                    'date' => $this->fDate($pd['date_end'] ?? $pd['date'] ?? ''),
                    'precision' => 'tz',
                ],
            ],
            'performers' => [  // FIXED: Changed from 'performer' to 'performers' (plural)
                [
                    'code' => [
                        'name' => $pd['provider_specialty'] ?? '',
                        'code' => $pd['provider_taxonomy'] ?? '',
                        'code_system' => '2.16.840.1.113883.6.101',
                        'code_system_name' => 'NUCC Health Care Provider Taxonomy',
                    ],
                    'identifiers' => [
                        [
                            'identifier' => '2.16.840.1.113883.4.6',
                            'extension' => $pd['npi'] ?? $this->npiProvider,
                        ],
                    ],
                    'name' => [
                        [
                            'last' => $pd['provider_lname'] ?? '',
                            'first' => $pd['provider_fname'] ?? '',
                        ],
                    ],
                ],
            ],
            'locations' => $locations,  // ADDED: Required by template
            'findings' => $findings,
        ];
    }

    /**
     * Populate plan of care data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populatePlanOfCare(array $pd): array
    {
        $author = $this->arr($pd['author'] ?? []);

        // Process performers
        $performers = [];
        $provider = $this->arr($pd['provider'] ?? []);
        if (!empty($provider)) {
            $performers[] = [
                'identifiers' => [
                    [
                        'identifier' => '2.16.840.1.113883.4.6',
                        'extension' => $this->str($provider['npi'] ?? null) ?: $this->npiProvider,
                    ],
                ],
                'code' => [
                    [
                        'name' => $this->str($provider['specialty'] ?? null, 'General physician'),
                        'code' => $this->str($provider['specialty_code'] ?? null, '59058001'),
                        'code_system_name' => 'SNOMED CT',
                    ],
                ],
                'name' => [
                    [
                        'last' => $this->str($provider['lname'] ?? null),
                        'first' => $this->str($provider['fname'] ?? null),
                    ],
                ],
                'phone' => [
                    [
                        'number' => $this->str($provider['phone'] ?? null),
                        'type' => 'work place',
                    ],
                ],
            ];
        }

        // Process locations
        $locations = [];
        if (!empty($pd['facility_name'])) {
            $locations[] = [
                'name' => $pd['facility_name'],
                'location_type' => [
                    'name' => $pd['facility_address'] ?? '',
                    'code' => '1160-1',
                    'code_system_name' => 'HealthcareServiceLocation',
                ],
                'address' => [
                    [
                        'street_lines' => [$pd['facility_address'] ?? ''],
                        'city' => $pd['facility_city'] ?? '',
                        'state' => $pd['facility_state'] ?? '',
                        'zip' => $pd['facility_zip'] ?? '',
                        'country' => 'US',
                    ],
                ],
                'phone' => [
                    [
                        'number' => $pd['facility_phone'] ?? '',
                        'type' => 'work place',
                    ],
                ],
            ];
        }

        // Process findings
        $findings = [];
        $findingsList = $this->arr($pd['findings'] ?? []);
        if (!empty($findingsList)) {
            if (!isset($findingsList[0])) {
                $findingsList = [$findingsList];
            }
            foreach ($findingsList as $finding) {
                $finding = $this->arr($finding);
                $findings[] = [
                    'identifiers' => [
                        [
                            'identifier' => $finding['sha_extension'] ?? '',
                            'extension' => $finding['extension'] ?? '',
                        ],
                    ],
                    'value' => [
                        'name' => $finding['name'] ?? $finding['title'] ?? '',
                        'code' => $this->cleanCode($finding['code'] ?? ''),
                        'code_system_name' => $finding['code_type'] ?? 'SNOMED CT',
                    ],
                    'date_time' => [
                        'low' => [
                            'date' => $this->fDate($finding['date'] ?? ''),
                            'precision' => 'day',
                        ],
                    ],
                    'status' => $finding['status'] ?? 'Completed',
                    'reason' => $finding['reason'] ?? '',
                ];
            }
        }

        return [
            'plan' => [  // ADDED: Required field
                'name' => $pd['code_text'] ?? $pd['title'] ?? '',
                'code' => $this->cleanCode($pd['code'] ?? ''),
                'code_system_name' => $pd['code_type'] ?? 'LOINC',
            ],
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'goal' => [  // ADDED: Goal field
                'code' => $this->cleanCode($pd['code'] ?? ''),
                'name' => $pd['description'] ?? $pd['text'] ?? '',
            ],
            'date_time' => [
                'point' => [
                    'date' => $this->fDate($pd['date'] ?? ''),
                    'precision' => 'day',
                ],
            ],
            'type' => $pd['type'] ?? 'observation',  // ADDED: Required for template selection
            'status' => [
                'code' => $pd['status'] ?? 'active',
            ],
            'author' => $this->buildAuthorBlock($author),
            'performers' => $performers,  // ADDED: Required field
            'locations' => $locations,    // ADDED: Required field
            'findings' => $findings,      // ADDED: Required field
            'name' => $pd['description'] ?? $pd['text'] ?? '',
            'mood_code' => $pd['moodCode'] ?? 'INT',
        ];
    }

    /**
     * Populate goal data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateGoal(array $pd): array
    {
        return [
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'goal' => [
                'name' => $pd['description'] ?? $pd['title'] ?? '',
                'code' => $this->cleanCode($pd['code'] ?? ''),
                'code_system_name' => $pd['code_type'] ?? 'SNOMED CT',
            ],
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($pd['date'] ?? ''),
                    'precision' => 'day',
                ],
            ],
            'status' => $pd['status'] ?? 'active',
        ];
    }

    /**
     * Populate health concern data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateHealthConcern(array $pd): array
    {
        return [
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'concern' => [
                'name' => $pd['title'] ?? $pd['description'] ?? '',
                'code' => $this->cleanCode($pd['code'] ?? ''),
                'code_system_name' => $pd['code_type'] ?? 'SNOMED CT',
            ],
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($pd['begdate'] ?? $pd['date'] ?? ''),
                    'precision' => 'day',
                ],
            ],
            'status' => $pd['status'] ?? 'active',
        ];
    }

    /**
     * Populate medical device data
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateMedicalDevice(array $pd): array
    {
        return [
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'device' => [
                'name' => $pd['title'] ?? $pd['code_text'] ?? '',
                'code' => $this->cleanCode($pd['code'] ?? ''),
                'code_system_name' => $pd['code_type'] ?? 'SNOMED CT',
            ],
            'udi' => $pd['udi'] ?? '',
            'status' => $pd['status'] ?? 'completed',
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($pd['date'] ?? ''),
                    'precision' => 'day',
                ],
            ],
        ];
    }

    /**
     * Populate social history data
     *
     * @param array<int|string, mixed> $pd
     * @return list<array<string, mixed>>
     */
    private function populateSocialHistory(array $pd): array
    {
        $observations = [];
        $extension = $this->str($pd['extension'] ?? null);

        // Smoking status
        if (!empty($pd['smoking'])) {
            $observations[] = [
                'identifiers' => [
                    [
                        'identifier' => $this->oidFacility,
                        'extension' => 'smoking-' . $extension,
                    ],
                ],
                'code' => [
                    'name' => 'Tobacco smoking status NHIS',
                    'code' => '72166-2',
                    'code_system' => '2.16.840.1.113883.6.1',
                    'code_system_name' => 'LOINC',
                ],
                'value' => [
                    'name' => $pd['smoking_status'] ?? $pd['smoking'],
                    'code' => $this->cleanCode($pd['smoking_status_code'] ?? ''),
                    'code_system' => '2.16.840.1.113883.6.96',
                    'code_system_name' => 'SNOMED CT',
                ],
                'date_time' => [
                    'low' => [
                        'date' => $this->fDate($pd['date'] ?? ''),
                        'precision' => 'day',
                    ],
                ],
            ];
        }

        // Social history observations
        $socialFields = ['alcohol', 'recreational_drugs', 'sexual_activity', 'exercise'];
        foreach ($socialFields as $field) {
            if (!empty($pd[$field])) {
                $observations[] = [
                    'identifiers' => [
                        [
                            'identifier' => $this->oidFacility,
                            'extension' => $field . '-' . $extension,
                        ],
                    ],
                    'code' => [
                        'name' => ucfirst(str_replace('_', ' ', $field)),
                        'code' => '',
                        'code_system_name' => 'SNOMED CT',
                    ],
                    'value' => $pd[$field],
                    'date_time' => [
                        'low' => [
                            'date' => $this->fDate($pd['date'] ?? ''),
                            'precision' => 'day',
                        ],
                    ],
                ];
            }
        }

        return $observations;
    }

    /**
     * Populate care team members
     *
     * @param array<string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateCareTeamMembers(array $pd): array
    {
        // Get provider data from correct path: care_team.provider
        $careTeam = $this->arr($pd['care_team'] ?? []);
        if (empty($careTeam)) {
            return [];
        }

        $providerData = $this->arr($careTeam['provider'] ?? []);
        if (empty($providerData)) {
            return [];
        }

        // Handle both array and single object
        if (!array_key_exists(0, $providerData)) {
            // Single provider object - wrap in array
            $providerData = [$providerData];
        }

        $result = [];
        foreach ($providerData as $member) {
            $member = $this->arr($member);
            if (empty($member)) {
                continue;
            }

            $result[] = [
                'identifiers' => [
                    [
                        'identifier' => '2.16.840.1.113883.4.6',
                        'extension' => $member['npi'] ?? '',
                    ],
                ],
                'code' => [
                    'code' => $member['role_code'] ?? '',
                    'display_name' => $member['role_display'] ?? ($member['role'] ?? ''),
                    'code_system' => '2.16.840.1.113883.6.101',
                    'code_system_name' => 'SNOMED CT',
                ],
                'date_time' => [
                    'low' => [
                        'date' => $this->fDate($member['provider_since'] ?? ''),
                        'precision' => 'tz',
                    ],
                ],
                'name' => [
                    [
                        'last' => $member['lname'] ?? '',
                        'first' => $member['fname'] ?? '',
                        'prefix' => $member['prefix'] ?? '',
                    ],
                ],
                'address' => [
                    [
                        'street_lines' => [$member['street'] ?? ''],
                        'city' => $member['city'] ?? '',
                        'state' => $member['state'] ?? '',
                        'zip' => $member['zip'] ?? '',
                        'country' => 'US',
                    ],
                ],
                'phone' => [
                    ['number' => $member['telecom'] ?? ''],
                ],
                'status' => $member['status'] ?? 'active',
            ];
        }

        // Get first provider date for the date_time field
        $firstProviderDate = '';
        if (!empty($result)) {
            $firstProvider = $result[0];
            $firstProviderDate = $this->str($firstProvider['date_time']['low']['date']);
        }

        // Return in expected format with author and status
        return [
            'providers' => [
                'provider' => $result
            ],
            'status' => $careTeam['is_active'] ?? 'active',
            'date_time' => [
                'low' => [
                    'date' => $firstProviderDate ?: $this->fDate(''),
                    'precision' => 'tz',
                ],
            ],
            'author' => $this->populateAuthorFromAuthorContainer($careTeam),
        ];
    }

    /**
     * Populate payer/insurance data
     *
     * @param array<int|string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populatePayer(array $pd): array
    {
        return [
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'policy' => [
                'identifiers' => [
                    [
                        'identifier' => $pd['policy_number'] ?? '',
                    ],
                ],
                'insurance' => [
                    'name' => $pd['name'] ?? $pd['company_name'] ?? '',
                    'code' => '',
                ],
            ],
            'payer' => [
                'name' => $pd['name'] ?? $pd['company_name'] ?? '',
            ],
            'date_time' => [
                'low' => [
                    'date' => $this->fDate($pd['date'] ?? ''),
                    'precision' => 'day',
                ],
            ],
            'participant' => [
                'name' => [
                    [
                        'last' => $pd['subscriber_lname'] ?? '',
                        'first' => $pd['subscriber_fname'] ?? '',
                    ],
                ],
                'relationship' => $pd['subscriber_relationship'] ?? '',
            ],
        ];
    }

    /**
     * Process advance directives
     *
     * @param array<int|string, mixed> $directives
     * @return list<array<string, mixed>>
     */
    private function processAdvanceDirectives(array $directives): array
    {
        $result = [];
        foreach ($directives as $directive) {
            $d = $this->arr($directive);
            $result[] = [
                'identifiers' => [
                    [
                        'identifier' => $this->str($d['sha_extension'] ?? null) ?: $this->oidFacility,
                        'extension' => $this->str($d['extension'] ?? null),
                    ],
                ],
                'type' => [
                    'name' => $this->str($d['code_text'] ?? null) ?: $this->str($d['title'] ?? null),
                    'code' => $this->cleanCode($d['code'] ?? ''),
                    'code_system_name' => 'LOINC',
                ],
                'date_time' => [
                    'low' => [
                        'date' => $this->fDate($d['date'] ?? ''),
                        'precision' => 'day',
                    ],
                ],
                'status' => $this->str($d['status'] ?? null, 'completed'),
            ];
        }
        return $result;
    }

    /**
     * Populate clinical note data
     *
     * @param array<int|string, mixed> $pd
     * @return array<string, mixed>
     */
    private function populateNote(array $pd): array
    {
        $author = $this->arr($pd['author'] ?? []);

        return [
            'identifiers' => [
                [
                    'identifier' => $pd['sha_extension'] ?? $this->oidFacility,
                    'extension' => $pd['extension'] ?? '',
                ],
            ],
            'code' => [
                'name' => $pd['code_text'] ?? $pd['note_type'] ?? 'Clinical Note',
                'code' => $this->cleanCode($pd['code'] ?? ''),
                'code_system_name' => 'LOINC',
            ],
            'date_time' => [
                'point' => [
                    'date' => $this->fDate($pd['date'] ?? ''),
                    'precision' => 'tz',
                ],
            ],
            'text' => $pd['description'] ?? $pd['note'] ?? '',
            'author' => $this->buildAuthorBlock($author),
        ];
    }

    /**
     * Populate author information from author container
     * (matches JavaScript populateAuthorFromAuthorContainer)
     *
     * @param array<int|string, mixed> $container
     * @return array<string, mixed>
     */
    private function populateAuthorFromAuthorContainer(array $container): array
    {
        if (empty($container)) {
            return [];
        }

        $author = $this->arr($container['author'] ?? []);
        if (empty($author)) {
            return [];
        }

        $npi = $this->str($author['npi'] ?? null);

        return [
            'code' => [
                'name' => $this->str($author['physician_type'] ?? null),
                'code' => $this->str($author['physician_type_code'] ?? null),
                'code_system' => $this->str($author['physician_type_system'] ?? null),
                'code_system_name' => $this->str($author['physician_type_system_name'] ?? null),
            ],
            'date_time' => [
                'point' => [
                    'date' => $this->fDate($author['time'] ?? ''),
                    'precision' => 'tz',
                ],
            ],
            'identifiers' => [
                [
                    'identifier' => $npi !== ''
                        ? '2.16.840.1.113883.4.6'
                        : $this->str($author['id'] ?? null),
                    'extension' => $npi !== '' ? $npi : 'NI',
                ],
            ],
            'name' => [
                [
                    'last' => $this->str($author['lname'] ?? null),
                    'first' => $this->str($author['fname'] ?? null),
                ],
            ],
            'organization' => [
                [
                    'identity' => [
                        [
                            'root' => $this->str($author['facility_oid'] ?? null, '2.16.840.1.113883.4.6'),
                            'extension' => $this->str($author['facility_npi'] ?? null, 'NI'),
                        ],
                    ],
                    'name' => [
                        $this->str($author['facility_name'] ?? null),
                    ],
                ],
            ],
        ];
    }
}
