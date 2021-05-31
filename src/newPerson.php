<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/* FORM NEW PERSON */
$app->get('/person', function(Request $request, Response $response, $args) {
    $tplVars['header'] = 'New person';
    $tplVars['formData'] = [
        'first_name' => '',
        'last_name' => '',
        'nickname' => '',
        'gender' => '',
        'height' => '',
        'birth_day' => '',
        'street_name' => '',
        'street_number' => '',
        'city' => '',
        'zip' => '',
        'Country' => '',
        'Name' => '',
        'Latitude' => '',
        'Longitude' => '',
    ];
    return $this->view->render($response, 'newPerson.latte', $tplVars);
})->setName('newPerson');

/* POST NEW PERSON */
$app->post('/person', function(Request $request, Response $response, $args) {
    $formData = $request->getParsedBody();
    if ( empty($formData['first_name']) || empty($formData['last_name']) || empty($formData['nickname']) ) {
        $tplVars['message'] = 'Please fill required fields';
    } else {
        try {
            $this->db->beginTransaction();

            //CHECK LOCATION
            if (!empty($formData['city']) || !empty($formData['street_name']) || !empty($formData['street_number']) ||
                !empty($formData['zip']) || !empty($formData['country']) || !empty($formData['name']) ||
                !empty($formData['latitude']) || !empty($formData['longitude'])) {
                $id_location = newLocation($this, $formData);}

            $stmt = $this->db->prepare('INSERT INTO person (first_name, last_name, nickname, gender, 
                                        height, birth_day, id_location)
                                        VALUES (:first_name, :last_name, :nickname, :gender, 
                                        :height, :birth_day, :id_location)');
            $stmt->bindValue(':first_name', $formData['first_name']);
            $stmt->bindValue(':last_name', $formData['last_name']);
            $stmt->bindValue(':nickname', $formData['nickname']);
            $stmt->bindValue(':gender', empty($formData['gender']) ? null : $formData['gender']);
            $stmt->bindValue(':height', empty($formData['height']) ? null : $formData['height']);
            $stmt->bindValue(':birth_day', empty($formData['birth_day']) ? null : $formData['birth_day']);
            $stmt->bindValue(':id_location', $id_location ? $id_location : null);
            $stmt->execute();
            $this->db->commit();
        } catch (PDOexception $e) {
            $tplVars['message'] = 'Error';
            $tplVars['formData'] = $formData;
            $this->logger->error($e->getMessage());
            $this->db->rollback();
        }
    }
    return $response->withHeader('Location', $this->router->pathFor('persons'));
});