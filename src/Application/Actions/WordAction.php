<?php

declare(strict_types=1);

namespace App\Application\Actions;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface as Response;

class WordAction extends Action
{
    protected function action(): Response
    {
        $word = (string) urldecode($this->resolveArg('word'));

        $cache = $this->redis->get('eninclusif_' . $word);
        if ($cache) {
            return $this->respondWithData(json_decode($cache));
        }
        $client = new Client();
        $res = $client->post('https://eninclusif.fr/recherche', [
            RequestOptions::JSON => [
                'mot' => $word,
                'token' => base64_encode(date('Y-m-d-H'))
            ]
        ]);

        $status = $res->getStatusCode();
        $body = json_decode($res->getBody()->getContents());

        if ($status === 200 && $body && !isset($body->error)) {
            $this->redis->set('eninclusif_' . $word, json_encode($body), 60 * 60 * 24 * 7);
            return $this->respondWithData($body);
        } else {
            return $this->respondWithData([
                'error' => 'No result'
            ], 404);
        }
    }
}
