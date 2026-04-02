<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$conn = new mysqli('localhost', 'root', 'lima-^123', 'sistema-partum');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$res = $conn->query("SHOW COLUMNS FROM manutencoes");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " ";
}
?>
