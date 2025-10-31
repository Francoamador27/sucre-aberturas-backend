<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventListResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventNotifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;       // <-- AGREGAR
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function __construct(private EventNotifier $notifier)
    {
    }
// EventController.php - método myEvents()

public function myEvents(Request $request)
{
    try {
        $userId = $request->user()->id;
        
        $patient = \App\Models\Patient::where('user_id', $userId)->first();
        
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró paciente asociado al usuario.',
                'data' => [],
            ], 404);
        }
        
        $events = Event::with([
            'patient:idpa,user_id,nompa,apepa,phon',
            'patient.user:id,name,email,dni',
            'doctor:idodc,user_id,nodoc,apdoc,color',
            'doctor.user:id,name,email,dni',
        ])
        ->where('idpa', $patient->idpa)
        ->orderBy('start', 'desc')
        ->get();
        
        // ✅ Usar EventListResource (el mismo que en index())
        return response()->json([
            'success' => true,
            'data' => \App\Http\Resources\EventListResource::collection($events),
            'patient' => [
                'id' => $patient->idpa,
                'name' => $patient->nompa . ' ' . $patient->apepa,
            ],
        ]);
        
    } catch (\Throwable $e) {
        Log::error('Error al obtener eventos del usuario', [
            'user_id' => $request->user()->id ?? null,
            'message' => $e->getMessage(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar eventos.',
        ], 500);
    }
}
    public function show(Event $event)
    {
        $event->load([
            // Paciente (incluimos user_id para poder eager-load patient.user)
            'patient:idpa,user_id,nompa,apepa,phon,direc,sex,grup,cump,state',
            // Usuario del paciente (ahí está el email, dni, etc.)
            'patient.user:id,name,email,dni,codigo_postal,provincia,rol',
            // Doctor
            'doctor:idodc,nodoc,apdoc,color',
        ]);

        return (new EventResource($event))
            ->additional(['success' => true]);
    }

    // GET /api/patients/{id}/events
    public function byPatient($id, Request $request)
    {
        $data = validator(['id' => $id], ['id' => 'required|integer|min:1'])->validate();

        $events = Event::with([
            'patient:idpa,nompa,phon,apepa',
            'doctor:idodc,nodoc,apdoc',
        ])
            ->where('idpa', $data['id'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => EventResource::collection($events),
        ]);
    }

    // POST /api/events
    public function store(Request $request)
    {
        Log::info('Creando Evento', [
            'payload' => $request->all(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        try {
            // 1) Normalizar alias: permitir 'fecha' como 'start'
            $input = $request->all();
            if (empty($input['start']) && !empty($input['fecha'])) {
                $input['start'] = $input['fecha'];
                unset($input['fecha']);
            }

            // 2) Validar (sin 'after:start' aún, porque vamos a comparar ya parseado)
            $data = Validator::make($input, [
                'title' => ['required', 'string', 'max:255'],
                'start' => ['required', 'date'],
                'end' => ['required', 'date'],
                'allDay' => ['required', 'boolean'],

                'patientId' => ['required', 'integer', Rule::exists('patients', 'idpa')],
                'patientName' => ['nullable', 'string', 'max:255'],

                'doctorId' => ['required', 'integer', Rule::exists('doctor', 'idodc')],
                'doctorName' => ['nullable', 'string', 'max:255'],

                'userId' => ['nullable', 'integer'],
                'userName' => ['nullable', 'string', 'max:255'],

                'amount' => ['nullable', 'numeric', 'min:0'],
                'isPaid' => ['nullable', 'boolean'],

                'color' => ['nullable', 'string', 'max:20'],
            ])->validate();


            $start = Carbon::parse($data['start']); // mantiene el offset recibido
            $end = Carbon::parse($data['end']);

            // 4) Validación lógica después de normalizar
            if ($end->lessThanOrEqualTo($start)) {
                throw ValidationException::withMessages([
                    'end' => 'La hora de fin debe ser posterior a la hora de inicio.',
                ]);
            }

            // 5) Guardar SIEMPRE como 'Y-m-d H:i:s' en hora AR
            $payload = [
                'title' => $data['title'],
                'idpa' => $data['patientId'],
                'idodc' => $data['doctorId'],
                'color' => $data['color'] ?? '#0ea5e9',
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
                'state' => 1,
                'monto' => $data['amount'] ?? 0,
                'chec' => isset($data['isPaid']) ? (int) $data['isPaid'] : 0,
            ];

            $event = Event::create($payload)->load([
                'patient:idpa,nompa,phon,apepa',
                'doctor:idodc,nodoc,apdoc',
            ]);

            $this->notifier->sendConfirmed($event);

            return response()->json([
                'success' => true,
                'data' => new EventResource($event),
                'message' => 'Cita agregada exitosamente',
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Error al crear evento', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al crear la cita.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // GET /api/events
    public function index(Request $request)
    {
        $busqueda = trim((string) $request->input('busqueda', ''));
        $isPaidParam = $request->input('is_paid', null);        // "1"|"0"|true|false|null
        $ordenarPor = $request->input('ordenar_por', 'start');  // start|end|monto|created_at
        $direccion = strtolower($request->input('direccion', 'asc')) === 'desc' ? 'desc' : 'asc';

        $startParam = $request->input('start');
        $endParam = $request->input('end');

        $shouldPaginate = $request->boolean('paginate', false);
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $sortable = ['start', 'end', 'monto', 'created_at'];
        if (!in_array($ordenarPor, $sortable, true))
            $ordenarPor = 'start';

        $query = Event::query()->with([
            // Paciente + su user (para email/dni/name)
            'patient:idpa,user_id,nompa,apepa,phon',
            'patient.user:id,name,email,dni,codigo_postal,provincia,rol',

            // 🔴 CLAVE: incluir user_id y cargar doctor.user
            'doctor:idodc,user_id,nodoc,apdoc,color,corr,ceddoc,phd',
            'doctor.user:id,name,email,dni,telefono',
        ]);

        // Filtrar pagado
        if ($isPaidParam !== null && $isPaidParam !== '') {
            $isPaidBool = filter_var($isPaidParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $query->where('chec', (int) ($isPaidBool === true));
        }

        // FILTRO POR idpa (columna real en la tabla events)
        // El front puede enviar 'idpa' o 'patient_id' (compatibilidad)
        if ($request->filled('idpa')) {
            // aseguramos que sea entero
            $idpa = (int) $request->input('idpa');
            if ($idpa > 0) {
                $query->where('idpa', $idpa);
            }
        } elseif ($request->filled('patient_id')) {
            $idpa = (int) $request->input('patient_id');
            if ($idpa > 0) {
                $query->where('idpa', $idpa);
            }
        }

        // FILTRO POR doctor (si se envia doctor_id)
        if ($request->filled('doctor_id')) {
            $idodc = (int) $request->input('doctor_id');
            if ($idodc > 0) {
                $query->where('idodc', $idodc);
            }
        }

        // Búsqueda por texto (igual que antes)
        if (mb_strlen($busqueda) >= 2) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('title', 'like', "%{$busqueda}%")
                    ->orWhere('monto', 'like', "%{$busqueda}%")
                    ->orWhereHas('patient', function ($qp) use ($busqueda) {
                        $qp->where('nompa', 'like', "%{$busqueda}%")
                            ->orWhere('apepa', 'like', "%{$busqueda}%")
                            ->orWhere('phon', 'like', "%{$busqueda}%")
                            ->orWhereHas('user', function ($qu) use ($busqueda) {
                                $qu->where('email', 'like', "%{$busqueda}%")
                                    ->orWhere('dni', 'like', "%{$busqueda}%")
                                    ->orWhere('name', 'like', "%{$busqueda}%");
                            });
                    })
                    ->orWhereHas('doctor', function ($qd) use ($busqueda) {
                        $qd->where('nodoc', 'like', "%{$busqueda}%")
                            ->orWhere('apdoc', 'like', "%{$busqueda}%")
                            // también deja buscar por datos del user del doctor
                            ->orWhereHas('user', function ($qu) use ($busqueda) {
                                $qu->where('email', 'like', "%{$busqueda}%")
                                    ->orWhere('dni', 'like', "%{$busqueda}%")
                                    ->orWhere('name', 'like', "%{$busqueda}%");
                            });
                    });
            });
        }

        // Rango
        if ($startParam || $endParam) {
            $startAt = $startParam ? \Carbon\Carbon::parse($startParam)->startOfDay() : null;
            $endAt = $endParam ? \Carbon\Carbon::parse($endParam)->endOfDay() : null;

            $query->where(function ($q) use ($startAt, $endAt) {
                if ($startAt && $endAt) {
                    $q->whereBetween('start', [$startAt, $endAt])
                        ->orWhereBetween('end', [$startAt, $endAt])
                        ->orWhere(function ($qq) use ($startAt, $endAt) {
                            $qq->where('start', '<', $startAt)
                                ->where('end', '>', $endAt);
                        });
                } elseif ($startAt) {
                    $q->where('end', '>=', $startAt);
                } elseif ($endAt) {
                    $q->where('start', '<=', $endAt);
                }
            });

            $query->orderBy('start', 'asc');

            $events = $shouldPaginate ? $query->paginate($perPage) : $query->get();
            return EventListResource::collection($events)->additional(['success' => true]);
        }

        $events = $query->orderBy($ordenarPor, $direccion)->paginate($perPage);
        return EventListResource::collection($events)->additional(['success' => true]);
    }

    // (opcional) GET /api/events?page=1
    public function paginated()
    {
        $events = Event::with([
            'patient:idpa,nompa,phon,apepa',
            'doctor:idodc,nodoc,apdoc',
        ])->paginate(50);

        return EventListResource::collection($events)
            ->additional(['success' => true]);
    }

    // POST /api/events/{event}/cancel  => cancela y manda email de cancelación
    public function cancel(Event $event, Request $request)
    {
        $event->update(['state' => 0]); // 0 = cancelado (ajusta a tu convención)
        $event->load(['patient:idpa,nompa,phon,apepa', 'doctor:idodc,nodoc,apdoc']);

        $this->notifier->sendCancelled($event);

        return response()->json([
            'success' => true,
            'data' => new EventResource($event),
            'message' => 'Cita cancelada y notificada',
        ]);
    }
    // DELETE /api/events/{event}
    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cita eliminada exitosamente',
        ]);
    }
    public function update(Request $request, Event $event)
    {
        try {
            // Loguear payload crudo (útil para depurar 422)
            Log::info('Actualizando evento', [
                'event_id' => $event->id,
                'payload' => $request->all(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);

            // 1) Validación (ISO-8601 con offset, ej: 2025-08-22T09:00:00-03:00)
            $data = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'start' => ['required', 'date'],
                'end' => ['required', 'date', 'after:start'],
                'amount' => ['required', 'numeric', 'min:0'],
                'isPaid' => ['required', 'boolean'],
                'doctorId' => ['nullable', 'integer'],
            ]);

            // 2) Parseo de fechas (Carbon entiende ISO con offset)
            $start = Carbon::parse($data['start']);
            $end = Carbon::parse($data['end']);

            // 3) Actualizar evento (ajustá a tus columnas reales)
            $event->title = $data['title'];
            $event->start = $start;               // columna datetime
            $event->end = $end;                 // columna datetime
            $event->monto = $data['amount'];      // si tu columna es "monto"
            $event->chec = (bool) $data['isPaid'];   // si tu columna es "chec"
            $event->idodc = $data['doctorId'] ?? null; // id doctor
            // opcional si tenés columna para nombre:

            $event->save();

            // 4) Responder (ej: en hora local AR para UI)
            $tz = 'America/Argentina/Buenos_Aires';

            return response()->json([
                'message' => 'Evento actualizado correctamente',
                'data' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start->clone()->setTimezone($tz)->toIso8601String(),
                    'end' => $event->end->clone()->setTimezone($tz)->toIso8601String(),
                    'amount' => (float) $event->monto,
                    'isPaid' => (bool) $event->chec,
                    'doctorId' => $event->idodc,
                    'doctorName' => $event->doctor_name ?? null,
                ],
            ], 200);

        } catch (ValidationException $ve) {
            // 422: datos inválidos
            Log::warning('Validación fallida al actualizar evento', [
                'event_id' => $event->id,
                'errors' => $ve->errors(),
            ]);

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $ve->errors(), // { campo: [mensajes...] }
            ], 422);

        } catch (QueryException $qe) {
            // Problema a nivel SQL/DB
            Log::error('Error de base de datos al actualizar evento', [
                'event_id' => $event->id,
                'message' => $qe->getMessage(),
                // getSql/getBindings pueden no estar siempre; por eso chequeo
                'sql' => method_exists($qe, 'getSql') ? $qe->getSql() : null,
                'bindings' => method_exists($qe, 'getBindings') ? $qe->getBindings() : null,
            ]);

            return response()->json([
                'message' => 'Database error while updating event.',
                'error' => $qe->getMessage(),
            ], 500);

        } catch (\Throwable $e) {
            // Cualquier otro error inesperado
            $debugId = (string) Str::uuid();

            Log::error('Error inesperado al actualizar evento', [
                'event_id' => $event->id,
                'debug_id' => $debugId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Unexpected error.',
                'debug_id' => $debugId,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}