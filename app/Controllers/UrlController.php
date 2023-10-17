<?php

namespace App\Controllers;

use Psr\Container\ContainerInterface;
use Slim\Views\PhpRenderer;
use Model\DataBase\DataBase;


class UrlController
{
    protected $container;
    private $connection;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->connection = new DataBase();
    }

    public function start($request, $response)
    {
        $messages = $this->container->get('flash')->getMessages();
        $params = ['flash' => $messages];
        $renderer = new PhpRenderer(__DIR__ . '/../../templates');
        return $renderer->render($response, 'index.phtml', $params);
    }

    public function index($request, $response)
    {
        $urls = $this->connection->getAllUrls();
        $params = ['urls' => $urls];
        $renderer = new PhpRenderer(__DIR__ . '/../../templates');
        return $renderer->render($response, 'urls.phtml', $params);
    }

    public function create($request, $response)
    {
        $url = $request->getParsedBody()['url']; //извлечение данных из тела запроса
        $validator = new \Valitron\Validator(array('website' => $url['name'])); //
        $validator->rule('url', 'website');

        if ($validator->validate()) {
            $message = $this->connection->writeUrlToBase($url['name']);
            $urlData = $this->connection->getUrlDataFromBaseByName($url['name']);
            $id = $urlData['id'];
            $this->container->get('flash')->addMessage('success', $message);

            return $response
                ->withHeader('Location', '/urls/' . $id)
                ->withStatus(302);
        }

        $this->container->get('flash')->addMessage('failed', 'Некорректный URL');
        return $response
            ->withHeader('Location', '/')
            ->withStatus(302);
    }

    public function show($request, $response, $args)
    {
        $id = $args['id'];
        $params = $this->connection->getUrlDataFromBaseById($id);
        $checks = $this->connection->getChecks($id);
        $messages = $this->container->get('flash')->getMessages();
        $params['flash'] = $messages;
        $params['checks'] = $checks;

        $renderer = new PhpRenderer(__DIR__ . '/../../templates');
        return $renderer->render($response, 'id.phtml', $params);
    }
}
