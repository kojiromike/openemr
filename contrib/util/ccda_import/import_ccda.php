<?php

declare(strict_types=1);

/**
 * Import CCDA script
 *
 * Prior to use:
 *   1. Place ccdas in a directory.
 *   2. Uncomment exit at top of this script.
 *
 * Use:
 *   1. See below help function for command usage.
 *   2. Note that development-mode will markedly improve performance by bypassing the import of
 *      the ccda document and bypassing the use of the audit_master and audit_details tables and
 *      will directly import the new patient data from the ccda. This will also turn off the audit log during
 *      the import.
 *   3, NOTE THAT THIS SCRIPT IS NOT WORKING AT THIS TIME IF THE DEVELOPMENT MODE IS TURNED OFF
 *   4. Note that a log.txt file is created with log/stats of the run.
 *
 * Description of what this script automates (for unlimited number of ccda documents):
 *  1. import ccda document (bypassed in development-mode)
 *  2. import to ccda table (bypassed in development-mode)
 *  3. import as new patient
 *  4. run function to populate all the uuids via the universal service function that already exists
 *  5. (optional via enableMoves) move files after being processed to the <openemrPath>/contrib/import_ccdas/processed
 *                                directory
 *  6. (optional via dedup) check for a patient duplicate before importing (if it is a duplicate and enableMoves is
 *                          true, then will not import patient and will move the file to the
 *                          <openemrPath>/contrib/import_ccdas/duplicates directory and log the duplicate information)
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2021-2025 Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2025 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

// comment this out when using this script (and then uncomment it again when done using script)
exit;
