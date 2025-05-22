#!/usr/bin/env php
<?php
/**
 * An installer for OpenCoreEMR's process. This installer skips several setup steps that would be done by the
 * upstream Installer class (because they should not be done with application-level permissions).
 * Also, it does not support multiple sites.
 *
 * Before you run this:
 *
 * 1. Initialize the database by loading at least sql/database.sql and create a non-root user with access to it.
 * 2. Ensure that sites/default/sqlconf.php accepts environment variables to connect to the database and that
 *    those environment variables are in the environment when you run this installer.
 * 3. Provide these positional arguments:
 *    iuser                            // initial user (admin)
 *    iuserpass                        // initial user password
 *    iuname                           // initial user display name
 *    iufname                          // initial user facility name
 *    igroup                           // initial user group
 *    loginhost                        // host from which the database connection is made
 *
 * @package OpenEMR
 * @link    https://www.opencoreemr.com/
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

const WORKDIR = __DIR__ . '/../../..';
const SITEDIR = WORKDIR . '/sites/default';
require_once(WORKDIR . '/vendor/autoload.php');
require_once(WORKDIR . '/library/classes/Installer.class.php');

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * OpenEMR Installer Command
 */
class InstallCommand extends Command
{
    protected function configure()
    {
        $helpText = <<<EOT
        This command allows you to install OpenEMR. It does not support multiple sites.
        It expects the the MySQL database to be running and accessible
        via the connection specified in the environment variables used in sqlconf.php.
        EOT;
        $this
            ->setName('install')
            ->setDescription('Install OpenEMR')
            ->setHelp($helpText)
            ->addArgument('iuser', InputArgument::REQUIRED, 'Initial user (admin)')
            ->addArgument('iuserpass', InputArgument::REQUIRED, 'Initial user password')
            ->addArgument('iuname', InputArgument::REQUIRED, 'Initial user display name')
            ->addArgument('iufname', InputArgument::REQUIRED, 'Initial user facility name')
            ->addArgument('igroup', InputArgument::REQUIRED, 'Initial user group')
            ->addArgument('loginhost', InputArgument::REQUIRED, 'HTTP/Apache server (usually localhost)');
    }

