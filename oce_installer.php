#!/usr/bin/env php
<?php
/**
 * Collects the settings required to run the OpenEMR Installer, either from the environment or from CLI arguments.
 * Then runs the `quick_install` method. Environment variable names are all-caps and start with `OPENEMR_`.
 *
 * Here are all the possible settings:
 * iuser ?: ''                      // initial user (admin)
 * iuserpass ?: '';                 // initial user password
 * iuname ?: '';                    // initial user display name
 * iufname ?: '';                   // initial user facility name
 * igroup ?: '';                    // initial user group
 * i2faenable ?: '';                // 2FA enable
 * i2fasecret ?: '';                // 2FA TOTP
 * loginhost ?: '';                 // httpd host
 * server ?: '';                    // mysql host
 * port ?: '';                      // mysql port
 * root ?: '';                      // mysql root user
 * rootpass ?: '';                  // mysql root password
 * login ?: '';                     // mysql user
 * pass ?: '';                      // mysql user password
 * dbname ?: '';                    // mysql database name
 * collate ?: '';                   // mysql database collation
 * site ?: 'default';               // initial openemr site
 * source_site_id ?: '';            // source site id for cloning
 * clone_database ?: '';            // clone database
 * no_root_db_access ?: '';         // disable root access to database. user/privileges pre-configured
 * development_translations ?: '';  // use online translations
 * new_theme ?: '';                 // set a new theme for cloned sites
 */

require_once(dirname(__FILE__) . '/vendor/autoload.php');

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * OpenEMR Installer Command
 */
class InstallCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install OpenEMR')
            ->setHelp('This command allows you to install OpenEMR')
            ->addOption('non_interactive', null, InputOption::VALUE_NONE, 'Run without asking for confirmation [env: OPENEMR_NON_INTERACTIVE]')
            ->addOption('iuser', null, InputOption::VALUE_OPTIONAL, 'Initial user (admin) [env: OPENEMR_IUSER]')
            ->addOption('iuserpass', null, InputOption::VALUE_OPTIONAL, 'Initial user password [env: OPENEMR_IUSERPASS]')
            ->addOption('iuname', null, InputOption::VALUE_OPTIONAL, 'Initial user display name [env: OPENEMR_IUNAME]')
            ->addOption('iufname', null, InputOption::VALUE_OPTIONAL, 'Initial user facility name [env: OPENEMR_IUFNAME]')
            ->addOption('igroup', null, InputOption::VALUE_OPTIONAL, 'Initial user group [env: OPENEMR_IGROUP]')
            ->addOption('i2faenable', null, InputOption::VALUE_OPTIONAL, '2FA enable [env: OPENEMR_I2FAENABLE]')
            ->addOption('i2fasecret', null, InputOption::VALUE_OPTIONAL, '2FA TOTP [env: OPENEMR_I2FASECRET]')
            ->addOption('loginhost', null, InputOption::VALUE_OPTIONAL, 'HTTP/Apache server (usually localhost) [env: OPENEMR_LOGINHOST]')
            ->addOption('server', null, InputOption::VALUE_OPTIONAL, 'MySQL server (usually localhost) [env: OPENEMR_SERVER]')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'MySQL port [env: OPENEMR_PORT]')
            ->addOption('root', null, InputOption::VALUE_OPTIONAL, 'MySQL root user [env: OPENEMR_ROOT]')
            ->addOption('rootpass', null, InputOption::VALUE_OPTIONAL, 'MySQL root password [env: OPENEMR_ROOTPASS]')
            ->addOption('login', null, InputOption::VALUE_OPTIONAL, 'MySQL user [env: OPENEMR_LOGIN]')
            ->addOption('pass', null, InputOption::VALUE_OPTIONAL, 'MySQL user password [env: OPENEMR_PASS]')
            ->addOption('dbname', null, InputOption::VALUE_OPTIONAL, 'MySQL database name [env: OPENEMR_DBNAME]')
            ->addOption('collate', null, InputOption::VALUE_OPTIONAL, 'MySQL database collation [env: OPENEMR_COLLATE]')
            ->addOption('site', null, InputOption::VALUE_OPTIONAL, 'Initial OpenEMR site [env: OPENEMR_SITE]', 'default')
            ->addOption('source_site_id', null, InputOption::VALUE_OPTIONAL, 'Source site id for cloning [env: OPENEMR_SOURCE_SITE_ID]')
            ->addOption('clone_database', null, InputOption::VALUE_OPTIONAL, 'Clone database [env: OPENEMR_CLONE_DATABASE]')
            ->addOption('no_root_db_access', null, InputOption::VALUE_OPTIONAL, 'Disable root access to database [env: OPENEMR_NO_ROOT_DB_ACCESS]')
            ->addOption('development_translations', null, InputOption::VALUE_OPTIONAL, 'Use online translations [env: OPENEMR_DEVELOPMENT_TRANSLATIONS]')
            ->addOption('new_theme', null, InputOption::VALUE_OPTIONAL, 'Set theme for cloned sites [env: OPENEMR_NEW_THEME]');
    }

    /**
     * Get value from environment or input options
     */
    private function getOption(InputInterface $input, string $name, string $default = '')
    {
        $envName = 'OPENEMR_' . strtoupper($name);
        // Check for command line option first
        if ($input->getOption($name) !== null) return $input->getOption($name);
        // Check environment variable next
        if (isset($_ENV[$envName])) return $_ENV[$envName];
        // Fall back to default
        return $default;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('OpenEMR Installer');

        if ($this->getOption($input, 'non_interactive')) $input->setInteractive(false);

        // Get all option names except 'non_interactive'
        $optionNames = array_keys(array_filter(
            $input->getOptions(),
            function ($key) {
                return $key !== 'non_interactive';
            },
            ARRAY_FILTER_USE_KEY
        ));

        // Create installation parameters array
        $installParams = array_combine(
            $optionNames,
            array_map(function($option) use ($input) {
                // Special case for 'site' with default value 'default'
                return $option === 'site' ?
                    $this->getOption($input, $option, 'default') :
                    $this->getOption($input, $option);
            }, $optionNames)
        );

        // Display installation parameters (mask passwords)
        $io->section('Installation Parameters');

        $displayParams = [];
        foreach ($installParams as $key => $value) {
            if (str_ends_with($key, 'pass') || str_ends_with($key, 'secret')) $value = '********';
            $value = $value ?: '(not set)';
            // Mask password values
            $displayParams[] = [
                $key,
                (str_ends_with($key, 'pass') || str_ends_with($key, 'secret')) ?
                    '********' :
                    ($value ?: '(not set)')
            ];
        }

        $table = new Table($output);
        $table->setHeaders(['Parameter', 'Value']);
        $table->setRows($displayParams);
        $table->render();

        // Confirm installation
        if ($input->isInteractive()) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with installation? [y/N] ', false);

            if (!$helper->ask($input, $output, $question)) {
                $io->warning('Installation canceled.');
                return Command::FAILURE;
            }
        }

        try {
            $io->section('Installing OpenEMR');
            $io->progressStart(3);

            // Create installer
            $installer = new Installer($installParams);
            $io->progressAdvance();

            // Run the installation
            $io->progressAdvance();
            $success = $installer->quick_install();
            $io->progressFinish();

            if ($success) {
                $io->success('OpenEMR installation completed successfully.');
                return Command::SUCCESS;
            } else {
                $io->error('Installation failed: ' . $installer->error_message);
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('Installation failed with exception: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

// Run the command
$application = new Application('OpenEMR Installer', '1.0.0');
$application->add(new InstallCommand());
$application->setDefaultCommand('install', true);
$application->run();
