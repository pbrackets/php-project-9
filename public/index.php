<?php

declare(strict_types=1);

namespace App;

require '/composer/vendor/autoload.php';

use Slim\Factory\AppFactory;

// BEGIN
$app = AppFactory::create();
$app->addErrorMiddleware(
    true,
    true,
    true
);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Hexlet!');
});

$app->run();
// END
