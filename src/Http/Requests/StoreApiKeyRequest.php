<?php

namespace Uspdev\ApiKeys\Http\Requests;

use Closure;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valida os dados usados para criar uma API Key pela interface de gerenciamento. */
class StoreApiKeyRequest extends FormRequest
{
    /** Mantém a autorização vinculada ao owner, resolvida pelo controller. */
    public function authorize(): bool
    {
        return true;
    }

    /** Retorna as regras configuráveis dos campos de criação da credencial. */
    public function rules(): array
    {
        $purposes = array_keys((array) config('api-keys.interface.purposes', []));
        $roles = array_keys((array) config('api-keys.interface.roles', []));

        return [
            'name' => ['required', 'string', 'max:255'],
            'purpose' => ['required', Rule::in($purposes)],
            'role' => ['required', Rule::in($roles)],
            'expires_at' => [
                'nullable',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $expiration = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
                    $errors = DateTimeImmutable::getLastErrors();

                    if (
                        $expiration === false
                        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
                    ) {
                        $fail('A data de expiração deve ser uma data válida.');

                        return;
                    }

                    if ($expiration->format('Y-m-d') < today()->toDateString()) {
                        $fail('A data de expiração não pode ser anterior à data atual.');
                    }
                },
            ],
        ];
    }
}
