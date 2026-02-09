<?php

/**
 * PlanOfCareEntryLevel.php - Plan of Care entry-level template definitions
 *
 * PHP port of oe-blue-button-generate/lib/entryLevel/planOfCareEntryLevel.js
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
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\TemplateHelpers as H;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\Translate;

class PlanOfCareEntryLevel
{
    /**
     * Health Concern Observation
     * JS: exports.healthConcernObservation
     * @return array<string, mixed>
     */
    public static function healthConcernObservation(): array
    {
        return [
            'key' => 'observation',
            'attributes' => [
                'classCode' => 'OBS',
                'moodCode' => 'EVN',
            ],
            'content' => [
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.4.5', '2014-06-09'),
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.5'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => [
                        'code' => '11323-3',
                        'codeSystem' => '2.16.840.1.113883.6.1',
                        'codeSystemName' => 'LOINC',
                        'displayName' => 'Health Status',
                    ],
                ],
                FieldLevel::$statusCodeCompleted,
                [
                    'key' => 'value',
                    'attributes' => array_merge(
                        LeafLevel::$typeCD,
                        LeafLevel::code()
                    ),
                    'dataKey' => 'value',
                    'existsWhen' => [Condition::class, 'codeOrDisplayname'],
                ],
                FieldLevel::effectiveTime(),
                FieldLevel::author(),
            ],
        ];
    }

    /**
     * Health Concern Activity Act
     * JS: exports.healthConcernActivityAct
     * @return array<string, mixed>
     */
    public static function healthConcernActivityAct(): array
    {
        return [
            'key' => 'act',
            'attributes' => [
                'classCode' => 'ACT',
                'moodCode' => 'EVN',
            ],
            'existsWhen' => Condition::keyExists('value'),
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.132'),
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.4.132', '2015-08-01'),
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.4.132', '2022-06-01'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => [
                        'code' => '75310-3',
                        'codeSystem' => '2.16.840.1.113883.6.1',
                        'codeSystemName' => 'LOINC',
                        'displayName' => 'Health Concern',
                    ],
                ],
                FieldLevel::$statusCodeActive,
                FieldLevel::effectiveTime(),
                FieldLevel::author(),
                [
                    'key' => 'entryRelationship',
                    'attributes' => ['typeCode' => 'REFR'],
                    'content' => [
                        [
                            'key' => 'observation',
                            'attributes' => [
                                'classCode' => 'OBS',
                                'moodCode' => 'EVN',
                            ],
                            'content' => [
                                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.4'),
                                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.4.4', '2015-08-01'),
                                FieldLevel::uniqueId(),
                                FieldLevel::id(),
                                [
                                    'key' => 'code',
                                    'attributes' => [
                                        'code' => '404684003',
                                        'displayName' => 'Clinical finding (finding)',
                                        'codeSystem' => '2.16.840.1.113883.6.96',
                                        'codeSystemName' => 'SNOMED CT',
                                    ],
                                    'content' => [
                                        [
                                            'key' => 'translation',
                                            'attributes' => [
                                                'code' => '75321-0',
                                                'displayName' => 'Clinical Finding',
                                                'codeSystem' => '2.16.840.1.113883.6.1',
                                                'codeSystemName' => 'LOINC',
                                            ],
                                        ],
                                    ],
                                ],
                                FieldLevel::$statusCodeCompleted,
                                FieldLevel::effectiveTime(),
                                [
                                    'key' => 'value',
                                    'attributes' => array_merge(
                                        LeafLevel::$typeCD,
                                        LeafLevel::code()
                                    ),
                                    'dataKey' => 'value',
                                    'existsWhen' => [Condition::class, 'codeOrDisplayname'],
                                ],
                                FieldLevel::author(),
                            ],
                        ],
                    ],
                    'dataTransform' => function ($input) {
                        if (!is_array($input)) {
                            return $input;
                        }
                        // Synthesize value if missing
                        $v = is_array($input['value'] ?? null) ? $input['value'] : [];
                        $code = H::strOrNull($input, 'code');
                        $codeText = H::strOrNull($input, 'code_text');
                        $codeType = H::strOrNull($input, 'code_type');

                        if (!isset($v['code']) && $code !== null) {
                            $v['code'] = trim($code);
                        }
                        if (!isset($v['name']) && $codeText !== null) {
                            $v['name'] = $codeText;
                        }
                        if (!isset($v['code_system_name']) && $codeType !== null) {
                            $v['code_system_name'] = str_replace(['-', '  '], [' ', ' '], trim($codeType));
                        }
                        $sysNameSource = $v['code_system_name'] ?? $codeType ?? null;
                        if (!isset($v['code_system']) && is_string($sysNameSource)) {
                            $sysName = strtolower(str_replace(' ', '', $sysNameSource));
                            if (str_contains($sysName, 'snomed')) {
                                $v['code_system'] = '2.16.840.1.113883.6.96';
                            } elseif (str_contains($sysName, 'loinc')) {
                                $v['code_system'] = '2.16.840.1.113883.6.1';
                            } elseif (str_contains($sysName, 'icd10')) {
                                $v['code_system'] = '2.16.840.1.113883.6.90';
                            }
                        }
                        $input['value'] = $v;
                        return $input;
                    },
                ],
            ],
        ];
    }

    /**
     * Plan of Care Activity Act
     * JS: exports.planOfCareActivityAct
     * @return array<string, mixed>
     */
    public static function planOfCareActivityAct(): array
    {
        return [
            'key' => 'act',
            'attributes' => [
                'classCode' => 'ACT',
                'moodCode' => 'RQO',
            ],
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.39'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => LeafLevel::code(),
                    'dataKey' => 'plan',
                ],
                FieldLevel::$statusCodeActive,
                FieldLevel::effectiveTime(),
                FieldLevel::author(),
            ],
            'existsWhen' => fn($input) => H::str($input, 'type') === 'act',
        ];
    }

    /**
     * Plan of Care Activity Observation
     * JS: exports.planOfCareActivityObservation
     * @return array<string, mixed>
     */
    public static function planOfCareActivityObservation(): array
    {
        return [
            'key' => 'observation',
            'attributes' => [
                'classCode' => 'OBS',
                'moodCode' => LeafLevel::inputProperty('mood_code'),
            ],
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.44'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => LeafLevel::code(),
                    'dataKey' => 'plan',
                ],
                FieldLevel::$statusCodeActive,
                FieldLevel::effectiveTime(),
                FieldLevel::author(),
            ],
            'existsWhen' => fn($input) => H::str($input, 'type') === 'observation',
        ];
    }

    /**
     * Planned Procedure
     * JS: exports.plannedProcedure
     * @return array<string, mixed>
     */
    public static function plannedProcedure(): array
    {
        return [
            'key' => 'procedure',
            'attributes' => [
                'classCode' => 'PROC',
                'moodCode' => LeafLevel::inputProperty('mood_code'),
            ],
            'content' => [
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.4.41', '2022-06-01'),
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.41'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => LeafLevel::code(),
                    'dataKey' => 'plan',
                ],
                FieldLevel::$statusCodeActive,
                FieldLevel::effectiveTime(),
                FieldLevel::author(),
            ],
            'existsWhen' => fn($input) => H::str($input, 'type') === 'planned_procedure',
        ];
    }

    /**
     * Plan of Care Activity Procedure
     * JS: exports.planOfCareActivityProcedure
     * @return array<string, mixed>
     */
    public static function planOfCareActivityProcedure(): array
    {
        return [
            'key' => 'procedure',
            'attributes' => [
                'classCode' => 'PROC',
                'moodCode' => 'RQO',
            ],
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.41'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => LeafLevel::code(),
                    'dataKey' => 'plan',
                ],
                FieldLevel::$statusCodeActive,
                FieldLevel::effectiveTime(),
                FieldLevel::author(),
            ],
            'existsWhen' => fn($input) => H::str($input, 'type') === 'procedure',
        ];
    }

    /**
     * Plan of Care Activity Encounter
     * JS: exports.planOfCareActivityEncounter
     * @return array<string, mixed>
     */
    public static function planOfCareActivityEncounter(): array
    {
        return [
            'key' => 'encounter',
            'attributes' => [
                'classCode' => 'ENC',
                'moodCode' => 'INT',
            ],
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.40'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => LeafLevel::code(),
                    'dataKey' => 'plan',
                ],
                FieldLevel::$statusCodeActive,
                FieldLevel::effectiveTime(),
                array_merge(FieldLevel::performer(), ['dataKey' => 'performers']),
                [
                    'key' => 'participant',
                    'attributes' => ['typeCode' => 'LOC'],
                    'content' => [
                        array_merge(SharedEntryLevel::serviceDeliveryLocation(), ['required' => true]),
                    ],
                    'dataKey' => 'locations',
                ],
                [
                    'key' => 'entryRelationship',
                    'attributes' => ['typeCode' => 'RSON'],
                    'content' => [
                        array_merge(SharedEntryLevel::indication(), ['required' => true]),
                    ],
                    'dataKey' => 'findings',
                    'dataTransform' => function ($input) {
                        if (!is_array($input)) {
                            return $input;
                        }
                        return array_map(function ($e) {
                            if (!is_array($e)) {
                                return $e;
                            }
                            $e['code'] = [
                                'code' => '282291009',
                                'name' => 'Diagnosis',
                                'code_system' => '2.16.840.1.113883.6.96',
                                'code_system_name' => 'SNOMED CT',
                            ];
                            return $e;
                        }, $input);
                    },
                ],
            ],
            'existsWhen' => fn($input) => H::str($input, 'type') === 'encounter',
        ];
    }

    /**
     * Plan of Care Activity Substance Administration
     * JS: exports.planOfCareActivitySubstanceAdministration
     * @return array<string, mixed>
     */
    public static function planOfCareActivitySubstanceAdministration(): array
    {
        return [
            'key' => 'substanceAdministration',
            'attributes' => [
                'classCode' => 'SBADM',
                'moodCode' => 'RQO',
            ],
            'content' => [
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.4.42', '2014-06-09'),
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.42'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'text',
                    'text' => LeafLevel::input(...),
                    'dataKey' => 'name',
                ],
                FieldLevel::$statusCodeActive,
                FieldLevel::effectiveTime(),
                [
                    'key' => 'consumable',
                    'content' => [self::carePlanMedicationInformation()],
                    'dataKey' => 'plan',
                ],
            ],
            'existsWhen' => fn($input) => H::str($input, 'type') === 'substanceAdministration',
        ];
    }

    /**
     * Care Plan Medication Information
     *
     * @return array<string, mixed>
     */
    private static function carePlanMedicationInformation(): array
    {
        return [
            'key' => 'manufacturedProduct',
            'attributes' => ['classCode' => 'MANU'],
            'content' => [
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.4.23', '2014-06-09'),
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.23'),
                [
                    'key' => 'manufacturedMaterial',
                    'content' => [
                        [
                            'key' => 'code',
                            'attributes' => LeafLevel::code(),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Plan of Care Activity Supply
     * JS: exports.planOfCareActivitySupply
     * @return array<string, mixed>
     */
    public static function planOfCareActivitySupply(): array
    {
        return [
            'key' => 'supply',
            'attributes' => [
                'classCode' => 'SPLY',
                'moodCode' => 'INT',
            ],
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.43'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => LeafLevel::code(),
                    'dataKey' => 'plan',
                ],
                FieldLevel::$statusCodeActive,
                FieldLevel::effectiveTime(),
                FieldLevel::author(),
            ],
            'existsWhen' => fn($input) => H::str($input, 'type') === 'supply',
        ];
    }

    /**
     * Plan of Care Activity Instructions
     * JS: exports.planOfCareActivityInstructions
     * @return array<string, mixed>
     */
    public static function planOfCareActivityInstructions(): array
    {
        return [
            'key' => 'instructions',
            'attributes' => [
                'classCode' => 'ACT',
                'moodCode' => 'INT',
            ],
            'content' => [
                FieldLevel::templateId('2.16.840.1.113883.10.20.22.4.20'),
                FieldLevel::uniqueId(),
                FieldLevel::id(),
                [
                    'key' => 'code',
                    'attributes' => LeafLevel::code(),
                    'dataKey' => 'plan',
                ],
                FieldLevel::$statusCodeActive,
                [
                    'key' => 'priorityCode',
                    'attributes' => [
                        'code' => LeafLevel::deepInputProperty('code'),
                        'displayName' => 'Severity Code',
                    ],
                    'content' => [
                        [
                            'key' => 'originalText',
                            'text' => LeafLevel::deepInputProperty('name'),
                        ],
                    ],
                    'dataKey' => 'severity',
                ],
                FieldLevel::effectiveTime(),
                FieldLevel::author(),
                [
                    'key' => 'entryRelationship',
                    'attributes' => ['typeCode' => 'COMP'],
                    'content' => [
                        [
                            'key' => 'observation',
                            'attributes' => [
                                'classCode' => 'OBS',
                                'moodCode' => 'GOL',
                            ],
                            'content' => [
                                FieldLevel::effectiveTime(),
                                [
                                    'key' => 'code',
                                    'attributes' => [
                                        'code' => LeafLevel::deepInputProperty('code'),
                                        'displayName' => 'Goal',
                                    ],
                                    'content' => [
                                        [
                                            'key' => 'originalText',
                                            'text' => LeafLevel::deepInputProperty('name'),
                                        ],
                                    ],
                                    'dataKey' => 'goal',
                                ],
                                [
                                    'key' => 'act',
                                    'attributes' => [
                                        'classCode' => 'ACT',
                                        'moodCode' => 'INT',
                                    ],
                                    'content' => [
                                        [
                                            'key' => 'entryRelationship',
                                            'attributes' => ['typeCode' => 'REFR'],
                                            'content' => [
                                                [
                                                    'key' => 'code',
                                                    'attributes' => [
                                                        'code' => LeafLevel::deepInputProperty('code'),
                                                        'displayName' => 'Intervention',
                                                    ],
                                                    'content' => [
                                                        [
                                                            'key' => 'originalText',
                                                            'text' => LeafLevel::deepInputProperty('name'),
                                                        ],
                                                    ],
                                                    'dataKey' => 'intervention',
                                                ],
                                            ],
                                            'dataKey' => 'interventions',
                                        ],
                                    ],
                                ],
                            ],
                            'dataKey' => 'goals',
                        ],
                    ],
                    'required' => true,
                ],
            ],
            'existsWhen' => fn($input) => H::str($input, 'type') === 'instructions',
        ];
    }
}
