<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * Mostrar la configuración actual.
     */
    public function index()
    {
        $settings = Setting::singleton();
        return response()->json($settings);
    }

    /**
     * Actualizar la configuración.
     */
public function update(UpdateSettingRequest $request)
{
    $settings = Setting::singleton();
    $data = $request->validated();

    // Manejo del logo
    if ($request->hasFile('logo')) {
        $directorio = public_path('storage/uploads/logo-company/');

        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $file = $request->file('logo');
        // Le agregamos timestamp para evitar que se sobreescriba
        $nombreImagen = time() . '_' . $file->getClientOriginalName();
        $file->move($directorio, $nombreImagen);

        // Ruta accesible públicamente
        $data['logo'] = '/storage/uploads/logo-company/' . $nombreImagen;
    }

    // Actualizar datos
    $settings->update($data);

    // ✅ Limpiar caché para que el provider cargue los valores actualizados
    Cache::forget('app_settings');

    return response()->json([
        'success' => true,
        'settings' => $settings,
    ]);
}
}
