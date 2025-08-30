<?php

namespace App\Http\Resources\Daparto;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\Daparto\DapartoResource;

class DapartoCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => DapartoResource::collection($this->collection),
            'meta' => [
                'total' => $this->collection->count(),
                'generated_at' => now()->format('Y-m-d H:i:s'),
            ],
        ];
    }
}
