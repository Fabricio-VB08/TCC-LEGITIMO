let botão1 = document.getElementById('teste')

function b1x(){
    document.getElementById('b1').style.visibility = visible;
    return false;
}

function testef(){
    alert("penis")
}

const materias = ['materia1','materia2','materia3']

const niveis = ["N1", "N2", "N3"]

const container = document.getElementById ('materias-container')

materias.forEach((materia, materiaIndex) => {
    const label = document.createElement ('label')
        label.textContent = materia + ':';
        label.style.display = ('block')

    niveis.forEach((nivel, nivelIndex) => {
        const checkbox = document.createElement ('input')
        checkbox.type = 'checkbox'
        checkbox.name = `materia-${materiaIndex}-nivel`
        checkbox.value = nivel
        checkbox.id = `materia=${materiaIndex}-nivel${nivelIndex}`

        const span = document.createElement('span')
        span.textContent = nivel

        label.appendChild(checkbox);
        label.appendChild(span);
        label.appendChild(document.createTextNode(" "));
        })
    container.appendChild(label)
    })


