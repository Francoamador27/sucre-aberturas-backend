<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventListResource extends JsonResource
{
    public function toArray($request)
    {
        $patient = $this->whenLoaded('patient');
        $doctor  = $this->whenLoaded('doctor');

        // Sólo si vienen cargadas, evitamos lazy-loading
        $patientUser = ($patient && $patient->relationLoaded('user')) ? $patient->user : null;
        $doctorUser  = ($doctor  && $doctor->relationLoaded('user'))  ? $doctor->user  : null;

        return [
            'id'     => $this->id,

            'idpa'   => $this->idpa  ?? $this->patient_id ?? $patient?->idpa,
            'idodc'  => $this->idodc ?? $doctor?->idodc, // ← corregido

            'monto'  => $this->monto,
            'title'  => $this->title,
            'state'  => $this->state,
            'chec'   => $this->chec,

            // ⚠️ Fechas: sin tocar
            'start'  => $this->start,
            'end'    => $this->end,

            'color'  => $doctor?->color,

            // Doctor (user → fallback a columnas de doctor)
            'doctor_name'      => $doctorUser->name
                                  ?? trim(($doctor->nodoc ?? '').' '.($doctor->apdoc ?? ''))
                                  ?: null,
            'doctor_lastname'  => $doctor?->apdoc, // si lo seguís mostrando
            'doctor_email'     => $doctorUser->email    ?? $doctor?->corr    ?? null,
            'doctor_dni'       => $doctorUser->dni      ?? $doctor?->ceddoc  ?? null,
            'doctor_phone'     => $doctorUser->telefono ?? $doctor?->phd     ?? null,

            // Paciente (email desde users)
            'patient_name'     => $patient ? trim(($patient->nompa ?? '').' '.($patient->apepa ?? '')) ?: null : null,
            'patient_lastname' => $patient?->apepa,
            'patient_phone'    => $patient?->phon,
            'patient_email'    => $patientUser?->email ?? null,

            'isPaid' => (bool) $this->chec,
        ];
    }
}
