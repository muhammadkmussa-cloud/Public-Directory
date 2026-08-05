<?php
/**
 * CSRF token endpoint — the frontend fetches this once and sends the
 * token back in the X-CSRF-Token header on all state-changing requests.
 */
require __DIR__ . '/_bootstrap.php';

require_method('GET');
json_ok(['csrf_token' => Auth::csrfToken()]);
