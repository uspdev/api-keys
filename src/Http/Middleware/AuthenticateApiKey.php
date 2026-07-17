<?php

namespace Uspdev\ApiKeys\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;

/** Autentica uma API Key recebida e a anexa à requisição. */
class AuthenticateApiKey
{
  
    public function __construct(private readonly ApiKeyManager $apiKeys)
    {
    }

    /** Extrai, autentica e expõe uma credencial antes de continuar a requisição. */
    public function handle(Request $request, Closure $next): Response
    {
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

        return $next($request);
    }

    /** Cria a resposta uniforme para credenciais ausentes, malformadas ou inválidas. */
    private function unauthenticatedResponse(): JsonResponse
    {
        return new JsonResponse(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
    }
}
