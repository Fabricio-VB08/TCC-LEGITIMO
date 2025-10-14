const professores = [
    { id: 1, name: "Fafafofo" },
    { id: 2, name: "Gabsgubos" },
    { id: 3, name: "Dudubumbum" },
  ]
  
  let selectedProfessorForAllocation = null
  let filteredProfessor = null
  const allocations = JSON.parse(localStorage.getItem("allocations")) || {}
  
  document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("professor-search")
  
    searchInput.addEventListener("input", (e) => {
      const query = e.target.value.trim()
      searchProfessors(query)
    })
  
    // Close search results when clicking outside
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".professor-search-container")) {
        document.getElementById("search-results").innerHTML = ""
      }
    })
  })
  
  function searchProfessors(query) {
    const resultsContainer = document.getElementById("search-results")
  
    if (!query) {
      resultsContainer.innerHTML = ""
      return
    }
  
    const filtered = professores.filter((professor) => professor.name.toLowerCase().includes(query.toLowerCase()))
  
    if (filtered.length === 0) {
      resultsContainer.innerHTML = '<div style="padding: 12px; color: #999;">Nenhum professor encontrado</div>'
      return
    }
  
    resultsContainer.innerHTML = filtered
      .map(
        (professor) => `
          <div class="search-result-item">
              <span>${professor.name}</span>
              <div class="search-result-actions">
                  <button class="action-btn allocate-btn" onclick="selectProfessorForAllocation(${professor.id})">
                      Alocar
                  </button>
                  <button class="action-btn filter-btn" onclick="filterByProfessor(${professor.id})">
                      Filtrar
                  </button>
              </div>
          </div>
      `,
      )
      .join("")
  }
  
  function selectProfessorForAllocation(professorId) {
    const professor = professores.find((p) => p.id === professorId)
    selectedProfessorForAllocation = professor
  
    document.getElementById("professor-search").value = ""
    document.getElementById("search-results").innerHTML = ""
  
    const selectedDiv = document.getElementById("selected-professor")
    document.getElementById("professor-name").textContent =
      `Selecionado: ${professor.name} (clique em uma célula para alocar)`
    selectedDiv.style.display = "flex"
  
    console.log("[v0] Professor selecionado para alocação:", professor)
  }
  
  function clearSelection() {
    selectedProfessorForAllocation = null
    document.getElementById("selected-professor").style.display = "none"
  }
  
  function filterByProfessor(professorId) {
    const professor = professores.find((p) => p.id === professorId)
    filteredProfessor = professor
  
    document.getElementById("professor-search").value = ""
    document.getElementById("search-results").innerHTML = ""
  
    const filterInfo = document.getElementById("filter-info")
    document.getElementById("filtered-professor-name").textContent = professor.name
    filterInfo.style.display = "flex"
  
    generateWeeklyCalendar()
    console.log("[v0] Filtrando por professor:", professor)
  }
  
  function clearFilter() {
    filteredProfessor = null
    document.getElementById("filter-info").style.display = "none"
    generateWeeklyCalendar()
  }
  
  function getAllocationKey(month, year, day, period) {
    return `${year}-${month}-${day}-${period}`
  }
  
  function allocateProfessor(month, year, day, period) {
    if (!selectedProfessorForAllocation) {
      alert("Selecione um professor primeiro!")
      return
    }
  
    const key = getAllocationKey(month, year, day, period)
  
    if (!allocations[key]) {
      allocations[key] = []
    }
  
    // Check if professor is already allocated
    const alreadyAllocated = allocations[key].some((alloc) => alloc.professorId === selectedProfessorForAllocation.id)
  
    if (alreadyAllocated) {
      alert(`${selectedProfessorForAllocation.name} já está alocado neste período!`)
      return
    }
  
    allocations[key].push({
      professorId: selectedProfessorForAllocation.id,
      professorName: selectedProfessorForAllocation.name,
      timestamp: new Date().toISOString(),
    })
  
    localStorage.setItem("allocations", JSON.stringify(allocations))
  
    console.log("[v0] Alocação registrada:", {
      professor: selectedProfessorForAllocation.name,
      data: `${day}/${month + 1}/${year}`,
      periodo: period,
    })
  
    clearSelection()
    generateWeeklyCalendar()
  }
  
  function generateWeeklyCalendar() {
    const month = Number.parseInt(document.getElementById("month").value)
    const year = Number.parseInt(document.getElementById("year").value)
    const daysInMonth = new Date(year, month + 1, 0).getDate()
  
    let firstDay = new Date(year, month, 1).getDay()
    firstDay = firstDay === 0 ? 7 : firstDay
    firstDay = firstDay - 1
  
    const weeks = []
    let currentDay = 1
  
    while (currentDay <= daysInMonth) {
      const week = {
        morning: ["", "", "", "", "", "", ""],
        afternoon: ["", "", "", "", "", "", ""],
        night: ["", "", "", "", "", "", ""],
      }
  
      for (let i = 0; i < 7; i++) {
        if (weeks.length === 0 && i < firstDay) {
          continue
        }
        if (currentDay <= daysInMonth) {
          week.morning[i] = currentDay
          week.afternoon[i] = currentDay
          week.night[i] = currentDay
          currentDay++
        }
      }
      weeks.push(week)
    }
  
    let calendarHTML = ""
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
                      ${week.morning.map((day) => generateCell(month, year, day, "morning")).join("")}
                  </tr>
                  <tr><th>Tarde</th>
                      ${week.afternoon.map((day) => generateCell(month, year, day, "afternoon")).join("")}
                  </tr>
                  <tr><th>Noite</th>
                      ${week.night.map((day) => generateCell(month, year, day, "night")).join("")}
                  </tr>
              </tbody>
          </table>`
    })
  
    document.getElementById("weekly-calendar-container").innerHTML = calendarHTML
  }
  
  function generateCell(month, year, day, period) {
    if (!day) {
      return `<td data-day=""></td>`
    }
  
    const key = getAllocationKey(month, year, day, period)
    const cellAllocations = allocations[key] || []
  
    let shouldDim = false
    if (filteredProfessor) {
      const hasFilteredProfessor = cellAllocations.some((alloc) => alloc.professorId === filteredProfessor.id)
      shouldDim = !hasFilteredProfessor
    }
  
    const hasAllocations = cellAllocations.length > 0
    const classes = `${hasAllocations ? "allocated" : ""} ${shouldDim ? "dimmed" : ""}`
  
    const professorsHTML = cellAllocations
      .map((alloc) => `<span class="professor-badge">${alloc.professorName}</span>`)
      .join("")
  
    return `<td 
          data-day="${day}" 
          class="${classes}"
          onclick="allocateProfessor(${month}, ${year}, ${day}, '${period}')"
      >${professorsHTML}</td>`
  }
  
  window.onload = generateWeeklyCalendar
