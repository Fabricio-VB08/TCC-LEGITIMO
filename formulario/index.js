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
