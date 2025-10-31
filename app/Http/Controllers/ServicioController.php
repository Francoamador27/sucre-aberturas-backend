<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServicioController extends Controller
{
    public function index(Request $request)
    {
        // Query params opcionales: q, type, tag, sort, dir, per_page, category
        $q = $request->query('q');
        $type = $request->query('type');                 // Landing Page | Sitio Institucional | Ecommerce
        $tag = $request->query('tag');                  // ej: SEO
        $sort = $request->query('sort', 'created_at');   // columna válida
        $dir = strtolower($request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->query('per_page', 12);
        $category = $request->query('category');             // "1", "2" o CSV "1,2"

        // columnas permitidas para sort para evitar SQL injection
        // NOTA: 'create_data' es un string YYYY-MM-DD => orden lexicográfico funciona como fecha
        $sortable = ['created_at', 'create_data', 'title', 'type'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }

        $query = Servicio::query();

        // Búsqueda por texto (title/description)
        if (!empty($q)) {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        // Filtro por tipo
        if (!empty($type)) {
            $query->where('type', $type);
        }

        // Filtro por tag (tags es JSON)
        if (!empty($tag)) {
            $query->whereJsonContains('tags', $tag);
        }

        // Filtro por categoría (1 = Desarrollo web, 2 = Integraciones web)
        if (!empty($category)) {
            // Soporta "1", "2" o "1,2"
            $cats = collect(explode(',', (string) $category))
                ->map(fn($c) => (int) trim($c))
                ->filter(fn($c) => in_array($c, [1, 2], true))
                ->unique()
                ->values()
                ->all();

            if (!empty($cats)) {
                if (count($cats) === 1) {
                    $query->where('category', $cats[0]);
                } else {
                    $query->whereIn('category', $cats);
                }
            }
        }

        $paginator = $query->orderBy($sort, $dir)->paginate($perPage);

        // helper para no prefijar asset() si ya viene URL absoluta
        $isAbsoluteUrl = function ($url) {
            return is_string($url) && preg_match('#^https?://#i', $url);
        };

        // Transform opcional para devolver URLs completas de imágenes
        $paginator->getCollection()->transform(function ($item) use ($isAbsoluteUrl) {
            if (!empty($item->image) && !$isAbsoluteUrl($item->image)) {
                $item->image = asset($item->image);
            }
            if (!empty($item->mainImage) && !$isAbsoluteUrl($item->mainImage)) {
                $item->mainImage = asset($item->mainImage);
            }

            if (is_array($item->gallery) && !empty($item->gallery)) {
                $item->gallery = array_map(function ($g) use ($isAbsoluteUrl) {
                    return $isAbsoluteUrl($g) ? $g : asset($g);
                }, $item->gallery);
            }
            return $item;
        });

        return response()->json($paginator);
    }

public function show(string $idOrSlug)
{
    // Detecta URLs absolutas o protocol-relative (//cdn...)
    $isAbsoluteUrl = fn($url) => is_string($url) && preg_match('#^(https?:)?//#i', $url);

    // Buscar por ID o slug
    $servicio = ctype_digit($idOrSlug)
        ? Servicio::findOrFail((int) $idOrSlug)
        : Servicio::where('slug', $idOrSlug)->firstOrFail();

    // Normalizar gallery a array (maneja si quedó string JSON)
    $gallery = $servicio->gallery;
    if (is_string($gallery)) {
        $decoded = json_decode($gallery, true);
        $gallery = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    }
    if (!is_array($gallery)) {
        $gallery = [];
    }

    // Helper para convertir a URL pública solo si no es absoluta
    $toPublicUrl = function ($path) use ($isAbsoluteUrl) {
        if (empty($path)) return null;
        if ($isAbsoluteUrl($path)) return $path;         // ya es absoluta
        $path = ltrim($path, '/');                        // por si viene con leading slash
        return asset($path);                              // e.g. https://dominio.com/storage/uploads/...
    };

    // Armamos payload sin pisar los campos crudos que usa destroy()
    $payload = $servicio->toArray();
    $payload['image_url']      = $toPublicUrl($servicio->image ?? null);
    $payload['mainImage_url']  = $toPublicUrl($servicio->mainImage ?? null);
    $payload['gallery']        = $gallery;                                   // crudo (relativo/absoluto)
    $payload['gallery_urls']   = array_values(array_filter(array_map($toPublicUrl, $gallery)));

    return response()->json(['data' => $payload]);
}
public function store(Request $request)
{
    $table = (new Servicio)->getTable();

    $data = $request->validate([
        'title'        => 'required|string|max:255',
        'description'  => 'required|string',
        'create_data'  => 'nullable|date_format:Y-m-d',

        // slug: sin unique acá; lo resolvemos abajo con el loop
        'slug'         => 'nullable|string|max:255',

        // Archivos
        'imagen'       => 'nullable|file|image',
        'mainImage'    => 'nullable|file|image',
        'gallery'      => 'nullable',

        // JSON / strings (luego casteamos)
        'tags'         => 'nullable',
        'features'     => 'nullable',
    ]);

    /* ---------- SLUG (normalizado + único) ---------- */
    $slugBase = Str::slug($data['slug'] ?? $data['title'] ?? Str::random(8));
    $slug     = $slugBase !== '' ? $slugBase : Str::slug(Str::random(8));
    $i = 2;
    while (Servicio::where('slug', $slug)->exists()) {
        $slug = "{$slugBase}-{$i}";
        $i++;
    }
    $data['slug'] = $slug;

    /* ---------- SUBIDAS ---------- */
    $uploadDir = public_path('storage/uploads/servicios/');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // mainImage (y sincronizo image)
    if ($request->hasFile('mainImage')) {
        $file = $request->file('mainImage');
        $name = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($uploadDir, $name);

        $data['mainImage'] = 'storage/uploads/servicios/'.$name;
        $data['image']     = $data['mainImage'];
    }

    // imagen (alias)
    if ($request->hasFile('imagen')) {
        $file = $request->file('imagen');
        $name = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($uploadDir, $name);

        $data['image'] = 'storage/uploads/servicios/'.$name;
        if (empty($data['mainImage'])) {
            $data['mainImage'] = $data['image'];
        }
    }

    // Galería: múltiples archivos o JSON/URLs
    if ($request->hasFile('gallery')) {
        $files = $request->file('gallery');
        if (!is_array($files)) $files = [$files];

        $paths = [];
        foreach ($files as $file) {
            $name = Str::uuid().'.'.$file->getClientOriginalExtension();
            $file->move($uploadDir, $name);
            $paths[] = 'storage/uploads/servicios/'.$name;
        }
        $data['gallery'] = $paths;
    } else {
        $gallery = $request->input('gallery');
        if (is_string($gallery)) {
            $decoded = json_decode($gallery, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data['gallery'] = $decoded;
            }
        } elseif (is_array($gallery)) {
            $data['gallery'] = $gallery;
        }
    }

    // Decodificar JSON si vino como string
    foreach (['tags','features'] as $jsonField) {
        if (is_string($data[$jsonField] ?? null)) {
            $parsed = json_decode($data[$jsonField], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data[$jsonField] = $parsed;
            }
        }
    }

    // create_data opcional: dejá null o seteá default si querés
    // if (empty($data['create_data'])) {
    //     $data['create_data'] = now()->format('Y-m-d');
    // }

    $servicio = Servicio::create($data);

    return response()->json($servicio, 201);
}
    public function destroy(string $idOrSlug)
    {
        // helper p/ chequear si es URL absoluta (no se borra del disco en ese caso)
        $isAbsoluteUrl = function ($url) {
            return is_string($url) && preg_match('#^https?://#i', $url);
        };

        // Buscar por ID o slug
        $query = \App\Models\Servicio::query();
        $servicio = ctype_digit($idOrSlug)
            ? $query->findOrFail((int) $idOrSlug)
            : $query->where('slug', $idOrSlug)->firstOrFail();

        // Juntar rutas a borrar (evitando duplicados)
        $paths = [];

        if (!empty($servicio->image) && !$isAbsoluteUrl($servicio->image)) {
            $paths[] = $servicio->image;
        }
        if (!empty($servicio->mainImage) && !$isAbsoluteUrl($servicio->mainImage)) {
            $paths[] = $servicio->mainImage;
        }
        if (is_array($servicio->gallery)) {
            foreach ($servicio->gallery as $g) {
                if (!empty($g) && !$isAbsoluteUrl($g)) {
                    $paths[] = $g;
                }
            }
        }

        // Eliminar archivos locales (public_path)
        foreach (array_unique($paths) as $relPath) {
            $full = public_path($relPath);
            try {
                if (is_file($full)) {
                    @unlink($full);
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo eliminar archivo de servicio', [
                    'path' => $full,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Borrar registro
        $servicio->delete();

        return response()->json(['message' => 'Servicio eliminado']);
    }


}
