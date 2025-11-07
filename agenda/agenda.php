<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário</title>
    <link rel="stylesheet" href="agenda.css">
</head>
<body>
    <h1 id="calendario">CALENDÁRIO</h1>
    <div id="controle-calendario">
        <select id="mes">

            <option value="0">Janeiro</option>
            <option value="1">Fevereiro</option>
            <option value="2">Março</option>
            <option value="3">Abril</option>
            <option value="4">Maio</option>
            <option value="5">Junho</option>
            <option value="6">Julho</option>
            <option value="7">Agosto</option>
            <option value="8">Setembro</option>
            <option value="9">Outubro</option>
            <option value="10">Novembro</option>
            <option value="11">Dezembro</option>

        </select>

        <button id="gerar">Gerar Calendário</button>
        <button id="filtrar">Filtrar Professor</button>
<div id="wrapper">
    <div id="calendario-semanal"></div>
</div>
    <script src="agenda.js"></script>
</body>
</html>
