// Namespace do Sistema
const NataDoCampo = window.NataDoCampo || {};

/**
 * Módulo de Clientes
 */
NataDoCampo.Clientes = {
    /**
     * Inicializa o módulo
     */
    init() {
        this.setupFormHandlers();
        this.setupCepSearch();
        this.setupMasks();
    },

    /**
     * Configura os handlers do formulário
     */
    setupFormHandlers() {
        // Alterna entre CPF e CNPJ baseado no tipo de pessoa
        document.querySelectorAll('input[name="tipo_pessoa"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const tipoPessoa = e.target.value;
                const cpfGroup = document.querySelector('.cpf-group');
                const cnpjGroup = document.querySelector('.cnpj-group');
                
                if (tipoPessoa === 'F') {
                    cpfGroup.style.display = 'block';
                    cnpjGroup.style.display = 'none';
                    document.getElementById('cpf').setAttribute('required', '');
                    document.getElementById('cnpj').removeAttribute('required');
                } else {
                    cpfGroup.style.display = 'none';
                    cnpjGroup.style.display = 'block';
                    document.getElementById('cpf').removeAttribute('required');
                    document.getElementById('cnpj').setAttribute('required', '');
                }
            });
        });

        // Validação do formulário
        const form = document.getElementById('formCadastroCliente');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        }
    },

    /**
     * Configura a busca de CEP
     */
    setupCepSearch() {
        const btnBuscarCep = document.getElementById('buscarCep');
        if (btnBuscarCep) {
            btnBuscarCep.addEventListener('click', async () => {
                const cep = document.getElementById('cep').value.replace(/\D/g, '');
                
                if (cep.length !== 8) {
                    NataDoCampo.showAlert('Erro', 'CEP inválido', 'error');
                    return;
                }

                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await response.json();

                    if (data.erro) {
                        NataDoCampo.showAlert('Erro', 'CEP não encontrado', 'error');
                        return;
                    }

                    document.getElementById('logradouro').value = data.logradouro;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('cidade').value = data.localidade;
                    document.getElementById('estado').value = data.uf;

                    // Foca no campo número se o logradouro foi preenchido
                    if (data.logradouro) {
                        document.getElementById('numero').focus();
                    }

                } catch (error) {
                    NataDoCampo.showAlert('Erro', 'Erro ao buscar CEP', 'error');
                    console.error('Erro ao buscar CEP:', error);
                }
            });
        }
    },

    /**
     * Configura as máscaras dos campos
     */
    setupMasks() {
        // CPF: 000.000.000-00
        NataDoCampo.Forms.setMask('cpf', '000.000.000-00');
        
        // CNPJ: 00.000.000/0000-00
        NataDoCampo.Forms.setMask('cnpj', '00.000.000/0000-00');
        
        // Telefone: (00) 00000-0000 ou (00) 0000-0000
        NataDoCampo.Forms.setMask('telefone_contato', '(00) 00000-0000');
        
        // CEP: 00000-000
        NataDoCampo.Forms.setMask('cep', '00000-000');
    },

    /**
     * Valida o formulário antes do envio
     */
    validateForm(form) {
        let isValid = true;
        const errors = [];

        // Validação de CPF/CNPJ
        const tipoPessoa = form.querySelector('input[name="tipo_pessoa"]:checked').value;
        if (tipoPessoa === 'F') {
            const cpf = form.querySelector('#cpf').value.replace(/\D/g, '');
            if (!this.validarCPF(cpf)) {
                errors.push('CPF inválido');
                isValid = false;
            }
        } else {
            const cnpj = form.querySelector('#cnpj').value.replace(/\D/g, '');
            if (!this.validarCNPJ(cnpj)) {
                errors.push('CNPJ inválido');
                isValid = false;
            }
        }

        // Validação do tipo de cliente
        const tipoCliente = form.querySelector('#tipo_cliente_id').value;
        if (!tipoCliente) {
            errors.push('Tipo de cliente é obrigatório');
            isValid = false;
        }

        // Validação do nome
        const nome = form.querySelector('#nome_cliente').value.trim();
        if (!nome || nome.length < 3) {
            errors.push('Nome deve ter no mínimo 3 caracteres');
            isValid = false;
        }

        // Validação do email
        const email = form.querySelector('#email').value.trim();
        if (email && !this.validarEmail(email)) {
            errors.push('E-mail inválido');
            isValid = false;
        }

        // Validação do telefone
        const telefone = form.querySelector('#telefone_contato').value.replace(/\D/g, '');
        if (telefone.length < 10) {
            errors.push('Telefone inválido');
            isValid = false;
        }

        // Validação do CEP
        const cep = form.querySelector('#cep').value.replace(/\D/g, '');
        if (cep.length !== 8) {
            errors.push('CEP inválido');
            isValid = false;
        }

        // Se houver erros, mostra alerta
        if (!isValid) {
            NataDoCampo.showAlert(
                'Erro de Validação',
                'Corrija os seguintes erros:<br>' + errors.map(e => `- ${e}`).join('<br>'),
                'error'
            );
        }

        return isValid;
    },

    /**
     * Valida um CPF
     */
    validarCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        
        if (cpf.length !== 11) return false;
        
        // Elimina CPFs inválidos conhecidos
        if (/^(\d)\1{10}$/.test(cpf)) return false;
        
        // Valida 1º dígito verificador
        let soma = 0;
        for (let i = 0; i < 9; i++) {
            soma += parseInt(cpf.charAt(i)) * (10 - i);
        }
        let rev = 11 - (soma % 11);
        if (rev === 10 || rev === 11) rev = 0;
        if (rev !== parseInt(cpf.charAt(9))) return false;
        
        // Valida 2º dígito verificador
        soma = 0;
        for (let i = 0; i < 10; i++) {
            soma += parseInt(cpf.charAt(i)) * (11 - i);
        }
        rev = 11 - (soma % 11);
        if (rev === 10 || rev === 11) rev = 0;
        if (rev !== parseInt(cpf.charAt(10))) return false;
        
        return true;
    },

    /**
     * Valida um CNPJ
     */
    validarCNPJ(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');
        
        if (cnpj.length !== 14) return false;
        
        // Elimina CNPJs inválidos conhecidos
        if (/^(\d)\1{13}$/.test(cnpj)) return false;
        
        // Valida DVs
        let tamanho = cnpj.length - 2;
        let numeros = cnpj.substring(0, tamanho);
        const digitos = cnpj.substring(tamanho);
        let soma = 0;
        let pos = tamanho - 7;
        
        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        
        let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado !== parseInt(digitos.charAt(0))) return false;
        
        tamanho = tamanho + 1;
        numeros = cnpj.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;
        
        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }
        
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado !== parseInt(digitos.charAt(1))) return false;
        
        return true;
    },

    /**
     * Valida um e-mail
     */
    validarEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
};

// Inicializa o módulo quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => NataDoCampo.Clientes.init());