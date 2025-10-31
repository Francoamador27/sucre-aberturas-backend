<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $this->whenLoaded('user');

        return [
            'id'        => $this->idodc,
            'name'      => $user->name ?? trim(($this->nodoc ?? '').' '.($this->apdoc ?? '')) ?: null,
            'email'     => $user->email ?? $this->corr ?? null,
            'dni'       => $user->dni   ?? $this->ceddoc ?? null,
            'phone'     => $user->telefono ?? $this->phd ?? null,
            'specialty' => $this->nomesp,
            'color'     => $this->color,
        ];
    }
}
