<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isProductDetails = $request->routeIs('product_details');
        $isSearchProduct = $request->routeIs('search_product');

        $quantity = $this->salons->isNotEmpty() ? $this->salons->first()->pivot->quantity : null;
        $quantity = $quantity > 0 ? $quantity : null;

        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image' => $this->image,
            'quantity' => $quantity,
            $this->mergeWhen($isProductDetails, [
                'admin' => $this->admin ? $this->admin->user_name : null,
                'salons' => SalonResource::collection($this->whenLoaded('salons')),
            ]),

            $this->mergeWhen($isSearchProduct, [
                'salons' => SalonResource::collection($this->whenLoaded('salons')),
            ]),
        ];
    }
}
