<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isemployeeDetails = $request->routeIs('employee_details');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'salary' => $this->salary,

            $this->mergeWhen($isemployeeDetails, [
                'admin' => $this->admin ? $this->admin->user_name : null,
                'service' => new ServiceResource($this->whenLoaded('service')),
                'salon' => $this->salon ? $this->salon->name : null,
            ]),
        ];
    }
}
