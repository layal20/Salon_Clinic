<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isSalonDetails = $request->routeIs('salon_details') || $request->routeIs('search_salon');

        return
        [
            'name' => $this->name,
            $this->mergeWhen(!$isSalonDetails, [
                'logo_image' => $this->logo_image,
                'description' => $this->description,
            ]),

            $this->mergeWhen($isSalonDetails, [
                'logo_image' => $this->logo_image,
                'description' => $this->description,
                'status' => $this->status,
                'admin' => $this->admin ? $this->admin->user_name : null,
                'products' => ProductResource::collection($this->whenLoaded('products')),
                'services' => ServiceResource::collection($this->whenLoaded('services')),
            ]),
            
        ];        
    }
}
