<?php
// Copy this file to .env.php and fill in your production credentials.
// DO NOT commit .env.php to GitHub.

define('ENV_DB_HOST', 'localhost');
define('ENV_DB_NAME', 'u839644576_go');
define('ENV_DB_USER', 'u839644576_go');
define('ENV_DB_PASS', 'YOUR_DATABASE_PASSWORD_HERE');

// Security: set a strong random secret for JWT token signing (min 32 chars)
// Generate one with: php -r "echo bin2hex(random_bytes(32));"
define('ENV_TOKEN_SECRET', 'YOUR_RANDOM_TOKEN_SECRET_HERE');

// Security: restrict CORS to your production frontend domain
define('ENV_CORS_ALLOWED_ORIGIN', 'https://your-production-domain.com');
