document.addEventListener('DOMContentLoaded', function() {
    // Inicializa o Select2 no campo de seleção de professor
    $('#professor').select2({
        placeholder: '-- Pesquise ou selecione um professor --',
        allowClear: true
    });

    // Adiciona um listener para o evento de mudança do Select2
    $('#professor').on('change', function() {
        const professorId = $(this).val();
        
        // Apenas submete o formulário se um professor válido for selecionado
        if (professorId) {
            // this.form não funciona com jQuery, então buscamos o formulário pai
            $(this).closest('form').submit();
        }
    });
});
document.getElementById('search-competencia').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const competencias = document.querySelectorAll('.competencia-row');

    competencias.forEach(row => {
        const nome = row.querySelector('.competencia-nome').textContent.toLowerCase();
        row.style.display = nome.includes(searchValue) ? '' : 'none';
    });

    // Ocultar fieldsets sem competências visíveis
    document.querySelectorAll('.materia-group').forEach(group => {
        const visiveis = group.querySelectorAll('.competencia-row:not([style*="display: none"])').length;
        group.style.display = visiveis > 0 ? '' : 'none';
    });
});
