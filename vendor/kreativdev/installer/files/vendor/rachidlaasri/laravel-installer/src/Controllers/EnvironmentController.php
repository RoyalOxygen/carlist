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

        $importError = $this->importDatabase(
            $request->input('database_hostname'),
            $request->input('database_username'),
            $request->input('database_password'),
            $request->input('database_name')
        );

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

        $connection = @mysqli_connect($host, $user, (string) $pass, $name);

        if (!$connection) {
            return 'Could not connect to the database.';
        }

        mysqli_set_charset($connection, 'utf8mb4');
        mysqli_query($connection, 'SET FOREIGN_KEY_CHECKS=0');

        $templine = '';
        $handle = fopen($sqlPath, 'r');

        if ($handle === false) {
            mysqli_close($connection);
            return 'Unable to read the database seed file.';
        }

        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }

            $templine .= $line;

            if (substr(rtrim($line), -1) === ';') {
                if (!mysqli_query($connection, $templine)) {
                    $error = mysqli_error($connection);
                    fclose($handle);
                    mysqli_query($connection, 'SET FOREIGN_KEY_CHECKS=1');
                    mysqli_close($connection);

                    return 'Database import failed: ' . $error;
                }

                $templine = '';
            }
        }

        fclose($handle);
        mysqli_query($connection, 'SET FOREIGN_KEY_CHECKS=1');
        mysqli_close($connection);

        return null;
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
                        'database' => $request->input('database_name'),
                        'username' => $request->input('database_username'),
                        'password' => $request->input('database_password'),
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
