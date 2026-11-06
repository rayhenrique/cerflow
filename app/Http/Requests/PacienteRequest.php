<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Paciente;
use App\Rules\CnsValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['recepcao', 'coordenacao']);
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('cns')) {
            $merge['cns'] = preg_replace('/\D/', '', (string) $this->input('cns'));
        }

        if ($this->filled('cpf')) {
            $merge['cpf'] = preg_replace('/\D/', '', (string) $this->input('cpf'));
        }

        if ($this->has('municipio_ibge')) {
            $merge['municipio_ibge'] = preg_replace('/\D/', '', (string) $this->input('municipio_ibge'));
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $pacienteParam = $this->route('paciente') ?? $this->input('id');
        $pacienteId = $pacienteParam instanceof Paciente ? $pacienteParam->id : $pacienteParam;

        return [
            'cns' => [
                'required',
                'string',
                'digits:15',
                new CnsValido,
                Rule::unique('pacientes', 'cns')->ignore($pacienteId),
            ],
            'cpf' => [
                'nullable',
                'string',
                'digits:11',
                Rule::unique('pacientes', 'cpf')->ignore($pacienteId),
            ],
            'nome' => ['required', 'string', 'max:255'],
            'data_nascimento' => ['required', 'date', 'before_or_equal:today'],
            'sexo' => ['required', Rule::in(['M', 'F'])],
            'nome_mae' => ['required', 'string', 'max:255'],
            'municipio_ibge' => ['required', 'string', 'digits:6'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'telefone_contato' => ['nullable', 'string', 'max:20'],
            'cid_principal' => ['required', 'string', 'max:10'],
            'cid_secundario' => ['nullable', 'string', 'max:10'],
            'modalidades_reabilitacao' => ['nullable', 'array'],
            'modalidades_reabilitacao.*' => [
                'string',
                Rule::in(['FISICA', 'INTELECTUAL', 'AUDITIVA', 'VISUAL']),
            ],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
