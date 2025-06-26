<?php

/**
 * Legacy wrapper for the OpenEMR install command - redirects to bin/console
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (C) 2010-2019 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

// Check if script is being run from command line
if (php_sapi_name() !== 'cli') {
    throw new RuntimeException('This script can only be run from the command line.');
}

echo "This script has been moved to the main console application.\n";
echo "Please use: bin/console openemr:install " . implode(' ', array_slice($argv, 1)) . "\n";
exit(1);