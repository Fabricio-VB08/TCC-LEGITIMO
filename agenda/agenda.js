function generateWeeklyCalendar() {
    const month = parseInt(document.getElementById('month').value);
    const year = parseInt(document.getElementById('year').value);
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Ajuste do primeiro dia: getDay() retorna domingo=0, segunda=1 ...
    // Nosso calendário começa na segunda-feira, então vamos fazer shift
    let firstDay = new Date(year, month, 1).getDay(); 
    // Converter domingo(0) para 7 para facilitar o cálculo
    firstDay = firstDay === 0 ? 7 : firstDay;
    // Ajustar para índice 0 (segunda = 0, domingo = 6)
    firstDay = firstDay - 1;

    let weeks = [];
    let currentDay = 1;

    // Enquanto houver dias no mês para preencher
    while (currentDay <= daysInMonth) {
        let week = {
            morning: ["", "", "", "", "", "", ""],
            afternoon: ["", "", "", "", "", "", ""],
            night: ["", "", "", "", "", "", ""]
        };

        // Preenche os dias da semana
        for (let i = 0; i < 7; i++) {
            // Na primeira semana, só começa a preencher do primeiro dia válido
            if (weeks.length === 0 && i < firstDay) {
                // deixa vazio antes do primeiro dia
                continue;
            }
            if (currentDay <= daysInMonth) {
                // Preenche as 3 linhas com o mesmo número do dia
                week.morning[i] = currentDay;
                week.afternoon[i] = currentDay;
                week.night[i] = currentDay;
                currentDay++;
            }
        }
        weeks.push(week);
    }

    // Criar o HTML para cada tabela da semana
    let calendarHTML = "";
    weeks.forEach((week, index) => {
        calendarHTML += `<table class="week-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Segunda</th>
                    <th>Terça</th>
                    <th>Quarta</th>
                    <th>Quinta</th>
                    <th>Sexta</th>
                    <th>Sábado</th>
                    <th>Domingo</th>
                </tr>
            </thead>
            <tbody>
                <tr><th>Manhã</th>
                    ${week.morning.map(day => `<td data-day="${day || ""}"></td>`).join("")}

                </tr>
                <tr><th>Tarde</th>
                    ${week.afternoon.map(day => `<td data-day="${day || ""}"></td>`).join("")}

                </tr>
                <tr><th>Noite</th>
                    ${week.night.map(day => `<td data-day="${day || ""}"></td>`).join("")}

                </tr>
            </tbody>
        </table>`;
    });

    document.getElementById('weekly-calendar-container').innerHTML = calendarHTML;
}

window.onload = generateWeeklyCalendar;