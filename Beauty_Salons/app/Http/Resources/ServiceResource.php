<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isServiceDetails = $request->routeIs('service_details') || $request->routeIs('search_service');
        $isServiceSalon = $request->routeIs('salon_details');

        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'status' => $this->status,
            'date' => $this->date,
            'time' => $this->time,
            $this->mergeWhen($isServiceDetails, [

                'admin' => $this->admin ? $this->admin->user_name : null,
                'employee' => $this->employee ? $this->employee->name : null,
                'salons' =>  SalonResource::collection($this->whenLoaded('salons')),

            ]),

            $this->mergeWhen($isServiceSalon, [
                'employee' => $this->employee ? $this->employee->name : null,
                'price' => $this->price,

            ]),


        ];
    }
}
