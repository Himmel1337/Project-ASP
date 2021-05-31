<?php
function newContact($app, $formData) {
    $stmt = $app->db->prepare('INSERT INTO contact(id_person, id_contact_type, contact)
								VALUES (:id_person, :id_contact_type, :contact) RETURNING id_contact');
    $stmt->bindValue(':id_person', empty($formData['id_person']) ? null : $formData['id_person']);
    $stmt->bindValue(':id_contact_type', empty($formData['id_contact_type']) ? null : $formData['id_contact_type']);
    $stmt->bindValue(':contact', empty($formData['contact']) ? null : $formData['contact']);
    $stmt->execute();
    return $stmt->fetch()['id_contact'];
}

function deleteContact($app, $formData) {
    $stmt = $app->db->prepare('DELETE FROM contact WHERE id_contact = :deleteContact');
    $stmt->bindValue(':deleteContact', ($formData['deleteContact']));
    $stmt->execute();
    return $stmt->fetch()['deleteContact'];
}