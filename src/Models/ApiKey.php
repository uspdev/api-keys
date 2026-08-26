<?php

namespace Uspdev\ApiKeys\Models;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use Throwable;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;

/** Representa uma credencial com hash vinculada polimorficamente ao proprietário. */
class ApiKey extends Model
{
    /** Finalidade padrão para integrações que consomem dados estruturados. */
    public const PURPOSE_INTEGRATION = 'integration';

    /** Finalidade padrão para integrações que consomem contexto de IA. */
    public const PURPOSE_AI = 'ai';

    /** Papel padrão com acesso de leitura limitado. */
    public const ROLE_VIEWER = 'viewer';

    /** Papel padrão com acesso de leitura e escrita definido pelo owner. */
    public const ROLE_COLLABORATOR = 'collaborator';

    /** Papel padrão que normalmente recebe todas as abilities do owner. */
    public const ROLE_ADMINISTRATOR = 'administrator';

    protected $table = 'uspdev_api_keys';

    protected $fillable = [
        'name',
        'purpose',
        'role',
        'expires_at',
        'created_by',
        'revoked_by',
    ];

    protected $hidden = [
        'secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'access_count' => 'integer',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'created_by' => 'integer',
            'revoked_by' => 'integer',
        ];
    }

    /**
     * Resolve o modelo polimórfico proprietário desta credencial.
     *
     * @return MorphTo<Model, $this> Relação polimórfica com o owner da chave.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determina se a credencial foi revogada explicitamente.
     *
     * @return bool `true` quando `revoked_at` estiver preenchido.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Determina se a data de expiração configurada já passou.
     *
     * @return bool `true` quando existir expiração anterior ao momento atual.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Determina se a credencial ainda pode ser autenticada.
     *
     * @return bool `true` para chaves não revogadas e não expiradas.
     */
    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * Verifica uma ability pela API pública de autorização da credencial.
     *
     * @param string $ability Ability de negócio exigida pela operação.
     * @return bool `true` quando o owner concede a ability ao papel da chave ou concede `*`.
     */
    public function allows(string $ability): bool
    {
        $owner = $this->owner;

        if ($owner === null) {
            return false;
        }

        if (! method_exists($owner, 'abilities')) {
            return false;
        }

        $abilities = $owner->abilities($this->role);

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /**
     * Autentica um token pelo serviço de chaves resolvido no container.
     *
     * @param string $token Credencial completa enviada pelo cliente.
     * @return self|null Chave ativa autenticada ou `null` para token recusado.
     */
    public static function authenticate(string $token): ?self
    {
        return app(ApiKeyManager::class)->authenticate($token);
    }

    /**
     * Retorna os dados necessários para renderizar o gerenciador Blade incorporável.
     *
     * @param Model $owner Owner cujas chaves serão exibidas.
     * @param string|null $ownerAlias Alias configurado para o owner ou `null` para resolvê-lo.
     * @return array{apiKeys: \Illuminate\Database\Eloquent\Collection<int, self>, ownerAlias: string, ownerRouteKey: string, purposes: array<string, string>, roles: array<string, string>, componentId: string, storeUrl: string} Dados base do componente.
     *
     * @throws InvalidArgumentException Quando o owner não usa `HasApiKeys` ou não está configurado.
     */
    public static function managerData(Model $owner, ?string $ownerAlias = null): array
    {
        if (! method_exists($owner, 'apiKeys')) {
            throw new InvalidArgumentException(
                'The API key manager owner must use the HasApiKeys trait.'
            );
        }

        $ownerAlias = self::resolveManagerOwnerAlias($owner, $ownerAlias);
        $ownerRouteKey = (string) $owner->getRouteKey();

        return [
            'apiKeys' => $owner->apiKeys()->latest('created_at')->get(),
            'ownerAlias' => $ownerAlias,
            'ownerRouteKey' => $ownerRouteKey,
            'purposes' => (array) config('api-keys.interface.purposes', []),
            'roles' => (array) config('api-keys.interface.roles', []),
            'componentId' => 'api-keys-manager-' . substr(
                sha1($ownerAlias . '|' . $ownerRouteKey . '|' . spl_object_id($owner)),
                0,
                12,
            ),
            'storeUrl' => route('api-keys.keys.store', [
                'ownerAlias' => $ownerAlias,
                'owner' => $ownerRouteKey,
            ]),
        ];
    }

    /**
     * Prepara todos os dados consumidos pelo componente Blade de gerenciamento.
     *
     * @param Model $owner Owner cujas chaves serão renderizadas.
     * @param string|null $ownerAlias Alias configurado para o owner ou `null` para resolvê-lo.
     * @return array<string, mixed> Dados completos para a view `api-keys::components.manager`.
     *
     * @throws InvalidArgumentException Quando o owner não usa `HasApiKeys` ou não está configurado.
     */
    public static function managerViewData(Model $owner, ?string $ownerAlias = null): array
    {
        $managerData = self::managerData($owner, $ownerAlias);
        $purposes = $managerData['purposes'];
        $roles = $managerData['roles'];
        $resolvedOwnerAlias = $managerData['ownerAlias'];
        $createForm = self::managerCreateFormData(
            $managerData['storeUrl'],
            $purposes,
            $roles,
        );

        $managerData['apiKeys'] = $managerData['apiKeys']->map(
            fn (self $apiKey): array => $apiKey->managerRowData(
                $owner,
                $resolvedOwnerAlias,
                $purposes,
                $roles,
            )
        );

        return [
            ...$managerData,
            ...self::createdApiKeyViewData($resolvedOwnerAlias, $managerData['ownerRouteKey']),
            'newApiKeyForm' => $createForm,
            'apiKeyForm' => self::managerFormData(
                $createForm,
                $managerData['apiKeys']->all(),
                $resolvedOwnerAlias,
                $managerData['ownerRouteKey'],
            ),
            'hasApiKeyFormErrors' => self::hasApiKeyFormErrors(
                $resolvedOwnerAlias,
                $managerData['ownerRouteKey'],
            ),
        ];
    }

    /**
     * Formata uma chave para a tabela e para o modal compartilhado de detalhes.
     *
     * @param Model $owner Owner ao qual a chave pertence.
     * @param string $ownerAlias Alias configurado para montar as URLs de ação.
     * @param array<string, string> $purposes Rótulos configurados para cada finalidade.
     * @param array<string, string> $roles Rótulos configurados para cada papel.
     * @return array<string, mixed> Dados serializáveis de uma linha do gerenciador.
     */
    public function managerRowData(Model $owner, string $ownerAlias, array $purposes, array $roles): array
    {
        $status = $this->managerStatusData();
        $credential = (string) config('api-keys.credential_prefix', 'gpp') . '_' . $this->prefix;
        $purpose = $purposes[$this->purpose] ?? ucfirst($this->purpose);
        $role = $roles[$this->role] ?? ucfirst($this->role);
        $accessCount = number_format($this->access_count);

        return [
            'name' => $this->name,
            'credential' => $credential,
            'purpose' => $purpose,
            'role' => $role,
            'status' => $status,
            'last_used_title' => $this->last_used_at?->format('d/m/Y H:i:s'),
            'last_used_human' => $this->last_used_at?->diffForHumans(),
            'access_count' => $accessCount,
            'is_active' => $this->isActive(),
            'renewal_form' => $this->managerRenewalFormData($owner, $ownerAlias),
            'revoke_url' => $this->managerRevokeUrl($owner, $ownerAlias),
            'details' => [
                'name' => $this->name,
                'prefix' => $credential,
                'status' => $status['label'],
                'status_class' => $status['class'],
                'purpose' => $purpose,
                'role' => $role,
                'created_at' => $this->created_at?->format('d/m/Y H:i') ?? '—',
                'expires_at' => $this->expires_at?->format('d/m/Y H:i') ?? 'Nunca',
                'last_used_at' => $this->last_used_at?->format('d/m/Y H:i') ?? 'Nunca',
                'last_used_ip' => $this->last_used_ip ?? '—',
                'access_count' => $accessCount,
                'created_by' => $this->created_by,
                'revoked_at' => $this->revoked_at?->format('d/m/Y H:i'),
                'revoked_by' => $this->revoked_by,
            ],
        ];
    }

    /**
     * Prepara os valores editáveis usados para renovar esta chave no modal compartilhado.
     *
     * @param Model $owner Owner ao qual a chave pertence.
     * @param string|null $ownerAlias Alias configurado para montar a URL de renovação.
     * @return array<string, mixed> Estado inicial do formulário de renovação.
     */
    public function managerRenewalFormData(Model $owner, ?string $ownerAlias = null): array
    {
        return [
            'action' => $this->managerRenewUrl($owner, $ownerAlias),
            'operation' => 'renew',
            'api_key_id' => (string) $this->getKey(),
            'title' => 'Renovar API Key',
            'submit_label' => 'Renovar chave',
            'errors' => [],
            'values' => [
                'name' => (string) $this->name,
                'purpose' => (string) $this->purpose,
                'role' => (string) $this->role,
                'expires_at' => $this->expires_at?->format('Y-m-d') ?? '',
            ],
        ];
    }

    /**
     * Monta a URL de revogação de uma chave vinculada ao owner informado.
     *
     * @param Model $owner Owner ao qual a chave pertence.
     * @param string|null $ownerAlias Alias configurado ou `null` para resolvê-lo.
     * @return string URL gerada pela rota nomeada de revogação.
     *
     * @throws InvalidArgumentException Quando o owner não estiver registrado no alias informado.
     */
    public function managerRevokeUrl(Model $owner, ?string $ownerAlias = null): string
    {
        $ownerAlias = self::resolveManagerOwnerAlias($owner, $ownerAlias);

        return route('api-keys.keys.revoke', [
            'ownerAlias' => $ownerAlias,
            'owner' => (string) $owner->getRouteKey(),
            'apiKey' => $this->getRouteKey(),
        ]);
    }

    /**
     * Monta a URL de renovação de uma chave vinculada ao owner informado.
     *
     * @param Model $owner Owner ao qual a chave pertence.
     * @param string|null $ownerAlias Alias configurado ou `null` para resolvê-lo.
     * @return string URL da rota nomeada de renovação.
     *
     * @throws InvalidArgumentException Quando o owner não estiver registrado no alias informado.
     */
    public function managerRenewUrl(Model $owner, ?string $ownerAlias = null): string
    {
        $ownerAlias = self::resolveManagerOwnerAlias($owner, $ownerAlias);

        return route('api-keys.keys.renew', [
            'ownerAlias' => $ownerAlias,
            'owner' => (string) $owner->getRouteKey(),
            'apiKey' => $this->getRouteKey(),
        ]);
    }

    /**
     * Resolve e valida o alias configurado para a classe do owner.
     *
     * @param Model $owner Owner cuja classe será procurada na configuração.
     * @param string|null $ownerAlias Alias explícito ou `null` para descoberta automática.
     * @return string Alias válido para a classe do owner.
     *
     * @throws InvalidArgumentException Quando o alias não corresponder ao owner configurado.
     */
    private static function resolveManagerOwnerAlias(Model $owner, ?string $ownerAlias): string
    {
        $owners = (array) config('api-keys.owners', []);

        if ($ownerAlias !== null) {
            if (($owners[$ownerAlias] ?? null) !== $owner::class) {
                throw new InvalidArgumentException(
                    sprintf('Owner alias [%s] is not registered for [%s].', $ownerAlias, $owner::class)
                );
            }

            return $ownerAlias;
        }

        $resolvedAlias = array_search($owner::class, $owners, true);

        if ($resolvedAlias === false) {
            throw new InvalidArgumentException(
                sprintf('Owner class [%s] must be registered in the api-keys.owners configuration.', $owner::class)
            );
        }

        return (string) $resolvedAlias;
    }

    /**
     * Retorna o rótulo e a classe visual correspondentes ao estado da chave.
     *
     * @return array{label: 'Ativa'|'Expirada'|'Revogada', class: 'badge-success'|'badge-warning'|'badge-danger'} Estado pronto para renderização.
     */
    private function managerStatusData(): array
    {
        if ($this->isRevoked()) {
            return ['label' => 'Revogada', 'class' => 'badge-danger'];
        }

        if ($this->isExpired()) {
            return ['label' => 'Expirada', 'class' => 'badge-warning'];
        }

        return ['label' => 'Ativa', 'class' => 'badge-success'];
    }

    /**
     * Recupera e consome o token temporário pertencente ao gerenciador atual.
     *
     * @param string $ownerAlias Alias do owner exibido pelo componente.
     * @param string $ownerRouteKey Chave de rota do owner exibido pelo componente.
     * @return array{createdApiKeyToken: string|null, createdApiKeyAction: 'created'|'renewed', createdApiKeyBelongsToManager: bool} Dados temporários da flash session.
     */
    private static function createdApiKeyViewData(string $ownerAlias, string $ownerRouteKey): array
    {
        $result = [
            'createdApiKeyToken' => null,
            'createdApiKeyAction' => 'created',
            'createdApiKeyBelongsToManager' => false,
        ];
        $createdApiKey = session('api-keys.created');

        if (! is_array($createdApiKey)) {
            return $result;
        }

        $result['createdApiKeyAction'] = ($createdApiKey['action'] ?? null) === 'renewed'
            ? 'renewed'
            : 'created';
        $belongsToManager = ($createdApiKey['owner_alias'] ?? null) === $ownerAlias
            && (string) ($createdApiKey['owner_key'] ?? '') === $ownerRouteKey;
        $encryptedToken = $createdApiKey['encrypted_token'] ?? null;

        if (! $belongsToManager || ! is_string($encryptedToken)) {
            return $result;
        }

        try {
            $token = app(Encrypter::class)->decrypt($encryptedToken, false);
            $result['createdApiKeyToken'] = is_string($token) ? $token : null;
        } catch (Throwable) {
            $result['createdApiKeyToken'] = null;
        } finally {
            session()->forget('api-keys.created');
        }

        $result['createdApiKeyBelongsToManager'] = $result['createdApiKeyToken'] !== null;

        return $result;
    }

    /**
     * Prepara o estado inicial do modal para a criação de uma nova chave.
     *
     * @param string $storeUrl URL da rota que recebe a criação.
     * @param array<string, string> $purposes Finalidades disponíveis na interface.
     * @param array<string, string> $roles Papéis disponíveis na interface.
     * @return array<string, mixed> Estado inicial do formulário de criação.
     */
    private static function managerCreateFormData(string $storeUrl, array $purposes, array $roles): array
    {
        return [
            'action' => $storeUrl,
            'operation' => 'create',
            'api_key_id' => '',
            'title' => 'Nova API Key',
            'submit_label' => 'Criar chave',
            'errors' => [],
            'values' => [
                'name' => '',
                'purpose' => (string) (array_key_first($purposes) ?? ''),
                'role' => (string) (array_key_first($roles) ?? ''),
                'expires_at' => '',
            ],
        ];
    }

    /**
     * Restaura no modal os dados submetidos quando houver erro de validação.
     *
     * @param array<string, mixed> $createForm Estado padrão do formulário de criação.
     * @param list<array<string, mixed>> $apiKeys Linhas de chaves exibidas pelo gerenciador.
     * @param string $ownerAlias Alias do owner exibido pelo componente.
     * @param string $ownerRouteKey Chave de rota do owner exibido pelo componente.
     * @return array<string, mixed> Formulário com valores antigos e erros da sessão, quando aplicáveis.
     */
    private static function managerFormData(
        array $createForm,
        array $apiKeys,
        string $ownerAlias,
        string $ownerRouteKey,
    ): array
    {
        if (! self::hasApiKeyFormErrors($ownerAlias, $ownerRouteKey)) {
            return $createForm;
        }

        $form = $createForm;
        $operation = session()->getOldInput('_api_keys_operation');
        $apiKeyId = (string) session()->getOldInput('_api_keys_id', '');

        if ($operation === 'renew') {
            foreach ($apiKeys as $apiKey) {
                if ((string) ($apiKey['renewal_form']['api_key_id'] ?? '') === $apiKeyId) {
                    $form = $apiKey['renewal_form'];

                    break;
                }
            }
        }

        foreach (array_keys($form['values']) as $field) {
            $value = session()->getOldInput($field, $form['values'][$field]);
            $form['values'][$field] = is_scalar($value) ? (string) $value : '';
        }

        $errors = session('errors');

        if ($errors instanceof ViewErrorBag) {
            foreach (array_keys($form['values']) as $field) {
                if ($errors->has($field)) {
                    $form['errors'][$field] = $errors->first($field);
                }
            }
        }

        return $form;
    }

    /**
     * Verifica se os erros pertencem ao formulário deste gerenciador.
     *
     * @param string $ownerAlias Alias do owner exibido pelo componente.
     * @param string $ownerRouteKey Chave de rota do owner exibido pelo componente.
     * @return bool `true` quando houver erros associados a este owner.
     */
    private static function hasApiKeyFormErrors(string $ownerAlias, string $ownerRouteKey): bool
    {
        $errors = session('errors');

        if (! $errors instanceof ViewErrorBag) {
            return false;
        }

        if (
            session()->getOldInput('_api_keys_owner_alias') !== $ownerAlias
            || (string) session()->getOldInput('_api_keys_owner_key', '') !== $ownerRouteKey
        ) {
            return false;
        }

        foreach (['name', 'purpose', 'role', 'expires_at'] as $field) {
            if ($errors->has($field)) {
                return true;
            }
        }

        return false;
    }
}
