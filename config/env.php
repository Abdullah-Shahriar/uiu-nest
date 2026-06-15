<?php
/**
 * UIU Nest — Simple .env Loader
 *
 * Parses the root .env file and populates $_ENV / getenv().
 * Call this once, as early as possible (e.g. top of database.php).
 *
 * Supports:
 *   KEY=value
 *   KEY="quoted value"
 *   KEY='single quoted'
 *   # comments
 *   blank lines
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        // In production you might want to throw here; locally just warn.
        error_log("[UIU Nest] .env file not found at: $path");
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and blank lines
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Must contain '='
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip surrounding quotes (single or double)
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[-1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        // Only set if not already defined in the real environment
        if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
            $_ENV[$key]     = $value;
            putenv("$key=$value");
        }
    }
}
