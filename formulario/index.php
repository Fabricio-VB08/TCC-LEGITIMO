<?php 
require "./mysql.php";

if ($_SERVER['REQUEST_METHOD'] =="POST"){
    $id_aula = $_POST["id_aula"];

    $sql = "INSERT INTO aulas (id_aula)
            VALUES (?)";
    
    if ($stmt->execute()) {
        echo "Aula inserida com sucesso!";
    } else {
        echo "Erro ao inserir aula:" . $stmt->error;
    }

    $stmt->close();

}
 
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

</head>
<body>
    <div class="retan">
        <form>
            <label>Nome do professor: <input></label>
        </form>
<!-- Materias -->
 <div class="porra">
    <div id="materias-container">

    </div>

</div>

<div class="materias-container"></div>


 <!--  -->



    <table >
        <tr class="tabela">
        <th></th>
        <th>Segunda</th>
        <th>Terça  </th>
        <th>Quarta </th>
        <th>Quinta </th>
        <th>Sexta  </th>
        <th>Sábado </th>
        <th>Domingo</th>
        </tr>
        <tr class="vaz">
            <th>Selecione aqui</th>
            <th><label class=" cb-container"><input type="checkbox"><span class="novaCaixa"></span></label></th>
            <th><label class=" cb-container"><input type="checkbox"><span class="novaCaixa"></span></label></th>
            <th><label class=" cb-container"><input type="checkbox"><span class="novaCaixa"></span></label></th>
            <th><label class=" cb-container"><input type="checkbox"><span class="novaCaixa"></span></label></th>
            <th><label class=" cb-container"><input type="checkbox"><span class="novaCaixa"></span></label></th>
            <th><label class=" cb-container"><input type="checkbox"><span class="novaCaixa"></span></label></th>
            <th><label class=" cb-container"><input type="checkbox"><span class="novaCaixa"></span></label></th>
        </tr>

    </table>
    <link rel="stylesheet" href="index.css">
<!--FORMULARIO SUPER FODAO-->
<form action="index.php" method="POST">
    <input id="aula" placeholder="Adicione uma nova materia" required>

</br>
</br>
    <button onclick="add_materia()">Adicionar</button>
</form>

    <button class="botaoazul">CADASTRAR PROFESSOR</button>
    </div>
    <script src="index.js"></script>
<!---->

<!-- QUADRADO COM X <label class=" cb-container"><input type="checkbox"><span class="novaCaixa"></span></label> -->
    
</body>
</html>

