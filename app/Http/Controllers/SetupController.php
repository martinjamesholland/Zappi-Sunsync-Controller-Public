<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EnvFileService;
use App\Services\SunSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SetupController extends Controller
{
    private EnvFileService $envService;

    public function __construct(EnvFileService $envService)
    {
        $this->envService = $envService;
    }

    /**
     * Show the welcome/overview page
     */
    public function index()
    {
        $setupStatus = $this->getSetupStatus();
        
        return view('setup.index', [
            'setupStatus' => $setupStatus
        ]);
    }

    /**
     * Show APP_KEY setup step
     */
    public function showAppKey()
    {
        $hasAppKey = !empty(config('app.key'));
        $hasApiKey = !empty(config('services.api.key'));
        
        return view('setup.app-key', [
            'hasAppKey' => $hasAppKey,
            'currentKey' => $hasAppKey ? config('app.key') : null,
            'hasApiKey' => $hasApiKey,
            'currentApiKey' => $hasApiKey ? config('services.api.key') : null
        ]);
    }

    /**
     * Generate and save APP_KEY
     */
    public function generateAppKey(Request $request)
    {
        try {
            // Create .env if it doesn't exist
            if (!$this->envService->exists()) {
                $this->envService->create();
            }

            // Generate new app key
            $appKey = $this->envService->generateAppKey();
            
            // Generate new API key (random 32-character string)
            $apiKey = bin2hex(random_bytes(32));
            
            // Update .env file with both keys
            $success = $this->envService->update([
                'APP_KEY' => $appKey,
                'API_KEY' => $apiKey
            ]);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update .env file. Please check file permissions.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Application keys generated successfully!',
                'app_key' => $appKey,
                'api_key' => $apiKey
            ]);
        } catch (\Exception $e) {
            Log::error('APP_KEY generation failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show database setup step
     */
    public function showDatabase()
    {
        $currentConnection = config('database.default', 'sqlite');
        $currentConfig = $this->getCurrentDatabaseConfig();
        
        return view('setup.database', [
            'currentConnection' => $currentConnection,
            'currentConfig' => $currentConfig
        ]);
    }

    /**
     * Test and save database configuration
     */
    public function saveDatabase(Request $request)
    {
        $validated = $request->validate([
            'db_connection' => 'required|in:sqlite,mysql,mariadb,pgsql',
            'db_host' => 'nullable|string',
            'db_port' => 'nullable|integer',
            'db_database' => 'nullable|string',
            'db_username' => 'nullable|string',
            'db_password' => 'nullable|string',
        ]);

        $connection = $validated['db_connection'];

        // Prepare environment variables based on connection type
        $envVars = [
            'DB_CONNECTION' => $connection,
        ];

        if ($connection === 'sqlite') {
            $dbPath = $validated['db_database'] ?? database_path('database.sqlite');
            $envVars['DB_DATABASE'] = $dbPath;
            
            // Create SQLite file if it doesn't exist
            if (!file_exists($dbPath)) {
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                touch($dbPath);
            }
        } else {
            $envVars['DB_HOST'] = $validated['db_host'] ?? '127.0.0.1';
            $envVars['DB_PORT'] = (string)($validated['db_port'] ?? $this->getDefaultPort($connection));
            $envVars['DB_DATABASE'] = $validated['db_database'] ?? 'laravel';
            $envVars['DB_USERNAME'] = $validated['db_username'] ?? 'root';
            $envVars['DB_PASSWORD'] = $validated['db_password'] ?? '';
        }

        // Test the database connection
        $testResult = $this->testDatabaseConnection($connection, $envVars);

        if (!$testResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed: ' . $testResult['error']
            ], 400);
        }

        // Save to .env file
        $this->envService->update($envVars);

        // Clear config cache to pick up new database settings
        Artisan::call('config:clear');
        
        // Purge the old database connection so it reconnects with new settings
        DB::purge();
        
        // Reconnect to the database with the new configuration
        try {
            DB::reconnect();
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            Log::error('Database reconnection failed after .env update', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Database reconnection failed: ' . $e->getMessage()
            ], 500);
        }

        // Run migrations with the new database connection
        try {
            // Update the default connection to the new one we just configured
            config(['database.default' => $connection]);
            
            $driver = DB::connection()->getDriverName();
            
            // Always drop all existing tables to start fresh
            if ($driver === 'mysql' || $driver === 'mariadb') {
                try {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                    $tables = DB::select('SHOW TABLES');
                    foreach ($tables as $table) {
                        $tableArray = get_object_vars($table);
                        $tableName = reset($tableArray);
                        DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
                    }
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                } catch (\Exception $e) {
                    Log::error('Failed to drop existing tables', ['error' => $e->getMessage()]);
                }
            } elseif ($driver === 'pgsql') {
                try {
                    DB::statement('DROP SCHEMA public CASCADE');
                    DB::statement('CREATE SCHEMA public');
                } catch (\Exception $e) {
                    Log::error('Failed to drop schema', ['error' => $e->getMessage()]);
                }
            } elseif ($driver === 'sqlite') {
                // For SQLite, delete the database file and recreate
                $dbPath = config('database.connections.sqlite.database');
                if ($dbPath && file_exists($dbPath)) {
                    unlink($dbPath);
                    touch($dbPath);
                }
            }
            
            // Now run fresh migrations
            Artisan::call('migrate', ['--force' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Database configured and migrations run successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Migration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Database connected but migrations failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show Zappi setup step
     */
    public function showZappi()
    {
        $currentSerial = config('myenergi.serial') ?: $this->envService->get('ZAPPI_SERIAL');
        $hasCredentials = !empty($currentSerial);
        
        return view('setup.zappi', [
            'currentSerial' => $currentSerial,
            'hasCredentials' => $hasCredentials
        ]);
    }

    /**
     * Test and save Zappi credentials
     */
    public function saveZappi(Request $request)
    {
        $validated = $request->validate([
            'zappi_serial' => 'required|string',
            'zappi_password' => 'required|string',
        ]);

        $serial = $validated['zappi_serial'];
        $password = $validated['zappi_password'];

        // Test credentials
        $testResult = $this->testZappiCredentials($serial, $password);

        if (!$testResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Zappi credentials test failed: ' . $testResult['error']
            ], 400);
        }

        // Save to .env file
        $this->envService->update([
            'ZAPPI_SERIAL' => $serial,
            'ZAPPI_PASSWORD' => $password,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Zappi credentials saved and verified successfully!',
            'data' => $testResult['data']
        ]);
    }

    /**
     * Show SunSync setup step
     */
    public function showSunSync()
    {
        $currentUsername = config('services.sunsync.username') ?: $this->envService->get('SUNSYNC_USERNAME');
        $hasCredentials = !empty($currentUsername);
        
        return view('setup.sunsync', [
            'currentUsername' => $currentUsername,
            'hasCredentials' => $hasCredentials
        ]);
    }

    /**
     * Test and save SunSync credentials
     */
    public function saveSunSync(Request $request)
    {
        $validated = $request->validate([
            'sunsync_username' => 'required|string|email',
            'sunsync_password' => 'required|string',
        ]);

        $username = $validated['sunsync_username'];
        $password = $validated['sunsync_password'];

        // Test credentials
        $testResult = $this->testSunSyncCredentials($username, $password);

        if (!$testResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'SunSync credentials test failed: ' . $testResult['error']
            ], 400);
        }

        // Save to .env file
        $this->envService->update([
            'SUNSYNC_USERNAME' => $username,
            'SUNSYNC_PASSWORD' => $password,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'SunSync credentials saved and verified successfully!',
            'data' => $testResult['data']
        ]);
    }

    /**
     * Show completion page
     */
    public function complete()
    {
        $setupStatus = $this->getSetupStatus();
        
        return view('setup.complete', [
            'setupStatus' => $setupStatus
        ]);
    }

    /**
     * Get overall setup status
     */
    private function getSetupStatus(): array
    {
        return [
            'env_exists' => $this->envService->exists(),
            'app_key' => !empty(config('app.key')) && !empty(config('services.api.key')),
            'database' => $this->isDatabaseConfigured(),
            'zappi' => !empty(config('myenergi.serial')) && !empty(config('myenergi.password')),
            'sunsync' => !empty(config('services.sunsync.username')) && !empty(config('services.sunsync.password')),
        ];
    }

    /**
     * Check if database is configured and has tables
     */
    private function isDatabaseConfigured(): bool
    {
        $connection = config('database.default');
        
        // Check if database configuration exists in .env
        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            
            // If using the default path and file doesn't exist, not configured
            if ($dbPath === database_path('database.sqlite') && !file_exists($dbPath)) {
                return false;
            }
            
            // If DB_DATABASE is not set in .env, not configured
            if (empty(env('DB_DATABASE'))) {
                return false;
            }
        } else {
            // For MySQL/MariaDB/PostgreSQL, check if credentials are set
            if (empty(env('DB_HOST')) || empty(env('DB_DATABASE')) || empty(env('DB_USERNAME'))) {
                return false;
            }
        }
        
        try {
            DB::connection()->getPdo();
            
            // Check if migrations table exists (indicating migrations have been run)
            if ($connection === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='migrations'");
            } elseif ($connection === 'mysql' || $connection === 'mariadb') {
                $tables = DB::select("SHOW TABLES LIKE 'migrations'");
            } elseif ($connection === 'pgsql') {
                $tables = DB::select("SELECT tablename FROM pg_tables WHERE tablename = 'migrations'");
            } else {
                // For other database types, assume configured if connection works
                return true;
            }
            
            return count($tables) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get current database configuration
     */
    private function getCurrentDatabaseConfig(): array
    {
        $connection = config('database.default', 'sqlite');
        
        if ($connection === 'sqlite') {
            return [
                'database' => config('database.connections.sqlite.database')
            ];
        }
        
        return [
            'host' => config("database.connections.{$connection}.host"),
            'port' => config("database.connections.{$connection}.port"),
            'database' => config("database.connections.{$connection}.database"),
            'username' => config("database.connections.{$connection}.username"),
        ];
    }

    /**
     * Get default port for database connection
     */
    private function getDefaultPort(string $connection): int
    {
        return match($connection) {
            'mysql', 'mariadb' => 3306,
            'pgsql' => 5432,
            default => 3306,
        };
    }

    /**
     * Test database connection
     */
    private function testDatabaseConnection(string $connection, array $config): array
    {
        try {
            $testConfig = [
                'driver' => $connection,
            ];

            if ($connection === 'sqlite') {
                $testConfig['database'] = $config['DB_DATABASE'];
            } else {
                $testConfig['host'] = $config['DB_HOST'];
                $testConfig['port'] = (int)$config['DB_PORT'];
                $testConfig['database'] = $config['DB_DATABASE'];
                $testConfig['username'] = $config['DB_USERNAME'];
                $testConfig['password'] = $config['DB_PASSWORD'];
                $testConfig['charset'] = 'utf8mb4';
                $testConfig['collation'] = 'utf8mb4_unicode_ci';
                $testConfig['prefix'] = '';
            }

            // Set temporary connection
            config(['database.connections.test_connection' => $testConfig]);
            
            // Test the connection
            DB::connection('test_connection')->getPdo();
            
            return [
                'success' => true,
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test Zappi credentials
     */
    private function testZappiCredentials(string $serial, string $password): array
    {
        try {
            $client = new Client([
                'base_uri' => 'https://s18.myenergi.net',
                'timeout' => 10.0,
                'http_errors' => false,
            ]);

            $endpoint = "/cgi-jstatus-Z{$serial}";
            $response = $client->request('GET', $endpoint, [
                'auth' => [$serial, $password, 'digest'],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                
                return [
                    'success' => true,
                    'message' => 'Zappi credentials verified',
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'error' => 'Invalid credentials or Zappi not found (Status: ' . $response->getStatusCode() . ')'
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test SunSync credentials
     */
    private function testSunSyncCredentials(string $username, string $password): array
    {
        try {
            // Temporarily set config for testing
            config([
                'services.sunsync.username' => $username,
                'services.sunsync.password' => $password,
            ]);

            $sunSyncService = app(SunSyncService::class);
            $result = $sunSyncService->authenticate($username, $password);

            if ($result && !isset($result['error'])) {
                return [
                    'success' => true,
                    'message' => 'SunSync credentials verified',
                    'data' => $result
                ];
            }

            $errorMessage = $result['message'] ?? 'Authentication failed';
            
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }
}

