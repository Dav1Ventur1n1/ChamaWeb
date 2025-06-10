/**
 * ticket.js - Melhorias interativas para a página de ticket
 * Adiciona funcionalidades AJAX para comentários e atualizações
 */

document.addEventListener('DOMContentLoaded', function() {
    // Verificar se estamos na página de ticket
    const ticketId = getTicketIdFromUrl();
    if (!ticketId) return;
    
    console.log(`Ticket.js carregado para o chamado #${ticketId}`);
    
    // Referências a elementos importantes
    const commentForm = document.querySelector('form[enctype="multipart/form-data"]');
    const ticketUpdateForm = document.querySelector('form input[name="acao"][value="atualizar"]')?.closest('form');
    
    // Configurar envio assíncrono de comentários
    if (commentForm) {
        setupAsyncComments(commentForm, ticketId);
    }
    
    // Configurar atualizações assíncronas de status
    if (ticketUpdateForm) {
        setupAsyncStatusUpdates(ticketUpdateForm, ticketId);
    }
    
    // Adicionar expansão/contração de comentários longos
    setupCommentExpansion();
    
    // Adicionar atualizações em tempo real dos comentários
    setupRealTimeUpdates(ticketId);
});

/**
 * Obtém o ID do ticket da URL
 * @returns {string|null} ID do ticket ou null se não encontrado
 */
function getTicketIdFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

/**
 * Configura envio assíncrono de comentários
 * @param {HTMLFormElement} form - Formulário de comentários
 * @param {string} ticketId - ID do ticket
 */
function setupAsyncComments(form, ticketId) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar o formulário
        const textarea = form.querySelector('textarea[name="comentario"]');
        if (!textarea || !textarea.value.trim()) {
            UI.toast.error('O comentário não pode estar vazio');
            return;
        }
        
        // Mostrar feedback visual
        const submitButton = form.querySelector('button[type="submit"]');
        UI.loading.start(submitButton, true);
        
        // Criar FormData para envio com possível anexo
        const formData = new FormData(form);
        
        // Adicionar o ID do ticket e ação
        formData.append('ticket_id', ticketId);
        formData.append('ajax_action', 'add_comment');
        
        // Enviar os dados via fetch
        fetch('api_ticket_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao enviar comentário');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Limpar formulário
                textarea.value = '';
                if (form.querySelector('input[type="file"]')) {
                    form.querySelector('input[type="file"]').value = '';
                }
                
                // Adicionar o novo comentário à lista
                addNewCommentToList(data.comment);
                
                UI.toast.success('Comentário adicionado com sucesso');
            } else {
                UI.toast.error(data.message || 'Erro ao adicionar comentário');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            UI.toast.error('Erro ao enviar comentário. Tente novamente.');
        })
        .finally(() => {
            UI.loading.stop(submitButton);
        });
    });
}

/**
 * Adiciona um novo comentário à lista de comentários
 * @param {Object} comment - Dados do comentário
 */
function addNewCommentToList(comment) {
    const commentsList = document.querySelector('.comentarios') || 
                       document.querySelector('h2:contains("Comentários")').nextElementSibling;
    
    if (!commentsList) {
        // Se não encontrar onde adicionar, recarregar a página
        location.reload();
        return;
    }
    
    // Criar o elemento do comentário
    const commentDiv = document.createElement('div');
    commentDiv.className = 'comentario fade-in';
    commentDiv.innerHTML = `
        <strong>${escapeHtml(comment.nome)}</strong> 
        ${comment.data_criacao}
        ${!comment.visivel_usuario ? ' <em>(Work Note)</em>' : ''}
        <p>${nl2br(escapeHtml(comment.conteudo))}</p>
        ${comment.anexo ? `<p><a href="${comment.anexo}" target="_blank">Ver Anexo</a></p>` : ''}
    `;
    
    // Adicionar ao início da lista de comentários
    commentsList.insertBefore(commentDiv, commentsList.firstChild);
    
    // Destacar o novo comentário
    setTimeout(() => {
        commentDiv.classList.add('highlight');
        setTimeout(() => {
            commentDiv.classList.remove('highlight');
        }, 3000);
    }, 100);
}

/**
 * Configura atualizações assíncronas de status do ticket
 * @param {HTMLFormElement} form - Formulário de atualização de status
 * @param {string} ticketId - ID do ticket
 */
