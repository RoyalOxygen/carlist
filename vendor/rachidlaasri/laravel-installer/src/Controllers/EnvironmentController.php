<?php

namespace RachidLaasri\LaravelInstaller\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use RachidLaasri\LaravelInstaller\Events\EnvironmentSaved;
use RachidLaasri\LaravelInstaller\Helpers\EnvironmentManager;
use Validator;

class EnvironmentController extends Controller
{
    /**
     * @var EnvironmentManager
     */
    protected $EnvironmentManager;

    /**
     * @param EnvironmentManager $environmentManager
     */
    public function __construct(EnvironmentManager $environmentManager)
    {
        $this->EnvironmentManager = $environmentManager;
    }

    /**
     * Display the Environment menu page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentMenu()
    {
        return redirect()->route('LaravelInstaller::environmentWizard');
    }

    /**
     * Display the Environment page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentWizard()
    {
        $envConfig = $this->EnvironmentManager->getEnvContent();

        return view('vendor.installer.environment-wizard', compact('envConfig'));
    }

    /**
     * Display the Environment page.
     *
     * @return \Illuminate\View\View
     */
    public function environmentClassic()
    {
        return redirect()->route('LaravelInstaller::environmentWizard');
    }

    /**
     * Processes the newly saved environment configuration (Classic).
     *
     * @param Request $input
     * @param Redirector $redirect
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveClassic(Request $input, Redirector $redirect)
    {
        return $redirect->route('LaravelInstaller::environmentWizard');
    }

    /**
     * Processes the newly saved environment configuration (Form Wizard).
     *
     * @param Request $request
     * @param Redirector $redirect
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveWizard(Request $request, Redirector $redirect)
    {
        $rules = config('installer.environment.form.rules');
        $messages = [
            'environment_custom.required_if' => trans('installer_messages.environment.wizard.form.name_required'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $redirect->route('LaravelInstaller::environmentWizard')->withInput()->withErrors($validator->errors());
        }

        if (! $this->checkDatabaseConnection($request)) {
            return $redirect->route('LaravelInstaller::environmentWizard')->withInput()->withErrors([
                'database_connection' => trans('installer_messages.environment.wizard.form.db_connection_failed'),
            ]);
        }

        $results = $this->EnvironmentManager->saveFileWizard($request);

        event(new EnvironmentSaved($request));

        if ($results !== trans('installer_messages.environment.success')) {
            return $redirect->route('LaravelInstaller::environmentWizard')->withInput()->withErrors([
                'database_connection' => trans('installer_messages.environment.errors'),
            ]);
        }

        try {
            $importError = $this->importDatabase(
                $request->input('database_hostname'),
                $request->input('database_username'),
                $request->input('database_password'),
                $request->input('database_name')
            );
        } catch (Exception $e) {
            $importError = 'Database import failed: ' . $e->getMessage();
        }

        if ($importError) {
            return $redirect->route('LaravelInstaller::environmentWizard')->withInput()->withErrors([
                'database_connection' => $importError,
            ]);
        }

        return redirect()->route('LaravelInstaller::final');
    }

    /**
     * Import the bundled SQL dump using a safe sequential importer.
     *
     * @param string $host
     * @param string $user
     * @param string|null $pass
     * @param string $name
     * @return string|null
     */
    private function importDatabase($host, $user, $pass, $name)
    {
        $sqlPath = database_path('installer/database.sql');

        if (!file_exists($sqlPath)) {
            return 'Database seed file was not found.';
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        @ini_set('mysqli.allow_local_infile', '0');
        mysqli_report(MYSQLI_REPORT_OFF);

        $pass = $this->normalizeDatabasePassword($pass);
        $connection = $this->connectMysql($host, $user, $pass, $name);

        if (!$connection) {
            return 'Could not connect to the database. Leave the password empty for XAMPP root if you have not set one.';
        }

        $this->prepareMysqlSession($connection);

        $templine = '';
        $handle = fopen($sqlPath, 'r');

        if ($handle === false) {
            mysqli_close($connection);
            return 'Unable to read the database seed file.';
        }

        while (($line = fgets($handle)) !== false) {
            $trimmed = ltrim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }

            $templine .= $line;

            if (substr(rtrim($line), -1) !== ';') {
                continue;
            }

            $statement = trim($templine);
            $templine = '';

            if ($this->shouldSkipImportStatement($statement)) {
                continue;
            }

            $result = $this->runImportQuery($connection, $statement, $host, $user, $pass, $name);
            $connection = $result['connection'];

            if ($result['error']) {
                fclose($handle);
                if ($connection) {
                    @mysqli_query($connection, 'SET FOREIGN_KEY_CHECKS=1');
                    @mysqli_close($connection);
                }

                return $result['error'];
            }
        }

        fclose($handle);

        if ($connection) {
            @mysqli_query($connection, 'SET FOREIGN_KEY_CHECKS=1');
            @mysqli_close($connection);
        }

        return null;
    }

