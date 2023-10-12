<?php


namespace App;

use GuzzleHttp\Client;
use DiDom\Document;

require __DIR__.'/../vendor/autoload.php';

class Check
{
    private $client;
    private $response;
    private $document;
    private $defaultHTML = "<body></body>";

    public function __construct($url)
    {
        $this->client   = new Client();
        $this->response = $this->client->request('GET', $url);
        $this->document = new Document();
        try {
            $this->document->loadHtmlFile($url);
        } catch (\Exception $e) {
            $this->document->loadHtml($this->defaultHTML);
        }
    }

    public function getFullCheckInformation()
    {
        $h1HTML = $this->document->first('h1');
        if ($h1HTML !== null) {
            $h1 = $h1HTML->text();
        }

        $titleHTML = $this->document->first('title');
        if ($titleHTML !== null) {
            $title = $titleHTML->text();
        }

        $descriptionHTML = $this->document->first('meta[name=description]');
        if ($descriptionHTML !== null) {
            $description = $descriptionHTML->content;
        }

        $maxStrLength = 254;

        return [
            'h1'          => substr($h1, 0, $maxStrLength) ?? '',
            'title'       => substr($title, 0, $maxStrLength) ?? '',
            'description' => substr($description, 0, $maxStrLength) ?? ''
        ];
    }

    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }
}