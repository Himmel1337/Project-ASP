<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

include 'persons.php';
include 'newPerson.php';
include 'editPerson.php';
include 'meetings.php';
include 'newMeeting.php';
include 'editMeeting.php';
include 'relation.php';
include 'location.php';
include 'contact.php';

$app->get('/', function (Request $request, Response $response, $args) {
    // Render index view
    return $this->view->render($response, 'index.latte');
})->setName('index');


$app->post('/test', function (Request $request, Response $response, $args) {
    //read POST data
    $input = $request->getParsedBody();
    //log
    $this->logger->info('Your name: ' . $input['person']);
    return $response->withHeader('Location', $this->router->pathFor('index'));
})->setName('redir');


