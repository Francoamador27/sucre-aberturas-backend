<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index(Request $request)
    {

        $q = trim((string) $request->query('q', ''));
        $fileQ = trim((string) $request->query('file', ''));
        $idpa = $request->query('idpa'); // puede venir string
        $sort = $request->query('sort', 'fere');
        $dir = strtolower($request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->query('per_page', 12);

        // Columnas permitidas para ordenar (evita SQL injection)
        // Ajustá según tus columnas reales en `document`
        $sortable = ['id', 'title', 'fere', 'idpa', 'created_at', 'updated_at', 'nomfi'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'fere'; // por defecto ordena por fecha de registro que guardás
        }

        $query = Document::query()
            // opcional: incluye datos mínimos del paciente
            ->with(['patient:idpa,nompa,apepa']);

        // Búsqueda full-text sencilla: title / descripcion
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                    ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        // Búsqueda por nombre/ruta de archivo (nomfi)
        if ($fileQ !== '') {
            $query->where('nomfi', 'like', "%{$fileQ}%");
        }

        // Filtro por paciente
        if ($idpa !== null && $idpa !== '') {
            $query->where('idpa', (int) $idpa);
        }

        // Orden + paginación
        $paginator = $query->orderBy($sort, $dir)->paginate($perPage);

        // Helper URL absoluta
        $isAbsoluteUrl = static function ($url) {
            return is_string($url) && preg_match('#^https?://#i', $url);
        };

        // Normalizar URL pública de nomfi
        $paginator->getCollection()->transform(function ($item) use ($isAbsoluteUrl) {
            if (!empty($item->nomfi) && !$isAbsoluteUrl($item->nomfi)) {
                $item->nomfi_url = asset($item->nomfi); // agrega campo derivado
            } else {
                $item->nomfi_url = $item->nomfi;
            }

            // (Opcional) nombre completo del paciente si lo tenés en el accessor
            if ($item->relationLoaded('patient') && $item->patient) {
                $item->patient_full_name = method_exists($item->patient, 'getFullNameAttribute')
                    ? $item->patient->full_name
                    : trim(($item->patient->nompa ?? '') . ' ' . ($item->patient->apepa ?? ''));
            }

            return $item;
        });

        return response()->json($paginator);
    }
    public function myDocuments(Request $request)
    {
        try {
            $userId = $request->user()->id;

            // Buscar paciente por user_id
            $patient = \App\Models\Patient::where('user_id', $userId)->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró paciente asociado al usuario.',
                    'data' => [],
                ], 404);
            }

            // Parámetros de búsqueda y paginación
            $q = trim((string) $request->query('q', ''));
            $sort = $request->query('sort', 'fere');
            $dir = strtolower($request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
            $perPage = (int) $request->query('per_page', 10);

            // Columnas permitidas para ordenar
            $sortable = ['id', 'title', 'fere', 'created_at', 'updated_at', 'nomfi'];
            if (!in_array($sort, $sortable, true)) {
                $sort = 'fere';
            }

            // Query base: solo documentos del paciente autenticado
            $query = Document::query()
                ->where('idpa', $patient->idpa)
                ->with(['patient:idpa,nompa,apepa']);

            // Búsqueda por título/descripción (mínimo 2 caracteres)
            if (strlen($q) >= 2) {
                $query->where(function ($w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('descripcion', 'like', "%{$q}%");
                });
            }

            // Orden + paginación
            $paginator = $query->orderBy($sort, $dir)->paginate($perPage);

            // Helper para URLs absolutas
            $isAbsoluteUrl = static function ($url) {
                return is_string($url) && preg_match('#^https?://#i', $url);
            };

            // Normalizar URLs
            $paginator->getCollection()->transform(function ($item) use ($isAbsoluteUrl) {
                if (!empty($item->nomfi) && !$isAbsoluteUrl($item->nomfi)) {
                    $item->nomfi_url = asset($item->nomfi);
                } else {
                    $item->nomfi_url = $item->nomfi;
                }
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'patient' => [
                    'id' => $patient->idpa,
                    'name' => $patient->nompa . ' ' . $patient->apepa,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Error al obtener documentos del usuario', [
                'user_id' => $request->user()->id ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar documentos.',
            ], 500);
        }
    }

    /**
     * Subir documento para el usuario autenticado
     */
    public function myUpload(Request $request)
    {
        try {
            $userId = $request->user()->id;

            // Buscar paciente
            $patient = \App\Models\Patient::where('user_id', $userId)->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró paciente asociado.',
                ], 404);
            }

            // Validación
            $data = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'descripcion' => ['required', 'string'],
                'document' => [
                    'required',
                    'file',
                    'max:10240', // 10MB
                    'mimetypes:image/jpeg,image/png,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
            ]);

            // Fecha actual
            $data['fere'] = now()->toDateString();

            // Guardar archivo
            $uploadDir = public_path('storage/uploads/documentos/');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file = $request->file('document');
            $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $name);

            $publicPath = 'storage/uploads/documentos/' . $name;

            // Crear documento
            $doc = Document::create([
                'title' => $data['title'],
                'descripcion' => $data['descripcion'],
                'nomfi' => $publicPath,
                'fere' => $data['fere'],
                'idpa' => $patient->idpa, // ✅ Se asigna automáticamente
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento subido exitosamente.',
                'data' => [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'descripcion' => $doc->descripcion,
                    'nomfi_url' => asset($doc->nomfi),
                    'fere' => $doc->fere,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error al subir documento del usuario', [
                'user_id' => $request->user()->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al subir el documento.',
            ], 500);
        }
    }



    public function show(string $idOrSlug)
    {
        $query = \App\Models\Document::query();

        $servicio = ctype_digit($idOrSlug)
            ? $query->findOrFail((int) $idOrSlug)
            : $query->where('slug', $idOrSlug)->firstOrFail();

        $servicio->image = $servicio->image ? asset($servicio->image) : null;
        $servicio->mainImage = $servicio->mainImage ? asset($servicio->mainImage) : null;
        if (is_array($servicio->gallery)) {
            $servicio->gallery = array_map(fn($g) => asset($g), $servicio->gallery);
        }

        return response()->json(['data' => $servicio]);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string'],
            'idpa' => ['required', 'integer', 'exists:patients,idpa'],
            'document' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            // 'fere' => ['nullable','date'], // por si te llega
        ]);

        // Fecha por defecto (YYYY-MM-DD) si no viene
        if (!$request->filled('fere')) {
            $data['fere'] = now()->toDateString();
        } else {
            $data['fere'] = $request->input('fere');
        }

        // === Guardado de archivo EXACTO al esquema que mostraste ===
        // 1) Se mueve físicamente a /public/storage/uploads/servicios/
        $uploadDir = public_path('storage/uploads/documentos/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file = $request->file('document');
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($uploadDir, $name);

        // 2) La ruta pública se construye como /storage/uploads/documentos/{archivo}
        //    (sí, distinto del dir físico, tal cual lo tenías)
        $publicPath = 'storage/uploads/documentos/' . $name;

        // Compat: si en algún lado consumís estas claves en la respuesta
        $data['document'] = $publicPath;
        $data['image'] = $publicPath;

        // Lo que realmente persiste en tu tabla `document`
        // (según tu $fillable: title, descripcion, nomfi, fere, idpa)
        $doc = Document::create([
            'title' => $data['title'],
            'descripcion' => $data['descripcion'],
            'nomfi' => $publicPath,   // <- acá va la ruta que venías guardando
            'fere' => $data['fere'],
            'idpa' => $data['idpa'],
        ]);

        // Opcional: devolver también las claves de compatibilidad
        $payload = $doc->toArray();
        $payload['document'] = $publicPath;
        $payload['image'] = $publicPath;

        return response()->json($payload, 201);
    }
    public function destroy(Document $document)
    {
        // Nomfi puede ser relativa (storage/...) o absoluta (http...)
        $path = $document->nomfi;
        $isAbsolute = is_string($path) && preg_match('#^https?://#i', $path);

        if ($path && !$isAbsolute) {
            $full = public_path($path); // => public/storage/uploads/documentos/xxx.ext
            try {
                if (is_file($full)) {
                    @unlink($full);
                } else {
                    // fallback defensivo por si hay cambios de carpeta
                    $alt = public_path(str_replace('/documentos/', '/servicios/', $path));
                    if (is_file($alt)) {
                        @unlink($alt);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo eliminar el archivo del documento', [
                    'document_id' => $document->id,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $document->delete();

        return response()->noContent(); // 204
    }

}
