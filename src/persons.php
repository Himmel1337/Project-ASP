<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/* LIST PERSON */
$app->get('/persons', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();
    if (empty($params['limit'])){
        $params ['limit'] = 10;
    };

    if (empty($params['page'])){
        $params ['page'] = 0;
    };

    $stmt = $this->db->query('SELECT count(*) count_person FROM person');
    $total_pages = $stmt->fetch()['count_person'] / $params['limit'];

    $stmt = $this->db->prepare('SELECT id_person, first_name, last_name, nickname, city 
                                FROM person LEFT JOIN location USING (id_location)
                                WHERE person.id_person = id_person
                                ORDER BY first_name LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $params['limit']);
    $stmt->bindValue(':offset', $params['page'] * $params['limit']);
    $stmt->execute();
    $tplVars = [
        'persons_list' => $stmt->fetchall(),
        'total_pages' => $total_pages,
        'page' => $params['page'],
        'limit' => $params['limit']
    ];
    return $this->view->render($response, 'persons.latte', $tplVars);
})->setName('persons');

/* SEARCH PERSON */
$app->get('/person/search', function(Request $request, Response $response, $args) {
    $queryParams = $request->getQueryParams();
    if (! empty($queryParams) && ! empty($queryParams['query'])) {
        $stmt = $this->db->prepare('SELECT id_person, first_name, last_name, nickname, city 
                                    FROM person LEFT JOIN location USING (id_location)
                                    WHERE lower(first_name) = lower(:first_name) OR 
                                    lower(last_name) = lower(:last_name) OR lower(nickname) = lower(:nickname) OR 
                                    lower(city) = lower(:city) ORDER BY first_name');
        $stmt->bindParam(':first_name', $queryParams['query']);
        $stmt->bindParam(':last_name', $queryParams['query']);
        $stmt->bindParam(':nickname', $queryParams['query']);
        $stmt->bindParam(':city', $queryParams['query']);
        $stmt->execute();
        $tplVars['persons_list'] = $stmt->fetchall();
        return $this->view->render($response, 'persons.latte', $tplVars);
    }
})->setName('search');

/* DETAILS PERSON */
$app->get('/person/{id_person}/details', function (Request $request, Response $response, $args) {
    if (! empty($args['id_person'])) {
        $stmt = $this->db->prepare('SELECT first_name, last_name, nickname, gender, 
                                    height, birth_day, id_location FROM person 
                                    LEFT JOIN location USING (id_location) 
                                    WHERE id_person = :id_person');
        $stmt->bindValue(':id_person', $args['id_person']);
        $stmt->execute();
        $tplVars['formData'] = $stmt->fetch();

        //CONTACT
        $stmt = $this->db->prepare('SELECT contact, name FROM contact
                                    JOIN contact_type USING (id_contact_type)
                                    WHERE contact.id_person = :id_person');
        $stmt->bindValue(":id_person", $args['id_person']);
        $stmt->execute();
        $tplVars['contact'] = $stmt->fetchAll();

        //RELATION
        $stmt = $this->db->prepare('SELECT * FROM relation LEFT JOIN relation_type
                                    USING (id_relation_type) LEFT JOIN person  
                                    ON relation.id_person2 = person.id_person
                                    WHERE relation.id_person1 = :id_person');
        $stmt->bindValue(":id_person", $args['id_person']);
        $stmt->execute();
        $person1 = $stmt->fetchAll();
        $tplVars["person1"] = $person1;

        $stmt = $this->db->prepare('SELECT * FROM relation LEFT JOIN relation_type
                                    USING (id_relation_type) LEFT JOIN person 
                                    ON relation.id_person1 = person.id_person
                                    WHERE relation.id_person2 = :id_person');
        $stmt->bindValue(":id_person", $args['id_person']);
        $stmt->execute();
        $person2 = $stmt->fetchAll();
        $tplVars["person2"] = $person2;

        //MEETING
        $stmt = $this->db->prepare('SELECT person.id_person, meeting.start, meeting.duration, location.city, 
                                    location.name FROM person LEFT JOIN person_meeting 
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
            $tplVars['header'] = 'Details Person';
            return $this->view->render($response, 'detailsPerson.latte', $tplVars);
        }
    }
})->setName('showDetails');


/* DELETE PERSON */
$app->get('/person/{id_person}/deletePerson', function (Request $request, Response $response, $args) {
    if(!empty($args['id_person'])){
        try{
            $stmt = $this->db->prepare('DELETE FROM contact WHERE id_person = :id_person');
            $stmt->bindValue(':id_person', $args['id_person']);
            $stmt->execute();

            $stmt = $this->db->prepare('DELETE FROM person_meeting WHERE id_person = :id_person');
            $stmt->bindValue(':id_person', $args['id_person']);
            $stmt->execute();

            $stmt = $this->db->prepare('DELETE FROM relation WHERE id_person1 = :id_person Or id_person2 = :id_person');
            $stmt->bindValue(':id_person', $args['id_person']);
            $stmt->bindValue(':id_person', $args['id_person']);
            $stmt->execute();

            $stmt = $this->db->prepare('DELETE FROM person WHERE id_person = :id_person');
            $stmt->bindValue(':id_person', $args['id_person']);
            $stmt->execute();

        } catch (PDOException $e){
            $this->logger->error($e->getMessage());
        }
    } else {
        exit('id person is missing');
    }
    return $response->withHeader('Location', $this->router->pathFor('persons'));
})->setName('deletePerson');




