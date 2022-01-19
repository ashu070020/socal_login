<?php

function OpenCon(){
    $dbname  = "social_login";
    $dbuser  = "root";
    $dbpass  = "";
    $dbhost  = "localhost";

    $conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or die("Connect failed: %s\n". $conn -> error);
    return $conn;
}

OpenCon();

function CloseCon($conn){
    $conn -> close();
}