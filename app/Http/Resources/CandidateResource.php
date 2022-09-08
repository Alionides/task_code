<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CandidateAttributeResource;
use App\Http\Resources\SkillResource;
use App\Http\Resources\FocusAreaResource;

class CandidateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        CandidateAttributeResource::collection($this->attributes);
        SkillResource::collection($this->skills);
        FocusAreaResource::collection($this->focusAreas);
        return parent::toArray($request);
    }
}
