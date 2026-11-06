<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class AssinarEvolucaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || $user->profissional === null || ! $user->profissional->ativo) {
            return false;
        }

        return $user->hasAnyRole(['terapeuta', 'coordenacao']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'agendamento_id' => ['required', 'integer', 'exists:agendamentos,id'],
            'procedimento_id' => ['required', 'integer', 'exists:procedimentos_sigtap,id'],
            'queixa_estado_atual' => ['required', 'string', 'min:5'],
            'conduta_terapeutica' => ['required', 'string', 'min:5'],
            'intercorrencias' => ['nullable', 'string'],
            'pin_assinatura' => ['required', 'string', 'min:4', 'max:12'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $profissional = $this->user()?->profissional;
                $pin = (string) $this->input('pin_assinatura');

                if ($profissional === null || ! Hash::check($pin, $profissional->pin_assinatura_hash)) {
                    $validator->errors()->add('pin_assinatura', 'PIN de assinatura eletrônica inválido.');
                }
            },
        ];
    }
}
