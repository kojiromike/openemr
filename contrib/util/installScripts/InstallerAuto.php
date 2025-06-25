<?php

/**
 * This script is for automatic installation and configuration
 *   of OpenEMR.
 *
 * This script uses Symfony Console component to provide a more
 * structured and maintainable CLI interface for OpenEMR installation.
 *
 * Usage:
 *   php InstallerAuto.php [options]
 *
 *   Options:
 *     --iuser=VALUE            Initial user login name (default: admin)
 *     --iuname=VALUE           Initial user last name (default: Administrator)
 *     --iuserpass=VALUE        Initial user password (default: pass)
 *     --igroup=VALUE           Practice group name (default: Default)
 *     --server=VALUE           MySQL server (default: localhost)
 *     --loginhost=VALUE        PHP/Apache server (default: localhost)
 *     --port=VALUE             MySQL port (default: 3306)
 *     --root=VALUE             MySQL server root username (default: root)
 *     --rootpass=VALUE         MySQL server root password (default: empty)
 *     --login=VALUE            Username to MySQL openemr database (default: openemr)
 *     --pass=VALUE             Password to MySQL openemr database (default: openemr)
 *     --dbname=VALUE           MySQL openemr database name (default: openemr)
 *     --collate=VALUE          Collation for MySQL (default: utf8mb4_general_ci)
 *     --site=VALUE             Location of this instance in sites/ (default: default)
 *     --source_site_id=VALUE   Location of instance to clone and mirror
 *     --clone_database=VALUE   If set, will clone database from source_site_id
 *     --no_root_db_access=VALUE Use pre-created database and user credentials
 *     --development_translations=VALUE Use development translations
 *
 * Examples:
 *   1) Install using default configuration settings
 *      php InstallerAuto.php
 *   2) Provide root sql user password
 *      php InstallerAuto.php --rootpass=howdy
 *   3) Provide root and openemr sql user passwords
 *      php InstallerAuto.php --rootpass=howdy --pass=hey
 *   4) Provide sql user settings and openemr user settings
 *      php InstallerAuto.php --rootpass=howdy --login=openemr2 --pass=hey --dbname=openemr2 --iuser=tom --iuname=Miller --iuserpass=heynow
 *   5) Create multi-site
 *      a. First create first installation
 *         php InstallerAuto.php
 *      b. Create duplicate of 'default' site (no database clone)
 *         php InstallerAuto.php --login=openemr2 --pass=openemr2 --dbname=openemr2 --site=default2 --source_site_id=default
 *      c. Create duplicate of 'default' site with database clone
 *         php InstallerAuto.php --login=openemr2 --pass=openemr2 --dbname=openemr2 --site=default2 --source_site_id=default --clone_database=yes
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

// Include standard libraries/classes
require_once __DIR__ . '/../../../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to install and configure OpenEMR
 */
class OpenEMRInstallCommand extends Command
{
    protected static $defaultName = 'openemr:install';
    protected static $defaultDescription = 'Install and configure OpenEMR';

    /**
     * Configure the command options
     */
    protected function configure()
    {
        $this
            ->setDescription('Automatically install and configure OpenEMR')
            ->setHelp('This command allows you to install and configure OpenEMR without using the web interface.')
            ->addOption('iuser', null, InputOption::VALUE_REQUIRED, 'Initial user login name', 'admin')
            ->addOption('iuname', null, InputOption::VALUE_REQUIRED, 'Initial user last name', 'Administrator')
            ->addOption('iuserpass', null, InputOption::VALUE_REQUIRED, 'Initial user password', 'pass')
            ->addOption('igroup', null, InputOption::VALUE_REQUIRED, 'Practice group name', 'Default')
            ->addOption('server', null, InputOption::VALUE_REQUIRED, 'MySQL server', 'localhost')
            ->addOption('loginhost', null, InputOption::VALUE_REQUIRED, 'php/apache server', 'localhost')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'MySQL port', '3306')
            ->addOption('root', null, InputOption::VALUE_REQUIRED, 'MySQL server root username', 'root')
            ->addOption('rootpass', null, InputOption::VALUE_REQUIRED, 'MySQL server root password', '')
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'Username to MySQL openemr database', 'openemr')
            ->addOption('pass', null, InputOption::VALUE_REQUIRED, 'Password to MySQL openemr database', 'openemr')
            ->addOption('dbname', null, InputOption::VALUE_REQUIRED, 'MySQL openemr database name', 'openemr')
            ->addOption('collate', null, InputOption::VALUE_REQUIRED, 'Collation for mysql', 'utf8mb4_general_ci')
            ->addOption('site', null, InputOption::VALUE_REQUIRED, 'Location of this instance in sites/', 'default')
            ->addOption('source_site_id', null, InputOption::VALUE_REQUIRED, 'Location of instance to clone and mirror', '')
            ->addOption('clone_database', null, InputOption::VALUE_REQUIRED, 'If set, will clone database from source_site_id', '')
            ->addOption('no_root_db_access', null, InputOption::VALUE_REQUIRED, 'Use pre-created database and user', '')
            ->addOption('development_translations', null, InputOption::VALUE_REQUIRED, 'Use development translations', '');
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create a SymfonyStyle object for nicer rendering
        $io = new SymfonyStyle($input, $output);
        $io->title('OpenEMR Installation');

        // Set up the installation settings from command options
        $installSettings = [
            'iuser' => $input->getOption('iuser'),
            'iuname' => $input->getOption('iuname'),
            'iuserpass' => $input->getOption('iuserpass'),
            'igroup' => $input->getOption('igroup'),
            'server' => $input->getOption('server'),
            'loginhost' => $input->getOption('loginhost'),
            'port' => $input->getOption('port'),
            'root' => $input->getOption('root'),
            'rootpass' => $input->getOption('rootpass'),
            'login' => $input->getOption('login'),
            'pass' => $input->getOption('pass'),
            'dbname' => $input->getOption('dbname'),
            'collate' => $input->getOption('collate'),
            'site' => $input->getOption('site'),
            'source_site_id' => $input->getOption('source_site_id'),
            'clone_database' => $input->getOption('clone_database'),
            'no_root_db_access' => $input->getOption('no_root_db_access'),
            'development_translations' => $input->getOption('development_translations'),
        ];

        // Display installation settings if verbose
        if ($output->isVerbose()) {
            $io->section('Installation Settings');
            $io->table(
                ['Setting', 'Value'],
                array_map(function ($k, $v) {
                    return [$k, $v ?: '(empty)'];
                }, array_keys($installSettings), array_values($installSettings))
            );
        }

        try {
            // Install and configure OpenEMR using the Installer class
            $installer = new Installer($installSettings);

            if (!$installer->quick_install()) {
                $io->error('Installation failed: ' . $installer->error_message);
                return Command::FAILURE;
            }

            $io->success($installer->debug_message);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Installation failed with exception: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

// Create and configure the application
$application = new Application('OpenEMR Installer', '1.0.0');
$command = new OpenEMRInstallCommand();
$application->add($command);
$application->setDefaultCommand(OpenEMRInstallCommand::getDefaultName(), true);
$application->run();
