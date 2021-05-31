<?php

function newLocation($app, $formData) {
    $stmt = $app->db->prepare('INSERT INTO location 
    (city, street_name, street_number, zip, country, name, latitude, longitude) 
	VALUES (:city, :street_name, :street_number, :zip, :country, :name, :latitude, :longitude) RETURNING id_location');
    $stmt->bindValue(':city', empty($formData['city']) ? null : $formData['city']  );
    $stmt->bindValue(':street_name', empty($formData['street_name']) ? null : $formData['street_name']  );
    $stmt->bindValue(':street_number', empty($formData['street_number']) ? null : $formData['street_number']  );
    $stmt->bindValue(':zip', empty($formData['zip']) ? null : $formData['zip']  );
    $stmt->bindValue(':country', empty($formData['country']) ? null : $formData['country']  );
    $stmt->bindValue(':name', empty($formData['name']) ? null : $formData['name']  );
    $stmt->bindValue(':latitude', empty($formData['latitude']) ? null : $formData['latitude']  );
    $stmt->bindValue(':longitude', empty($formData['longitude']) ? null : $formData['longitude']  );
    $stmt->execute();
    return $stmt->fetch()['id_location'];
}

function editLocation($app, $id_location, $formData) {
    $stmt = $app->db->prepare('UPDATE location SET street_name = :street_name, street_number = :street_number, 
                                city = :city,  zip = :zip, country = :country, name = :name, latitude = :latitude, 
                                longitude = :longitude WHERE id_location = :id_location');
    $stmt->bindValue(':street_name', empty($formData['street_name']) ? null : $formData['street_name']);
    $stmt->bindValue(':street_number', empty($formData['street_number']) ? null : $formData['street_number']);
    $stmt->bindValue(':zip', empty($formData['zip']) ? null : $formData['zip']);
    $stmt->bindValue(':city', empty($formData['city']) ? null : $formData['city']);
    $stmt->bindValue(':country', empty($formData['country']) ? null : $formData['country']);
    $stmt->bindValue(':name', empty($formData['name']) ? null : $formData['name']);
    $stmt->bindValue(':latitude', empty($formData['latitude']) ? null : $formData['latitude']);
    $stmt->bindValue(':longitude', empty($formData['longitude']) ? null : $formData['longitude']);
    $stmt->bindValue(':id_location', $id_location);
    $stmt->execute();
    return True;
}