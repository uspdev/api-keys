# API Key

Este componente provê uma infraestrutura robusta e segura para a gestão de chaves de API associadas a projetos. O sistema foi projetado para suportar tanto integrações de dados tradicionais quanto o consumo de contexto otimizado para Inteligência Artificial (LLMs).

## 🚀 Funcionalidades

- **Gestão Centralizada:** Uma única tabela para autenticação de diversos tipos de integração.
- **Segurança:** Armazenamento de chaves utilizando hash, garantindo que a chave original nunca fique exposta no banco de dados.
- **Propósito:** Suporte para retornos estruturados (`integration`) ou contextos consolidados para IA (`ai`).
- **Controle de Acesso:** Permissões baseadas em papéis (RBAC) herdadas diretamente do projeto.
- **Rastreabilidade:** Monitoramento de contagem de acessos e data do último uso.

## Installation

### 1. **Instale a biblioteca pelo Composer**

Execute o comando abaixo para instalar o pacote:

```bash
composer require uspdev/api-key
```

### 2. **Publique a configuração e as migrations**

```bash
php artisan vendor:publish --tag=api-key-config
php artisan vendor:publish --tag=api-key-migrations
```

### 3. **Execute as migrations**

```bash
php artisan migrate
```

## Requirements

- PHP 8.2 ou superior;
- Laravel 12;

## Configuração

A biblioteca pode ser configurada editando o arquivo `config/api-key.php`, publicado durante a instalação.

## Contribuições

Contribuições são bem-vindas. Para contribuir:

1. Faça um fork do repositório;
2. Crie uma branch para sua alteração;
3. Implemente e teste as mudanças;
4. Envie seus commits para o fork;
5. Abra um Pull Request.

## Licença

Este pacote é distribuído sob a licença GPL-2.0-or-later.
