<?php

/**
 * HeaderLevel.php - Header-level template definitions
 *
 * PHP port of oe-blue-button-generate/lib/headerLevel.js
 * Contains templates for recordTarget, author, custodian, and other header elements.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2026 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Templates;

use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\Condition;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\FieldLevel;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\LeafLevel;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\TemplateHelpers as H;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Core\Translate;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Templates\EntryLevel\EntryLevel;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\Templates\EntryLevel\SharedEntryLevel;
use OpenEMR\Carecoordination\Model\PhpCcdaBuilder\CodeSystems\CcdaTemplateCodes;

class HeaderLevel
{
    /**
     * Patient name with use="L" (legal name)
     *
     * @return array<string, mixed>
     * @return array<string, mixed>
     */
    public static function patientName(): array
    {
        $name = FieldLevel::usRealmName();
        $name['attributes'] = ['use' => 'L'];
        return $name;
    }

    /**
     * Patient element within patientRole
     *
     * @return array<string, mixed>
     * @return array<string, mixed>
     */
    public static function patient(): array
    {
        return [
            'key' => 'patient',
            'content' => [
                // Legal name
                self::patientName(),
                // Birth name (if different)
                [
                    'key' => 'name',
                    'content' => [
                        [
                            'key' => 'given',
                            'attributes' => ['qualifier' => 'BR'],
                            'text' => fn($input) => H::strOrNull($input, 'first'),
                        ],
                        [
                            'key' => 'given',
                            'text' => fn($input) => H::strOrNull($input, 'middle'),
                            'existsWhen' => fn($input) => H::has($input, 'middle'),
                        ],
                        [
                            'key' => 'family',
                            'attributes' => ['qualifier' => 'BR'],
                            'text' => fn($input) => H::strOrNull($input, 'last'),
                        ],
                    ],
                    'dataKey' => 'birth_name',
                    'existsWhen' => fn($input) => H::has($input, 'last'),
                ],
                // Administrative Gender
                [
                    'key' => 'administrativeGenderCode',
                    'attributes' => [
                        'code' => fn($input) => H::firstChar($input, is_string($input) ? null : 'code'),
                        'codeSystem' => '2.16.840.1.113883.5.1',
                        'codeSystemName' => 'HL7 AdministrativeGender',
                        'displayName' => fn($input) => H::valueOrKey($input, 'name'),
                    ],
                    'dataKey' => 'gender',
                ],
                // Birth Time
                [
                    'key' => 'birthTime',
                    'attributes' => [
                        'value' => fn($input) => H::nestedOrNull($input, 'point.date') ?? H::strOrNull($input, 'date'),
                    ],
                    'dataKey' => 'dob',
                ],
                // Marital Status
                [
                    'key' => 'maritalStatusCode',
                    'attributes' => [
                        'code' => fn($input) => H::firstChar($input, is_string($input) ? null : 'code'),
                        'displayName' => fn($input) => H::valueOrKey($input, 'name'),
                        'codeSystem' => '2.16.840.1.113883.5.2',
                        'codeSystemName' => 'HL7 Marital Status',
                    ],
                    'dataKey' => 'marital_status',
                    'existsWhen' => H::notEmpty(...),
                ],
                // Religious Affiliation
                [
                    'key' => 'religiousAffiliationCode',
                    'attributes' => LeafLevel::codeFromName('2.16.840.1.113883.5.1076'),
                    'dataKey' => 'religion',
                    'existsWhen' => H::notEmpty(...),
                ],
                // Race Code
                [
                    'key' => 'raceCode',
                    'attributes' => LeafLevel::codeFromName('2.16.840.1.113883.6.238'),
                    'dataKey' => 'race',
                    'existsWhen' => H::notEmpty(...),
                ],
                // Additional Race (sdtc extension)
                [
                    'key' => 'sdtc:raceCode',
                    'attributes' => LeafLevel::codeFromName('2.16.840.1.113883.6.238'),
                    'dataKey' => 'race_additional',
                    'existsWhen' => H::notEmpty(...),
                ],
                // Ethnic Group
                [
                    'key' => 'ethnicGroupCode',
                    'attributes' => LeafLevel::codeFromName('2.16.840.1.113883.6.238'),
                    'dataKey' => 'ethnicity',
                    'existsWhen' => H::notEmpty(...),
                ],
                // Guardian
                self::guardian(),
                // Birthplace
                self::birthplace(),
                // Language Communication
                self::languageCommunication(),
            ],
        ];
    }

    /**
     * Guardian element
     * @return array<string, mixed>
     */
    public static function guardian(): array
    {
        return [
            'key' => 'guardian',
            'content' => [
                [
                    'key' => 'code',
                    'attributes' => LeafLevel::code(...),
                    'dataKey' => 'code',
                ],
                [
                    'key' => 'addr',
                    'attributes' => ['use' => fn($input) => Translate::acronymize(H::str($input, 'use', 'HP'))],
                    'content' => [
                        ['key' => 'streetAddressLine', 'text' => fn($input) => H::arr($input, 'street_lines')[0] ?? null, 'dataKey' => 'addresses'],
                        ['key' => 'city', 'text' => fn($input) => H::strOrNull($input, 'city')],
                        ['key' => 'state', 'text' => fn($input) => H::strOrNull($input, 'state')],
                        ['key' => 'postalCode', 'text' => fn($input) => H::strOrNull($input, 'zip')],
                        ['key' => 'country', 'text' => fn($input) => H::str($input, 'country', 'US')],
                    ],
                    'dataKey' => 'addresses',
                ],
                FieldLevel::telecom(),
                [
                    'key' => 'guardianPerson',
                    'content' => [
                        'key' => 'name',
                        'content' => [
                            ['key' => 'given', 'text' => fn($input) => H::strOrNull($input, 'first')],
                            ['key' => 'family', 'text' => fn($input) => H::strOrNull($input, 'last')],
                        ],
                        'dataKey' => 'names',
                    ],
                ],
            ],
            'dataKey' => 'guardians',
            'existsWhen' => H::notEmpty(...),
        ];
    }

    /**
     * Birthplace element
     * @return array<string, mixed>
     */
    public static function birthplace(): array
    {
        return [
            'key' => 'birthplace',
            'content' => [
                'key' => 'place',
                'content' => [
                    [
                        'key' => 'addr',
                        'content' => [
                            ['key' => 'city', 'text' => fn($input) => H::strOrNull($input, 'city')],
                            ['key' => 'state', 'text' => fn($input) => H::strOrNull($input, 'state')],
                            ['key' => 'postalCode', 'text' => fn($input) => H::strOrNull($input, 'zip')],
                            ['key' => 'country', 'text' => fn($input) => H::strOrNull($input, 'country')],
                        ],
                        'dataKey' => 'birthplace',
                    ],
                ],
            ],
            'existsWhen' => fn($input) => H::has($input, 'birthplace'),
        ];
    }

    /**
     * Language Communication element
     * @return array<string, mixed>
     */
    public static function languageCommunication(): array
    {
        return [
            'key' => 'languageCommunication',
            'content' => [
                [
                    'key' => 'languageCode',
                    'attributes' => ['code' => fn($input) => $input],
                    'dataKey' => 'language',
                ],
                [
                    'key' => 'modeCode',
                    'attributes' => LeafLevel::codeFromName('2.16.840.1.113883.5.60'),
                    'dataKey' => 'mode',
                    'existsWhen' => H::notEmpty(...),
                ],
                [
                    'key' => 'proficiencyLevelCode',
                    'attributes' => [
                        'code' => function ($input) {
                            if (is_string($input)) {
                                return strtoupper(substr($input, 0, 1));
                            }
                            return strtoupper(substr(H::str($input, 'code'), 0, 1));
                        },
                        'displayName' => fn($input) => is_string($input) ? $input : H::str($input, 'name'),
                        'codeSystem' => '2.16.840.1.113883.5.61',
                        'codeSystemName' => 'LanguageAbilityProficiency',
                    ],
                    'dataKey' => 'proficiency',
                    'existsWhen' => H::notEmpty(...),
                ],
                [
                    'key' => 'preferenceInd',
                    'attributes' => ['value' => fn($input) => $input ? 'true' : 'false'],
                    'dataKey' => 'preferred',
                    'existsWhen' => fn($input) => $input !== null,
                ],
            ],
            'dataKey' => 'languages',
            'existsWhen' => H::notEmpty(...),
        ];
    }

    /**
     * Provider Organization (attributed_provider)
     * @return array<string, mixed>
     */
    public static function attributedProvider(): array
    {
        return [
            'key' => 'providerOrganization',
            'content' => [
                [
                    'key' => 'id',
                    'attributes' => [
                        'root' => fn($input) => H::strOrNull($input, 'root'),
                        'extension' => fn($input) => H::strOrNull($input, 'extension'),
                    ],
                    'dataKey' => 'identity',
                ],
                [
                    'key' => 'name',
                    'text' => fn($input) => H::strOrNull($input, 'full') ?? H::strOrNull($input, 'name'),
                    'dataKey' => 'name',
                ],
                [
                    'key' => 'telecom',
                    'attributes' => [
                        'use' => 'WP',
                        'value' => fn($input) => H::has($input, 'number') ? 'tel:' . H::str($input, 'number') : null,
                    ],
                    'dataKey' => 'phone',
                ],
                self::simpleAddress(),
            ],
            'dataKey' => 'attributed_provider',
            'existsWhen' => H::notEmpty(...),
        ];
    }

    /**
     * Simple address element
     * @return array<string, mixed>
     */
    public static function simpleAddress(): array
    {
        return [
            'key' => 'addr',
            'attributes' => ['use' => fn($input) => Translate::acronymize(H::str($input, 'use', 'WP'))],
            'content' => [
                ['key' => 'country', 'text' => fn($input) => H::strOrNull($input, 'country')],
                ['key' => 'state', 'text' => fn($input) => H::strOrNull($input, 'state')],
                ['key' => 'city', 'text' => fn($input) => H::strOrNull($input, 'city')],
                ['key' => 'postalCode', 'text' => fn($input) => H::strOrNull($input, 'zip')],
                [
                    'key' => 'streetAddressLine',
                    'text' => fn($input) => $input,
                    'dataKey' => 'street_lines',
                ],
            ],
            'dataKey' => 'address',
            'existsWhen' => H::notEmpty(...),
        ];
    }

    /**
     * Record Target - main patient demographic wrapper
     * @return array<string, mixed>
     */
    public static function recordTarget(): array
    {
        return [
            'key' => 'recordTarget',
            'content' => [
                'key' => 'patientRole',
                'content' => [
                    // ID - uses identifiers from demographics
                    [
                        'key' => 'id',
                        'attributes' => [
                            'root' => fn($input) => H::str($input, 'identifier'),
                            'extension' => fn($input) => H::str($input, 'extension'),
                        ],
                        'dataKey' => 'identifiers',
                        'existsWhen' => fn($input) => H::has($input, 'identifier'),
                    ],
                    // Address - uses addresses array
                    [
                        'key' => 'addr',
                        'attributes' => ['use' => fn($input) => Translate::acronymize(H::str($input, 'use', 'HP'))],
                        'content' => [
                            [
                                'key' => 'streetAddressLine',
                                'text' => fn($input) => is_array($input) ? ($input[0] ?? '') : (is_string($input) ? $input : ''),
                                'dataKey' => 'street_lines',
                            ],
                            ['key' => 'city', 'text' => fn($input) => H::str($input, 'city')],
                            ['key' => 'state', 'text' => fn($input) => H::str($input, 'state')],
                            ['key' => 'postalCode', 'text' => fn($input) => H::str($input, 'zip')],
                            ['key' => 'country', 'text' => fn($input) => H::str($input, 'country', 'US')],
                        ],
                        'dataKey' => 'addresses',
                    ],
                    // Telecom - handles both phone numbers and emails
                    [
                        'key' => 'telecom',
                        'attributes' => [
                            'value' => function ($input) {
                                if (!is_array($input)) {
                                    return null;
                                }
                                // Handle email
                                $email = $input['email'] ?? null;
                                if (is_string($email) && $email !== '') {
                                    return 'mailto:' . $email;
                                }
                                // Handle phone number
                                $number = $input['number'] ?? null;
                                if (is_string($number) && $number !== '') {
                                    $num = preg_replace('/[^\d+]/', '', $number) ?? '';
                                    return 'tel:' . ($num !== '' && $num[0] === '+' ? $num : '+' . $num);
                                }
                                // Handle pre-formatted value
                                return is_string($input['value'] ?? null) ? $input['value'] : null;
                            },
                            'use' => fn($input) => H::strOrNull($input, 'use') ?? Translate::acronymize(H::str($input, 'type', 'HP')),
                        ],
                        'dataKey' => 'phone',
                        'existsWhen' => fn($input) => H::has($input, 'number') || H::has($input, 'email') || H::has($input, 'value'),
                    ],
                    // Patient element
                    self::patient(),
                    // Provider Organization (attributed_provider)
                    self::attributedProvider(),
                ],
            ],
            'dataKey' => 'data.demographics',
        ];
    }
    /**
     * Header Author
     * @return array<string, mixed>
     */
    public static function headerAuthor(): array
    {
        return [
            'key' => 'author',
            'content' => [
                [
                    'key' => 'time',
                    'attributes' => ['value' => fn($input) => H::nestedOrNull($input, 'point.date') ?? H::strOrNull($input, 'date')],
                    'dataKey' => 'date_time',
                    'required' => true,
                ],
                [
                    'key' => 'assignedAuthor',
                    'content' => [
                        [
                            'key' => 'id',
                            'attributes' => [
                                'root' => fn($input) => H::strOrNull($input, 'identifier'),
                                'extension' => fn($input) => H::strOrNull($input, 'extension'),
                            ],
                            'dataKey' => 'identifiers',
                        ],
                        [
                            'key' => 'code',
                            'attributes' => LeafLevel::code(...),
                            'dataKey' => 'code',
                            'existsWhen' => fn($input) => H::has($input, 'code'),
                        ],
                        self::simpleAddress(),
                        [
                            'key' => 'telecom',
                            'attributes' => [
                                'value' => fn($input) => H::strOrNull($input, 'value'),
                                'use' => fn($input) => H::strOrNull($input, 'use'),
                            ],
                            'dataTransform' => Translate::telecom(...),
                        ],
                        [
                            'key' => 'assignedPerson',
                            'content' => [
                                'key' => 'name',
                                'content' => [
                                    ['key' => 'family', 'text' => fn($input) => H::strOrNull($input, 'family')],
                                    ['key' => 'given', 'text' => fn($input) => $input, 'dataKey' => 'given'],
                                    ['key' => 'prefix', 'text' => fn($input) => H::strOrNull($input, 'prefix')],
                                    ['key' => 'suffix', 'text' => fn($input) => H::strOrNull($input, 'suffix')],
                                ],
                                'dataKey' => 'name',
                                'dataTransform' => Translate::name(...),
                            ],
                        ],
                        [
                            'key' => 'representedOrganization',
                            'content' => [
                                [
                                    'key' => 'id',
                                    'attributes' => ['root' => fn($input) => H::strOrNull($input, 'root')],
                                    'dataKey' => 'identity',
                                ],
                                ['key' => 'name', 'text' => fn($input) => $input, 'dataKey' => 'name'],
                                [
                                    'key' => 'telecom',
                                    'attributes' => [
                                        'value' => fn($input) => H::strOrNull($input, 'value'),
                                        'use' => fn($input) => H::strOrNull($input, 'use'),
                                    ],
                                    'dataTransform' => Translate::telecom(...),
                                    'dataKey' => 'phone',
                                ],
                                self::simpleAddress(),
                            ],
                            'dataKey' => 'organization',
                        ],
                    ],
                ],
            ],
            'dataKey' => 'meta.ccda_header.author',
        ];
    }

    /**
     * Header Informant
     * @return array<string, mixed>
     */
    public static function headerInformant(): array
    {
        return [
            'key' => 'informant',
            'content' => [
                'key' => 'assignedEntity',
                'content' => [
                    [
                        'key' => 'id',
                        'attributes' => ['root' => fn($input) => H::strOrNull($input, 'identifier')],
                        'dataKey' => 'identifiers',
                    ],
                    [
                        'key' => 'representedOrganization',
                        'content' => [
                            [
                                'key' => 'id',
                                'attributes' => ['root' => fn($input) => H::strOrNull($input, 'identifier')],
                                'dataKey' => 'identifiers',
                            ],
                            [
                                'key' => 'name',
                                'text' => fn($input) => H::strOrNull($input, 'name'),
                                'dataKey' => 'name',
                            ],
                        ],
                    ],
                ],
            ],
            'dataKey' => 'meta.ccda_header.informant',
            'existsWhen' => H::notEmpty(...),
        ];
    }

    /**
     * Header Custodian
     * @return array<string, mixed>
     */
    public static function headerCustodian(): array
    {
        return [
            'key' => 'custodian',
            'content' => [
                'key' => 'assignedCustodian',
                'content' => [
                    [
                        'key' => 'representedCustodianOrganization',
                        'content' => [
                            [
                                'key' => 'id',
                                'attributes' => [
                                    'root' => fn($input) => H::strOrNull($input, 'root'),
                                    'extension' => fn($input) => H::strOrNull($input, 'extension'),
                                ],
                                'dataKey' => 'identity',
                            ],
                            ['key' => 'name', 'text' => fn($input) => $input, 'dataKey' => 'name'],
                            [
                                'key' => 'telecom',
                                'attributes' => [
                                    'value' => fn($input) => H::strOrNull($input, 'value'),
                                    'use' => fn($input) => H::strOrNull($input, 'use'),
                                ],
                                'dataTransform' => Translate::telecom(...),
                                'dataKey' => 'phone',
                            ],
                            self::simpleAddress(),
                        ],
                    ],
                ],
            ],
            'dataKey' => 'meta.ccda_header.custodian',
        ];
    }

    /**
     * Header Information Recipient
     * @return array<string, mixed>
     */
    public static function headerInformationRecipient(): array
    {
        return [
            'key' => 'informationRecipient',
            'content' => [
                'key' => 'intendedRecipient',
                'content' => [
                    [
                        'key' => 'informationRecipient',
                        'content' => [
                            'key' => 'name',
                            'content' => [
                                ['key' => 'family', 'text' => fn($input) => H::strOrNull($input, 'family')],
                                ['key' => 'given', 'text' => fn($input) => $input, 'dataKey' => 'given'],
                                ['key' => 'prefix', 'text' => fn($input) => H::strOrNull($input, 'prefix')],
                                ['key' => 'suffix', 'text' => fn($input) => H::strOrNull($input, 'suffix')],
                            ],
                            'dataKey' => 'name',
                            'dataTransform' => Translate::name(...),
                        ],
                    ],
                    [
                        'key' => 'receivedOrganization',
                        'content' => [
                            [
                                'key' => 'name',
                                'text' => fn($input) => H::strOrNull($input, 'name'),
                                'dataKey' => 'organization',
                            ],
                        ],
                    ],
                ],
            ],
            'dataKey' => 'meta.ccda_header.information_recipient',
            'existsWhen' => H::notEmpty(...),
        ];
    }

    /**
     * Header Component Of (Encompassing Encounter)
     * @return array<string, mixed>
     */
    public static function headerComponentOf(): array
    {
        return [
            'key' => 'componentOf',
            'content' => [
                'key' => 'encompassingEncounter',
                'content' => [
                    FieldLevel::id(),
                    [
                        'key' => 'code',
                        'attributes' => LeafLevel::code(...),
                        'dataKey' => 'code',
                        'existsWhen' => fn($input) => H::has($input, 'code'),
                    ],
                    [
                        'key' => 'effectiveTime',
                        'content' => [
                            [
                                'key' => 'low',
                                'attributes' => ['value' => fn($input) => H::strOrNull($input, 'date')],
                                'dataKey' => 'low',
                            ],
                            [
                                'key' => 'high',
                                'attributes' => ['value' => fn($input) => H::strOrNull($input, 'date')],
                                'dataKey' => 'high',
                            ],
                        ],
                        'dataKey' => 'date_time',
                        'required' => true,
                    ],
                    [
                        'key' => 'encounterParticipant',
                        'attributes' => ['typeCode' => 'ATND'],
                        'content' => [
                            [
                                'key' => 'assignedEntity',
                                'content' => [
                                    [
                                        'key' => 'id',
                                        'attributes' => ['root' => fn($input) => H::strOrNull($input, 'root')],
                                    ],
                                    FieldLevel::usRealmAddress(),
                                    FieldLevel::telecom(),
                                    [
                                        'key' => 'assignedPerson',
                                        'content' => FieldLevel::usRealmName(),
                                    ],
                                ],
                            ],
                        ],
                        'dataKey' => 'encounter_participant',
                        'existsWhen' => fn($input) => is_array($input) && is_array($input['name'] ?? null) && !empty($input['name']['last']),
                    ],
                ],
            ],
            'dataKey' => 'meta.ccda_header.component_of',
            'existsWhen' => H::notEmpty(...),
        ];
    }

    /**
     * Providers / Documentation Of
     * @return array<string, mixed>
     */
    public static function providers(): array
    {
        return [
            'key' => 'documentationOf',
            'attributes' => ['typeCode' => 'DOC'],
            'content' => [
                'key' => 'serviceEvent',
                'attributes' => ['classCode' => 'PCPR'],
                'content' => [
                    [
                        'key' => 'code',
                        'attributes' => LeafLevel::code(...),
                        'dataKey' => 'providers.code',
                        'existsWhen' => fn($input) => H::has($input, 'code'),
                    ],
                    [
                        'key' => 'effectiveTime',
                        'content' => [
                            [
                                'key' => 'low',
                                'attributes' => ['value' => fn($input) => H::strOrNull($input, 'date')],
                                'dataKey' => 'low',
                            ],
                            [
                                'key' => 'high',
                                'attributes' => ['value' => fn($input) => H::strOrNull($input, 'date')],
                                'dataKey' => 'high',
                            ],
                        ],
                        'dataKey' => 'providers.date_time',
                        'required' => true,
                    ],
                    self::provider(),
                ],
            ],
            'dataKey' => 'data.demographics',
            'existsWhen' => fn($input) => H::has($input, 'providers'),
        ];
    }

    /**
     * Individual provider performer
     * @return array<string, mixed>
     */
    public static function provider(): array
    {
        return [
            'key' => 'performer',
            'attributes' => ['typeCode' => 'PRF'],
            'content' => [
                [
                    'key' => 'functionCode',
                    'attributes' => [
                        'code' => 'PP',
                        'displayName' => 'Primary Performer',
                        'codeSystem' => '2.16.840.1.113883.12.443',
                        'codeSystemName' => 'Provider Role',
                    ],
                    'content' => [['key' => 'originalText', 'text' => fn() => 'Primary Care Provider']],
                    'existsWhen' => fn($input) => H::has($input, 'function_code'),
                ],
                [
                    'key' => 'assignedEntity',
                    'content' => [
                        [
                            'key' => 'id',
                            'attributes' => [
                                'root' => fn($input) => H::strOrNull($input, 'root'),
                                'extension' => fn($input) => H::strOrNull($input, 'extension'),
                            ],
                            'dataKey' => 'identity',
                        ],
                        [
                            'key' => 'code',
                            'attributes' => LeafLevel::code(...),
                            'content' => [['key' => 'originalText', 'text' => fn() => 'Care Team Member']],
                            'dataKey' => 'type',
                        ],
                        FieldLevel::usRealmAddress(),
                        FieldLevel::telecom(),
                        [
                            'key' => 'assignedPerson',
                            'content' => FieldLevel::usRealmName(),
                        ],
                    ],
                ],
            ],
            'dataKey' => 'providers.provider',
        ];
    }

    /**
     * Participant (related persons, etc.)
     * @return array<string, mixed>
     */
    public static function participant(): array
    {
        return [
            'key' => 'participant',
            'attributes' => [
                'typeCode' => fn($input) => H::str($input, 'typeCode', 'IND'),
            ],
            'content' => [
                FieldLevel::templateIdExt('2.16.840.1.113883.10.20.22.5.8', '2023-05-01'),
                [
                    'key' => 'time',
                    'content' => [
                        ['key' => 'low', 'attributes' => ['value' => fn($input) => H::strOrNull($input, 'date')], 'dataKey' => 'low'],
                        ['key' => 'high', 'attributes' => ['value' => fn($input) => H::strOrNull($input, 'date')], 'dataKey' => 'high'],
                    ],
                    'dataKey' => 'date_time',
                    'required' => true,
                ],
                FieldLevel::assignedEntity(),
            ],
            'dataKey' => 'meta.ccda_header.participants',
            'existsWhen' => H::notEmpty(...),
        ];
    }
}
