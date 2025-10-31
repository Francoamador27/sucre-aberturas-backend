<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class EventResource extends JsonResource
{
    public function toArray($request)
    {
        $dias = [1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes',6=>'sábado',7=>'domingo'];

        // Si en tu modelo las columnas start/end están casteadas a datetime, podés usar $this->start->copy()
        $start = Carbon::parse($this->start);
        $end   = Carbon::parse($this->end);

        $dayOfWeek = (int) $start->isoFormat('E'); // 1..7 (lunes..domingo)

        return [
            'id'     => $this->id,
            'idpa'   => $this->idpa,
            'idodc'  => $this->idodc,
            'title'  => $this->title,

            // ❗ Para FORMULARIO (crudos/ISO)
            'start_raw' => $this->start,                  // tal cual BD/eloquent (string o datetime cast)
            'end_raw'   => $this->end,                    // idem
            'start_iso' => $start->toIso8601String(),     // ISO con zona
            'end_iso'   => $end->toIso8601String(),

            // ✅ Para UI (visibles)
            'start'     => $start->format('d/m/Y H:i'),   // antes tenías solo fecha; ahora incluyo hora
            'end'       => $end->format('d/m/Y H:i'),

            // Negocio
            'monto' => (float) $this->monto,
            'chec'  => (bool) $this->chec,
            'color' => $this->color,

            // Relaciones
            'doctor_name'     => $this->doctor?->nodoc,
            'doctor_lastname' => $this->doctor?->apdoc,
            'patient_name'    => $this->patient?->nompa,
            'patient_email'   => $this->patient?->email_p,
            'patient_phone'   => $this->patient?->phon,
            'patient_lastname'=> $this->patient?->apepa,

            // Extras
            'day_of_week'  => $dias[$dayOfWeek] ?? null,
            'day_of_month' => $start->format('d'),
        ];
    }
}
