<?php
// MYSQL database creation/assignment
// this code used in conjunction with userLogin.php and userPage.php
$hn = "localhost";
$un = 'root';
$pw = '';
$db='dictionaryDB';
$table1 = 'credentials';
$table2 = 'user_files';


    function create_database($hn, $un, $pw, $db, $table1, $table2) {
        $conn = new mysqli($hn, $un, $pw);
        if ($conn->connect_error) die(mysql_error());

        $query = "CREATE DATABASE IF NOT EXISTS $db";
        $conn->query($query);
        if ($conn->error) die(mysql_error());

        $query = "USE $db";
        $conn->query($query);
        if ($conn->error) die(mysql_error());

        $query = "CREATE TABLE IF NOT EXISTS $table1 (" .
                "email VARCHAR(64) NOT NULL, " .
                "username VARCHAR(64) NOT NULL, " .
                "password CHAR(60) NOT NULL)";
        $conn->query($query);
        if ($conn->error) die(mysql_error());

        $query = "CREATE TABLE IF NOT EXISTS $table2 (" .
                "user VARCHAR(64) NOT NULL, " .
                "fileName VARCHAR(64) NOT NULL, " .
                "fileContent LONGBLOB NOT NULL)";
        $conn->query($query);
        if ($conn->error) die(mysql_error());


        $conn->close();
    }

?>
