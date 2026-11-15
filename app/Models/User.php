<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'student_id', 'active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function classes()
    {
        $q = SchoolClass::query();

        return match ($this->role) {
            'trainer' => $q->where(fn ($query) => $query->where('trainer_id', $this->id)->orWhereIn('id', Module::where('trainer_id', $this->id)->select('school_class_id'))->orWhereIn('id', Lesson::where('trainer_id', $this->id)->select('school_class_id'))),'dosb' => $q->whereIn('section', ['nursery', 'primary', 'olevel']),'dost' => $q->where('section', 'tvet'),'head','admin' => $q,'student','parent' => $q->whereIn('id', Student::where('id', $this->student_id)->pluck('school_class_id')),default => $q->whereRaw('1=0')
        };
    }

    public function modules()
    {
        return $this->hasMany(Module::class, 'trainer_id');
    }

    public function isClassTeacher(): bool
    {
        return $this->role === 'trainer' && SchoolClass::where('trainer_id', $this->id)->exists();
    }

    public function isClassTeacherFor(int $schoolClassId): bool
    {
        return $this->role === 'trainer' && SchoolClass::whereKey($schoolClassId)->where('trainer_id', $this->id)->exists();
    }

    public function label(): string
    {
        return ['admin' => 'System Administrator', 'head' => 'Head Teacher', 'dosb' => 'DOS · Nursery, Primary & O-Level', 'dost' => 'DOS · TVET Software Development', 'trainer' => 'Trainer / Teacher', 'student' => 'Student', 'parent' => 'Parent / Guardian', 'accountant' => 'Accountant', 'storekeeper' => 'Storekeeper'][$this->role] ?? $this->role;
    }
}
