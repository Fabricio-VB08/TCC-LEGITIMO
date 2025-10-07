<?php
$host = "localhost";   // servidor
$user = "root";        // usuário do MySQL
$pass = "";            // senha do MySQL (coloque a sua)
$db   = "test";        // nome do banco

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
