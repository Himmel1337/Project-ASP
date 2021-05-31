<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/* UPDATE PERSSON FORM */
$app->get('/person/{id_person}/editPerson', function (Request $request, Response $response, $args) {
    if (! empty($args['id_person'])) {
        $stmt = $this->db->prepare('SELECT * FROM person 
                                    LEFT JOIN location USING (id_location) 
                                    WHERE id_person = :id_person');
        $stmt->bindValue(':id_person', $args['id_person']);
        $stmt->execute();
        $tplVars['formData'] = $stmt->fetch();

        //HAVE A CONTACT
        $stmt = $this->db->prepare('SELECT * FROM contact LEFT JOIN contact_type
                                    USING (id_contact_type)
                                    LEFT JOIN person ON contact.id_person = person.id_person
                                    WHERE contact.id_person = :id_person');
        $stmt->bindValue(':id_person', $args['id_person']);
        $stmt->execute();
        $tplVars['contact'] = $stmt->fetchAll();;

        //ADD CONTACT
        $stmt = $this->db->query('SELECT id_contact_type, name FROM contact_type');
        $tplVars['addContact'] = $stmt->fetchAll();

        //HAVE A RELATION
        $stmt = $this->db->prepare('SELECT * FROM relation LEFT JOIN relation_type
                                    USING (id_relation_type)
                                    LEFT JOIN person ON relation.id_person2 = person.id_person
                                    WHERE relation.id_person1 = :id_person');
        $stmt->bindValue(":id_person", $args['id_person']);
        $stmt->execute();
        $tplVars["person1"] = $stmt->fetchAll();;

        $stmt = $this->db->prepare('SELECT * FROM relation LEFT JOIN relation_type
                                    USING (id_relation_type)
                                    LEFT JOIN person ON relation.id_person1 = person.id_person
                                    WHERE relation.id_person2 = :id_person');
        $stmt->bindValue(":id_person", $args['id_person']);
        $stmt->execute();
        $tplVars["person2"] = $stmt->fetchAll();;

        //ADD RELATION
        $stmt = $this->db->prepare('SELECT * FROM person
                                    WHERE NOT id_person = :id_person
                                    ORDER BY first_name');
        $stmt->bindValue(':id_person', $args['id_person']);
        $stmt->execute();
        $tplVars['addRelation'] = $stmt->fetchAll();;

        $stmt = $this->db->query('SELECT * FROM relation_type');
        $stmt->execute();
        $tplVars['relation_type'] = $stmt->fetchAll();

        //HAVE A MEETING
        $stmt = $this->db->prepare('SELECT person.id_person, meeting.id_meeting, meeting.start, meeting.duration, 
                                    location.city, location.name FROM person LEFT JOIN person_meeting 
                                    ON person.id_person = person_meeting.id_person   
                                    LEFT JOIN meeting ON person_meeting.id_meeting = meeting.id_meeting   
                                    LEFT JOIN location ON meeting.id_location = location.id_location 
                                    WHERE person.id_person = :id_person ORDER BY meeting.start');
        $stmt->bindValue(':id_person', $args['id_person']);
        $stmt->execute();
        $tplVars['member'] = $stmt->fetchAll();

        if (empty($tplVars['formData'])) {
            exit('person not found');
        } else {
            $tplVars['header'] = 'Edit person';
            return $this->view->render($response, 'editPerson.latte', $tplVars);
        }
    }
})->setName('editPerson');


/* EDIT PERSON */
$app->post('/person/{id_person}/editPerson', function (Request $request, Response $response, $args) {
    $formData = $request->getParsedBody();
    $tplVars = [];
    if ( empty($formData['first_name']) || empty($formData['last_name']) || empty($formData['nickname']) ) {
        $tplVars['message'] = 'Please fill required fields';
    } else {
        try {

            //CHEKING ADDRESSES
            if (!empty($formData['street_name']) || !empty($formData['street_number']) || !empty($formData['city'])
                || !empty($formData['zip']) || !empty($formData['country']) || !empty($formData['name']) ||
                !empty($formData['latitude']) || !empty($formData['longitude'])) {
                $stmt = $this->db->prepare('SELECT id_location FROM person WHERE id_person = :id_person');
                $stmt->bindValue(':id_person', $args['id_person']);
                $stmt->execute();
                $id_location = $stmt->fetch()['id_location'];
                if ($id_location) {
                    ## The person has an address
                    editLocation($this, $id_location, $formData);
                } else {
                    ## Person has no address
                    $id_location = newLocation($this, $formData);
                }
            }

            //ADD CONTACT
            if (!empty($formData['contact']) || !empty($formData['id_contact_Type'])){
                $id_contact = newContact($this, $formData);
            }

            //ADD RELATION
            if (!empty($formData['id_person2']) || !empty($formData['id_relation_type'])){
                $id_relation = newRelation($this, $formData);
            }

            //DELETE CONTACT
            if (!empty($formData['deleteContact'])){
                $id_contact = deleteContact($this, $formData);
            }

            //DELETE RELATION
            if (!empty($formData['deleteRelation'])){
                $id_relation = deleteRelation($this, $formData);
            }

            //DELETE FROM MEETING
            if (!empty($formData['deleteFromMeeting'])){
                $stmt = $this->db->prepare('DELETE FROM person_meeting WHERE id_meeting = :deleteFromMeeting AND
                                            id_person = :id_person');
                $stmt->bindValue(':deleteFromMeeting', ($formData['deleteFromMeeting']));
                $stmt->bindValue(':id_person', ($formData['id_person']));
                $stmt->execute();
            }

            $stmt = $this->db->prepare('UPDATE person SET 
                                        first_name = :first_name, last_name = :last_name,
                                        nickname = :nickname, birth_day = :birth_day,
                                        gender = :gender, height = :height,
                                        id_location = :id_location
                                        WHERE id_person = :id_person');
            $stmt->bindValue(':nickname', $formData['nickname']);
            $stmt->bindValue(':first_name', $formData['first_name']);
            $stmt->bindValue(':last_name', $formData['last_name']);
            $stmt->bindValue(':gender', empty($formData['gender']) ? null : $formData['gender'] );
            $stmt->bindValue(':birth_day', empty($formData['birth_day']) ? null : $formData['birth_day']);
            $stmt->bindValue(':height', empty($formData['height']) ? null : $formData['height']);
            $stmt->bindValue(':id_location',  $id_location ? $id_location : null);
            $stmt->bindValue(':id_person', $args['id_person']);
            $stmt->execute();
            $tplVars['message'] = 'Person succesfully updated';

        } catch (PDOexception $e) {
            $tplVars['message'] = 'Error';
            $this->logger->error($e->getMessage());
        }
    }

    return $response->withHeader('Location', $this->router->pathFor('persons'));
});