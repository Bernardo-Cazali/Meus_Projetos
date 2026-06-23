<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "eletrodomesticos";

$conn = new mysqli($host, $usuario, $senha, $banco);

if($conn->connect_error)
{
    die("Erro na conexão");
}

?>