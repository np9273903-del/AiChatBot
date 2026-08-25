<?php
/**
 * AIChatPHP (PHP/MySQL edition) - core configuration
 * Copy this file's values from a real .env in production; kept plain here for simplicity.
 */

// ---- Database ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'soen_chat');
define('DB_USER', 'root');
define('DB_PASS', 'pravin807');

// ---- App ----
define('APP_NAME', 'AIChatPHP');
define('APP_URL', 'http://localhost/soen-php'); // change to your deployed URL

// ---- AI provider ----
// Choose 'openai' or 'gemini'. Add your API key below.
define('AI_PROVIDER', 'gemini');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('OPENAI_MODEL', 'gpt-4o-mini');

define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY_HERE');
define('GEMINI_MODEL', 'gemini-1.5-flash');

// ---- Email (real sending via PHP mail()) ----
define('MAIL_FROM_EMAIL', 'no-reply@soen.local');
define('MAIL_FROM_NAME', APP_NAME);

// ---- Session / security ----
define('SESSION_NAME', 'soen_session');

error_reporting(E_ALL);
ini_set('display_errors', '1'); // turn OFF in production