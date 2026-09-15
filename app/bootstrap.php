<?php
declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));

// Les erreurs vont dans le journal PHP : affichées, elles casseraient les réponses JSON des formulaires.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/helpers.php';
require __DIR__ . '/google.php';
require __DIR__ . '/mail.php';
require __DIR__ . '/agenda.php';

date_default_timezone_set(config('booking.timezone'));
