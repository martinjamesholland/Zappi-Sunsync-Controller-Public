<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class EnvFileService
{
    private string $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    /**
     * Check if .env file exists
     */
    public function exists(): bool
    {
        return file_exists($this->envPath);
    }

    /**
     * Create a new .env file from .env.example or with minimal defaults
     */
    public function create(): bool
    {
        try {
            $examplePath = base_path('.env.example');
            
            if (file_exists($examplePath)) {
                copy($examplePath, $this->envPath);
            } else {
                // Create with minimal defaults
                $content = $this->getMinimalEnvContent();
                file_put_contents($this->envPath, $content);
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get minimal .env content
     */
    private function getMinimalEnvContent(): string
    {
        return <<<ENV
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

VITE_APP_NAME="\${APP_NAME}"
ENV;
    }

    /**
     * Read the .env file content
     */
    public function read(): ?string
    {
        if (!$this->exists()) {
            return null;
        }
        
        return file_get_contents($this->envPath);
    }

    /**
     * Update or add environment variables
     */
    public function update(array $variables): bool
    {
        try {
            if (!$this->exists()) {
                $this->create();
            }
            
            $content = $this->read();
            
            foreach ($variables as $key => $value) {
                $content = $this->updateOrAddVariable($content, $key, $value);
            }
            
            file_put_contents($this->envPath, $content);
            
            // Clear config cache to reflect changes
            Artisan::call('config:clear');
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update or add a single variable in the env content
     */
    private function updateOrAddVariable(string $content, string $key, ?string $value): string
    {
        // Escape special characters in value
        $escapedValue = $this->escapeValue($value);
        
        // Check if the key exists
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
        
        if (preg_match($pattern, $content)) {
            // Update existing key
            $content = preg_replace($pattern, $key . '=' . $escapedValue, $content);
        } else {
            // Add new key at the end
            $content = rtrim($content) . "\n" . $key . '=' . $escapedValue . "\n";
        }
        
        return $content;
    }

    /**
     * Escape value for .env file
     */
    private function escapeValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        
        // If value contains spaces, quotes, or special characters, wrap in quotes
        if (preg_match('/[\s#"\'\\\\]/', $value)) {
            $value = str_replace('"', '\\"', $value);
            return '"' . $value . '"';
        }
        
        return $value;
    }

    /**
     * Get a specific environment variable value
     */
    public function get(string $key): ?string
    {
        $content = $this->read();
        
        if (!$content) {
            return null;
        }
        
        $pattern = '/^' . preg_quote($key, '/') . '=(.*)$/m';
        
        if (preg_match($pattern, $content, $matches)) {
            $value = trim($matches[1]);
            
            // Remove surrounding quotes if present
            if (preg_match('/^"(.*)"$/', $value, $quotedMatches)) {
                return str_replace('\\"', '"', $quotedMatches[1]);
            }
            
            return $value;
        }
        
        return null;
    }

    /**
     * Generate a new application key
     */
    public function generateAppKey(): string
    {
        return 'base64:' . base64_encode(Str::random(32));
    }

    /**
     * Check if .env file is writable
     */
    public function isWritable(): bool
    {
        if (!$this->exists()) {
            // Check if parent directory is writable
            return is_writable(dirname($this->envPath));
        }
        
        return is_writable($this->envPath);
    }

    /**
     * Get the path to the .env file
     */
    public function getPath(): string
    {
        return $this->envPath;
    }
}

