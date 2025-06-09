/**
 * dashboard.js - Funcionalidades avançadas para o dashboard
 * Implementa filtros em tempo real e atualizações assíncronas
 */

document.addEventListener('DOMContentLoaded', function() {
    // Instanciar contador para auto-refresh
    let autoRefreshTimer = null;
    let autoRefreshInterval = 60000; // 60 segundos
    
    // Instanciar indicador de carregamento
    let isLoading = false;
    
    // Salvar referências a elementos importantes
    const ticketsTable = document.getElementById('tickets-table');
    const refreshButton = document.getElementById('refresh-tickets');
    const filterForm = document.querySelector('form[method="GET"]');
    const pesquisaInput = document.querySelector('input[name="pesquisa"]');
    const teamSelect = document.querySelector('select[name="team_id"]');
    
    // Verificar se estamos na página do dashboard
    if (!ticketsTable || !filterForm) return;
    
    // Adicionar auto-refresh toggle
    const autoRefreshToggle = document.createElement('div');
    autoRefreshToggle.className = 'auto-refresh-toggle';
    autoRefreshToggle.innerHTML = `
        <label>
            <input type="checkbox" id="auto-refresh-toggle"> 
            Auto-atualizar a cada <select id="refresh-interval">
                <option value="30000">30s</option>
                <option value="60000" selected>1min</option>
                <option value="300000">5min</option>
            </select>
        </label>
    `;
    
    // Inserir toggle após o botão de refresh
    if (refreshButton) {
        refreshButton.parentNode.insertBefore(autoRefreshToggle, refreshButton.nextSibling);
        
        // Adicionar indicador de última atualização
        const lastUpdateSpan = document.createElement('span');
        lastUpdateSpan.id = 'last-update-time';
        lastUpdateSpan.className = 'last-update';
        lastUpdateSpan.textContent = 'Última atualização: agora';
        refreshButton.parentNode.insertBefore(lastUpdateSpan, autoRefreshToggle.nextSibling);
    }
    
    // Configurar evento de toggle do auto-refresh
    const autoRefreshCheckbox = document.getElementById('auto-refresh-toggle');
    const refreshIntervalSelect = document.getElementById('refresh-interval');
    
    if (autoRefreshCheckbox && refreshIntervalSelect) {
        autoRefreshCheckbox.addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
                UI.toast.info(`Auto-atualização ativada a cada ${refreshIntervalSelect.options[refreshIntervalSelect.selectedIndex].text}`);
            } else {
                stopAutoRefresh();
                UI.toast.info('Auto-atualização desativada');
            }
        });
        
        refreshIntervalSelect.addEventListener('change', function() {
            autoRefreshInterval = parseInt(this.value);
            
            if (autoRefreshCheckbox.checked) {
                // Reiniciar timer com novo intervalo
                stopAutoRefresh();
                startAutoRefresh();
                UI.toast.info(`Intervalo alterado para ${this.options[this.selectedIndex].text}`);
            }
        });
    }
    
    // Função para iniciar auto-refresh
    function startAutoRefresh() {
        stopAutoRefresh(); // Garantir que não haja duplicação
        
        autoRefreshTimer = setInterval(function() {
            if (!isLoading) {
                fetchTickets();
                updateLastRefreshTime();
            }
        }, autoRefreshInterval);
    }
    
    // Função para parar auto-refresh
    function stopAutoRefresh() {
        if (autoRefreshTimer) {
            clearInterval(autoRefreshTimer);
            autoRefreshTimer = null;
        }
    }
    
    // Função para atualizar texto de última atualização
    function updateLastRefreshTime() {
        const lastUpdateSpan = document.getElementById('last-update-time');
        if (lastUpdateSpan) {
            const now = new Date();
            const timeStr = now.toLocaleTimeString();
            lastUpdateSpan.textContent = `Última atualização: ${timeStr}`;
        }
    }
    
    // Converter formulário tradicional para AJAX
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetchTickets();
        });
        
        // Adicionar debounce para o campo de pesquisa
        if (pesquisaInput) {
            let debounceTimer;
            pesquisaInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    fetchTickets();
                }, 500); // Aguardar 500ms após o último caractere digitado
            });
        }
        
        // Filtro instantâneo para seleção de equipe
        if (teamSelect) {
            teamSelect.addEventListener('change', function() {
                fetchTickets();
            });
        }
    }
    
    // Configurar botão de refresh
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            fetchTickets();
            updateLastRefreshTime();
        });
    }
    
    // Função principal para buscar tickets via AJAX
    function fetchTickets() {
        // Evitar múltiplas requisições simultâneas
        if (isLoading) return;
        isLoading = true;
        
        // Feedback visual
        if (refreshButton) {
            UI.loading.start(refreshButton, true);
        }
        
        // Construir parâmetros a partir do formulário
        const params = new URLSearchParams();
        if (pesquisaInput) params.append('pesquisa', pesquisaInput.value);
        if (teamSelect) params.append('team_id', teamSelect.value);
        
        // Fazer a requisição
        fetch(`api_tickets.php?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                updateTicketsTable(data);
                
                // Feedback de sucesso
                UI.toast.success('Lista de chamados atualizada com sucesso');
            })
            .catch(error => {
                console.error('Erro ao carregar chamados:', error);
                UI.toast.error('Erro ao atualizar lista de chamados');
            })
            .finally(() => {
                // Encerrar estado de carregamento
                isLoading = false;
                
                if (refreshButton) {
                    UI.loading.stop(refreshButton);
                }
            });
    }
    
    // Função para atualizar a tabela de tickets
    function updateTicketsTable(tickets) {
        const tableBody = ticketsTable.querySelector('tbody');
        if (!tableBody) return;
        
        // Guardar referência à posição de scroll
        const scrollPos = window.scrollY;
        
        // Limpar tabela atual
        tableBody.innerHTML = '';
        
        // Se não houver tickets, mostrar mensagem
        if (tickets.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" style="text-align: center;">Nenhum chamado encontrado</td>';
            tableBody.appendChild(row);
            return;
        }
        
        // Adicionar cada ticket à tabela
        tickets.forEach((ticket, index) => {
            const row = document.createElement('tr');
            
            // Definir classes para estados e prioridades
            if (ticket.estado === 'Fechado') {
                row.classList.add('status-closed');
            } else if (ticket.estado === 'Aguardando Usuario') {
                row.classList.add('status-waiting');
            } else if (ticket.prioridade === 'Critico' || ticket.prioridade === 'Alto') {
                row.classList.add('priority-high');
            }
            
            row.innerHTML = `
                <td>${ticket.id}</td>
                <td>${escapeHtml(ticket.titulo)}</td>
                <td>${ticket.estado}</td>
                <td>${ticket.prioridade}</td>
                <td>${ticket.tipo}</td>
                <td>
                    <a href="ticket.php?id=${ticket.id}">Ver Detalhes</a>
                </td>
            `;
            
            // Adicionar à tabela com animação
            tableBody.appendChild(row);
            UI.table.fadeInRow(row);
            
            // Destacar itens novos ou alterados (verificação simplificada pelo ID)
            if (isNewOrUpdated(ticket)) {
                setTimeout(() => {
                    UI.table.highlightRow(row);
                }, 100 * index); // Escalonar os destaques
            }
        });
        
        // Restaurar posição de scroll
        window.scrollTo(0, scrollPos);
    }
    
    // Função para verificar se um ticket é novo ou foi atualizado
    // Esta é uma implementação simplificada. Em uma versão real,
    // você compararia timestamps ou outros campos para determinar mudanças
    let previousTicketIds = new Map();
    
    function isNewOrUpdated(ticket) {
        const previousTicket = previousTicketIds.get(ticket.id);
        const isUpdated = previousTicket && 
            (previousTicket.estado !== ticket.estado || 
             previousTicket.prioridade !== ticket.prioridade);
        
        // Atualizar o cache de tickets
        previousTicketIds.set(ticket.id, {...ticket});
        
        // Se é um novo ticket (não estava na lista anterior) ou foi atualizado
        return !previousTicket || isUpdated;
    }
    
    // Função para escapar HTML e prevenir XSS
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
    
    // Adicionar estatísticas em tempo real
    function addRealTimeStats() {
        // Criar container para estatísticas
        const statsContainer = document.createElement('div');
        statsContainer.className = 'stats-container';
        statsContainer.innerHTML = `
            <div class="card stat-card">
                <div class="card-header">Estatísticas em Tempo Real</div>
                <div class="card-content">
                    <div class="stat-item">
                        <span class="stat-label">Total de Chamados:</span>
                        <span class="stat-value" id="stat-total">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Chamados Abertos:</span>
                        <span class="stat-value" id="stat-open">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Alta Prioridade:</span>
                        <span class="stat-value" id="stat-high">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Aguardando:</span>
                        <span class="stat-value" id="stat-waiting">-</span>
                    </div>
                </div>
            </div>
        `;
        
        // Inserir antes da tabela
        const main = document.querySelector('main');
        if (main && main.firstChild) {
            main.insertBefore(statsContainer, main.firstChild);
        }
        
        // Função para atualizar estatísticas
        function updateStats() {
            fetch('api_stats.php')
                .then(response => response.json())
                .then(stats => {
                    document.getElementById('stat-total').textContent = stats.total || '0';
                    document.getElementById('stat-open').textContent = stats.abertos || '0';
                    document.getElementById('stat-high').textContent = stats.alta_prioridade || '0';
                    document.getElementById('stat-waiting').textContent = stats.aguardando || '0';
                })
                .catch(error => console.error('Erro ao carregar estatísticas:', error));
        }
        
        // Atualizar estatísticas inicialmente e a cada 60 segundos
        updateStats();
        setInterval(updateStats, 60000);
    }
    
    // Adicionar estatísticas (comentar se não quiser essa funcionalidade)
    addRealTimeStats();
    
    // Carregar dados inicialmente
    fetchTickets();
});