function setupAsyncStatusUpdates(form, ticketId) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Mostrar feedback visual
        const submitButton = form.querySelector('button[type="submit"]');
        UI.loading.start(submitButton, true);
        
        // Criar FormData para envio
        const formData = new FormData(form);
        
        // Adicionar o ID do ticket e ação
        formData.append('ticket_id', ticketId);
        formData.append('ajax_action', 'update_ticket');
        
        // Enviar os dados via fetch
        fetch('api_ticket_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao atualizar chamado');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Atualizar os campos na página
                updateTicketFields(data.ticket);
                
                UI.toast.success('Chamado atualizado com sucesso');
            } else {
                UI.toast.error(data.message || 'Erro ao atualizar chamado');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            UI.toast.error('Erro ao atualizar chamado. Tente novamente.');
        })
        .finally(() => {
            UI.loading.stop(submitButton);
        });
    });
}

/**
 * Atualiza os campos de exibição do ticket na página
 * @param {Object} ticket - Dados do ticket
 */
function updateTicketFields(ticket) {
    // Atualizar campos de texto
    document.querySelectorAll('[data-field]').forEach(element => {
        const field = element.getAttribute('data-field');
        if (ticket[field] !== undefined) {
            element.textContent = ticket[field];
        }
    });
    
    // Atualizar seletores
    document.querySelectorAll('select').forEach(select => {
        const field = select.getAttribute('name').replace('novo_', '');
        if (ticket[field]) {
            select.value = ticket[field];
        }
    });
    
    // Destacar campos alterados
    const changedFields = ticket.changed_fields || [];
    changedFields.forEach(field => {
        const element = document.querySelector(`[data-field="${field}"]`);
        if (element) {
            element.classList.add('highlight-change');
            setTimeout(() => {
                element.classList.remove('highlight-change');
            }, 3000);
        }
    });
}

/**
 * Configura a expansão/contração de comentários longos
 */
function setupCommentExpansion() {
    document.querySelectorAll('.comentario p').forEach(comment => {
        // Se o comentário for longo, adicionar botão de expansão
        if (comment.textContent.length > 200) {
            // Guardar o texto completo
            comment.dataset.fullText = comment.innerHTML;
            
            // Truncar o texto
            const shortText = comment.textContent.substring(0, 200) + '...';
            comment.innerHTML = shortText;
            
            // Adicionar botão
            const expandButton = document.createElement('button');
            expandButton.className = 'expand-button';
            expandButton.textContent = 'Ver mais';
            comment.parentNode.insertBefore(expandButton, comment.nextSibling);
            
            // Configurar toggle
            expandButton.addEventListener('click', function() {
                if (expandButton.textContent === 'Ver mais') {
                    // Expandir
                    comment.innerHTML = comment.dataset.fullText;
                    expandButton.textContent = 'Ver menos';
                } else {
                    // Contrair
                    comment.innerHTML = shortText;
                    expandButton.textContent = 'Ver mais';
                }
            });
        }
    });
}

/**
 * Configura atualizações em tempo real de comentários
 * @param {string} ticketId - ID do ticket
 */
function setupRealTimeUpdates(ticketId) {
    // Armazenar timestamp do último comentário carregado
    let lastCommentTimestamp = Date.now();
    
    // Verificar por novos comentários a cada 30 segundos
    setInterval(() => {
        checkForNewComments(ticketId, lastCommentTimestamp);
    }, 30000);
}

/**
 * Verifica se há novos comentários
 * @param {string} ticketId - ID do ticket
 * @param {number} timestamp - Timestamp do último comentário
 */
function checkForNewComments(ticketId, timestamp) {
    fetch(`api_ticket_actions.php?action=check_comments&ticket_id=${ticketId}&since=${timestamp}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.comments && data.comments.length > 0) {
                // Atualizar timestamp
                lastCommentTimestamp = Date.now();
                
                // Adicionar novos comentários
                data.comments.forEach(comment => {
                    addNewCommentToList(comment);
                });
                
                // Notificar o usuário
                if (data.comments.length === 1) {
                    UI.toast.info('1 novo comentário adicionado');
                } else {
                    UI.toast.info(`${data.comments.length} novos comentários adicionados`);
                }
            }
        })
        .catch(error => console.error('Erro ao verificar novos comentários:', error));
}

/**
 * Escapa caracteres HTML para prevenir XSS
 * @param {string} text - Texto a ser escapado
 * @returns {string} Texto escapado
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Converte quebras de linha em <br>
 * @param {string} text - Texto com quebras de linha
 * @returns {string} Texto com <br>
 */
function nl2br(text) {
    return text.replace(/\n/g, '<br>');
}