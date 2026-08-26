# Uso do package `uspdev/api-keys`

Esta pasta reúne a documentação prática para instalar, configurar e integrar
o package em uma aplicação Laravel.

## Documentos

| Arquivo | Conteúdo |
| --- | --- |
| [guia.md](guia.md) | Passo a passo mínimo para instalar e começar a usar o package. |
| [contrato.md](contrato.md) | Contratos públicos, assinaturas e responsabilidades de integração. |
| [configuracao.md](configuracao.md) | Arquivo de configuração, opções disponíveis e variáveis de ambiente. |
| [traits-e-rotas.md](traits-e-rotas.md) | Traits dos owners, middleware e criação das rotas de negócio. |
| [views.md](views.md) | Componente Blade, componentes internos e integração na página da aplicação. |

## Ordem recomendada

1. Leia o [guia.md](guia.md) para a instalação inicial.
2. Consulte [contrato.md](contrato.md) para implementar os owners e consumir a API pública.
3. Configure os owners e as opções em [configuracao.md](configuracao.md).
4. Adicione as traits e proteja as rotas em [traits-e-rotas.md](traits-e-rotas.md).
5. Incorpore o componente na página da aplicação conforme [views.md](views.md).

As regras de negócio, abilities, respostas das APIs e comportamento associado
ao `purpose` continuam pertencendo à aplicação hospedeira.
