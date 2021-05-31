<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/* FORM NEW MEETING */
$app->get('/meeting', function(Request $request, Response $response, $args) {
    $tplVars['header'] = 'New meeting';
    $tplVars['formData'] = [
        'start' => '',
        'description' => '',
        'duration' => '',
        'city' => '',
        'street_name' => '',
        'street_number' => '',
        'zip' => '',
        'country' => '',
        'name' => '',
        'latitude' => '',
        'longitude' => '',
        'id_meeting' => ''
    ];
    return $this->view->render($response, 'newMeeting.latte', $tplVars);
})->setName('newMeeting');

/* POST NEW MEETING */
$app->post('/meeting', function(Request $request, Response $response, $args) {
    $formData = $request->getParsedBody();
    if (empty($formData['start']) || empty($formData['description']) || empty($formData['city'])){
        $tplVars['message'] = 'Please fill required fields';
    } else {
        try {
            $this->db->beginTransaction();
            //CHECK LOCATION
            if (!empty($formData['city']) || !empty($formData['street_name']) || !empty($formData['street_number']) ||
                !empty($formData['zip']) || !empty($formData['country']) || !empty($formData['name']) ||
                !empty($formData['latitude']) || !empty($formData['longitude'])){
                $id_location = newLocation($this, $formData);
            }
            $stmt = $this->db->prepare('INSERT INTO meeting(start, description, duration, id_location)
                                        VALUES (:start, :description, :duration, :id_location)');
            $stmt->bindValue(':start', $formData['start']);
            $stmt->bindValue(':description', $formData['description']);
            $stmt->bindValue(':duration', empty($formData['duration']) ? null : $formData['duration']);
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
    return $response->withHeader('Location', $this->router->pathFor('meetings'));
});