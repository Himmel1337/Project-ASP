<?php
function newRelation($app, $formData){
    $stmt = $app->db->prepare('INSERT INTO relation(id_person1, id_person2, description, id_relation_type) 
							    VALUES (:id_person1, :id_person2, :description, :id_relation_type) RETURNING id_relation');
    $stmt->bindValue(':id_person1', empty($formData['id_person1']) ? null : $formData['id_person1']);
    $stmt->bindValue(':id_person2', empty($formData['id_person2']) ? null : $formData['id_person2']);
    $stmt->bindValue(':description', empty($formData['description']) ? null : $formData['description']);
    $stmt->bindValue(':id_relation_type', empty($formData['id_relation_type']) ? null : $formData['id_relation_type']);
    $stmt->execute();
    return $stmt->fetch()['id_relation'];
}

function deleteRelation($app, $formData) {
    $stmt = $app->db->prepare('DELETE FROM relation WHERE id_relation = :deleteRelation');
    $stmt->bindValue(':deleteRelation', ($formData['deleteRelation']));
    $stmt->execute();
    return $stmt->fetch()['deleteRelation'];
}