    function add_initial_user()
    {
        $hash = password_hash($this->installer->iuserpass, PASSWORD_DEFAULT);
        if (empty($hash)) {
            // Something is seriously wrong
            error_log('OpenEMR Error : OpenEMR is not working because unable to create a hash.');
            die("OpenEMR Error : OpenEMR is not working because unable to create a hash.");
        }

        $query = "SELECT 1 FROM groups WHERE id = 1 LIMIT 1";
        $result = $this->installer->dbh->query($query);
        if ($result === false) {
            $this->installer->error_message = "ERROR. Unable to check for initial group\n" .
            "<p>" . $this->installer->dbh->error . " (#" . $this->installer->dbh->errno . ")\n";
            return false;
        }
        if ($result->num_rows === 0) {
            $queryAddGroup = $this->installer->dbh->prepare('INSERT INTO groups (id, name, user) VALUES (1, ?, ?)');
            $queryAddGroup->bind_param('ss', $this->installer->igroup, $this->installer->iuser);
            if ($queryAddGroup->execute() === false) {
                $this->installer->error_message = "ERROR. Unable to addb initial user group\n" .
                "<p>" . $this->installer->dbh->error . " (#" . $this->installer->dbh->errno . ")\n";
                return false;
            }
        }

        $query = "SELECT 1 FROM users WHERE id = 1 LIMIT 1";
        $result = $this->installer->dbh->query($query);
        if ($result === false) {
            $this->installer->error_message = "ERROR. Unable to check for initial user\n" .
            "<p>" . $this->installer->dbh->error . " (#" . $this->installer->dbh->errno . ")\n";
            return false;
        }

        // Only insert if the user doesn't exist
        if ($result->num_rows == 0) {
            $queryAddUser = $this->installer->dbh->prepare('INSERT INTO users (id, username, password, authorized, lname, fname, facility_id, calendar, cal_ui) VALUES (1, ?, ?, 1, ?, ?, 3, 1, 3)');
            $queryAddUser->bind_param('sssss', $this->installer->iuser, $this->installer->iuserpass, $this->installer->iuname, $this->installer->iufname);
            if ($queryAddUser->execute() === false) {
                $this->installer->error_message = "ERROR. Unable to add initial user\n" .
                "<p>" . $this->installer->dbh->error . " (#" . $this->installer->dbh->errno . ")\n";
                return false;
            }
        }

        $query = "SELECT 1 FROM users_secure WHERE id = 1 LIMIT 1";
        $result = $this->installer->dbh->query($query);
        if ($result === false) {
            $this->installer->error_message = "ERROR. Unable to check for initial user login credentials\n" .
            "<p>" . $this->installer->dbh->error . " (#" . $this->installer->dbh->errno . ")\n";
            return false;
        }

        // Only insert if the credentials don't exist
        if ($result->num_rows == 0) {
            $queryAddCredentials = $this->installer->dbh->prepare('INSERT INTO users_secure (id, username, password, last_update_password) VALUES (1, ?, ?, NOW())');
            $queryAddCredentials->bind_param('ss', $this->installer->iuser, $hash);
            if ($queryAddCredentials->execute() === false) {
                $this->installer->error_message = "ERROR. Unable to add initial user login credentials\n" .
                "<p>" . $this->installer->dbh->error . " (#" . $this->installer->dbh->errno . ")\n";
                return false;
            }
        }

        // Create new 2fa if enabled
        if (!$this->installer->i2faEnable) return true;
        if (empty($this->installer->i2faSecret)) {
            $this->installer->error_message = "ERROR. 2FA is enabled, but totp secret is empty\n";
            return false;
        }
        if (!class_exists('Totp')) {
            $this->installer->error_message = "ERROR. 2FA is enabled, but totp class is not found\n";
            return false;
        }
        if (!class_exists('OpenEMR\Common\Crypto\CryptoGen')) {
            $this->installer->error_message = "ERROR. 2FA is enabled, but CryptoGen class is not found\n";
            return false;
        }
        $query = "SELECT 1 FROM login_mfa_registrations WHERE user_id = 1 LIMIT 1";
        $result = $this->installer->dbh->query($query);
        if ($result === false) {
            $this->installer->error_message = "ERROR. 2FA is enabled, but unable to check for initial user's 2FA credentials\n" .
                "<p>" . $this->installer->dbh->error . " (#" . $this->installer->dbh->errno . ")\n";
            return false;
        }

        // Only insert if the MFA registration doesn't exist
        if ($result->num_rows == 0) {
            // Encrypt the new secret with the hashed password
            $cryptoGen = new OpenEMR\Common\Crypto\CryptoGen();
            $secret = $cryptoGen->encryptStandard($this->installer->i2faSecret, $hash);
            $queryAdd2fa = $this->installer->dbh->prepare('INSERT INTO login_mfa_registrations (user_id, name, method, var1, var2) VALUES (1, ?, ?, ?, ?)');
            $queryAdd2fa->bind_param('ssss', $this->installer->i2faName, $this->installer->i2faMethod, $secret, $this->installer->i2faVar2);
            if ($queryAdd2fa->execute() === false) {
                $this->installer->error_message = "ERROR. Unable to add initial user's 2FA credentials\n" .
                    "<p>" . $this->installer->dbh->error . " (#" . $this->installer->dbh->errno . ")\n";
                return false;
            }
        }

        return true;
    }

    /**
     * Run the simplified OpenCoreEMR installation process.
     *
     * This method is modeled after the Installer class's quick_install method, but does less.
     */
    public function doInstall()
    {
        # The sequence of Installer functions to call.
        # These are all thunks that return a boolean.
        $install_dag = [
            'login_is_valid',
            'iuser_is_valid',
            'user_password_is_valid',
            'user_database_connection',
            'add_version_info',
            'insert_globals',
        ];
        foreach ($install_dag as $step) {
            if (!$this->installer->$step()) return false;
        }
        $this->add_initial_user();
        $continue_dag = [
            'install_gacl',
            'install_additional_users',
            'on_care_coordination',
        ];
        foreach ($continue_dag as $step) {
            if (!$this->installer->$step()) return false;
        }
        return true;
    }

