<?php

$app = require_once __DIR__ . '/config/config.php';

use Propilex\Model\Document;
use Propilex\Model\DocumentQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see http://silex.sensiolabs.org/doc/cookbook/json_request_body.html
 */
$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);

        // filter values
        foreach ($data as $k => $v) {
            if (false === array_search($k, array('Id', 'Title', 'Body'))) {
                unset($data[$k]);
            }
        }

        $request->request->replace(is_array($data) ? $data : array());
    }
});

/**
 * Entry point
 */
$app->get('/', function() use ($app) {
    return new Response(file_get_contents(__DIR__ . '/../web/index.html'), 200);
});

/**
 * Returns all documents
 */
$app->get('/documents', function () use ($app) {
    $query = DocumentQuery::create()
        ->select(array('Id', 'Title', 'Body'));

    return new Response($query->find()->exportTo($app['json_parser']), 200, array(
        'Content-Type' => 'application/json',
    ));
});

/**
 * Returns a specific document identified by a given id
 */
$app->get('/documents/{id}', function ($id) use ($app) {
    $document = DocumentQuery::create()
        ->findPk($id);

    if ($document instanceof Document) {
        return new Response($document->exportTo($app['json_parser']), 200, array (
            'Content-Type' => 'application/json',
        ));
    } else {
        return new Response('', 404);
    }
});

/**
 * Create a new Document
 */
$app->post('/documents', function (Request $request) use ($app) {
    $document = new Document();
    $document->fromArray($request->request->all());
    $document->save();

    return new Response($document->exportTo($app['json_parser']), 201, array (
        'Content-Type' => 'application/json',
    ));
});

/**
 * Update a Document identified by a given id
 */
$app->put('/documents/{id}', function ($id, Request $request) use ($app) {
    $document = DocumentQuery::create()
        ->findPk($id);

    if ($document instanceof Document) {
        $document->fromArray($request->request->all());
        $document->save();

        return new Response($document->exportTo($app['json_parser']), 200, array (
            'Content-Type' => 'application/json',
        ));
    } else {
        return new Response('', 404);
    }
});

$app->delete('/documents/{id}', function ($id) use ($app) {
    try {
        $document = DocumentQuery::create()
            ->filterById($id)
            ->delete();

        return new Response('', 200);
    } catch (\PropelException $e) {
        return new Response('', 404);
    }
});

return $app;