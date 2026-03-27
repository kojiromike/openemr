<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type array\\{category\\: string\\|null, subcategory\\: string\\|null, item\\: string\\|null, content\\: string\\|null, date\\: string\\|null\\} is not subtype of type array\\{id\\: int, item\\: string\\|null, content\\: string\\|null, subcategory_id\\: int\\}\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../interface/forms/CAMOS/new.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type array\\{code\\: string\\|null, code_type\\: string\\|null, code_text\\: string\\|null, modifier\\: string\\|null, units\\: string\\|null, fee\\: string\\|null\\} is not subtype of type array\\{id\\: int, item\\: string\\|null, content\\: string\\|null, subcategory_id\\: int\\}\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../interface/forms/CAMOS/new.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type array\\{code_type\\: string\\|null, code\\: string\\|null, code_text\\: string\\|null, modifier\\: string\\|null, units\\: string\\|null, fee\\: string\\|null, justify\\: string\\|null\\} is not subtype of type array\\{weight\\: string\\|null, height\\: string\\|null, bps\\: string\\|null, bpd\\: string\\|null, pulse\\: string\\|null, temperature\\: string\\|null\\}\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../interface/forms/CAMOS/new.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type array\\{id\\: int, category\\: string\\|null, subcategory\\: string\\|null, item\\: string\\|null, content\\: string\\|null\\} is not subtype of type array\\{id\\: int, item\\: string\\|null, content\\: string\\|null, subcategory_id\\: int\\}\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../interface/forms/CAMOS/new.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type array\\{id\\: int, item\\: string\\|null, content\\: string\\|null, subcategory_id\\: int\\} is not subtype of type array\\{id\\: int, subcategory\\: string\\|null, category_id\\: int\\}\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../interface/forms/CAMOS/new.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type array\\{id\\: int, subcategory\\: string\\|null, category_id\\: int\\} is not subtype of type array\\{id\\: int, category\\: string\\|null\\}\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../interface/forms/CAMOS/new.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type array\\{weight\\: string\\|null, height\\: string\\|null, bps\\: string\\|null, bpd\\: string\\|null, pulse\\: string\\|null, temperature\\: string\\|null\\} is not subtype of type array\\{category\\: string\\|null, subcategory\\: string\\|null, item\\: string\\|null, content\\: string\\|null, date\\: string\\|null\\}\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../interface/forms/CAMOS/new.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type mixed is not subtype of type array\\<mixed\\>\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../src/Common/Database/QueryUtils.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type int is not subtype of type string\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../src/Services/AddressService.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type OpenEMR\\\\Services\\\\Search\\\\TokenSearchField is not subtype of type OpenEMR\\\\Services\\\\FHIR\\\\Observation\\\\ISearchField\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../src/Services/FHIR/Observation/FhirObservationAdvanceDirectiveService.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type OpenEMR\\\\Services\\\\Search\\\\TokenSearchField is not subtype of type OpenEMR\\\\Services\\\\FHIR\\\\Observation\\\\ISearchField\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../src/Services/FHIR/Observation/FhirObservationSocialHistoryService.php',
];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var with type array\\{next_pid\\: int\\|string\\}\\|null is not subtype of type array\\<mixed\\>\\|false\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../tests/Tests/Services/DuplicatePatientDetectionTest.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
