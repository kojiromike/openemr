<?php

/**
 * OpenEMR Installation Command
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (C) 2010-2019 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Common\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'openemr:install',
    description: 'Install and configure OpenEMR automatically'
)]
class InstallCommand extends Command
{
    protected function configure(): void
    {
        $this
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
            // Include the Installer class (needs to be done here since we skipped globals.php)
            require_once __DIR__ . '/../../../library/classes/Installer.class.php';
            
            // Install and configure OpenEMR using the Installer class
            $installer = new \Installer($installSettings);

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