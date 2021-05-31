<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


/* LIST MEETINGS */
$app->get('/meetings', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();
    if (empty($params['limit'])){
        $params ['limit'] = 10;
    };

    if (empty($params['page'])){
        $params ['page'] = 0;
    };

    $stmt = $this->db->query('SELECT count(*) count_meeting FROM meeting');
    $total_pages = $stmt->fetch()['count_meeting'] / $params['limit'];

    $stmt = $this->db->prepare('SELECT meeting.*, location.name, location.city
                                FROM meeting LEFT JOIN location USING (id_location)
                                WHERE meeting.id_meeting = id_meeting
                                ORDER BY start LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $params['limit']);
    $stmt->bindValue(':offset', $params['page'] * $params['limit']);
    $stmt->execute();
    $tplVars = [
        'meetings_list' => $stmt->fetchall(),
        'total_pages' => $total_pages,
        'page' => $params['page'],
        'limit' => $params['limit']
    ];
    return $this->view->render($response, 'meetings.latte', $tplVars);
})->setName('meetings');


/* SEARCH MEETING */
$app->get('/meeting/searchMeeting', function(Request $request, Response $response, $args) {
    $queryParams = $request->getQueryParams();
    if (! empty($queryParams) && ! empty($queryParams['query'])) {
        $stmt = $this->db->prepare('SELECT id_meeting, city, name, description FROM meeting 
                                    LEFT JOIN location USING (id_location)
                                    WHERE lower(city) = lower(:city) OR lower(name) = lower(:name) OR 
                                    lower(description) = lower(:description)');
        $stmt->bindParam(':city', $queryParams['query']);
        $stmt->bindParam(':name', $queryParams['query']);
        $stmt->bindParam(':description', $queryParams['query']);
        $stmt->execute();
        $tplVars['meeting_list'] = $stmt->fetchall();
        return $this->view->render($response, 'meetings.latte', $tplVars);
    }
})->setName('searchMeeting');


/* DETAILS MEETING */
$app->get('/meeting/{id_meeting}/detailsMeeting', function (Request $request, Response $response, $args) {
    if (! empty($args['id_meeting'])) {
        $stmt = $this->db->prepare('SELECT * FROM meeting 
                                LEFT JOIN location USING (id_location) 
                                WHERE id_meeting = :id_meeting');
        $stmt->bindValue(':id_meeting', $args['id_meeting']);
        $stmt->execute();
        $tplVars['formData'] = $stmt->fetch();

        //MEMBERS
        $stmt = $this->db->prepare('SELECT person.* FROM person WHERE person.id_person IN
                                    (SELECT person_meeting.id_person FROM meeting LEFT JOIN
                                    person_meeting USING (id_meeting) WHERE id_meeting = :id_meeting)
                                    ORDER BY first_name');
        $stmt->bindValue(':id_meeting', $args['id_meeting']);
        $stmt->execute();
        $tplVars['member'] = $stmt->fetchAll();


        if (empty($tplVars['formData'])) {
            exit('Meeting not found');
        } else {
            $tplVars['header'] = 'Details Meeting';
            return $this->view->render($response, 'detailsMeeting.latte', $tplVars);
        }
    }
})->setName('detailsMeeting');



/* DELETE MEETING */
$app->get('/meeting/{id_meeting}/deleteMeeting', function (Request $request, Response $response, $args) {
    if(!empty($args['id_meeting'])){
        try{
            $stmt = $this->db->prepare('DELETE FROM person_meeting WHERE id_meeting = :id_meeting');
            $stmt->bindValue(':id_meeting', $args['id_meeting']);
            $stmt->execute();

            $stmt = $this->db->prepare('DELETE FROM meeting WHERE id_meeting = :id_meeting');
            $stmt->bindValue(':id_meeting', $args['id_meeting']);
            $stmt->execute();

        } catch (PDOException $e){
            $this->logger->error($e->getMessage());

        }
    } else {
        exit('id meeting is missing');
    }
    return $response->withHeader('Location', $this->router->pathFor('meetings'));
})->setName('deleteMeeting');



