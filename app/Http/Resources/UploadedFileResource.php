<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class UploadedFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'file' => $this->resource->toArray(),
            'user' => $this->user,
            'view_url' => route('file.show', ['file' => $this->filename]),
            // ShareX stores this locally for later use, so the API response keeps
            // a permanent signed URL while the web UI can use temporary links.
            'delete_url' => URL::signedRoute('file.delete', $this),
        ];
    }
}
