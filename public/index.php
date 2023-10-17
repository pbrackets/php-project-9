<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';



use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use PDO;

//use Valitron\Validator;

// Старт PHP сессии
session_start();

//создание контейнера с двумя компонентами
$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function() {
    return new \Slim\Flash\Messages();
});

//создание приложения с  подготовленным контейнером
$app = AppFactory::createFromContainer($container);

$router = $app->getRouteCollector()->getRouteParser();

$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);


//подключение БД
$databaseUrl = parse_url(getenv('DATABASE_URL'));

//$databaseUrl = parse_url($_ENV['DATABASE_URL']);
$username = $databaseUrl['user']; // janedoe
$password = $databaseUrl['pass']; // mypassword
$host = $databaseUrl['host']; // localhost
$port = $databaseUrl['port']; // 5432
$dbName = ltrim($databaseUrl['path'], '/'); // mydb

//формируем dsn для подключения
$dsn = "pgsql:host=".$host.";port=".$port.";dbname=".$dbName;
//PDO подключение к базе данных
$db = new PDO($dsn, $username, $password);

//Подготавливает и выполняет выражение SQL без заполнителей
$statement = $db->query('SELECT 1');
//Извлечение всех оставшихся строк результирующего набора
$result = $statement->fetchAll();
var_dump($result);


$app->get('/', function ($request, $response) use ($databaseUrl, $router) {
    var_dump($databaseUrl);
    $messages = $this->get('flash')->getMessages();
    $params = ['flashMessages' => $messages];
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    return $renderer->render($response, 'index.phtml', $params);
})->setName('home');


$app->post('/urls', function ($request, $response) use ($router) {
    sleep(1);

    //извлекаем из контейнера компонент и добавляем flash сообщение
    $this->get('flash')->addMessage('success', 'Страница успешно добавлена!');
    return $response->withRedirect($router->urlFor('home'));
    // $this->get('flash')->addMessage('error', 'Страница уже существует!');
    // return $response->withRedirect($router->urlFor('home'));
})->setName('');



$app->run();
