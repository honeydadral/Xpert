<?php

DEFINE ('DB_USER', 'c4054234');
DEFINE ('DB_PASSWORD', 'Kj9mPqRtY2nW');
DEFINE ('DB_HOST', 'localhost');
DEFINE ('DB_NAME', 'c4054234_db2');

// Create connection
$dbc = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if ($dbc->connect_error) {
    error_log("Connection failed: " . $dbc->connect_error);
    die("An error occurred connecting to the database. Please check the server logs or contact support.");
}

$dbc -> set_charset("utf8");
?>

