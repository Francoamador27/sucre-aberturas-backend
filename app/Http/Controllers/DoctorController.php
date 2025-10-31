<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\DoctorResource;


class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $q = Doctor::query()->with(['user:id,name,email,dni,telefono']); // 👈 eager load

        if ($request->filled('busqueda')) {
            $b = trim($request->busqueda);
            $q->where(function ($w) use ($b) {
                $w->where('nodoc', 'like', "%{$b}%")
                    ->orWhere('apdoc', 'like', "%{$b}%")
                    ->orWhere('corr', 'like', "%{$b}%")
                    ->orWhere('ceddoc', 'like', "%{$b}%")
                    ->orWhere('phd', 'like', "%{$b}%")
                    ->orWhere('nomesp', 'like', "%{$b}%")
                    ->orWhereHas('user', function ($wu) use ($b) {
                        $wu->where('name', 'like', "%{$b}%")
                            ->orWhere('email', 'like', "%{$b}%")
                            ->orWhere('dni', 'like', "%{$b}%");
                    });
            });
        }

        $dir = in_array($request->get('direccion'), ['asc', 'desc'], true) ? $request->get('direccion') : 'desc';
        $orderCol = \Schema::hasColumn('doctor', 'fere') ? 'fere' : 'idodc';
        $perPage = (int) $request->get('per_page', 15);

        $doctors = $q->orderBy($orderCol, $dir)->paginate($perPage);

        return DoctorResource::collection($doctors)->additional(['success' => true]);
    }


    /** ---------- REGLAS DE VALIDACIÓN ---------- */
    // en DoctorController
