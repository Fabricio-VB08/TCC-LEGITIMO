
const eventos = [
  { dia: 5, mes: 0, professor: "João" },
  { dia: 12, mes: 0, professor: "Maria" },
  { dia: 19, mes: 11, professor: "Gabriel" },
  { dia: 25, mes: 0, professor: "Carlos" }
];

function GerarCalendário() {
    const mes = parseInt(document.getElementById("mes").value);
    const ano = new Date().getFullYear();
    const container = document.getElementById("calendario-semanal");
    container.innerHTML = "";

    const diaSemana = ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"];
    const primdia = new Date(ano, mes, 1);
    const ultimodia = new Date(ano, mes + 1, 0);

    let dataAtual = new Date(primdia);

    let semana = document.createElement("div");
    semana.classList.add("semana");

    for (let i = 0; i < dataAtual.getDay(); i++) {
        const vazio = document.createElement("div");
        vazio.classList.add("dia");
        vazio.textContent = "";
        semana.appendChild(vazio);
    }

    while (dataAtual <= ultimodia) {
        if (semana.children.length === 7) {
            container.appendChild(semana);
            semana = document.createElement("div");
            semana.classList.add("semana");
        }

        const dia = document.createElement("div");
        dia.classList.add("dia");
        dia.textContent = dataAtual.getDate();
        semana.appendChild(dia);

        dataAtual.setDate(dataAtual.getDate() + 1);
    }

    if (semana.children.length > 0) {
        container.appendChild(semana);
    }
}

document.getElementById("gerar").addEventListener("click", GerarCalendário);

function Filtrar() {

    const nome = prompt("Digite o nome do professor");
    const messelecionado = parseInt(document.getElementById("mes").value);

    const dias = document.querySelectorAll(".dia");

    dias.forEach(dia => {

        const numero = parseInt(dia.textContent);
        const evento = eventos.find(e => e.dia === numero && e.mes === messelecionado && e.professor === nome);
        
         if (evento) {
      dia.style.backgroundColor = "#28a745"; // verde para destacar
      dia.style.color = "white";
    } else {
      dia.style.backgroundColor = "rgba(0, 0, 0, 0.3)";
      dia.style.color = "#c2c4c9";
    }
    });
}

document.getElementById("filtrar").addEventListener("click", Filtrar);
