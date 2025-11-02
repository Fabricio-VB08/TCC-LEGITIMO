function GerarCalendário() {
    const mes = parseInt(document.getElementById("mes").value);
    const ano = new Date().getFullYear();
    const container = document.getElementById("calendario-semanal");
    container.innerHTML = "";

    const diaSemana = ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"];
    const primdia = new Date(ano, mes, 1);
    const ultimodia = new Date(ano, mes + 1);

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
        dia.textContent = `${diaSemana[dataAtual.getDay()]} ${dataAtual.getDate()}/${mes + 1}`;
        semana.appendChild(dia);

        dataAtual.setDate(dataAtual.getDate() + 1);
    }

    if (semana.children.length > 0) {
        container.appendChild(semana);
    }
}

document.getElementById("gerar").addEventListener("click", GerarCalendário);
