<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/* UPDATE MEETING FORM */
$app->get('/meeting/{id_meeting}/editMeeting', function (Request $request, Response $response, $args) {
    if (!empty($args['id_meeting'])) {
        $stmt = $this->db->prepare('SELECT * FROM meeting 
                                LEFT JOIN location USING (id_location) 
                                WHERE id_meeting = :id_meeting');
        $stmt->bindValue(':id_meeting', $args['id_meeting']);
        $stmt->execute();
        $tplVars['formData'] = $stmt->fetch();

        //ALREADY MEMBERS
        $stmt = $this->db->prepare('SELECT id_person, first_name, last_name FROM person WHERE person.id_person IN
                                    (SELECT person_meeting.id_person FROM meeting LEFT JOIN
                                    person_meeting USING (id_meeting) WHERE id_meeting = :id_meeting)
                                    ORDER BY first_name');
        $stmt->bindValue(':id_meeting', $args['id_meeting']);
        $stmt->execute();
        $tplVars['alreadyMember'] = $stmt->fetchAll();

        //ADD MEMBER
        $stmt = $this->db->prepare('SELECT person.id_person, first_name, last_name FROM person WHERE person.id_person IN
                                    (SELECT person_meeting.id_person FROM meeting LEFT JOIN
                                    person_meeting USING (id_meeting) WHERE NOT id_meeting = :id_meeting)
                                    ORDER BY first_name');
        $stmt->bindValue(':id_meeting', $args['id_meeting']);
        $stmt->execute();
        $tplVars['addMember'] = $stmt->fetchAll();

        if (empty($tplVars['formData'])) {
            exit('meeting not found');
        } else {
            $tplVars['header'] = 'Edit meeting';
            return $this->view->render($response, 'editMeeting.latte', $tplVars);
        }
    }
})->setName('editMeeting');


/* EDIT MEETING */
$app->post('/meeting/{id_meeting}/editMeeting', function (Request $request, Response $response, $args) {
    $formData = $request->getParsedBody();
    $alreadyMember = $request->getParsedBody();
    $tplVars = [];
    if (empty($formData['start']) || empty($formData['description']) || empty($formData['city'])){
        $tplVars['message'] = 'Please fill required fields';
    } else {
        try {
            //CHECKING ADDRESSES
            if (!empty($formData['city']) || !empty($formData['street_name']) || !empty($formData['street_number']) ||
                !empty($formData['zip']) || !empty($formData['country']) || !empty($formData['name']) ||
                !empty($formData['latitude']) || !empty($formData['longitude'])){
                $stmt = $this->db->prepare('SELECT id_location FROM meeting WHERE id_meeting = :id_meeting');
                $stmt->bindValue(':id_meeting', $args['id_meeting']);
                $stmt->execute();
                $id_location = $stmt->fetch()['id_location'];
                if ($id_location) {
                    ## The meeting has an address
                    editLocation($this, $id_location, $formData);
                } else {
                    ## Meeting has no address
                    $id_location = newLocation($this, $formData);
                }
            }

            //ADD MEMBER
            if (!empty($alreadyMember['id_person'])) {
                $stmt = $this->db->prepare('INSERT INTO person_meeting (id_meeting, id_person)
	                                        VALUES (:id_meeting, :id_person)');
                $stmt->bindValue(':id_meeting', ($alreadyMember['id_meeting']));
                $stmt->bindValue(':id_person', ($alreadyMember['id_person']));
                $stmt->execute();
            }

            //DELETE MEMBER
            if (!empty($alreadyMember['deleteMember'])){
                $stmt = $this->db->prepare('DELETE FROM person_meeting WHERE id_person = :deleteMember AND
                                            id_meeting = :id_meeting');
                $stmt->bindValue(':deleteMember', ($alreadyMember['deleteMember']));
                $stmt->bindValue(':id_meeting', ($alreadyMember['id_meeting']));
                $stmt->execute();
            }

            $stmt = $this->db->prepare('UPDATE meeting SET 
                                        start = :start,  
                                        description = :description,
                                        duration = :duration,
                                        id_location = :id_location
                                        WHERE id_meeting = :id_meeting');
            $stmt->bindValue(':start', $formData['start']);
            $stmt->bindValue(':description', $formData['description']);
            $stmt->bindValue(':duration', empty($formData['duration']) ? null : $formData['duration']);
            $stmt->bindValue(':id_location', $id_location ? $id_location : null);
            $stmt->bindValue(':id_meeting', $args['id_meeting']);
            $stmt->execute();
            $tplVars['message'] = 'Meeting succesfully updated';

        } catch (PDOexception $e) {
            $tplVars['message'] = 'Error';
            $this->logger->error($e->getMessage());
        }
    }

    return $response->withHeader('Location', $this->router->pathFor('meetings'));
});
