<?php
session_start();
// Se não estiver logado, redireciona para o login
if (!isset($_SESSION['id_usuario'])) { header('Location: /TCC-LEGITIMO/cadastroElogin/login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início</title>
</head>
<body>

<!--NAVBAR  -->
    <div class="navbar">
    <div class="esquerda"></div>

   
    <div class="direita">
    <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 'administrador'): ?>
        <a href="/TCC-LEGITIMO/cadastroElogin/crud_usuarios.php">Gerenciar Usuários</a>
        
        <?php endif; ?>
        <a href="/TCC-LEGITIMO/cadastroElogin/login.php">Sair</a>    
    </div>


   </div>

<!--  -->

    <div class="bodyartificial">
        <div class="container-botao">
            <p class="bemvindo">Bem vindo!</p>
            <div class="horizonte-botao">
            <button class="botao-picao"  onclick="window.location.href = '/TCC-LEGITIMO/agenda/agenda.php'">
                <img class="icone-botao" src="/TCC-LEGITIMO/home/icones/iconcalendario.png">
                <h3>Ver/Editar Calendário</h3>
            </button>

            <button class="botao-picao" onclick="window.location.href = '/TCC-LEGITIMO/formulario/index.php'">
                <img class="icone-botao" src="/TCC-LEGITIMO/home/icones/iconprofessores.png">
                <h3>Ver/Cadastrar Professores</h3>
            </button>

            <button class="botao-picao"  onclick="window.location.href = '' ">
                <a href=""></a>
                <img class="icone-botao" src="/TCC-LEGITIMO/home/icones/iconmaterias.png">
                <h3>Ver/Adicionar Matérias</h3>
            </button>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="home.css">
