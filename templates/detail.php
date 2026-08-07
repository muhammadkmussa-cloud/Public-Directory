<?php
/**
 * Abstract detail template — common structure for business/mosque/fundi/charity
 * Child templates should set: $entity, $pageTitle, $pageDescription, $jsonLd, $pageScript, $mainContent
 */
declare(strict_types=1);

require_once __DIR__ . '/base.php';

// Child templates should override these
// $entity = [...] // the entity data from API
// $pageScript = 'business.js' // or mosque.js, fundi.js, charity.js
// $jsonLd = '...' // JSON-LD structured data
// $mainContent = '...' // the detail HTML content