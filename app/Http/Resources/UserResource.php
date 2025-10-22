<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'lastname' => $this->lastname,
            'firstname' => $this->firstname,
            'wallet' => $this->wallet,
            'phone' => $this->phone,
            'callbridge_number' => $this->number,
            'image' => $this->preference?->image ? asset('storage/'.$this->preference->image) : null,
            'display_name' => $this->preference?->display_name,
            'bio' => $this->preference?->bio,
            'email' => $this->preference?->email
        ];
    }
}
