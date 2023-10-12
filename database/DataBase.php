<?php declare(strict_types=1);

namespace Model\DataBase;

use Carbon\Carbon;
use PDO;

require __DIR__.'/../vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();


class DataBase
{
    private $connection;
    private $flashMessages;

    public function __construct()
    {
        $dsn = "mysql:host={$_ENV['MYSQLHOST']};port={$_ENV['MYSQLPORT']};dbname={$_ENV['MYSQLDATABASE']};";
        $this->connection = new PDO($dsn, $_ENV['MYSQLUSER'], $_ENV['MYSQLPASSWORD']);
        $this->flashMessages = [
            'existed' => 'Страница уже существует',
            'new' => 'Страница успешно добавлена',
            'newCheck' => 'Страница успешно проверена'
        ];
    }

    private function doQuery($sql, $data, $isNeedToFetch = true)
    {
        $query = $this->connection->prepare($sql);
        $query->execute($data);
        if ($isNeedToFetch) {
            return $query->fetchAll();
        }
    }

    private function isInBase($name)
    {
        return $this->getUrlDataFromBaseByName($name);
    }

    public function writeUrlToBase($name)
    {
        if ($this->isInBase($name)) {
            return $this->flashMessages['existed'];
        }
        $created_at = Carbon::now();
        $sql = 'INSERT INTO Urls (name, created_at) VALUES (:name, :created_at)';
        $this->doQuery($sql, ['name' => $name, 'created_at' => $created_at], false);
        return $this->flashMessages['new'];
    }

    public function getUrlDataFromBaseByName($name)
    {
        $sql = 'SELECT * FROM Urls WHERE name = :name';
        $urlData = $this->doQuery($sql, ['name' => $name]);
        return $urlData[0];
    }

    public function getUrlDataFromBaseById($id)
    {
        $sql = 'SELECT * FROM Urls WHERE id = :id';
        $urlData = $this->doQuery($sql, ['id' => $id]);
        return $urlData[0];
    }

    public function addCheck($id, $check = null)
    {
        $created_at = Carbon::now();
        $sql = 'INSERT INTO Url_checks (url_id, status_code, h1, title, description, created_at) 
        VALUES (:url_id, :status_code, :h1, :title, :description, :created_at)';
        $info = $check->getFullCheckInformation();
        $title = $info['title'];
        $this->doQuery($sql, [
            'url_id' => $id,
            'status_code' => $check->getStatusCode(),
            'h1' => $info['h1'],
            'title' => $title,
            'description' => $info['description'],
            'created_at' => $created_at,], false);
        return $this->flashMessages['newCheck'];
    }

    public function getChecks($id)
    {
        $sql = 'SELECT * FROM Url_checks WHERE url_id = :url_id';
        $checkData = $this->doQuery($sql, ['url_id' => $id]);
        usort($checkData, fn($check1, $check2) => $check2['id'] <=> $check1['id']);
        return $checkData;
    }

    private function getCheckedData()
    {
        $sql = 'SELECT status_code, url_id, MAX(created_at) 
                FROM Url_checks GROUP BY url_id, status_code';
        $data = $this->doQuery($sql, []);
        return $data;
    }

    private function getUrlsData()
    {
        $sql = 'SELECT id, name FROM Urls';
        $data = $this->doQuery($sql, []);
        return $data;
    }

    public function getAllUrls()
    {
        $checkedData = $this->getCheckedData();
        $urlsData = $this->getUrlsData();

        $checksById = array_reduce($checkedData, function($arr, $url) {
            $arr[$url['url_id']] = [
                'status_code' => $url['status_code'],
                'created_at' => $url['MAX(created_at)']];
            return $arr;
        }, []);

        $fullUrlsData = array_reduce($urlsData, function($arr, $url) use($checksById) {
            $id = $url['id'];
            $arr[$id]['name'] = $url['name'];
            $arr[$id]['id'] = $url['id'];
            if ($checksById[$id]) {
                $arr[$id]['status_code'] = $checksById[$id]['status_code'];
                $arr[$id]['created_at'] = $checksById[$id]['created_at'];
            }
            return $arr;
        }, []);

        return $fullUrlsData;
    }

    public function dropTables()
    {
        $this->connection->exec('DELETE FROM Url_checks');
        $this->connection->exec('DELETE FROM Urls');
    }

    public function createTables()
    {
        $this->connection->exec('DROP TABLE Url_checks');
        $this->connection->exec('DROP TABLE Urls');

        $this->connection->exec('CREATE TABLE Urls (
            id int PRIMARY KEY AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            created_at TIMESTAMP,
            UNIQUE (name)
            )');

        $this->connection->exec('CREATE TABLE Url_checks (
            id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
            url_id int,
            status_code int,
            h1 varchar(255),
            title varchar(255),
            description varchar(255),
            created_at TIMESTAMP,
            FOREIGN KEY (url_id) REFERENCES Urls (id)
        )');
    }
}