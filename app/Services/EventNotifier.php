<?php

namespace App\Services;

use App\Mail\EventCreated;
use App\Mail\EventCancelled;
use App\Models\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\EventAssignedToDoctor;   // NUEVO

class EventNotifier
{



    public function sendConfirmed(Event $event): void
    {
        // Releer evento con relaciones necesarias
        $event = Event::query()
            ->with([
                'patient' => fn($q) => $q->select('idpa', 'user_id', 'nompa', 'apepa', 'phon'),
                'patient.user:id,name,email',
                'doctor' => fn($q) => $q->select('idodc', 'user_id', 'nodoc', 'apdoc'),
                'doctor.user:id,name,email',
            ])
            ->find($event->id);

        if (!$event)
            return;

        // Fallbacks directos a users por si falló el eager-load
        $patientEmail = $event->patient?->user?->email;
        if (!$patientEmail && ($uid = $event->patient?->user_id)) {
            $patientEmail = User::whereKey($uid)->value('email');
        }

        $doctorEmail = $event->doctor?->user?->email;
        if (!$doctorEmail && ($duid = $event->doctor?->user_id)) {
            $doctorEmail = User::whereKey($duid)->value('email');
        }

        Log::info('Event email debug', [
            'event_id' => $event->id,
            'patient_user_id' => $event->patient?->user_id,
            'doctor_user_id' => $event->doctor?->user_id,
            'patient_email' => $patientEmail,
            'doctor_email' => $doctorEmail,
        ]);

        // 1) Correo al PACIENTE
        if ($patientEmail) {
            try {
                Mail::to($patientEmail)->send(new EventCreated($event)); // usa tu mailable existente
                Log::info('EventCreated: correo al paciente enviado', [
                    'event_id' => $event->id,
                    'to' => $patientEmail
                ]);
            } catch (\Throwable $e) {
                Log::error('EventCreated: fallo envío a paciente', [
                    'event_id' => $event->id,
                    'to' => $patientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('EventCreated: sin email de paciente, se omite envío', [
                'event_id' => $event->id,
            ]);
        }

        // 2) Correo al DOCTOR (nuevo)
        if ($doctorEmail) {
            try {
                Mail::to($doctorEmail)->send(new EventAssignedToDoctor($event));
                Log::info('EventAssignedToDoctor: correo al doctor enviado', [
                    'event_id' => $event->id,
                    'to' => $doctorEmail
                ]);
            } catch (\Throwable $e) {
                Log::error('EventAssignedToDoctor: fallo envío a doctor', [
                    'event_id' => $event->id,
                    'to' => $doctorEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('EventAssignedToDoctor: sin email de doctor, se omite envío', [
                'event_id' => $event->id,
            ]);
        }
    }


    public function sendCancelled(Event $event): void
    {
        $event->loadMissing(['patient:idpa,nompa,email_p,phon,apepa', 'doctor:idodc,nodoc,apdoc']);

        $to = optional($event->patient)->email_p;
        if (!$to)
            return;

        try {
            Mail::to($to)->send(new EventCancelled($event));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar EventCancelled', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
