/**
 * Módulo de gerenciamento de formulários
 */
NataDoCampo.forms = {
    /**
     * Validadores disponíveis
     */
    validators: {
        required: (value) => {
            return value.trim() !== '';
        },
        email: (value) => {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        },
        cpf: (value) => {
            const cpf = value.replace(/[^\d]/g, '');
            if (cpf.length !== 11) return false;
            if (/^(\d)\1+$/.test(cpf)) return false;
            
            let sum = 0;
            let remainder;
            
            for (let i = 1; i <= 9; i++) {
                sum = sum + parseInt(cpf.substring(i-1, i)) * (11 - i);
            }
            
            remainder = (sum * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            if (remainder !== parseInt(cpf.substring(9, 10))) return false;
            
            sum = 0;
            for (let i = 1; i <= 10; i++) {
                sum = sum + parseInt(cpf.substring(i-1, i)) * (12 - i);
            }
            
            remainder = (sum * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            if (remainder !== parseInt(cpf.substring(10, 11))) return false;
            
            return true;
        },
        cnpj: (value) => {
            const cnpj = value.replace(/[^\d]/g, '');
            if (cnpj.length !== 14) return false;
            if (/^(\d)\1+$/.test(cnpj)) return false;
            
            let size = cnpj.length - 2;
            let numbers = cnpj.substring(0, size);
            const digits = cnpj.substring(size);
            let sum = 0;
            let pos = size - 7;
            
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            
            let result = sum % 11 < 2 ? 0 : 11 - sum % 11;
            if (result !== parseInt(digits.charAt(0))) return false;
            
            size = size + 1;
            numbers = cnpj.substring(0, size);
            sum = 0;
            pos = size - 7;
            
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            
            result = sum % 11 < 2 ? 0 : 11 - sum % 11;
            if (result !== parseInt(digits.charAt(1))) return false;
            
            return true;
        },
        phone: (value) => {
            return /^\(\d{2}\) \d{4,5}-\d{4}$/.test(value);
        },
        date: (value) => {
            return /^\d{2}\/\d{2}\/\d{4}$/.test(value);
        },
        cep: (value) => {
            return /^\d{5}-\d{3}$/.test(value);
        },
        minLength: (value, min) => {
            return value.length >= min;
        },
        maxLength: (value, max) => {
            return value.length <= max;
        },
        numeric: (value) => {
            return /^\d+$/.test(value);
        },
        decimal: (value) => {
            return /^\d+(\,\d{1,2})?$/.test(value);
        }
    },

    /**
     * Mensagens de erro padrão
     */
    errorMessages: {
        required: 'Este campo é obrigatório',
        email: 'Por favor, insira um e-mail válido',
        cpf: 'Por favor, insira um CPF válido',
        cnpj: 'Por favor, insira um CNPJ válido',
        phone: 'Por favor, insira um telefone válido',
        date: 'Por favor, insira uma data válida',
        cep: 'Por favor, insira um CEP válido',
        minLength: (min) => `Este campo deve ter no mínimo ${min} caracteres`,
        maxLength: (max) => `Este campo deve ter no máximo ${max} caracteres`,
        numeric: 'Este campo deve conter apenas números',
        decimal: 'Este campo deve conter um valor decimal válido'
    },

    /**
     * Inicializa os formulários
     */
    init() {
        this.setupFormValidation();
        this.setupMasks();
        this.setupDynamicFields();
    },

    /**
     * Configura a validação dos formulários
     */
    setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(form => {
            // Previne submissão se houver erros
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    this.showFormErrors(form);
                }
            });

            // Validação em tempo real
            form.querySelectorAll('[data-validate]').forEach(field => {
                field.addEventListener('blur', () => {
                    this.validateField(field);
                });

                field.addEventListener('input', () => {
                    if (field.classList.contains('error')) {
                        this.validateField(field);
                    }
                });
            });
        });
    },

    /**
     * Configura máscaras de input
     */
    setupMasks() {
        if (typeof IMask !== 'undefined') {
            // CPF
            document.querySelectorAll('.mask-cpf').forEach(el => {
                IMask(el, { mask: '000.000.000-00' });
            });
            
            // CNPJ
            document.querySelectorAll('.mask-cnpj').forEach(el => {
                IMask(el, { mask: '00.000.000/0000-00' });
            });
            
            // Telefone
            document.querySelectorAll('.mask-phone').forEach(el => {
                IMask(el, { mask: '(00) 00000-0000' });
            });
            
            // Data
            document.querySelectorAll('.mask-date').forEach(el => {
                IMask(el, {
                    mask: Date,
                    pattern: 'd/`m/`Y',
                    blocks: {
                        d: {
                            mask: IMask.MaskedRange,
                            from: 1,
                            to: 31,
                            maxLength: 2
                        },
                        m: {
                            mask: IMask.MaskedRange,
                            from: 1,
                            to: 12,
                            maxLength: 2
                        },
                        Y: {
                            mask: IMask.MaskedRange,
                            from: 1900,
                            to: 2099
                        }
                    }
                });
            });
            
            // CEP
            document.querySelectorAll('.mask-cep').forEach(el => {
                IMask(el, { mask: '00000-000' });
            });
            
            // Dinheiro
            document.querySelectorAll('.mask-money').forEach(el => {
                IMask(el, {
                    mask: 'R$ num',
                    blocks: {
                        num: {
                            mask: Number,
                            thousandsSeparator: '.',
                            radix: ',',
                            scale: 2,
                            padFractionalZeros: true
                        }
                    }
                });
            });
        }
    },

    /**
     * Configura campos dinâmicos
     */
    setupDynamicFields() {
        // Auto busca CEP
        $('[data-cep]').on('blur', async function() {
            const cep = $(this).val().replace(/\D/g, '');
            if (cep.length === 8) {
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await response.json();
                    
                    if (!data.erro) {
                        $('[data-cep-logradouro]').val(data.logradouro);
                        $('[data-cep-bairro]').val(data.bairro);
                        $('[data-cep-cidade]').val(data.localidade);
                        $('[data-cep-estado]').val(data.uf);
                    }
                } catch (error) {
                    console.error('Erro ao buscar CEP:', error);
                }
            }
        });

        // Troca entre CPF e CNPJ
        $('[data-tipo-documento]').on('change', function() {
            const tipo = $(this).val();
            const cpfField = $('[data-mask="cpf"]');
            const cnpjField = $('[data-mask="cnpj"]');
            
            if (tipo === 'cpf') {
                cpfField.parent().show();
                cnpjField.parent().hide();
            } else {
                cpfField.parent().hide();
                cnpjField.parent().show();
            }
        });
    },

    /**
     * Valida um formulário inteiro
     */
    validateForm(form) {
        let isValid = true;
        const fields = form.querySelectorAll('[data-validate]');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    },

    /**
     * Valida um campo específico
     */
    validateField(field) {
        const rules = field.dataset.validate.split('|');
        let isValid = true;
        let errorMessage = '';
        
        for (const rule of rules) {
            const [validatorName, param] = rule.split(':');
            
            if (this.validators[validatorName]) {
                const value = field.value;
                const isValidField = param ? 
                    this.validators[validatorName](value, param) : 
                    this.validators[validatorName](value);
                
                if (!isValidField) {
                    isValid = false;
                    errorMessage = param ? 
                        this.errorMessages[validatorName](param) : 
                        this.errorMessages[validatorName];
                    break;
                }
            }
        }
        
        if (!isValid) {
            this.showFieldError(field, errorMessage);
        } else {
            this.clearFieldError(field);
        }
        
        return isValid;
    },

    /**
     * Mostra erro em um campo
     */
    showFieldError(field, message) {
        field.classList.add('error');
        
        let feedbackElement = field.nextElementSibling;
        if (!feedbackElement || !feedbackElement.classList.contains('form-feedback')) {
            feedbackElement = document.createElement('div');
            feedbackElement.className = 'form-feedback error';
            field.parentNode.insertBefore(feedbackElement, field.nextSibling);
        }
        
        feedbackElement.textContent = message;
        feedbackElement.style.display = 'block';
    },

    /**
     * Limpa erro de um campo
     */
    clearFieldError(field) {
        field.classList.remove('error');
        
        const feedbackElement = field.nextElementSibling;
        if (feedbackElement && feedbackElement.classList.contains('form-feedback')) {
            feedbackElement.style.display = 'none';
        }
    },

    /**
     * Mostra erros do formulário
     */
    showFormErrors(form) {
        const firstError = form.querySelector('.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        NataDoCampo.showMessage('Por favor, corrija os erros no formulário antes de continuar.', 'error');
    },

    /**
     * Serializa um formulário para objeto
     */
    serializeForm(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (const [key, value] of formData.entries()) {
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        }
        
        return data;
    },

    /**
     * Preenche um formulário com dados
     */
    fillForm(form, data) {
        Object.keys(data).forEach(key => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'radio') {
                    form.querySelector(`[name="${key}"][value="${data[key]}"]`).checked = true;
                } else if (field.type === 'checkbox') {
                    field.checked = !!data[key];
                } else {
                    field.value = data[key];
                }
                
                // Dispara evento de change para acionar máscaras e validações
                field.dispatchEvent(new Event('change'));
            }
        });
    }
};

// Inicializa os formulários quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    NataDoCampo.forms.init();
});