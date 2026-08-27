<?php

namespace Uspdev\ApiKeys\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;

/** Autentica uma API Key, autoriza suas abilities e a anexa à requisição. */
class AuthenticateApiKey
{
    /**
     * Recebe o serviço que autentica as credenciais.
     *
     * @param ApiKeyManager $apiKeys Serviço de ciclo de vida das API Keys.
     */
    public function __construct(private readonly ApiKeyManager $apiKeys) {}

    /**
     * Autentica a credencial e exige ao menos uma das abilities da rota.
     *
     * @param Request $request Requisição que contém a credencial.
     * @param Closure(Request): Response $next Próximo middleware ou controlador da cadeia.
     * @param string ...$abilities Abilities alternativas exigidas pela rota.
     * @return Response Resposta seguinte ou erro 401, 403 ou 500.
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $abilities = array_map('trim', $abilities);

        if ($abilities === [] || in_array('', $abilities, true)) {
            return $this->invalidAbilitiesResponse();
        }

        $token = $request->bearerToken();

        /** Permite o fallback menos seguro por query string apenas quando habilitado. */
        if (! $token && config('api-keys.query_parameter.enabled', false)) {
            $parameter = (string) config('api-keys.query_parameter.name', 'api_key');
            $token = $request->query($parameter);
        }

        if (! is_string($token) || $token === '') {
            return $this->unauthenticatedResponse();
        }

        $apiKey = $this->apiKeys->authenticate($token, $request->ip());

        if ($apiKey === null) {
            return $this->unauthenticatedResponse();
        }

        $attribute = (string) config('api-keys.middleware.request_attribute', 'apiKey');
        $request->attributes->set($attribute, $apiKey);

        // Permite que a rota seja acessada se a API Key tiver pelo menos uma das abilities exigidas.
        foreach ($abilities as $ability) {
            if ($apiKey->allows($ability)) {
                return $next($request);
            }
        }

        return $this->forbiddenResponse();
    }

    /**
     * Cria a resposta uniforme para credenciais ausentes, malformadas ou inválidas.
     *
     * @return JsonResponse Resposta JSON com HTTP 401.
     */
    private function unauthenticatedResponse(): JsonResponse
    {
        return new JsonResponse(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Cria a resposta para uma chave válida sem nenhuma ability exigida.
     *
     * @return JsonResponse Resposta JSON com HTTP 403.
     */
    private function forbiddenResponse(): JsonResponse
    {
        return new JsonResponse(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
    }

    /**
     * Cria a resposta para middleware sem abilities válidas configuradas.
     *
     * @return JsonResponse Resposta JSON com HTTP 500.
     */
    private function invalidAbilitiesResponse(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'O middleware requer pelo menos uma ability válida',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
