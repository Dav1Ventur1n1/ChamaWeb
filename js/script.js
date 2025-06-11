// Script principal para o Portal de Chamados
document.addEventListener('DOMContentLoaded', function() {
    console.log("JS carregado!");
    
    // Verificar se estamos na página de dashboard
    const ticketsTable = document.getElementById('tickets-table');
    const refreshButton = document.getElementById('refresh-tickets');
    
    if (ticketsTable && refreshButton) {
        console.log("Dashboard detectado. Configurando recursos AJAX...");
        setupDashboardFeatures();
    }
    
    // Adicionar container para notificações toast globalmente
    addToastContainer();
});

/**
 * Adiciona um container para notificações toast
 */
function addToastContainer() {
    if (!document.querySelector('.toast-container')) {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
}

/**
 * Configura funcionalidades do dashboard
 */
document.addEventListener('DOMContentLoaded', function () {
    setupDashboardFeatures();
});

function setupDashboardFeatures() {
    const refreshButton = document.getElementById('refresh-tickets');
    const ticketsTable = document.getElementById('tickets-table');
    const filterForm = document.querySelector('form[method="GET"]');
    const pesquisaInput = document.querySelector('input[name="pesquisa"]');
    const teamSelect = document.querySelector('select[name="team_id"]');

    let isLoading = false;

    function fetchTickets(showToastOnce = true) {
        if (isLoading) return;
        isLoading = true;

        if (refreshButton) {
            refreshButton.textContent = 'Carregando...';
            refreshButton.disabled = true;
        }

        const params = new URLSearchParams();
        if (pesquisaInput) params.append('pesquisa', pesquisaInput.value);
        if (teamSelect) params.append('team_id', teamSelect.value);

        fetch('api_chamados_rest.php?' + params.toString(), {
            headers: {
                'Authorization': 'Bearer ' + (window.JWT_TOKEN || '')
            }
        })
            .then(response => {
                if (!response.ok) throw new Error('Erro na resposta da rede');
                return response.json();
            })
            .then(data => {
                updateTicketsTable(data);
                if (showToastOnce) showToast('success', 'Lista de chamados atualizada com sucesso');
                updateLastRefreshTime();
            })
            .catch(error => {
                console.error('Erro ao carregar chamados:', error);
                showToast('error', 'Erro ao atualizar chamados');
            })
            .finally(() => {
                if (refreshButton) {
                    refreshButton.textContent = 'Atualizar via API';
                    refreshButton.disabled = false;
                }
                isLoading = false;
            });
    }

    function updateTicketsTable(tickets) {
        const tableBody = ticketsTable?.querySelector('tbody');
        if (!tableBody) return;
        tableBody.innerHTML = '';

        if (tickets.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" class="no-records">Nenhum chamado encontrado</td>';
            tableBody.appendChild(row);
            return;
        }

        tickets.forEach(ticket => {
            const row = document.createElement('tr');
            row.dataset.id = ticket.id;

            if (ticket.estado === 'Fechado') row.classList.add('status-closed');
            if (ticket.prioridade === 'Critico') row.classList.add('priority-critical');
            if (ticket.prioridade === 'Alto') row.classList.add('priority-high');

            row.innerHTML = `
                <td>${ticket.id}</td>
                <td>${escapeHtml(ticket.titulo)}</td>
                <td data-field="estado">${ticket.estado}</td>
                <td data-field="prioridade">${ticket.prioridade}</td>
                <td>${ticket.tipo}</td>
                <td><a href="ticket.php?id=${ticket.id}" class="action-link">Ver Detalhes</a></td>
            `;
            tableBody.appendChild(row);
            row.classList.add('fade-in');
        });
    }

    function updateLastRefreshTime() {
        const span = document.getElementById('last-update-time');
        if (span) {
            const now = new Date();
            span.textContent = `Última atualização: ${now.toLocaleTimeString()}`;
        }
    }

    function showToast(type, message) {
        if (window.UI?.toast?.[type]) {
            UI.toast[type](message);
        } else {
            // Fallback
            if (type === 'success') {
                console.log(message);
            } else {
                alert(message);
            }
        }
    }

    if (refreshButton) {
        refreshButton.addEventListener('click', function () {
            fetchTickets(true); // apenas aqui mostra toast
        });
    }

    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            fetchTickets(false); // sem mostrar toast repetido
        });

        if (pesquisaInput) {
            let debounceTimer;
            pesquisaInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => fetchTickets(false), 500);
            });
        }

        if (teamSelect) {
            teamSelect.addEventListener('change', () => fetchTickets(false));
        }
    }

    // Executa na carga da página sem mostrar o toast duplicado
    fetchTickets(false);
}


/**
 * Mostra uma notificação toast
 * @param {string} type - Tipo de notificação: success, error, info, warning
 * @param {string} message - Mensagem a ser mostrada
 */
function showToast(type, message) {
    // Encontrar ou criar o container
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    // Criar o toast
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    // Adicionar ao container
    container.appendChild(toast);
    
    // Mostrar o toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Remover após alguns segundos
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            container.removeChild(toast);
        }, 300);
    }, 3000);
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