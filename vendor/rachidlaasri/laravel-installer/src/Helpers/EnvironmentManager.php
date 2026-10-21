<?php

namespace RachidLaasri\LaravelInstaller\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EnvironmentManager
{
    /**
     * @var string
     */
    private $envPath;

    /**
     * @var string
     */
    private $envExamplePath;

    /**
     * Set the .env and .env.example paths.
     */
    public function __construct()
    {
        $this->envPath = base_path('.env');
        $this->envExamplePath = base_path('.env.example');
    }

    /**
     * Get the content of the .env file.
     *
     * @return string
     */
    public function getEnvContent()
    {
        if (! file_exists($this->envPath)) {
            if (file_exists($this->envExamplePath)) {
                copy($this->envExamplePath, $this->envPath);
            } else {
                touch($this->envPath);
            }
        }

        return file_get_contents($this->envPath);
    }

    /**
     * Get the the .env file path.
     *
     * @return string
     */
    public function getEnvPath()
    {
        return $this->envPath;
    }

    /**
     * Get the the .env.example file path.
     *
     * @return string
     */
    public function getEnvExamplePath()
    {
        return $this->envExamplePath;
    }

    /**
     * Save the edited content to the .env file.
     *
     * @param Request $input
     * @return string
     */
    public function saveFileClassic(Request $input)
    {
        $message = trans('installer_messages.environment.success');

        try {
            file_put_contents($this->envPath, $input->get('envConfig'));
        } catch (Exception $e) {
            $message = trans('installer_messages.environment.errors');
        }

        return $message;
    }

    /**
     * Save the form content to the .env file.
     *
     * @param Request $request
     * @return string
     */
    public function saveFileWizard(Request $request)
    {
        $results = trans('installer_messages.environment.success');

        $appName = addslashes($request->app_name);
        $appDebug = $request->app_debug === 'true' || $request->app_debug === true ? 'true' : 'false';
        $appUrl = rtrim($request->app_url, '/');
        $dbHost = $request->database_hostname;
        $dbName = $request->database_name;
        $dbUser = $request->database_username;
        $dbPass = trim((string) $request->database_password);
        if (strcasecmp($dbPass, 'null') === 0) {
            $dbPass = '';
        }
        $dbPass = str_replace('"', '\"', $dbPass);

        $envFileData =
        'APP_NAME=\''.$appName."'\n".
        'APP_ENV=local'."\n".
        'APP_KEY=base64:'.base64_encode(Str::random(32))."\n".
        'APP_DEBUG='.$appDebug."\n".
        'APP_URL='.$appUrl."\n\n".
        'LOG_CHANNEL=stack'."\n\n".
        'DB_CONNECTION=mysql'."\n".
        'DB_HOST='.$dbHost."\n".
        'DB_PORT=3306'."\n".
        'DB_DATABASE='.$dbName."\n".
        'DB_USERNAME='.$dbUser."\n".
        'DB_PASSWORD="'.$dbPass.'"'."\n\n".
        'BROADCAST_DRIVER=log'."\n".
        'CACHE_DRIVER=file'."\n".
        'QUEUE_CONNECTION=sync'."\n".
        'SESSION_DRIVER=file'."\n".
        'SESSION_LIFETIME=120'."\n\n".
        'REDIS_HOST=127.0.0.1'."\n".
        'REDIS_PASSWORD=null'."\n".
        'REDIS_PORT=6379'."\n\n".
        'MAIL_MAILER=smtp'."\n".
        'MAIL_HOST=smtp.mailtrap.io'."\n".
        'MAIL_PORT=2525'."\n".
        'MAIL_USERNAME=null'."\n".
        'MAIL_PASSWORD=null'."\n".
        'MAIL_ENCRYPTION=null'."\n\n".
        'PUSHER_APP_ID='."\n".
        'PUSHER_APP_KEY='."\n".
        'PUSHER_APP_SECRET='."\n";

        try {
            file_put_contents($this->envPath, $envFileData);
        } catch (Exception $e) {
            $results = trans('installer_messages.environment.errors');
        }

        return $results;
    }
}
