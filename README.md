# A small & simple ORM solution for PHP

_Written by Michael Gill for EntityObjects.com  (http://EntityObjects.com/)_

_Licensed under the MIT license: http://opensource.org/licenses/MIT_

## Installation  and Usage

Linux or Mac OS users should place the file buildModel in their PATH and make sure it
is an executable.

Windows users can invoke buildModel by typing "php buildModel buildModel.json" where
buildModel.json contains the configuration of the database you would like to model.

Linux or Mac OS users should invoke using "buildModel buildModel.json"

the following example shows the structure of buildModel.json:

{
    "dbname":      "exampleDB",
    "dbserver":    "127.0.0.1",
    "dbport":      "3306",
    "dbdriver":    "mysql",
    "dbuser":      "dbuser",
    "dbpassword":  "dbpassword",
    "scriptdir":   "./",
    "modeldir":    "./model/",
    "dbbaseclass": "ExampleDB"
}

dbname     => the database schema name
dbserver   => the ip address of the database
dbport     => the tcp port number for the database
dbdriver   => the PDO driver to use (currently only mysql is supported)
dbuser     => a valid database username
dbpassword => a valid password
scriptdir  => the full path of directory contaaining writeDB.php and writeModel.php
modeldir   => the directory where buildModel put the PHP class files
dbbaseclass=> the name of the PHP base class for model classes

## Notes for proper database design

1) all tables should have ONE auto increment primary key (the model builder will
   othrewise assume it's a many2many table)

2) create views for queries that join multiple tables/fields. The model builder
  will create a class for each view in the database -- the use of views allows
  you to have a PHP model that requires no programming

## Class files created

DBObject.php       => base class for value objects of tables and view sets 
DBConstants.php    => constants including database DSN (Database Source Name)
ExampleDB.php      => the base class for exampleDB access.
Tablename.php      => value objects for each table and view in the database
TablenameModel.php => entity objects for each table and view in the database

