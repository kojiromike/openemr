<?php

/**
 * SocialHistoryEntryLevel.php - Social History entry-level template definitions
 *
 * PHP port of oe-blue-button-generate/lib/entryLevel/socialHistoryEntryLevel.js
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2026 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Templates\EntryLevel;

use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\Condition;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\FieldLevel;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\LeafLevel;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\Translate;

class SocialHistoryEntryLevel
{
    /**
     * Social History Observation
     * JS: exports.socialHistoryObservation
     * @return array<string, mixed>
     */
    public static function socialHistoryObservation(): array
    {
        return [
            'key' => 'observation',
            'attributes' => [
                'classCode' => 'OBS',
                'moodCode' => 'EVN',
            ],
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.38'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => LeafLevel::code(),
                    'content' => [
                        [
                            'key' => 'originalText',
                            'text' => LeafLevel::inputProperty('unencoded_name'),
                            'content' => [
                                [
                                    'key' => 'reference',
                                    'attributes' => [
                                        'value' => LeafLevel::nextReference('social'),
                                    ],
                                ],
                            ],
                        ],
                        [
                            'key' => 'translation',
                            'attributes' => LeafLevel::code(),
                            'dataKey' => 'translations',
                        ],
                    ],
                    'dataKey' => 'code',
                ],
                FieldLevel::$statusCodeCompleted,
                FieldLevel::effectiveTime(),
                [
                    'key' => 'value',
                    'attributes' => ['xsi:type' => 'ST'],
                    'text' => LeafLevel::inputProperty('value'),
                ],
            ],
            'existsWhen' => fn(mixed $input): bool => !is_array($input) || !isset($input['value']) || (is_string($input['value']) && str_contains($input['value'], 'smoke')),
        ];
    }

    /**
     * Smoking Status Observation
     * JS: exports.smokingStatusObservation
     * @return array<string, mixed>
     */
    public static function smokingStatusObservation(): array
    {
        return [
            'key' => 'observation',
            'attributes' => [
                'classCode' => 'OBS',
                'moodCode' => 'EVN',
            ],
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.78'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                FieldLevel::templateCode('SmokingStatusObservation'),
                FieldLevel::$statusCodeCompleted,
                array_merge(FieldLevel::effectiveTime(), ['required' => true]),
                [
                    'key' => 'value',
                    'attributes' => fn(mixed $input): array => array_merge(
                        ['xsi:type' => 'CD'],
                        Translate::codeFromName('2.16.840.1.113883.11.20.9.38', is_array($input) || is_string($input) ? $input : null)
                    ),
                    'required' => true,
                    'dataKey' => 'value',
                ],
            ],
            'existsWhen' => fn(mixed $input): bool => is_array($input) && isset($input['value']) && is_string($input['value']) && str_contains($input['value'], 'smoke'),
        ];
    }

    /**
     * Gender Status Observation
     * JS: exports.genderStatusObservation
     * @return array<string, mixed>
     */
    public static function genderStatusObservation(): array
    {
        return [
            'key' => 'observation',
            'attributes' => [
                'classCode' => 'OBS',
                'moodCode' => 'EVN',
            ],
            'content' => [
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.4.200', '2016-06-01'),
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.200'),
                FieldLevel::templateCode('GenderStatusObservation'),
                FieldLevel::$statusCodeCompleted,
                [
                    'key' => 'value',
                    'attributes' => fn(mixed $input): array => array_merge(
                        ['xsi:type' => 'CD'],
                        Translate::codeFromName('2.16.840.1.113883.5.1', is_array($input) || is_string($input) ? $input : null)
                    ),
                    'required' => true,
                    'dataKey' => 'gender',
                ],
            ],
            'existsWhen' => fn(mixed $input): bool => is_array($input) && isset($input['gender']),
        ];
    }

    /**
     * Tribal Affiliation Observation
     * JS: exports.tribalAffiliationObservation
     * @return array<string, mixed>
     */
    public static function tribalAffiliationObservation(): array
    {
        return [
            'key' => 'observation',
            'attributes' => [
                'classCode' => 'OBS',
                'moodCode' => 'EVN',
            ],
            'content' => [
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.4.506', '2023-05-01'),
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.506'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => [
                        'code' => '95370-3',
                        'codeSystem' => '2.16.840.1.113883.6.1',
                        'codeSystemName' => 'LOINC',
                        'displayName' => 'Tribal affiliation',
                    ],
                ],
                FieldLevel::$statusCodeCompleted,
                array_merge(FieldLevel::effectiveTime(), ['dataKey' => 'effective_date']),
                [
                    'key' => 'value',
                    'attributes' => [
                        'xsi:type' => 'CD',
                        'code' => LeafLevel::inputProperty('tribal_code'),
                        'codeSystem' => '2.16.840.1.113883.5.140',
                        'codeSystemName' => 'Tribal TribalEntityUS',
                        'displayName' => LeafLevel::inputProperty('tribal_title'),
                    ],
                    'dataKey' => 'tribal_affiliation',
                ],
            ],
            'existsWhen' => fn(mixed $input): bool => is_array($input) && is_array($input['tribal_affiliation'] ?? null) && isset($input['tribal_affiliation']['tribal_code']),
        ];
    }

    /**
     * Pregnancy Status Observation
     * JS: exports.pregnancyStatusObservation
     * @return array<string, mixed>
     */
    public static function pregnancyStatusObservation(): array
    {
        return [
            'key' => 'observation',
            'attributes' => [
                'classCode' => 'OBS',
                'moodCode' => 'EVN',
            ],
            'content' => [
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.15.3.8', '2023-05-01'),
                FieldLevel::templateId('2.16.840.1.113883.10.20.15.3.8'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => [
                        'code' => 'ASSERTION',
                        'codeSystem' => '2.16.840.1.113883.5.4',
                    ],
                ],
                FieldLevel::$statusCodeCompleted,
                array_merge(FieldLevel::effectiveTime(), ['dataKey' => 'effective_date']),
                [
                    'key' => 'value',
                    'attributes' => [
                        'xsi:type' => 'CD',
                        'code' => LeafLevel::inputProperty('pregnancy_code'),
                        'codeSystem' => '2.16.840.1.113883.6.96',
                        'codeSystemName' => 'SNOMED-CT',
                        'displayName' => LeafLevel::inputProperty('pregnancy_title'),
                    ],
                    'dataKey' => 'pregnancy_status',
                ],
            ],
            'existsWhen' => fn(mixed $input): bool => is_array($input) && is_array($input['pregnancy_status'] ?? null) && isset($input['pregnancy_status']['pregnancy_code']),
        ];
    }

    /**
     * Sexual Orientation Observation
     * JS: exports.sexualOrientationObservation
     * @return array<string, mixed>
     */
    public static function sexualOrientationObservation(): array
    {
        return [
            'key' => 'observation',
            'attributes' => [
                'classCode' => 'OBS',
                'moodCode' => 'EVN',
            ],
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.38'),
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.34.3.24', '2019-06-21'),
                FieldLevel::uniqueIdRoot(),
                [
                    'key' => 'code',
                    'attributes' => [
                        'code' => '76690-7',
                        'codeSystem' => '2.16.840.1.113883.6.1',
                        'codeSystemName' => 'LOINC',
                        'displayName' => 'Sexual Orientation',
                    ],
                ],
                FieldLevel::$statusCodeCompleted,
                [
                    'key' => 'effectiveTime',
                    'attributes' => ['nullFlavor' => 'NI'],
                ],
                [
                    'key' => 'value',
                    'attributes' => function (mixed $input): array {
                        $attrs = ['xsi:type' => 'CD'];
                        if (is_array($input) && isset($input['code'])) {
                            $attrs['code'] = is_string($input['code']) ? $input['code'] : '';
                            $attrs['displayName'] = is_string($input['display'] ?? null) ? $input['display'] : null;
                            $attrs['codeSystem'] = is_string($input['code_system'] ?? null) ? $input['code_system'] : '2.16.840.1.113883.6.1';
                            $attrs['codeSystemName'] = is_string($input['code_system_name'] ?? null) ? $input['code_system_name'] : 'LOINC';
                        } else {
                            $attrs['nullFlavor'] = 'UNK';
                        }
                        return $attrs;
                    },
                    'dataKey' => 'sexual_orientation',
                ],
            ],
            'existsWhen' => fn() => true,
        ];
    }

    /**
     * Gender Identity Observation
     * JS: exports.genderIdentityObservation
     * @return array<string, mixed>
     */
    public static function genderIdentityObservation(): array
    {
        return [
            'key' => 'observation',
            'attributes' => [
                'classCode' => 'OBS',
                'moodCode' => 'EVN',
            ],
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.38'),
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.34.3.45', '2022-06-01'),
                FieldLevel::uniqueIdRoot(),
                [
                    'key' => 'code',
                    'attributes' => [
                        'code' => '76691-5',
                        'codeSystem' => '2.16.840.1.113883.6.1',
                        'codeSystemName' => 'LOINC',
                        'displayName' => 'Gender Identity',
                    ],
                ],
                FieldLevel::$statusCodeCompleted,
                [
                    'key' => 'effectiveTime',
                    'attributes' => ['nullFlavor' => 'NI'],
                ],
                [
                    'key' => 'value',
                    'attributes' => function (mixed $input): array {
                        $attrs = ['xsi:type' => 'CD'];
                        if (is_array($input) && isset($input['code'])) {
                            $attrs['code'] = is_string($input['code']) ? $input['code'] : '';
                            $attrs['displayName'] = is_string($input['display'] ?? null) ? $input['display'] : null;
                            $attrs['codeSystem'] = is_string($input['code_system'] ?? null) ? $input['code_system'] : '2.16.840.1.113883.6.1';
                            $attrs['codeSystemName'] = is_string($input['code_system_name'] ?? null) ? $input['code_system_name'] : 'LOINC';
                        } else {
                            $attrs['nullFlavor'] = 'ASKU';
                        }
                        return $attrs;
                    },
                    'dataKey' => 'gender_identity',
                ],
            ],
            'existsWhen' => fn() => true,
        ];
    }

    /**
     * Sex Observation
     * JS: exports.sexObservation
     * @return array<string, mixed>
     */
    public static function sexObservation(): array
    {
        return [
            'key' => 'observation',
            'attributes' => [
                'classCode' => 'OBS',
                'moodCode' => 'EVN',
            ],
            'content' => [
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.4.507', '2023-06-28'),
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.507'),
                FieldLevel::uniqueIdRoot(),
                [
                    'key' => 'code',
                    'attributes' => [
                        'code' => '46098-0',
                        'codeSystem' => '2.16.840.1.113883.6.1',
                        'codeSystemName' => 'LOINC',
                        'displayName' => 'Sex',
                    ],
                ],
                FieldLevel::$statusCodeCompleted,
                [
                    'key' => 'effectiveTime',
                    'attributes' => ['nullFlavor' => 'NI'],
                ],
                [
                    'key' => 'value',
                    'attributes' => self::resolveSexValueAttributes(...),
                    'dataKey' => 'sex_observation',
                ],
            ],
            'existsWhen' => function (mixed $input): bool {
                if (!is_array($input)) {
                    return false;
                }
                $so = $input['sex_observation'] ?? null;
                return (is_string($so) && $so !== '') ||
                    (is_array($so) && (isset($so['gender']) || isset($so['code_spec']) || isset($so['code']))) ||
                    isset($input['gender']);
            },
        ];
    }

    /**
     * Resolve sex value attributes
     *
     * @return array<string, mixed>
     */
    private static function resolveSexValueAttributes(mixed $input): array
    {
        if (!is_array($input)) {
            return ['xsi:type' => 'CD', 'code' => 'unknown', 'codeSystem' => '2.16.840.1.113883.4.642.4.1048', 'displayName' => 'Unknown'];
        }
        $so = is_array($input['sex_observation'] ?? null) ? $input['sex_observation'] : [];
        $attrs = ['xsi:type' => 'CD'];

        // Check option_id/gender first
        $optionId = is_string($so['gender'] ?? null) ? $so['gender'] : null;
        $resolved = self::resolveAdministrativeSexFromOptionId($optionId);
        if ($resolved !== null) {
            $attrs['code'] = $resolved['code'];
            $attrs['codeSystem'] = $resolved['system'];
            $attrs['displayName'] = $resolved['display'];
            return $attrs;
        }

        // Check code_spec
        $codeSpec = $so['code_spec'] ?? null;
        if (is_string($codeSpec)) {
            $fromSpec = self::parseCodeSpec($codeSpec);
            if ($fromSpec !== null) {
                $attrs['code'] = $fromSpec['code'];
                $attrs['codeSystem'] = $fromSpec['systemUri'];
                $attrs['displayName'] = self::displayForSexCode($fromSpec['systemUri'], $fromSpec['code']);
                return $attrs;
            }
        }

        // Check direct code/codeSystem
        $soCode = $so['code'] ?? null;
        $soCodeSystem = $so['code_system'] ?? $so['codeSystem'] ?? null;
        if (is_string($soCode) && is_string($soCodeSystem)) {
            $attrs['code'] = $soCode;
            $attrs['codeSystem'] = $soCodeSystem;
            $display = $so['display'] ?? null;
            $attrs['displayName'] = is_string($display) ? $display : self::displayForSexCode($soCodeSystem, $soCode);
            return $attrs;
        }

        // Fallback to input.gender
        $inputGender = $input['gender'] ?? null;
        $g = is_string($inputGender) ? strtolower($inputGender) : null;
        $genderMap = [
            'm' => ['248153007', '2.16.840.1.113883.6.96', 'Male'],
            'male' => ['248153007', '2.16.840.1.113883.6.96', 'Male'],
            'f' => ['248152002', '2.16.840.1.113883.6.96', 'Female'],
            'female' => ['248152002', '2.16.840.1.113883.6.96', 'Female'],
            'nonbinary' => ['33791000087105', '2.16.840.1.113883.6.96', 'Identifies as nonbinary gender (finding)'],
            'asked-declined' => ['asked-declined', '2.16.840.1.113883.4.642.4.1048', 'Asked But Declined'],
            'unk' => ['unknown', '2.16.840.1.113883.4.642.4.1048', 'Unknown'],
            'unknown' => ['unknown', '2.16.840.1.113883.4.642.4.1048', 'Unknown'],
        ];

        if ($g && isset($genderMap[$g])) {
            [$code, $system, $display] = $genderMap[$g];
            $attrs['code'] = $code;
            $attrs['codeSystem'] = $system;
            $attrs['displayName'] = $display;
            return $attrs;
        }

        // Last resort
        $attrs['code'] = 'unknown';
        $attrs['codeSystem'] = '2.16.840.1.113883.4.642.4.1048';
        $attrs['displayName'] = 'Unknown';
        return $attrs;
    }

    /**
     * @return array{code: string, system: string, display: string}|null
     */
    private static function resolveAdministrativeSexFromOptionId(?string $optionId): ?array
    {
        if ($optionId === null || $optionId === '') {
            return null;
        }
        $optionId = strtolower(trim($optionId));

        $map = [
            'male' => ['code' => '248153007', 'system' => '2.16.840.1.113883.6.96', 'display' => 'Male'],
            'female' => ['code' => '248152002', 'system' => '2.16.840.1.113883.6.96', 'display' => 'Female'],
            'nonbinary' => ['code' => '33791000087105', 'system' => '2.16.840.1.113883.6.96', 'display' => 'Identifies as nonbinary gender (finding)'],
            'asked-declined' => ['code' => 'asked-declined', 'system' => '2.16.840.1.113883.4.642.4.1048', 'display' => 'Asked But Declined'],
            'unk' => ['code' => 'unknown', 'system' => '2.16.840.1.113883.4.642.4.1048', 'display' => 'Unknown'],
            'unknown' => ['code' => 'unknown', 'system' => '2.16.840.1.113883.4.642.4.1048', 'display' => 'Unknown'],
        ];

        return $map[$optionId] ?? null;
    }

    /**
     * @return array{systemUri: string, code: string}|null
     */
    private static function parseCodeSpec(string $spec): ?array
    {
        if ($spec === '') {
            return null;
        }
        $parts = explode(':', $spec);
        if (count($parts) !== 2) {
            return null;
        }
        [$sys, $code] = array_map(trim(...), $parts);
        if (!$sys || !$code) {
            return null;
        }

        $systems = [
            'SNOMED-CT' => '2.16.840.1.113883.6.96',
            'SNOMED' => '2.16.840.1.113883.6.96',
            'DataAbsentReason' => '2.16.840.1.113883.4.642.4.1048',
        ];

        return ['systemUri' => $systems[$sys] ?? $sys, 'code' => $code];
    }

    private static function displayForSexCode(string $systemUri, string $code): string
    {
        if ($systemUri === '2.16.840.1.113883.6.96') {
            $map = [
                '248153007' => 'Male',
                '248152002' => 'Female',
                '33791000087105' => 'Identifies as nonbinary gender (finding)',
            ];
            return $map[$code] ?? '';
        }
        if ($systemUri === '2.16.840.1.113883.4.642.4.1048') {
            $map = [
                'asked-declined' => 'Asked But Declined',
                'unknown' => 'Unknown',
            ];
            return $map[$code] ?? '';
        }
        return '';
    }
}
