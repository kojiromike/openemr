<?php

declare(strict_types=1);

namespace Comlink\OpenEMR\Modules\TeleHealthModule\Util;

class TelehealthAuthUtils
{
    public static function getFormattedPassword($password): string
    {
        return hash('sha256', $password);
    }
}