// DoctorController.php
    protected function rules(bool $updating = false, $id = null): array
    {
        $ignore = $id ? Rule::unique('doctor', 'corr')->ignore($id, 'idodc') : Rule::unique('doctor', 'corr');

        return [
            'ceddoc' => ['nullable', 'string', 'max:30'],
            'nodoc' => ['required', 'string', 'max:120'],
            'apdoc' => ['nullable', 'string', 'max:120'],
            'nomesp' => ['nullable', 'string', 'max:120'],
            'direcd' => ['nullable', 'string', 'max:255'],

            // 👇 requerido al crear, opcional al actualizar
            'sexd' => [$updating ? 'nullable' : 'required', 'in:Masculino,Femenino,Otro'],

            'phd' => ['nullable', 'string', 'max:40'],

            // 👇 requerido al crear
            'nacd' => [$updating ? 'nullable' : 'required', 'date'],

            'corr' => ['nullable', 'email', 'max:190', $ignore],
            'password' => [$updating ? 'nullable' : 'required', 'string', 'min:6'],


            'state' => ['nullable', 'integer'],
            'fere' => ['nullable', 'date'],
            'color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/'],
        ];
    }




    public function store(Request $request)
    {
        // Normalizar SOLO lo necesario (sin tocar nombres de columnas que ya están bien)
        $request->merge([
            'name' => $request->input('name', $request->input('nodoc')), // para users.name
            'email' => $request->input('email', $request->input('corr')),       // para users.email
            // rol llega como número (1,2,3) desde el front -> lo usamos tal cual
            // admin lo derivamos del rol (1=Admin)
        ]);

        $messages = [
            // USER
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'El correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'rol.required' => 'El rol es obligatorio.',
            'rol.in' => 'Rol inválido. Valores permitidos: 1 (Admin), 2 (Doctor), 3 (Secretario).',

            // DOCTOR
            'sexd.required' => 'El sexo es obligatorio.',
            'sexd.in' => 'El sexo debe ser Masculino, Femenino u Otro.',
            'color.regex' => 'El color debe ser un hex válido (#RRGGBB o #RRGGBBAA).',
        ];

        $data = $request->validate([
            // USER (tabla users)
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'rol' => 'required|in:1,2,3',   // 👈 acá estaba la causa del “selected rol is invalid”

            // DOCTOR (tabla doctor)
            'nodoc' => 'required|string|max:150',
            'apdoc' => 'nullable|string|max:150',
            'ceddoc' => 'nullable|string|max:50',
            'nomesp' => 'nullable|string|max:150',
            'phd' => 'nullable|string|max:50',
            'nacd' => 'nullable|date',
            'sexd' => 'required|in:Masculino,Femenino,Otro',
            'color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/'],
        ], $messages);

        [$user, $doctor] = DB::transaction(function () use ($data) {
            // Crear User (tabla users: name, email, password, rol, admin)
            $user = User::create([
                'name' => $data['nodoc'] . (!empty($data['apdoc']) ? ' ' . $data['apdoc'] : ''),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'rol' => (int) $data['rol'],          // 1,2,3 tal cual llega
                'admin' => ((int) $data['rol'] === 1),  // true si rol=1
            ]);

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'nodoc' => $data['nodoc'],
                'apdoc' => $data['apdoc'] ?? null,
                'ceddoc' => $data['ceddoc'] ?? null,
                'nomesp' => $data['nomesp'] ?? null,
                'phd' => $data['phd'] ?? null,
                'nacd' => $data['nacd'] ?? null,
                'sexd' => $data['sexd'],
                'color' => $data['color'] ?? null,
                'fere' => Carbon::now(),
            ]);

            return [$user, $doctor];
        });

        return response()->json([
            'message' => 'Doctor creado correctamente.',
            'data' => [
                // users tiene name/rol/admin (no first_name/last_name/role/is_admin)
                'user' => $user->only(['id', 'name', 'email', 'rol', 'admin']),
                'doctor' => $doctor,
            ],
        ], 201);
    }

    /** ---------- VER DETALLE ---------- */
    public function show(Doctor $doctor)
    {

        return response()->json($doctor);
    }

    /** ---------- ACTUALIZAR ---------- */
    public function update(Request $request, Doctor $doctor)
    {
        // dueña/dueño del registro doctor
        $user = User::findOrFail($doctor->user_id);

        // 1) Normalizar: "" -> null (solo en campos susceptibles)
        $nullableKeys = [
            'name',
            'email',
            'password',
            'rol',
            'nodoc',
            'apdoc',
            'ceddoc',
            'nomesp',
            'phd',
            'nacd',
            'sexd',
            'color',
            'corr',
        ];
        $patch = [];
        foreach ($nullableKeys as $k) {
            if ($request->has($k)) {
                $v = $request->input($k);
                if (is_string($v)) {
                    $v = trim($v);
                    if ($v === '')
                        $v = null;
                }
                $patch[$k] = $v;
            }
        }
        // Aplicar normalización al request
        $request->merge($patch);

        // 2) Validación
        $messages = [
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'El correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'rol.in' => 'Rol inválido. Valores permitidos: 1 (Admin), 2 (Doctor), 3 (Secretario).',
            'nodoc.required' => 'El nombre es obligatorio.',
            'nacd.date' => 'La fecha de nacimiento no es válida.',
            'sexd.in' => 'El sexo debe ser Masculino, Femenino u Otro.',
            'color.regex' => 'El color debe ser un hex válido (#RRGGBB o #RRGGBBAA).',
            'corr.email' => 'El correo no tiene un formato válido.',
            'corr.unique' => 'El correo ya está registrado en doctores.',
        ];

        $data = $request->validate([
            // USER (opcionales)
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'rol' => ['nullable', 'in:1,2,3'],

            // DOCTOR (opcionales; exige nodoc si viene en payload)
            'nodoc' => ['sometimes', 'required', 'string', 'max:150'],
            'apdoc' => ['nullable', 'string', 'max:150'],
            'ceddoc' => ['nullable', 'string', 'max:50'],
            'nomesp' => ['nullable', 'string', 'max:150'],
            'phd' => ['nullable', 'string', 'max:50'],
            'nacd' => ['nullable', 'date'],
            'sexd' => ['nullable', 'in:Masculino,Femenino,Otro'],
            'color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/'],

            // si manejas email en doctor como "corr"
            'corr' => ['nullable', 'email', 'max:190', Rule::unique('doctor', 'corr')->ignore($doctor->getKey(), 'idodc')],
        ], $messages);

        DB::transaction(function () use ($data, $doctor, $user) {
            // ------- Doctor -------
            $doctorData = [];
            foreach (['nodoc', 'apdoc', 'ceddoc', 'nomesp', 'phd', 'nacd', 'sexd', 'color', 'corr'] as $k) {
                if (array_key_exists($k, $data))
                    $doctorData[$k] = $data[$k];
            }
            if (!empty($doctorData)) {
                $doctor->update($doctorData);
            }

            // ------- User -------
            $userData = [];

            // name: si no viene 'name' pero sí cambiaste nodoc/apdoc, se recalcula
            if (array_key_exists('name', $data) && $data['name']) {
                $userData['name'] = $data['name'];
            } elseif (array_key_exists('nodoc', $data) || array_key_exists('apdoc', $data)) {
                $nom = array_key_exists('nodoc', $data) ? ($data['nodoc'] ?? '') : $doctor->nodoc;
                $ap = array_key_exists('apdoc', $data) ? ($data['apdoc'] ?? '') : $doctor->apdoc;
                $userData['name'] = trim($nom . ' ' . $ap);
            }

            // email: preferimos 'email' si viene; si no, puedes optar por sincronizar con 'corr'
            if (array_key_exists('email', $data) && $data['email']) {
                $userData['email'] = $data['email'];
            } elseif (array_key_exists('corr', $data) && $data['corr']) {
                $userData['email'] = $data['corr'];
            }

            // password: solo si vino y no es null
            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            // rol/admin
            if (array_key_exists('rol', $data) && $data['rol'] !== null && $data['rol'] !== '') {
                $userData['rol'] = (int) $data['rol'];
                $userData['admin'] = ((int) $data['rol'] === 1);
            }

            if (!empty($userData)) {
                $user->update($userData);
            }
        });

        $doctor->refresh();
        $user->refresh();

        return response()->json([
            'message' => 'Doctor y usuario actualizados correctamente.',
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'rol', 'admin']),
                'doctor' => $doctor,
            ],
        ]);
    }


    /** ---------- ELIMINAR ---------- */
    public function destroy(Doctor $doctor)
    {
        DB::transaction(function () use ($doctor) {
            // eliminar primero el usuario asociado (si existe)
            if ($doctor->user_id) {
                $user = User::find($doctor->user_id);
                if ($user) {
                    $user->delete();
                }
            }

            // luego eliminar el doctor
            $doctor->delete();
        });

        return response()->json([
            'message' => 'Doctor y usuario eliminados correctamente.'
        ], 200);
    }

}
