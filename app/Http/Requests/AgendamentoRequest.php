<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Agendamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['recepcao', 'coordenacao']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'sala_id' => ['required', 'integer', 'exists:salas,id'],
            'data_atendimento' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'dia_semana' => [
                'required',
                Rule::in(['SEGUNDA', 'TERCA', 'QUARTA', 'QUINTA', 'SEXTA']),
            ],
            'tipo_atendimento' => [
                'required',
                Rule::in(['INDIVIDUAL', 'COMPARTILHADO', 'TRABALHO_INTERNO']),
            ],
            'profissionais' => ['required', 'array', 'min:1'],
            'profissionais.*' => ['required', 'integer', 'distinct', 'exists:profissionais,id'],
            'recorrente' => ['sometimes', 'boolean'],
            'observacao' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $agendamentoParam = $this->route('agendamento') ?? $this->input('agendamento_id');
                $ignorarId = $agendamentoParam instanceof Agendamento ? $agendamentoParam->id : $agendamentoParam;

                $data = (string) $this->input('data_atendimento');
                $inicio = (string) $this->input('hora_inicio');
                $fim = (string) $this->input('hora_fim');
                $salaId = (int) $this->input('sala_id');
                $pacienteId = (int) $this->input('paciente_id');
                $tipoAtendimento = (string) $this->input('tipo_atendimento', 'INDIVIDUAL');
                $compartilhado = $tipoAtendimento === 'COMPARTILHADO';

                $queryBase = Agendamento::query()
                    ->whereDate('data_atendimento', $data)
                    ->where('status', '!=', 'CANCELADO')
                    ->where('hora_inicio', '<', $fim)
                    ->where('hora_fim', '>', $inicio)
                    ->when($ignorarId !== null, fn ($q) => $q->where('id', '!=', (int) $ignorarId));

                if (! $compartilhado) {
                    $conflitoSala = (clone $queryBase)->where('sala_id', $salaId)->exists();
                    if ($conflitoSala) {
                        $validator->errors()->add('sala_id', 'A sala selecionada já possui um agendamento neste horário.');
                    }

                    $conflitoPaciente = (clone $queryBase)->where('paciente_id', $pacienteId)->exists();
                    if ($conflitoPaciente) {
                        $validator->errors()->add('paciente_id', 'O paciente já possui outro agendamento simultâneo neste horário.');
                    }
                } else {
                    $conflitoPacienteOutraSala = (clone $queryBase)
                        ->where('paciente_id', $pacienteId)
                        ->where('sala_id', '!=', $salaId)
                        ->exists();

                    if ($conflitoPacienteOutraSala) {
                        $validator->errors()->add('paciente_id', 'O paciente já está agendado em outra sala neste mesmo horário.');
                    }
                }
            },
        ];
    }
}
