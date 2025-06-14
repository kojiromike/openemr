<?php

declare(strict_types=1);

/**
 * This script is for automatic installation and configuration
 *   of OpenEMR.
 *
 * This script is meant to be run as php command line (php-cli),
 *   and needs to be first activated by removing the 'exit' line
 *   at top (via sed command).
 *
 * To activate script, need to comment out the exit command at top
 *   of script.
 *
 * Command ( Note that the ordering and number of custom settings
 *           that can be sent is flexible ):
 *     php -f iuser=[iuser] iuname=[iuname] iuserpass=[iuserpass] igroup=[igroup]
 *       server=[server] loginhost=[loginhost] port=[port] root=[root] rootpass=[rootpass]
 *       login=[login] pass=[pass] dbname=[dbname] collate=[collate] site=[site]
 *       source_site_id=[source_site_id] clone_database=[clone_database]
 *
 *   Description of settings (default value in parenthesis):
 *     iuser      -> initial user login name (admin)
 *     iuname     -> initial user last name (Administrator)
 *     iuserpass  -> initial user password (pass)
 *     igroup     -> practice group name (Default)
 *     server     -> mysql server (localhost)
 *     loginhost  -> php/apache server (localhost)
 *     port       -> MySQL port (3306)
 *     root       -> MySQL server root username (root)
 *     rootpass   -> MySQL server root password ()
 *     login      -> username to MySQL openemr database (openemr)
 *     pass       -> password to MySQL openemr database (openemr)
 *     dbname     -> MySQL openemr database name (openemr)
 *     collate    -> collation for mysql (utf8_general_ci)
 *     site       -> location of this instance in sites/ (default)
 *     source_site_id -> location of instance to clone and mirror ()
 *                         Advanced option of multi site module to allow cloning/mirroring of another local site.
 *     clone_database -> if set to anything, then will clone database from source_site_id ()
 *                         Advanced option of multi site module to allow cloning/mirroring of another local database.
 *     no_root_db_access -> if set to anything, will use pre-created and pre-configured login/pass/dbname and
 *                             will disable cloning / migration since that generally requires root access to the db
 *     development_translations -> If set to anything, will then download and use the development set (updated daily)
 *                                   of translations (indirectly) from the github repository.
 *
 *     Examples of use:
 *     1) Install using default configuration settings
 *          php -f InstallerAuto.php
 *     2) Provide root sql user password for installation
 *        (otherwise use default configuration settings)
 *          php -f InstallerAuto.php rootpass=howdy
 *     3) Provide root sql user password and openemr sql user password
 *        (otherwise use default configuration settings)
 *          php -f InstallerAuto.php rootpass=howdy pass=hey
 *     4) Provide sql user settings and openemr user settings
 *        (otherwise use default configuration settings)
 *          php -f InstallerAuto.php rootpass=howdy login=openemr2 pass=hey dbname=openemr2 iuser=tom iuname=Miller iuserpass=heynow
 *     5) Create mutli-site (note this is very advanced usage)
 *          a. First create first installation
 *            php -f InstallerAuto.php
 *          b. Can create an installation that duplicates 'default' site but not the database
 *            php -f InstallerAuto.php login=openemr2 pass=openemr2 dbname=openemr2 site=default2 source_site_id=default
 *          c. Or can create an installation that duplicates 'default' site and database
 *             php -f InstallerAuto.php login=openemr2 pass=openemr2 dbname=openemr2 site=default2 source_site_id=default clone_database=yes
 *          d. Can continue installing new instances as needed ...
 *             php -f InstallerAuto.php login=openemr3 pass=openemr3 dbname=openemr3 site=default3 source_site_id=default clone_database=yes
 *     6) Provide pre-created database and restricted privilege user access credentials - example from Planettel.com.sg Proxmox OpenVZ Template
 *        (otherwise use default configuration settings - do not use for cloning / migration)
 *          php -f /var/www/openemr/contrib/util/installScripts/InstallerAuto.php no_root_db_access=1 iuserpass=oemr123 login=oemrusr pass=${UPASSWD} > /dev/null 2>&1
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (C) 2010-2019 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

// This exit is to avoid malicious use of this script.
exit;
