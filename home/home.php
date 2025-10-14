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
        <a href="/CalenafrontGABAS/cadastroElogin/crud_usuarios.php">Gerenciar Usuários</a>
        <a href="/CalenafrontGABAS/cadastroElogin/login.php">Sair</a>    
    </div>


   </div>

<!--  -->

    <div class="bodyartificial">
        <div class="container-botao">
            <p class="bemvindo">Bem vindo!</p>
            <div class="horizonte-botao">
            <button class="botao-picao"  onclick="window.location.href = '/CalenafrontGABAS/agenda/agenda.php'">
                <img class="icone-botao" src="/CalenafrontGABAS/home/icones/iconcalendario.png">
                <h3>Ver/Editar Calendário</h3>
            </button>

            <button class="botao-picao" onclick="window.location.href = '/CalenafrontGABAS/formulario/index.php'">
                <img class="icone-botao" src="/CalenafrontGABAS/home/icones/iconprofessores.png">
                <h3>Ver/Cadastrar Professores</h3>
            </button>

            <button class="botao-picao"  onclick="window.location.href = '' ">
                <a href=""></a>
                <img class="icone-botao" src="/CalenafrontGABAS/home/icones/iconmaterias.png">
                <h3>Ver/Adicionar Matérias</h3>
            </button>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="home.css">
