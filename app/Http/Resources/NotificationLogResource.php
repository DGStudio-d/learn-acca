<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationLogResource extends JsonResource
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
            'type' => $this->type,
            'channel' => $this->channel,
            'status' => $this->status,
            'payload' => $this->when(
                $request->user()?->isAdmin() || $request->user()?->id === $this->user_id,
                $this->payload
            ),
            'sent_at' => $this->sent_at?->toISOString(),
            'error_message' => $this->when(
                $this->hasFailed() && ($request->user()?->isAdmin() || $request->user()?->id === $this->user_id),
                $this->error_message
            ),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Include user information for admin views
            'user' => $this->when(
                $request->user()?->isAdmin() && $this->relationLoaded('user'),
                [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                    'email' => $this->user?->email,
                ]
            ),
        ];
    }
}