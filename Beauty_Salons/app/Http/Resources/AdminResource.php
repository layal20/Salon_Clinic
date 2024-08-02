<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isAdminDetails = $request->routeIs('admin_details');
        return [
            'id' => $this->id,
            'name' => $this->user_name,
            $this->mergeWhen($isAdminDetails, [
                'salon' => $this->salon ? $this->salon->name  : null,
                'services' => ServiceResource::collection($this->whenLoaded('services')),
            ]),



        ];
    }
}
