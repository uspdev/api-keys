# Sugestões em avaliação

Este documento reúne ideias que podem ser consideradas para evoluções futuras
do package. Seu conteúdo não representa requisito, decisão arquitetural fechada
ou funcionalidade comprometida.

As propostas descritas aqui devem ser amadurecidas e, caso sejam aprovadas,
incorporadas à documentação principal antes da implementação.

## Exibir uma URL de uso após a criação da API Key

### Motivação

Algumas ferramentas, especialmente clientes de IA que consomem conteúdo por
uma URL, podem não permitir a configuração do header `Authorization: Bearer`.
Nesses casos, seria útil apresentar ao usuário uma URL pronta para copiar junto
da credencial recém-criada.

### Possível comportamento

O fluxo poderia funcionar da seguinte forma:

1. O package cria e persiste a API Key, armazenando somente o hash do segredo.
2. O token completo permanece disponível temporariamente para a exibição
   única já realizada pelo package.
3. Se o owner implementar um contrato opcional, o package entrega a ele a
   instância de `ApiKey` e o token completo recém-gerado.
4. A aplicação hospedeira monta a URL segundo suas próprias rotas e regras de
   negócio e retorna uma `string` ou `null`.
5. O modal de sucesso apresenta a credencial e, quando disponível, a URL de
   uso, ambas com botões para copiar.
6. A URL e o token deixam de ficar disponíveis depois da exibição única.

O contrato poderia ter uma forma semelhante a esta:

```php
interface ProvidesApiKeyUsageUrl
{
    public function apiKeyUsageUrl(
        ApiKey $apiKey,
        #[SensitiveParameter] string $plainTextToken,
    ): ?string;
}
```

Essa assinatura é apenas ilustrativa e ainda não deve ser considerada parte da
API pública do package.

### Limites de responsabilidade

O package continuaria responsável somente por criar a credencial e realizar sua
exibição temporária. A aplicação hospedeira continuaria responsável por:

- definir o endpoint de negócio;
- decidir se aquela integração admite autenticação por query string;
- montar a URL;
- produzir os dados ou o contexto consumido pela ferramenta externa.

O package não deve inferir uma URL a partir do `purpose` nem conhecer rotas ou
regras de negócio específicas do owner.

### Cuidados de segurança

Uma URL contendo a API Key pode ser registrada no histórico do navegador, em
logs de acesso, proxies, ferramentas de monitoramento ou cabeçalhos de
referência. Por isso, essa forma de consumo deve permanecer excepcional e ser
utilizada apenas quando o cliente não oferecer suporte ao header Bearer.

Se a ideia for aprovada, devem ser preservadas pelo menos estas garantias:

- a URL completa não deve ser persistida no banco de dados;
- token e URL não devem aparecer em logs, eventos ou mensagens de erro;
- durante o redirecionamento, ambos devem permanecer apenas na flash session
  criptografada;
- a sessão deve ser consumida e removida na primeira renderização do owner
  correspondente;
- a URL não deve ser acessada automaticamente pelo componente;
- o suporte existente a API Key por query string deve estar explicitamente
  habilitado na aplicação.

### Pontos a amadurecer

Antes de implementar, ainda será necessário decidir:

- o nome e a assinatura definitiva do contrato;
- se o recurso também será aplicado à renovação de chaves;
- como tratar falhas da aplicação ao montar a URL sem impedir a entrega do
  token criado;
- quais validações devem ser aplicadas à URL retornada;
- quais orientações adicionais sobre cache, logs e exposição da query string
  devem constar na documentação de uso;
- se a conveniência para ferramentas sem suporte a Bearer compensa o aumento
  da superfície de exposição da credencial.

### Situação

**Ideia em avaliação. Não aprovada para implementação.**
