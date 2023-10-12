<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';



use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;



// Старт PHP сессии
session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function() {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);

$router = $app->getRouteCollector()->getRouteParser();

$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

// $app->get('/', function ($request, $response) {
//     $response->getBody()->write('Welcome to Slim!');
//
//     return $response;
//     // Благодаря пакету slim/http этот же код можно записать короче
//     // return $response->write('Welcome to Slim!');
// });
//
// $app->get('/', function ($request, $response) {
//     return $this->get('renderer')->render($response, 'index.phtml');
// });
$app->get('/', function ($request, $response) use ($router) {
    // $messages = $this->container->get('flash')->getMessages();
    // $params = ['flash' => $messages];
    $params = [];
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    return $renderer->render($response, 'index.phtml', $params);
})->setName('home');


$app->post('/urls', function ($request, $response) use ($router) {
    sleep(1);
    return $response->withRedirect($router->urlFor('home'));
})->setName('');

// $container->set('UrlController', function($c) {
//     $view = $c->get("view");
//     return new UrlController($view);
// });




// $app->get('/', UrlController::class . ':start');
// $app->get('/urls', UrlController::class . ':index');
// $app->get('/urls/{id}', UrlController::class . ':show');
//
// $app->post('/', UrlController::class . ':create');
// $app->post('/urls/{url_id}/checks', UrlCheckController::class . ':create');

$app->run();
