/**
 * ui.js - Biblioteca de utilidades para interface do usuário
 * Fornece funções para notificações, animações e validação de formulários
 */

// Namespace para funções de UI
const UI = {
    // Sistema de notificações Toast
    toast: {
        /**
         * Mostra uma notificação toast
         * @param {string} message - A mensagem a ser exibida
         * @param {string} type - Tipo de notificação: 'success', 'error', 'info', 'warning'
         * @param {number} duration - Duração em milissegundos
         */
        show: function(message, type = 'info', duration = 3000) {
            // Criar o container de toast se não existir
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
            
            // Criar o elemento de toast
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            
            // Adicionar ao container
            container.appendChild(toast);
            
            // Trigger da animação
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Remover após o tempo especificado
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    container.removeChild(toast);
                }, 300);
            }, duration);
        },
        
        success: function(message, duration) {
            this.show(message, 'success', duration);
        },
        
        error: function(message, duration) {
            this.show(message, 'error', duration);
        },
        
        info: function(message, duration) {
            this.show(message, 'info', duration);
        },
        
        warning: function(message, duration) {
            this.show(message, 'warning', duration);
        }
    },
    
    // Efeitos de carregamento
    loading: {
        /**
         * Adiciona um efeito de loading a um elemento
         * @param {HTMLElement} element - Elemento a receber o efeito
         * @param {boolean} showSpinner - Se deve mostrar um spinner girando
         */
        start: function(element, showSpinner = true) {
            // Guarda o texto original
            element.dataset.originalText = element.innerHTML;
            
            // Adiciona classe de loading
            element.classList.add('loading');
            
            if (showSpinner) {
                const spinner = document.createElement('span');
                spinner.className = 'spinner';
                element.prepend(spinner);
            }
        },
        
        /**
         * Remove o efeito de loading
         * @param {HTMLElement} element - Elemento com o efeito
         */
        stop: function(element) {
            // Remove classe de loading
            element.classList.remove('loading');
            
            // Restaura o texto original
            if (element.dataset.originalText) {
                element.innerHTML = element.dataset.originalText;
                delete element.dataset.originalText;
            }
            
            // Remove spinner se existir
            const spinner = element.querySelector('.spinner');
            if (spinner) {
                element.removeChild(spinner);
            }
        }
    },
    
    // Validação de formulários
    form: {
        /**
         * Valida um campo de formulário
         * @param {HTMLElement} field - Campo a ser validado
         * @param {Object} rules - Regras de validação
         * @returns {boolean} - Se o campo é válido
         */
        validateField: function(field, rules) {
            // Elemento pai que receberá as classes de erro/sucesso
            const formField = field.closest('.form-field') || field.parentNode;
            const errorElement = formField.querySelector('.error-message');
            
            // Remover classes anteriores
            formField.classList.remove('error', 'success');
            
            // Valor do campo
            const value = field.value.trim();
            
            // Verificar cada regra
            if (rules.required && !value) {
                formField.classList.add('error');
                if (errorElement) errorElement.textContent = 'Este campo é obrigatório';
                return false;
            }
            
            if (rules.email && value && !/\S+@\S+\.\S+/.test(value)) {
                formField.classList.add('error');
                if (errorElement) errorElement.textContent = 'E-mail inválido';
                return false;
            }
            
            if (rules.minLength && value.length < rules.minLength) {
                formField.classList.add('error');
                if (errorElement) errorElement.textContent = `Mínimo de ${rules.minLength} caracteres`;
                return false;
            }
            
            if (rules.maxLength && value.length > rules.maxLength) {
                formField.classList.add('error');
                if (errorElement) errorElement.textContent = `Máximo de ${rules.maxLength} caracteres`;
                return false;
            }
            
            if (rules.pattern && !new RegExp(rules.pattern).test(value)) {
                formField.classList.add('error');
                if (errorElement) errorElement.textContent = rules.patternMessage || 'Formato inválido';
                return false;
            }
            
            if (rules.match && value !== document.querySelector(rules.match).value) {
                formField.classList.add('error');
                if (errorElement) errorElement.textContent = 'Os valores não coincidem';
                return false;
            }
            
            // Campo válido
            formField.classList.add('success');
            return true;
        },
        
        /**
         * Valida um formulário inteiro
         * @param {HTMLFormElement} form - Formulário a ser validado
         * @param {Object} fieldsRules - Regras para cada campo
         * @returns {boolean} - Se o formulário é válido
         */
        validateForm: function(form, fieldsRules) {
            let isValid = true;
            
            // Validar cada campo com regras
            for (const fieldName in fieldsRules) {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    const fieldValid = this.validateField(field, fieldsRules[fieldName]);
                    isValid = isValid && fieldValid;
                }
            }
            
            return isValid;
        },
        
        /**
         * Configura validação em tempo real para um formulário
         * @param {HTMLFormElement} form - Formulário a configurar
         * @param {Object} fieldsRules - Regras para cada campo
         */
        setupLiveValidation: function(form, fieldsRules) {
            // Para cada campo com regras
            for (const fieldName in fieldsRules) {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    // Adicionar validação em eventos como input, blur, change
                    field.addEventListener('blur', () => {
                        this.validateField(field, fieldsRules[fieldName]);
                    });
                    
                    field.addEventListener('input', () => {
                        // Remover status de erro durante digitação
                        const formField = field.closest('.form-field') || field.parentNode;
                        formField.classList.remove('error', 'success');
                    });
                }
            }
            
            // Validar todo o formulário no submit
            form.addEventListener('submit', (e) => {
                const isValid = this.validateForm(form, fieldsRules);
                if (!isValid) {
                    e.preventDefault();
                    UI.toast.error('Por favor, corrija os erros no formulário');
                }
            });
        }
    },
    
    // Efeitos para tabelas e listas
    table: {
        /**
         * Destaca uma linha da tabela
         * @param {HTMLElement} row - Linha a destacar
         * @param {number} duration - Duração em milissegundos
         */
        highlightRow: function(row, duration = 2000) {
            row.classList.add('highlight-row');
            setTimeout(() => {
                row.classList.remove('highlight-row');
            }, duration);
        },
        
        /**
         * Adiciona efeito de fade-in para novas linhas
         * @param {HTMLElement} row - Linha a animar
         */
        fadeInRow: function(row) {
            row.classList.add('fade-in');
            setTimeout(() => {
                row.classList.remove('fade-in');
            }, 500);
        }
    },
    
    // Expandir/contrair elementos
    expand: {
        /**
         * Alterna a expansão de um elemento
         * @param {HTMLElement} element - Elemento a expandir/contrair
         */
        toggle: function(element) {
            if (element.classList.contains('expanded')) {
                this.collapse(element);
            } else {
                this.expand(element);
            }
        },
        
        /**
         * Expande um elemento
         * @param {HTMLElement} element - Elemento a expandir
         */
        expand: function(element) {
            element.classList.add('expanded');
        },
        
        /**
         * Contrai um elemento
         * @param {HTMLElement} element - Elemento a contrair
         */
        collapse: function(element) {
            element.classList.remove('expanded');
        }
    }
};

// Exportar a biblioteca
window.UI = UI;