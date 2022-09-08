<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Filters\Filterable;
use Laravel\Scout\Searchable;
class Candidate extends Model
{
    use HasFactory, Filterable, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'email',
        'first_name',
        'last_name',
        'status',
        'linkedin_url',
        'source',
        'notes',
        'dob',
        'salary_expectation',
        'potential_start_date',
        'willing_to_move'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_name'
    ];

    public function attributes()
    {
        return $this->hasMany(CandidateAttribute::class);
    }

    public function documents()
    {
        return $this->hasMany(CandidateDocument::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    public function focusAreas()
    {
        return $this->belongsToMany(FocusArea::class);
    }

    public function user()
    {
        return $this->belongsToOne(User::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
