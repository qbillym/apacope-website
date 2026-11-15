<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSubjectTeachersSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = [
            [
                'email' => 'mathteacher@apacope.test',
                'name' => 'Aline Demo Teacher',
                'subject' => 'Mathematics',
                'classes' => ['P1', 'P2', 'P3'],
            ],
            [
                'email' => 'scienceteacher@apacope.test',
                'name' => 'Eric Demo Teacher',
                'subject' => 'Science',
                'classes' => ['P1', 'P2', 'P3'],
            ],
        ];

        foreach ($teachers as $teacherDetails) {
            $teacher = User::updateOrCreate(
                ['email' => $teacherDetails['email']],
                [
                    'name' => $teacherDetails['name'],
                    'role' => 'trainer',
                    'password' => 'password',
                    'active' => true,
                ],
            );

            $classIds = SchoolClass::whereIn('name', $teacherDetails['classes'])->pluck('id');
            $modules = Module::whereIn('school_class_id', $classIds)
                ->where('name', $teacherDetails['subject'])
                ->get();

            if ($modules->count() !== count($teacherDetails['classes'])) {
                throw new \RuntimeException("Demo {$teacherDetails['subject']} modules are missing from one or more assigned classes.");
            }

            $modules->each(function (Module $module) use ($teacher): void {
                $module->update(['trainer_id' => $teacher->id]);

                Lesson::where('school_class_id', $module->school_class_id)
                    ->where('subject', $module->name)
                    ->update(['trainer_id' => $teacher->id]);
            });
        }
    }
}
