# Nata do Campo - Sistema de Gestão

Sistema de gestão desenvolvido para a Nata do Campo, focado no controle de vendas, estoque, clientes e programa de fidelidade.

## 🚀 Funcionalidades Principais

- **Gestão de Vendas**
  - Lançamento de vendas
  - Controle de pedidos
  - Histórico de transações
  - Relatórios de vendas

- **Controle de Estoque**
  - Cadastro de produtos
  - Gestão de lotes
  - Controle de validade
  - Movimentações de entrada/saída
  - Alertas de estoque baixo

- **Gestão de Clientes**
  - Cadastro de clientes
  - Categorização por tipo
  - Histórico de compras
  - Preços diferenciados por categoria

- **Clube Nata (Programa de Fidelidade)**
  - Sistema de pontuação
  - Recompensas
  - Histórico de resgates
  - Indicações

- **Controle de Usuários**
  - Níveis de acesso (Admin, Gestor, Vendedor, Industria)
  - Gestão de permissões
  - Registro de ações

## 🛠️ Tecnologias Utilizadas

- PHP 8.3
- MySQL 8.0
- HTML5/CSS3
- JavaScript
- Bootstrap (Framework CSS)
- FontAwesome (Ícones)

## 📋 Requisitos do Sistema

- PHP >= 8.3
- MySQL >= 8.0
- Servidor Web (Apache/Nginx)
- Extensões PHP:
  - mysqli
  - pdo
  - session

## 🔧 Instalação

1. Clone o repositório
2. Configure o banco de dados usando o arquivo `database/database.sql`
3. Configure as credenciais do banco em `config/conexao.php`
4. Configure o servidor web para apontar para a pasta `public`
5. Acesse o sistema através do navegador

## 🔐 Configuração do Banco de Dados

O arquivo de configuração do banco de dados está localizado em:
```
config/conexao.php
```

## 📁 Estrutura de Diretórios

```
├── api/              # Endpoints da API
├── config/           # Arquivos de configuração
├── database/         # Scripts do banco de dados
├── includes/         # Arquivos incluídos globalmente
├── public/           # Arquivos públicos
│   ├── assets/      # Recursos estáticos
│   ├── css/         # Estilos
│   └── images/      # Imagens
└── src/             # Código fonte
    ├── Cliente/     # Módulo de clientes
    ├── ClubeNata/   # Módulo do programa de fidelidade
    ├── Estoque/     # Módulo de estoque
    ├── Pedido/      # Módulo de pedidos
    ├── Produto/     # Módulo de produtos
    ├── Usuario/     # Módulo de usuários
    ├── Utils/       # Utilitários
    └── Venda/       # Módulo de vendas
```

## 👥 Níveis de Acesso

- **Admin**: Acesso total ao sistema
- **Gestor**: Acesso a relatórios e gestão operacional
- **Vendedor**: Lançamento de vendas e consultas
- **Industria**: Gestão de estoque e produção

## 📊 Módulo de Vendas

- Lançamento de vendas
- Bonificações
- Trocas
- Validação de pedidos
- Relatórios gerenciais

## 📦 Módulo de Estoque

- Controle de lotes
- Rastreabilidade
- Movimentações
- Alertas de estoque baixo
- Relatórios de movimentação

## 🎯 Clube Nata (Fidelidade)

- Sistema de pontuação
- Catálogo de recompensas
- Gestão de resgates
- Sistema de indicações

## ⚙️ Configurações Principais

- Tipos de cliente
- Preços por categoria
- Limites de estoque
- Pontuações do programa de fidelidade

## 🤝 Suporte

Para suporte, entre em contato através do WhatsApp:
- 📱 (14) 99855-9540

## 📝 Licença

© 2025 Nata do Campo - Todos os direitos reservados