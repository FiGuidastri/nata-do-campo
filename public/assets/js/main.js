// Main JavaScript file for Nata do Campo system

// Namespace para o sistema
const NataDoCampo = {
    // Configurações globais
    config: {
        apiBaseUrl: '/api',
        debounceDelay: 300,
        dateFormat: 'DD/MM/YYYY',
        currency: 'BRL'
    },

    // Inicialização do sistema
    init() {
        this.setupEventListeners();
        this.setupUserMenu();
        this.setupMobileNavigation();
        this.setupFormValidation();
        this.setupDataTables();
        this.setupMasks();
    },

    // Configuração de listeners de eventos
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeComponents();
        });

        // Delegação de eventos para elementos dinâmicos
        document.body.addEventListener('click', (e) => {
            this.handleGlobalClick(e);
        });
    },

    // Inicializa componentes da página
    initializeComponents() {
        // Inicializa todos os tooltips
        const tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });

        // Inicializa todos os popovers
        const popovers = document.querySelectorAll('[data-toggle="popover"]');
        popovers.forEach(popover => {
            new bootstrap.Popover(popover);
        });
    },

    // Gerenciamento do menu do usuário
    setupUserMenu() {
        const userMenuButton = document.querySelector('.user-menu-button');
        const userMenuDropdown = document.querySelector('.user-menu-dropdown');

        if (userMenuButton && userMenuDropdown) {
            userMenuButton.addEventListener('click', (e) => {
                e.preventDefault();
                userMenuDropdown.classList.toggle('show');
            });

            // Fecha o menu quando clicar fora
            document.addEventListener('click', (e) => {
                if (!userMenuButton.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                    userMenuDropdown.classList.remove('show');
                }
            });
        }
    },

    // Configuração de navegação mobile
    setupMobileNavigation() {
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    },

    // Validação de formulários
    setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    },

    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                this.showFieldError(input, 'Este campo é obrigatório');
            } else {
                this.clearFieldError(input);
            }
        });

        return isValid;
    },

    // Configuração de DataTables
    setupDataTables() {
        const tables = document.querySelectorAll('.data-table');
        
        tables.forEach(table => {
            $(table).DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json'
                },
                responsive: true,
                pageLength: 25
            });
        });
    },

    // Configuração de máscaras de input
    setupMasks() {
        const masks = {
            date: '00/00/0000',
            time: '00:00:00',
            datetime: '00/00/0000 00:00:00',
            cep: '00000-000',
            phone: '(00) 0000-00009',
            cpf: '000.000.000-00',
            cnpj: '00.000.000/0000-00',
            money: '#.##0,00'
        };

        document.querySelectorAll('[data-mask]').forEach(input => {
            const maskType = input.dataset.mask;
            if (masks[maskType]) {
                $(input).mask(masks[maskType]);
            }
        });
    },

    // Utilitários
    utils: {
        // Formata número para moeda
        formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        },

        // Formata data
        formatDate(date) {
            return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
        },

        // Debounce para eventos
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    },

    // API helpers
    api: {
        async get(endpoint) {
            try {
                const response = await fetch(`${NataDoCampo.config.apiBaseUrl}/${endpoint}`);
                if (!response.ok) throw new Error('Erro na requisição');
                return await response.json();
            } catch (error) {
                console.error('Erro na requisição GET:', error);
                throw error;
            }
        },

        async post(endpoint, data) {
            try {
                const response = await fetch(`${NataDoCampo.config.apiBaseUrl}/${endpoint}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                if (!response.ok) throw new Error('Erro na requisição');
                return await response.json();
            } catch (error) {
                console.error('Erro na requisição POST:', error);
                throw error;
            }
        }
    },

    // Feedback visual
    showMessage(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `feedback-message status-${type} fade-in`;
        alertDiv.textContent = message;

        document.querySelector('.main-content').insertAdjacentElement('afterbegin', alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    },

    showFieldError(field, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error status-error';
        errorDiv.textContent = message;

        field.classList.add('error');
        field.parentNode.appendChild(errorDiv);
    },

    clearFieldError(field) {
        field.classList.remove('error');
        const errorDiv = field.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    },

    // Handler global de cliques
    handleGlobalClick(e) {
        // Manipula links de ações em tabelas
        if (e.target.matches('[data-action]')) {
            e.preventDefault();
            const action = e.target.dataset.action;
            const id = e.target.dataset.id;

            switch (action) {
                case 'edit':
                    this.handleEdit(id);
                    break;
                case 'delete':
                    this.handleDelete(id);
                    break;
                case 'view':
                    this.handleView(id);
                    break;
            }
        }
    },

    // Handlers de ações
    handleEdit(id) {
        // Implementar lógica de edição
    },

    handleDelete(id) {
        if (confirm('Tem certeza que deseja excluir este item?')) {
            // Implementar lógica de exclusão
        }
    },

    handleView(id) {
        // Implementar lógica de visualização
    }
};

// Inicializa o sistema quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    NataDoCampo.init();
});