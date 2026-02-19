<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Carbon\Carbon;

class Birthday extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'birthdays';

    protected $fillable = [
        'family_id',
        'person_name',
        'birth_date',
        'created_by',
    ];

    protected $casts = [
        'birth_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'next_birthday',
        'age_on_next_birthday',
        'days_until_birthday',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNextBirthdayAttribute(): Carbon
    {
        $today = Carbon::today();
        $birthDate = Carbon::parse($this->birth_date);

        $nextBirthday = Carbon::create(
            $today->year,
            $birthDate->month,
            $birthDate->day
        );

        if ($nextBirthday->lt($today)) {
            $nextBirthday->addYear();
        }

        return $nextBirthday;
    }

    public function getAgeOnNextBirthdayAttribute(): int
    {
        $birthDate = Carbon::parse($this->birth_date);
        return $this->next_birthday->year - $birthDate->year;
    }

    public function getDaysUntilBirthdayAttribute(): int
    {
        return Carbon::today()->diffInDays($this->next_birthday, false);
    }
}
