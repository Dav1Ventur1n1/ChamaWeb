/**
 * forms.js - Validação e melhoria de formulários no Portal de Chamados
 */

document.addEventListener('DOMContentLoaded', function() {
    // Configuração de validação para formulário de login
    const loginForm = document.querySelector('form[action="dashboard.php"]');
    if (loginForm) {
        // Adicionar classes para estilização
        const inputs = loginForm.querySelectorAll('input');
        inputs.forEach(input => {
            // Envolver o input em um div com classe form-field
            const label = input.previousElementSibling;
            const wrapper = document.createElement('div');
            wrapper.className = 'form-field';
            input.parentNode.insertBefore(wrapper, input);
            if (label) wrapper.appendChild(label);
            wrapper.appendChild(input);
            
            // Adicionar mensagem de erro
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            wrapper.appendChild(errorMessage);
        });
        
        // Configurar validação em tempo real
        UI.form.setupLiveValidation(loginForm, {
            email: {
                required: true,
                email: true
            },
            senha: {
                required: true,
                minLength: 4
            }
        });
    }
    
    // Configuração de validação para formulário de novo chamado
    const newTicketForm = document.querySelector('form[action="criar_chamado.php"]');
    if (newTicketForm) {
        // Adicionar classes para estilização
        const inputs = newTicketForm.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Envolver o input em um div com classe form-field
            const label = input.previousElementSibling;
            const wrapper = document.createElement('div');
            wrapper.className = 'form-field';
            input.parentNode.insertBefore(wrapper, input);
            if (label) wrapper.appendChild(label);
            wrapper.appendChild(input);
            
            // Adicionar mensagem de erro
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            wrapper.appendChild(errorMessage);
        });
        
        // Configurar validação em tempo real
        UI.form.setupLiveValidation(newTicketForm, {
            titulo: {
                required: true,
                minLength: 5,
                maxLength: 100
            },
            descricao: {
                required: true,
                minLength: 10
            },
            categoria_id: {
                required: true
            }
        });
        
        // Adicionar feedback ao enviar o formulário
        newTicketForm.addEventListener('submit', function(e) {
            // Se o formulário já está em estado de submissão, ignorar
            if (newTicketForm.dataset.submitting === 'true') {
                e.preventDefault();
                return;
            }
            
            // Validar formulário
            const isValid = UI.form.validateForm(newTicketForm, {
                titulo: {
                    required: true,
                    minLength: 5,
                    maxLength: 100
                },
                descricao: {
                    required: true,
                    minLength: 10
                },
                categoria_id: {
                    required: true
                }
            });
            
            if (isValid) {
                // Mostrar feedback
                const submitButton = newTicketForm.querySelector('button[type="submit"]');
                UI.loading.start(submitButton, true);
                newTicketForm.dataset.submitting = 'true';
                
                UI.toast.info('Enviando chamado, por favor aguarde...');
                
                // Não bloqueamos o envio do formulário
            } else {
                // Impedir envio para corrigir erros
                e.preventDefault();
                UI.toast.error('Por favor, corrija os erros antes de enviar');
            }
        });
    }
    
    // Configuração de validação para formulário de comentários
    const commentForm = document.querySelector('form[enctype="multipart/form-data"]');
    if (commentForm) {
        // Adicionar classes para estilização
        const textarea = commentForm.querySelector('textarea[name="comentario"]');
        if (textarea) {
            // Envolver em um div form-field
            const wrapper = document.createElement('div');
            wrapper.className = 'form-field';
            textarea.parentNode.insertBefore(wrapper, textarea);
            wrapper.appendChild(textarea);
            
            // Adicionar mensagem de erro
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            wrapper.appendChild(errorMessage);
            
            // Configurar validação em tempo real
            textarea.addEventListener('blur', function() {
                if (!textarea.value.trim()) {
                    wrapper.classList.add('error');
                    errorMessage.textContent = 'Por favor, digite um comentário';
                } else {
                    wrapper.classList.remove('error');
                    wrapper.classList.add('success');
                }
            });
            
            textarea.addEventListener('input', function() {
                wrapper.classList.remove('error', 'success');
            });
        }
        
        // Configurar validação no envio
        commentForm.addEventListener('submit', function(e) {
            const textarea = commentForm.querySelector('textarea[name="comentario"]');
            if (textarea && !textarea.value.trim()) {
                e.preventDefault();
                const wrapper = textarea.closest('.form-field');
                wrapper.classList.add('error');
                const errorMessage = wrapper.querySelector('.error-message');
                if (errorMessage) errorMessage.textContent = 'Por favor, digite um comentário';
                
                UI.toast.error('O comentário não pode estar vazio');
            } else {
                // Mostrar feedback
                const submitButton = commentForm.querySelector('button[type="submit"]');
                UI.loading.start(submitButton, true);
                UI.toast.info('Enviando comentário...');
            }
        });
    }
    
    // Configuração de validação para formulário de reset de senha
    const resetForm = document.querySelector('form[action="reset_password.php"], form[method="POST"][action=""]');
    if (resetForm && resetForm.querySelector('input[name="email"]')) {
        // Adicionar classes para estilização
        const emailInput = resetForm.querySelector('input[name="email"]');
        
        if (emailInput) {
            // Envolver em um div form-field
            const label = emailInput.previousElementSibling;
            const wrapper = document.createElement('div');
            wrapper.className = 'form-field';
            emailInput.parentNode.insertBefore(wrapper, emailInput);
            if (label) wrapper.appendChild(label);
            wrapper.appendChild(emailInput);
            
            // Adicionar mensagem de erro
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            wrapper.appendChild(errorMessage);
            
            // Configurar validação
            UI.form.setupLiveValidation(resetForm, {
                email: {
                    required: true,
                    email: true
                }
            });
        }
    }
});