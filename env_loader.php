<?php
/**
 * ENVIRONMENT VARIABLE LOADER
 * 
 * Loads environment variables from .env file
 * Makes them available via getenv() or $_ENV
 */

function loadEnv($path = null) {
    // Default to .env in current directory
    if ($path === null) {
        $path = dirname(__DIR__) . '/.env';
    }
    
    // Check if .env file exists
    if (!file_exists($path)) {
        error_log("Warning: .env file not found at {$path}");
        return false;
    }
    
    // Read .env file
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Handle variable substitution (e.g., ${APP_NAME})
            if (preg_match('/\$\{([A-Z_]+)\}/', $value, $matches)) {
                foreach ($matches as $match) {
                    $varName = str_replace(['${', '}'], '', $match);
                    $varValue = getenv($varName) ?: '';
                    $value = str_replace($match, $varValue, $value);
                }
            }
            
            // Set environment variable
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
    
    return true;
}

/**
 * Get environment variable with optional default
 */
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        $value = $default;
    }
    
    // Handle boolean strings
    if (is_string($value)) {
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }
    }
    
    return $value;
}

// Automatically load .env file
loadEnv();

?>