    /**
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $name
     * @return \mysqli|false
     */
    private function connectMysql($host, $user, $pass, $name)
    {
        $connection = @mysqli_init();

        if (!$connection) {
            return false;
        }

        @mysqli_options($connection, MYSQLI_OPT_CONNECT_TIMEOUT, 15);
        @mysqli_options($connection, MYSQLI_SET_CHARSET_NAME, 'utf8mb4');

        if (!@mysqli_real_connect($connection, $host, $user, $pass, $name, 3306)) {
            return false;
        }

        return $connection;
    }

    /**
     * @param \mysqli $connection
     * @return void
     */
    private function prepareMysqlSession($connection)
    {
        @mysqli_set_charset($connection, 'utf8mb4');
        @mysqli_query($connection, 'SET SESSION sql_mode="NO_AUTO_VALUE_ON_ZERO"');
        @mysqli_query($connection, 'SET NAMES utf8mb4');
        @mysqli_query($connection, 'SET FOREIGN_KEY_CHECKS=0');
        @mysqli_query($connection, 'SET UNIQUE_CHECKS=0');
        @mysqli_query($connection, 'SET AUTOCOMMIT=1');
        @mysqli_query($connection, 'SET SESSION wait_timeout=600');
        @mysqli_query($connection, 'SET SESSION interactive_timeout=600');
        @mysqli_query($connection, 'SET SESSION net_read_timeout=600');
        @mysqli_query($connection, 'SET SESSION net_write_timeout=600');
        @mysqli_query($connection, 'SET SESSION max_allowed_packet=67108864');
    }

    /**
     * @param \mysqli|false $connection
     * @param string $sql
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $name
     * @return array{connection:\mysqli|false,error:?string}
     */
    private function runImportQuery($connection, $sql, $host, $user, $pass, $name)
    {
        if (!$connection) {
            return [
                'connection' => false,
                'error' => 'Database import failed: lost connection to MySQL.',
            ];
        }

        if (@mysqli_query($connection, $sql)) {
            return [
                'connection' => $connection,
                'error' => null,
            ];
        }

        $errno = mysqli_errno($connection);
        $error = mysqli_error($connection) ?: 'Unknown MySQL error';

        if (in_array($errno, [2006, 2013], true)) {
            if ($connection) {
                @mysqli_close($connection);
            }

            $connection = $this->connectMysql($host, $user, $pass, $name);

            if ($connection) {
                $this->prepareMysqlSession($connection);

                if (@mysqli_query($connection, $sql)) {
                    return [
                        'connection' => $connection,
                        'error' => null,
                    ];
                }

                $error = mysqli_error($connection) ?: $error;
            }
        }

        return [
            'connection' => $connection,
            'error' => 'Database import failed: ' . $error,
        ];
    }

    /**
     * @param string $statement
     * @return bool
     */
    private function shouldSkipImportStatement($statement)
    {
        $normalized = strtoupper(rtrim($statement, "; \t\n\r"));

        if ($normalized === '' || $normalized === 'BEGIN' || $normalized === 'START TRANSACTION' || $normalized === 'COMMIT' || $normalized === 'ROLLBACK') {
            return true;
        }

        return false;
    }

    /**
     * @param mixed $password
     * @return string
     */
    private function normalizeDatabasePassword($password)
    {
        if ($password === null) {
            return '';
        }

        $password = trim((string) $password);

        if ($password === '' || strcasecmp($password, 'null') === 0) {
            return '';
        }

        return $password;
    }

    /**
     * Validate database connection with user credentials (Form Wizard).
     *
     * @param Request $request
     * @return bool
     */
    private function checkDatabaseConnection(Request $request)
    {
        $connection = 'mysql';

        $settings = config("database.connections.$connection");

        config([
            'database' => [
                'default' => $connection,
                'connections' => [
                    $connection => array_merge($settings, [
                        'driver' => $connection,
                        'host' => $request->input('database_hostname'),
                        'port' => 3306,
                        'database' => $request->input('database_name'),
                        'username' => $request->input('database_username'),
                        'password' => $this->normalizeDatabasePassword($request->input('database_password')),
                    ]),
                ],
            ],
        ]);

        DB::purge();

        try {
            DB::connection()->getPdo();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
