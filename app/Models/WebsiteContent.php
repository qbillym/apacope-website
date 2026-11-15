<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteContent extends Model
{
    protected $fillable = ['key', 'value'];

    private const DEFAULTS = [
        'hero_title_1' => "Educating learners\nwith discipline\nand skills.",
        'hero_text_1' => 'A school community in Nyarugenge, Kigali, from nursery to technical training in Software Development.',
        'hero_title_2' => "Four sections.\nOne school family.",
        'hero_text_2' => 'Each section has its own Director of Studies, its own classes and its own timetable.',
        'hero_title_3' => "One sign-in for\neverything.",
        'hero_text_3' => 'Attendance, marks, timetables, learning materials and school messages, each person seeing only what is theirs.',
        'about_title' => 'Educating learners with discipline, knowledge and practical skills.',
        'about_text' => 'Groupe Scolaire APACOPE is a school in Nyarugenge, Kigali. Our learners move from nursery and primary through O-Level to technical training in Software Development, supported by teachers, trainers and a Director of Studies for each section.',
        'section_nursery_title' => 'Nursery',
        'section_nursery_text' => 'N1 to N3. A caring start where young children build language, play and early learning habits.',
        'section_nursery_photo' => '',
        'section_primary_title' => 'Primary',
        'section_primary_text' => 'P1 to P6. Strong foundations in reading, mathematics, science and character.',
        'section_primary_photo' => '',
        'section_olevel_title' => 'O-Level Secondary',
        'section_olevel_text' => 'S1 to S3. Broad subjects that prepare learners for the next stage of study.',
        'section_olevel_photo' => '',
        'section_tvet_title' => 'TVET Software Development',
        'section_tvet_text' => 'Levels 3 to 5. Practical training in web programming, databases and mobile apps, with competency-based assessment.',
        'section_tvet_photo' => '',
        'managing_director_name' => '',
        'headmaster_name' => '',
        'student_photo_caption' => 'Our students',
        'managing_director_photo' => '',
        'headmaster_photo' => '',
        'student_photo' => '',
    ];

    public static function values(): array
    {
        $saved = static::query()
            ->whereIn('key', array_keys(self::DEFAULTS))
            ->pluck('value', 'key')
            ->all();

        return array_replace(self::DEFAULTS, $saved);
    }

    public static function contentKeys(): array
    {
        return array_keys(self::DEFAULTS);
    }
}