    public function printPhpDeets()
    {
        $this->io->text("PHP Version: " . PHP_VERSION);
        $this->io->text("PHP SAPI: " . php_sapi_name());
        $this->io->text("PHP OS: " . PHP_OS);
        $this->io->text("PHP Memory Limit: " . ini_get('memory_limit'));
        $this->io->text("PHP Max Execution Time: " . ini_get('max_execution_time'));
    }

    /**
     * Restructure the CLI arguments into what the Installer class expects.
     */
    protected function collectInstallParameters() {
        require_once(WORKDIR . '/sites/default/sqlconf.php');
        $this->io->text('Collecting installation parameters...');
        // $arguments key is the argument name, value is the InputArgument object
        $arguments = $this->input->getArguments();
        $installParams = [
            'server' => $sqlconf['host'],
            'port' => $sqlconf['port'],
            'login' => $sqlconf['login'],
            'pass' => $sqlconf['pass'],
            'dbname' => $sqlconf['dbase'],
        ] + array_combine(
            array_keys($arguments),
            array_map([$this->input, 'getArgument'], array_keys($arguments))
        );
        return $installParams;
    }

    /**
     * Create a display representation of the install parameters, masking sensitive values.
     */
    protected function maskSensitiveParameters(array $installParams)
    {
        $displayParams = [];
        foreach ($installParams as $key => $value) {
            if (str_ends_with($key, 'pass') || str_ends_with($key, 'secret')) $value = '********';
            $value = $value ?: '(not set)';
            $displayParams[] = [$key, $value];
        }
        return $displayParams;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('OpenEMR Installer');
        $this->io->text('Starting installation process at ' . date('Y-m-d H:i:s'));

        // Display installation parameters (mask passwords)
        $installParams = $this->collectInstallParameters();

        $this->io->section('Installation Parameters');
        $displayParams = $this->maskSensitiveParameters($installParams);
        $table = new Table($output);
        $table->setHeaders(['Parameter', 'Value']);
        $table->setRows($displayParams);
        $table->render();

        $this->io->section('Installing OpenEMR');
        $this->io->text('Installation started at ' . date('Y-m-d H:i:s'));
        $this->io->progressStart(3);

        try {
            // Create installer
            $this->io->text('Step 1: Creating installer instance...');
            $startTime = microtime(true);

            $this->installer = new Installer($installParams);

            $endTime = microtime(true);
            $this->io->text(sprintf('Installer created in %.2f seconds', $endTime - $startTime));
            $this->io->progressAdvance();

            // Run the installation
            $this->io->text('Step 2: Running installer...');
            $startTime = microtime(true);
            $this->io->progressAdvance();
            $success = $this->doInstall();

            $endTime = microtime(true);
            $this->io->text(sprintf('Installation process took %.2f seconds', $endTime - $startTime));
            $this->io->progressFinish();

            return ($success) ? $this->handleSuccess() : $this->handleFailure();
        } catch (\Exception $e) {
            $this->io->error('Installation failed with exception: ' . $e->getMessage());
            $this->io->text('Exception details: ' . get_class($e));
            $this->io->text('Stack trace:');
            $this->io->text($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    protected function handleSuccess()
    {
        $this->io->success('OpenEMR installation completed successfully at ' . date('Y-m-d H:i:s'));
        $this->io->text('Installation directory: ' . getcwd());
        $this->io->text('Validating installation');

        // Check if sites directory exists and has expected contents
        if (is_dir(SITEDIR)) {
            $this->io->text('Sites directory found: ' . SITEDIR);
        } else {
            $this->io->warning('Sites directory not found at expected location');
        }
        return Command::SUCCESS;
    }

    protected function handleFailure()
    {
        $this->io->error('Installation failed: ' . $this->installer->error_message);
        if (isset($this->installer->debug_info)) {
            $this->io->section('Debug Information');
            foreach ($this->installer->debug_info as $key => $value) {
                $this->io->text("$key: " . print_r($value, true));
            }
        }
        return Command::FAILURE;
    }
}

// Run the command
$application = new Application('OpenEMR Installer', '1.0.0');
$application->add(new InstallCommand());
$application->setDefaultCommand('install', true);

$application->run();
