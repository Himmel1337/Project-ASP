# APV project

## Links
- [Website of course and walkthrough](http://akela.mendelu.cz/~lysek/tmwa/)
- [Slim framework docs](https://www.slimframework.com/docs/)

## Assignment
Create a web application for recording persons and contacts. The main goal of
the application is to record persons (friend, acquaintances), their addresses,
relationships and meetings. Each person can have a name, nickname, age, location
and contacts. Each person can have any number of contacts (mobile, Skype,
Jabber, ….). A person can have more contacts of the same type (e.g. two emails).
Each person can have any number of relationships with other persons in the
database. Each relationship should be of a type (friend, fiend, acquaintance,
spouse, …) and description. The contact and relationship types are recorded in
the database and can be modified by the end-user. The application also records
meetings between persons. Each meeting can be joined by any number of persons.
Each meeting should have a place and date. The application must allow user
friendly entering and modifying the data. Take advantage of the proposed schema,
create a database and implement the entire application.

## Installation
- Copy sources to a machine with PHP and Composer
- Copy `/.env.example` file to `/.env`. Insert database credentials into it.
- Make `/cache` folder writeable (`chmod 0777 cache`).
- Make `/logs` folder writable too (`chmod 0777 logs`).
- Install project dependencies using `composer install` command.

## Docker
To run project in [Docker](https://www.docker.com/) type `docker-compose up` command
in projet root folder. Docker should open two ports on your machine:

- http://localhost:8080 for your project
- http://localhost:8081 for Adminer

DB connection inside Docker:

- hostname: postgres
- user: postgres
- pass: docker
- database name: db

### First run:
You have to import DB structure using following command:
`docker-compose exec postgres bash /tmp/docker/import.sh`