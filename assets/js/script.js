// assets/js/script.js
// JavaScript personalizado do AgroControl

$(document).ready(function() {
    
    // Auto-fechar alerts após 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Máscara para campos de data
    $('.data-mask').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length > 8) value = value.substr(0,8);
        
        if (value.length > 4) {
            value = value.substr(0,2) + '/' + value.substr(2,2) + '/' + value.substr(4);
        } else if (value.length > 2) {
            value = value.substr(0,2) + '/' + value.substr(2);
        }
        
        $(this).val(value);
    });
    
    // Máscara para campos de CPF
    $('.cpf-mask').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length > 11) value = value.substr(0,11);
        
        if (value.length > 9) {
            value = value.substr(0,3) + '.' + value.substr(3,3) + '.' + value.substr(6,3) + '-' + value.substr(9);
        } else if (value.length > 6) {
            value = value.substr(0,3) + '.' + value.substr(3,3) + '.' + value.substr(6);
        } else if (value.length > 3) {
            value = value.substr(0,3) + '.' + value.substr(3);
        }
        
        $(this).val(value);
    });
    
    // Máscara para campos de CNPJ
    $('.cnpj-mask').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length > 14) value = value.substr(0,14);
        
        if (value.length > 12) {
            value = value.substr(0,2) + '.' + value.substr(2,3) + '.' + value.substr(5,3) + '/' + value.substr(8,4) + '-' + value.substr(12);
        } else if (value.length > 8) {
            value = value.substr(0,2) + '.' + value.substr(2,3) + '.' + value.substr(5,3) + '/' + value.substr(8);
        } else if (value.length > 5) {
            value = value.substr(0,2) + '.' + value.substr(2,3) + '.' + value.substr(5);
        } else if (value.length > 2) {
            value = value.substr(0,2) + '.' + value.substr(2);
        }
        
        $(this).val(value);
    });
    
    // Máscara para campos de telefone
    $('.phone-mask').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length > 11) value = value.substr(0,11);
        
        if (value.length > 6) {
            value = '(' + value.substr(0,2) + ') ' + value.substr(2,5) + '-' + value.substr(7);
        } else if (value.length > 2) {
            value = '(' + value.substr(0,2) + ') ' + value.substr(2);
        } else if (value.length > 0) {
            value = '(' + value;
        }
        
        $(this).val(value);
    });
    
    // Máscara para campos de CEP
    $('.cep-mask').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length > 8) value = value.substr(0,8);
        
        if (value.length > 5) {
            value = value.substr(0,5) + '-' + value.substr(5);
        }
        
        $(this).val(value);
    });
    
    // Máscara para campos de valor monetário
    $('.money-mask').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length === 0) {
            $(this).val('');
            return;
        }
        
        value = parseInt(value) / 100;
        $(this).val('R$ ' + value.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.'));
    });
    
    // Confirmação antes de excluir
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Tem certeza que deseja excluir este registro?')) {
            e.preventDefault();
        }
    });
    
    // Tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Popovers do Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Validação de formulários
    $('form').on('submit', function(e) {
        var isValid = true;
        $(this).find('[required]').each(function() {
            if ($(this).val().trim() === '') {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
        }
    });
});