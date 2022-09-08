<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Skill;
use App\Models\Candidate;
use App\Models\FocusArea;
use App\Http\Resources\CandidateResource;
use App\Filters\CandidateFilters;
use App\Models\CandidateAttribute;

class CandidateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, CandidateFilters $filters)
    {
        if($request->has('search')) {
            $candidates = Candidate::search($request->search)->paginate($request->input('perPage', 10));
        } else {
            $candidates = Candidate::filter($filters)->paginate($request->input('perPage', 10));
        }
        return CandidateResource::collection($candidates);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            "email" => "required|email|unique:candidates",
            "first_name" => "required",
            "last_name" => "required",
            "dob" => "date_format:Y-m-d",
            "user_id" => ["required", "exists:users,id"],
            "attributes.*.name" => "required",
            "attributes.*.value" => "required",
            "skills.*.name" => "required",
            "focus_areas.*.name" => "required",
        ]);

        if($validator->fails()) {
            return response()->json([
                'message'   => 'The given data was invalid.', 
                'errors'    => $validator->errors()
            ], 400);
        }
                
        $inputs = $request->all();
        $candidate = Candidate::create($inputs);
        
        // Save attributes
        $attributes = array();
        if(!empty($request->get('attributes'))) {
            foreach ($request->get('attributes') as $attribute) {
                $attributes[] = new CandidateAttribute($attribute);
            }
            $candidate->attributes()->saveMany($attributes);
        }    

        // Save skills
        $skills = array();
        if(!empty($request->get('skills'))) {
            foreach ($request->get('skills') as $skill) {
                $skill['slug'] = strtolower( preg_replace('/\s/', '-', $skill['name']) );

                $existing_skill = Skill::where('slug', $skill['slug'])->first();
                if($existing_skill) {
                    $skills[] = $existing_skill;
                } else {
                    $skills[] = new Skill($skill);
                }
            }
            $candidate->skills()->saveMany($skills);
        }

        // Save focus_areas
        $focus_areas = array();
        if(!empty($request->focus_areas)) {
            foreach ($request->focus_areas as $focus_area) {
                $focus_area['slug'] = strtolower( preg_replace('/\s/', '-', $focus_area['name']) );

                $existing_focus_area = FocusArea::where('slug', $focus_area['slug'])->first();
                if($existing_focus_area) {
                    $focus_areas[] = $existing_focus_area;
                } else {
                    $focus_areas[] = new FocusArea($focus_area);
                }
            }
            $candidate->focusAreas()->saveMany($focus_areas);
        }

        if(!is_null($candidate)) {
            return new CandidateResource($candidate->load('attributes', 'skills', 'focusAreas'));
        } else {
            return response()->json(["error" => "Candidate was not created!"],500);
        } 
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Candidate  $contact
     * @return \Illuminate\Http\Response
     */
    public function show(Candidate $candidate)
    {
        return new CandidateResource($candidate->load('attributes', 'skills', 'focusAreas'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Candidate $candidate)
    {
        $validator = Validator::make($request->all(), [
            "user_id" => ["required", "exists:users,id"],
            "skills.*.name" => "required",
            "focus_areas.*.name" => "required",
            "dob" => "date_format:Y-m-d",
        ]);

        if($validator->fails()) {
            return response()->json([
                'message'   => 'The given data was invalid.', 
                'errors'    => $validator->errors()
            ], 400);
        }

        try {
            $candidate->update($request->all());

            // Update / save attributes
            $attributes = array();
            if(!empty($request->get('attributes'))) {
                foreach ($request->get('attributes') as $attribute) {
                    if(!empty($attribute['id'])) {
                        $attribute = CandidateAttribute::find($attribute['id']);
                        $attributes[] = $attribute;
                    } else {
                        $attributes[] = new CandidateAttribute($attribute);
                    }
                }

                if(!empty($attributes)) {
                    $candidate->attributes()->saveMany($attributes);
                }
            }

            // Update / save skills
            $skills = array();
            if($request->has('skills')) {
                foreach ($request->get('skills') as $skill_data) {
                    $skill_data['slug'] = strtolower( preg_replace('/\s/', '-', $skill_data['name']) );
                    if(!empty($skill_data['id'])) {
                        $skill = Skill::findOrFail($skill_data['id']);
                        $skill->update($skill_data);
                        $skills[] = $skill;
                    } else {
                        $existing_skill = Skill::where('slug', $skill_data['slug'])->first();
                        if($existing_skill) {
                            $skills[] = $existing_skill;
                        } else {
                            $skills[] = new Skill($skill_data);
                        }
                    }
                }

                $candidate->skills()->detach();

                if(!empty($skills)) {
                    $candidate->skills()->saveMany($skills);
                }
            }

            // Update / save focus_areas
            $focus_areas = array();
            if($request->has('focus_areas')) {
                foreach ($request->get('focus_areas') as $focus_area_data) {
                    $focus_area_data['slug'] = strtolower( preg_replace('/\s/', '-', $focus_area_data['name']) );
                    if(!empty($focus_area_data['id'])) {
                        $focus_area = FocusArea::findOrFail($focus_area_data['id']);
                        $focus_area->update($focus_area_data);
                        $focus_areas[] = $focus_area;
                    } else {
                        $existing_focus_area = FocusArea::where('slug', $focus_area_data['slug'])->first();
                        if($existing_focus_area) {
                            $focus_areas[] = $existing_focus_area;
                        } else {
                            $focus_areas[] = new FocusArea($focus_area_data);
                        }
                    }
                }

                $candidate->focusAreas()->detach();

                if(!empty($focus_areas)) {
                    $candidate->focusAreas()->saveMany($focus_areas);
                }
            }

        } catch (Exception $e) {
            return $e->getMessage();
        } catch (\Illuminate\Database\QueryException $e) {
            return $e->getMessage();
        }

        return new CandidateResource($candidate->load('attributes', 'skills', 'focusAreas'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function destroy(Candidate $candidate)
    {
        try {
            $candidate->delete();
        } catch (Exception $e) {
            return $e->getMessage();
        } catch (\Illuminate\Database\QueryException $e) {
            return $e->getMessage();
        }

        return new CandidateResource($candidate);
    }

}
