<?php

namespace App\Controllers;

use Psr\Container\ContainerInterface;
use Model\DataBase\DataBase;
use App\Check;

class UrlCheckController
{
    protected $container;
    private $connection;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->connection = new Database();
    }

    public function create($request, $response, $args)
    {
        $id = $args['url_id'];
        $url = $this->connection->getUrlDataFromBaseById($id);
        $check = new Check($url['name']);
        $message = $this->connection->addCheck($id, $check);
        $this->container->get('flash')->addMessage('success', $message);

        return $response
            ->withHeader('Location', '/urls/' . $id)
            ->withStatus(302);
    }
}