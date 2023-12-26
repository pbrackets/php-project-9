<?php

// Подключение автозагрузки через composer
require __DIR__.'/../vendor/autoload.php';


use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use Carbon\Carbon;
use Valitron\Validator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Старт PHP сессии
session_start();

//создание контейнера с двумя компонентами
$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__.'/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

//создание приложения с  подготовленным контейнером
$app = AppFactory::createFromContainer($container);

$router = $app->getRouteCollector()->getRouteParser();

$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);


// Переменные с формы
//$name = $_POST['url[name]'];


//подключение БД
$databaseUrl = parse_url(getenv('DATABASE_URL'));

//$databaseUrl = parse_url($_ENV['DATABASE_URL']);
$username = $databaseUrl['user'];             // janedoe
$password = $databaseUrl['pass'];             // mypassword
$host     = $databaseUrl['host'];             // localhost
$port     = $databaseUrl['port'];             // 5432
$dbName   = ltrim($databaseUrl['path'], '/'); // mydb
$db_table = 'urls';                           // Имя Таблицы БД

//формируем dsn для подключения
$dsn = "pgsql:host=".$host.";port=".$port.";dbname=".$dbName;
//PDO подключение к базе данных
$db = new PDO($dsn, $username, $password);


$app->get('/', function ($request, $response) use ($databaseUrl, $router) {
    $messages = $this->get('flash')->getMessages();
    $params   = ['flashMessages' => $messages];
    $renderer = new PhpRenderer(__DIR__.'/../templates');

    return $renderer->render($response, 'index.phtml', $params);
})->setName('home');

$app->get('/urls', function ($request, $response) use ($databaseUrl, $router, $db) {
    $statement = $db->prepare(
        'SELECT urls.id,
                   name,
                   max(url_checks.created_at) AS last_check_at,
                   
            FROM urls
                     JOIN url_checks ON urls.id = url_checks.url_id
            GROUP BY urls.id
            ORDER BY urls.id DESC;'
    );
    $statement->execute();
    $urls     = $statement->fetchAll();
    $messages = $this->get('flash')->getMessages();
    $params   = ['flashMessages' => $messages, 'urls' => $urls];
    $renderer = new PhpRenderer(__DIR__.'/../templates');

    return $renderer->render($response, 'urls.phtml', $params);
})->setName('url');


$app->get('/urls/{id}', function ($request, $response, $args) use ($router, $db) {
    $id        = $args['id'];
    $statement = $db->prepare('SELECT * FROM urls WHERE id = :id');
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    $data = $statement->fetch();


    $statement2 = $db->prepare('SELECT * FROM url_checks  WHERE url_id = :url_id');
    $statement2->bindParam(':url_id', $id, PDO::PARAM_INT);
    $statement2->execute();
    $checks = $statement2->fetchAll();


    $messages = $this->get('flash')->getMessages();
    $params   = ['flashMessages' => $messages, 'urlData' => $data, 'urlChecks' => $checks];
    $renderer = new PhpRenderer(__DIR__.'/../templates');

    return $renderer->render($response, 'id.phtml', $params);
})->setName('id');


$app->post('/urls', function ($request, $response) use ($db, $router) {
    //var_dump($_POST);
    //die();
    $urlValidator = new Validator($_POST['url']);
    $urlValidator->rule('required', 'name');
    //->message('URL не должен быть пустым');
    $urlValidator->rule('url', 'name');
    //->message('Некорректный URL');
    $urlValidator->rule('lengthMax', 'name', 255);
    //->message('Некорректный URL');
    if ($urlValidator->validate()) {
        //$_SESSION['success'] = 'Валидация пройдена';
        $statement = $db->prepare('INSERT INTO urls (name, created_at) VALUES (:name, :created_at)');
        $statement->bindValue(':name', $_POST['url']['name'], PDO::PARAM_STR);
        $statement->bindValue(':created_at', Carbon::now(), PDO::PARAM_STR);
        $success = $statement->execute();
        if ($success) {
            //извлекаем из контейнера компонент и добавляем flash сообщение
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
            $lastId = $db->lastInsertId(); //извлекает id последнего добавленного url

            return $response->withRedirect($router->urlFor('id', ['id' => $lastId]));
        } else {
            $this->get('flash')->addMessage('error', 'Не могу вставить запись в таблицу');
        }
    } else {
        $this->get('flash')->addMessage('errorUrl', 'Некорректный URL');
    }


    return $response->withRedirect($router->urlFor('home'));
})->setName('');

$app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($router, $db) {
    $id = $args['url_id'];
    try {
        $client              = new GuzzleHttp\Client();
        $responseFromUrl     = $client->request('GET', 'http://0.0.0.0:800');
        $statusCode          = $responseFromUrl->getStatusCode();
        $sql = "INSERT INTO url_checks (
            url_id, 
            created_at, 
            status_code, 
            h1, 
            title, 
            description) 
            VALUES (:url_id, :created_at, :status_code, :h1, :title, :description)";
        $stm = $db->prepare($sql);
        $stm->bindParam(':url_id', $id, PDO::PARAM_INT);
        $stm->bindValue(':created_at', Carbon::now(), PDO::PARAM_STR);
        $stm->bindParam(':status_code', $statusCode, PDO::PARAM_INT);
        $stm->bindValue(':h1', '', PDO::PARAM_STR);
        $stm->bindValue(':title', '', PDO::PARAM_STR);
        $stm->bindValue(':description', '', PDO::PARAM_STR);
        $stm->execute();

        $this->get('flash')->addMessage('successVerification', 'Страница успешно проверена');
    } catch (Exception $e) {
        $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
    }
    return $response->withRedirect($router->urlFor('id', ['id' => $id]));
});


$app->run();
