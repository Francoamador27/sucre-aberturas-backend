<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function store(Request $request)
    {
        // Mapa para rol, por si en DB guardás string en vez de número
        $rolesMapNumToStr = [
            1 => 'admin',
            2 => 'doctor',
            3 => 'client', // "Paciente"
        ];

        // Validación del payload que mostraste
        $data = $request->validate([
            // ------ users ------
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'dni' => ['nullable', 'string', 'max:255', 'unique:users,dni'],
            'codigo_postal' => ['nullable', 'string', 'max:255'],
            'provincia' => ['nullable', 'string', 'max:255'],
            // Si guardás rol numérico (1/2/3):
            'rol' => ['required', 'integer', Rule::in([1, 2, 3])],
            // Si en tu base rol es VARCHAR (client/admin/doctor): cambia la línea anterior por:
            // 'rol'         => ['required','string', Rule::in(['client','admin','doctor'])],

            // ------ patients ------
            'nompa' => ['required', 'string', 'max:255'],
            'apepa' => ['nullable', 'string', 'max:255'],
            'direc' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', 'max:50', Rule::in(['Masculino', 'Femenino', 'Otro'])],
            'grup' => ['nullable', 'string', 'max:50'],
            'phon' => ['nullable', 'string', 'max:255'],
            'cump' => ['nullable', 'date'],   // viene "YYYY-MM-DD"
            'state' => ['nullable', 'integer', Rule::in([0, 1])],
        ]);

        return DB::transaction(function () use ($data, $rolesMapNumToStr) {
            // Normalizaciones
            $nom = trim($data['nompa'] ?? '');
            $ape = trim($data['apepa'] ?? '');
            $name = trim($nom . ' ' . $ape) ?: $nom;

            // Si tu columna "rol" en users es STRING:
            // $roleValue = is_numeric($data['rol']) ? ($rolesMapNumToStr[(int) $data['rol']] ?? 'client') : $data['rol'];
            // Si tu columna "rol" en users es NUMÉRICA:
            $roleValue = is_numeric($data['rol']) ? (int) $data['rol'] : array_flip($rolesMapNumToStr)[$data['rol']] ?? 3;

            // 1) Crear usuario
            $user = User::create([
                'name' => $name,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'dni' => $data['dni'] ?? null,
                'codigo_postal' => $data['codigo_postal'] ?? null,
                'provincia' => $data['provincia'] ?? null,
                'rol' => $roleValue,

            ]);


            $patient = Patient::create([
                'user_id' => $user->id,
                'nompa' => $data['nompa'],
                'apepa' => $data['apepa'] ?? null,
                'direc' => $data['direc'] ?? null,
                'sex' => $data['sex'] ?? null,
                'grup' => $data['grup'] ?? null,
                'phon' => $data['phon'] ?? null,
                'cump' => $data['cump'] ?? null,   // date Y-m-d
                'state' => $data['state'] ?? 1,
                // 'fere' la suele setear el DB (DEFAULT CURRENT_TIMESTAMP) o en eventos
            ]);

            return response()->json([
                'message' => 'Paciente creado correctamente.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'rol' => $user->rol,
                ],
                'patient' => $patient,
            ], 201);
        });
    }
    public function index(Request $request)
    {
        $q = Patient::with(['user:id,name,email,dni,codigo_postal,provincia,rol']);

        // 🔎 Búsqueda (usa el scope del modelo)
        $q->search($request->get('busqueda'));

        // ✅ Filtro por estado si viene
        if ($request->filled('state')) {
            $q->where('state', (int) $request->get('state'));
        }

        // ↕ Orden
        $direccion = in_array($request->get('direccion'), ['asc', 'desc']) ? $request->get('direccion') : 'desc';
        $orderCol = \Schema::hasColumn('patients', 'fere') ? 'fere' : 'idpa';
        $q->orderBy($orderCol, $direccion);

        // 📄 Paginación
        $perPage = (int) $request->get('per_page', 15);

        return response()->json($q->paginate($perPage));
    }
    public function show($id)
    {
        $patient = Patient::with(['user:id,name,email,dni,codigo_postal,provincia,rol'])
            ->findOrFail($id);

        return response()->json($patient);
    }
    public function showByUser($userId)
    {
        $patient = Patient::where('user_id', $userId)
            ->with(['user:id,name,email,dni,codigo_postal,provincia,rol'])
            ->firstOrFail();

        return response()->json($patient);
    }
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $patient = Patient::with('user')->findOrFail($id);
            $user = $patient->user;

            // 🗑️ Borrado físico (hard delete)
            $patient->delete();

            // Si el usuario es exclusivo de este paciente (1:1), lo borramos también
            if ($user) {
                $user->delete();
            }

            return response()->json([
                'message' => 'Paciente y usuario eliminados correctamente.',
                'deleted_patient_id' => $patient->getKey(), // idpa
                'deleted_user_id' => $user?->id,
            ], 200);
        });
    }
    public function update(Request $request, $id)
    {
        // Traer el paciente con su usuario primero para conocer el user_id
        $patient = Patient::with('user')->findOrFail($id);
        $userId = $patient->user_id;

        // Mapa para rol, por si en DB guardás string en vez de número
        $rolesMapNumToStr = [
            1 => 'admin',
            2 => 'doctor',
            3 => 'client', // "Paciente"
        ];

        // Validación
        $data = $request->validate([
            // ------ users ------
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:6'], // opcional en update
            'dni' => ['nullable', 'string', 'max:255', Rule::unique('users', 'dni')->ignore($userId)],
            'codigo_postal' => ['nullable', 'string', 'max:255'],
            'provincia' => ['nullable', 'string', 'max:255'],
            // rol numérico
            'rol' => ['required', 'integer', Rule::in([1, 2, 3])],
            // si tu columna users.rol fuera VARCHAR, usar:
            // 'rol'         => ['required','string', Rule::in(['client','admin','doctor'])],

            // ------ patients ------
            'nompa' => ['required', 'string', 'max:255'],
            'apepa' => ['nullable', 'string', 'max:255'],
            'direc' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', 'max:50', Rule::in(['Masculino', 'Femenino', 'Otro'])],
            'grup' => ['nullable', 'string', 'max:50'],
            'phon' => ['nullable', 'string', 'max:255'],
            'cump' => ['nullable', 'date'],   // "YYYY-MM-DD"
            'state' => ['nullable', 'integer', Rule::in([0, 1])],
        ]);

        return DB::transaction(function () use ($data, $patient, $rolesMapNumToStr) {
            // Normalizar name a partir de nompa + apepa
            $nom = trim($data['nompa'] ?? '');
            $ape = trim($data['apepa'] ?? '');
            $name = trim($nom . ' ' . $ape) ?: $nom;

            // Resolver valor de rol según tu esquema en users
            // Si users.rol es STRING:
            // $roleValue = is_numeric($data['rol']) ? ($rolesMapNumToStr[(int)$data['rol']] ?? 'client') : $data['rol'];
            // Si users.rol es NUMÉRICO:
            $roleValue = is_numeric($data['rol']) ? (int) $data['rol'] : (array_flip($rolesMapNumToStr)[$data['rol']] ?? 3);

            // ------- Actualizar USER -------
            $user = $patient->user; // puede ser null si no existe relación (caso raro)
            if ($user) {
                $user->name = $name;
                $user->email = $data['email'];
                $user->dni = $data['dni'] ?? null;
                $user->codigo_postal = $data['codigo_postal'] ?? null;
                $user->provincia = $data['provincia'] ?? null;
                $user->rol = $roleValue;

                if (!empty($data['password'])) {
                    $user->password = Hash::make($data['password']);
                }

                $user->save();
            }

            // ------- Actualizar PATIENT -------
            $patient->nompa = $data['nompa'];
            $patient->apepa = $data['apepa'] ?? null;
            $patient->direc = $data['direc'] ?? null;
            $patient->sex = $data['sex'] ?? null;
            $patient->grup = $data['grup'] ?? null;
            $patient->phon = $data['phon'] ?? null;
            $patient->cump = $data['cump'] ?? null;
            $patient->state = $data['state'] ?? $patient->state;

            $patient->save();

            // Respuesta
            $patient->load(['user:id,name,email,dni,codigo_postal,provincia,rol']);
            return response()->json([
                'message' => 'Paciente actualizado correctamente.',
                'patient' => $patient,
            ], 200);
        });
    }

}
