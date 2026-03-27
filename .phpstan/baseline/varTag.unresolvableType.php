<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^PHPDoc tag @var for variable \\$result contains unresolvable type\\.$#',
    'count' => 1,
    'path' => __DIR__ . '/../../interface/forms/CAMOS/new.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
