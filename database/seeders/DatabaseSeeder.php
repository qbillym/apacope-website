<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\Lesson;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;

class DatabaseSeeder
{
    public function run(): void
    {
        foreach (['th1' => 3, 'th2' => 5, 'th3' => 8] as $k => $v) {
            Setting::updateOrCreate(['key' => $k], ['value' => $v]);
        }
        $people = ['admin' => 'System Administrator', 'head' => 'Head Teacher', 'dosb' => 'DOS Nursery-Primary-O-Level', 'dost' => 'DOS TVET SWD', 'trainer' => 'Kamanzi J.', 'student' => 'Uwase Aline', 'parent' => 'Mrs Uwera', 'accountant' => 'Rutayisire E.', 'storekeeper' => 'Storekeeper'];
        $u = [];
        foreach ($people as $role => $n) {
            $u[$role] = User::updateOrCreate(['email' => "$role@apacope.test"], ['name' => $n, 'role' => $role, 'password' => 'password']);
        }
        $map = ['nursery' => ['N1', 'N2', 'N3'], 'primary' => ['P1', 'P2', 'P3', 'P4', 'P5', 'P6'], 'olevel' => ['S1', 'S2', 'S3'], 'tvet' => ['L3 SWD', 'L4 SWD', 'L5 SWD']];
        $names = ['Uwase Aline', 'Mugisha Eric', 'Ineza Grace', 'Niyonzima Jean', 'Mukamana Divine', 'Habimana Samuel', 'Iradukunda Chris', 'Uwimana Claire'];
        $days = collect(range(1, 28))->map(fn ($i) => today()->subDays($i))->filter(fn ($d) => $d->isWeekday())->values();
        $gen = ['Mathematics', 'English', 'Kinyarwanda', 'Science', 'Social Studies', 'Sport'];
        $swd = ['Web Programming', 'Databases', 'Mobile App Development', 'Practical Lab', 'Mathematics', 'English', 'PHP Programming'];
        foreach ($map as $sec => $list) {
            foreach ($list as $n) {
                $c = SchoolClass::updateOrCreate(['name' => $n], ['section' => $sec, 'trainer_id' => $n === 'L5 SWD' ? $u['trainer']->id : null, 'trade' => $sec === 'tvet' ? 'Software Development' : null, 'level' => $sec === 'tvet' ? 'Level '.substr($n, 1, 1) : null]);
                $sub = $sec === 'tvet' ? $swd : $gen;
                Lesson::where('school_class_id', $c->id)->delete();
                for ($d = 1; $d <= 5; $d++) {
                    for ($s = 1; $s <= 4; $s++) {
                        $subject = $sub[($d * 3 + $s * 2 + $c->id) % count($sub)];
                        Lesson::create(['school_class_id' => $c->id, 'day' => $d, 'slot' => $s, 'subject' => $subject, 'room' => 'Room '.(1 + ($d + $s) % 4), 'trainer_id' => $n === 'L5 SWD' && $subject === 'PHP Programming' ? $u['trainer']->id : null]);
                    }
                }
                if ($n === 'L5 SWD' && ! Lesson::where('school_class_id', $c->id)->where('subject', 'PHP Programming')->exists()) {
                    $lesson = Lesson::where('school_class_id', $c->id)->whereIn('subject', ['Mobile App Development', 'Web Programming'])->orderBy('day')->orderBy('slot')->first();
                    if ($lesson) {
                        $lesson->update(['subject' => 'PHP Programming', 'trainer_id' => $u['trainer']->id]);
                    }
                }
                foreach ($names as $i => $nm) {
                    $s = Student::updateOrCreate(['admission_no' => sprintf('APA-%s-%02d', str_replace(' ', '', $n), $i + 1)], ['name' => $nm, 'school_class_id' => $c->id, 'guardian_phone' => '0788 100 2'.sprintf('%02d', $i)]);
                    Fee::updateOrCreate(['student_id' => $s->id], ['invoice' => 120000, 'paid' => $i == 0 ? 120000 : 120000 - $i * 15000]);
                    $t = $i == 3 ? 72 : ($i == 2 ? 82 : 94);
                    $rows = [];
                    foreach ($days as $d) {
                        $x = random_int(1, 100);
                        $rows[] = ['student_id' => $s->id, 'school_class_id' => $c->id, 'date' => $d->toDateString(), 'status' => $x > $t ? 'A' : ($x > $t - 3 ? 'L' : ($x > $t - 6 ? 'E' : 'P')), 'created_at' => now(), 'updated_at' => now()];
                    }
                    Attendance::upsert($rows, ['student_id', 'date'], ['status']);
                    if ($n === 'L5 SWD') {
                        foreach (array_slice($swd, 0, 3) as $m) {
                            Mark::updateOrCreate(['student_id' => $s->id, 'module' => $m], ['f' => random_int(15, 28), 's' => random_int(20, 38), 'c' => random_int(14, 28)]);
                        }
                        if ($i == 0) {
                            User::whereIn('role', ['student', 'parent'])->update(['student_id' => $s->id]);
                        }
                    }
                }
            }
        }
        Announcement::create(['user_id' => $u['head']->id, 'title' => 'Term examination timetable released', 'body' => 'Examinations begin on 3 November. Please read the timetable and acknowledge.', 'audience' => 'School-wide']);
        Announcement::create(['user_id' => $u['trainer']->id, 'title' => 'Web Programming practical on Friday', 'body' => 'Bring your laptops. We will build and test the login page together.', 'audience' => 'L5 SWD']);
        $this->call([ExtraSeeder::class, Phase4Seeder::class, SiteSeeder::class, Phase10Seeder::class, DemoSubjectTeachersSeeder::class]);
    }
}
