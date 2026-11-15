<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Outcome;
use App\Models\Register;
use App\Models\ReportApproval;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Submission;
use App\Models\User;
use App\Models\WebsiteContent;
use App\Services\Tasks;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_welcome_content_and_public_page_uses_it(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->post('/website/content', [
            'hero_title_1' => 'Welcome from APACOPE',
            'hero_text_1' => 'A school community in Kigali.',
            'hero_title_2' => 'One school family',
            'hero_text_2' => 'Four sections, one community.',
            'hero_title_3' => 'Learning together',
            'hero_text_3' => 'A portal for every learner.',
            'about_title' => 'About our school',
            'about_text' => 'Serving our community.',
            'section_nursery_title' => 'Nursery',
            'section_nursery_text' => 'N1 to N3.',
            'section_primary_title' => 'Primary',
            'section_primary_text' => 'P1 to P6.',
            'section_olevel_title' => 'O-Level',
            'section_olevel_text' => 'S1 to S3.',
            'section_tvet_title' => 'TVET',
            'section_tvet_text' => 'Levels 3 to 5.',
            'managing_director_name' => '',
            'headmaster_name' => '',
            'student_photo_caption' => 'Our students',
        ])->assertRedirect();

        $this->actingAs($admin)->get('/website')
            ->assertOk()
            ->assertSee('Welcome page content')
            ->assertSee('calculated automatically from current school records')
            ->assertDontSee('name="stat_students"', false)
            ->assertSee('Photos for each school section');
        $this->get('/')
            ->assertOk()
            ->assertSee('Welcome from APACOPE')
            ->assertSee('images/welcome/hero-1.png', false)
            ->assertSee('images/welcome/hero-4.png', false)
            ->assertSee('images/welcome/nursery.png', false)
            ->assertSee('images/welcome/primary.png', false)
            ->assertSee('images/welcome/secondary.png', false)
            ->assertSee('images/welcome/tvet-1.png', false)
            ->assertSee('images/welcome/tvet-2.png', false);
        $this->assertSame('Welcome from APACOPE', WebsiteContent::values()['hero_title_1']);
    }

    public function test_welcome_page_counts_always_use_current_school_records(): void
    {
        $admin = $this->user('admin');
        WebsiteContent::create(['key' => 'stat_students', 'value' => '250']);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-to="0"', false)
            ->assertSee('data-to="1"', false);
        $this->getJson('/public/statistics')
            ->assertOk()
            ->assertExactJson(['n' => 0, 'c' => 0, 's' => 1]);

        $firstClass = SchoolClass::create(['name' => 'Live Count P1', 'section' => 'primary']);
        $secondClass = SchoolClass::create(['name' => 'Live Count P2', 'section' => 'primary']);
        Student::create([
            'admission_no' => 'LIVE-COUNT-001',
            'name' => 'Enrolled Student',
            'school_class_id' => $firstClass->id,
            'status' => 'Enrolled',
        ]);
        Student::create([
            'admission_no' => 'LIVE-COUNT-002',
            'name' => 'Former Student',
            'school_class_id' => $secondClass->id,
            'status' => 'Left',
        ]);
        $this->user('trainer');
        $this->user('parent');
        $this->user('student');

        $this->get('/')
            ->assertOk()
            ->assertSee('data-to="1"', false)
            ->assertSee('data-to="2"', false)
            ->assertDontSee('data-to="250"', false);
        $this->getJson('/public/statistics')
            ->assertOk()
            ->assertExactJson(['n' => 1, 'c' => 2, 's' => 2]);
    }

    public function test_login_page_has_rotating_school_photo_backgrounds(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('login-gallery', false)
            ->assertSee('images/welcome/hero-1.png', false)
            ->assertSee('images/welcome/hero-4.png', false);
    }

    public function test_admin_can_upload_a_school_section_photo_for_the_public_page(): void
    {
        Storage::fake('public');
        $admin = $this->user('admin');

        $this->actingAs($admin)->post('/website/content', [
            'hero_title_1' => 'Welcome',
            'hero_title_2' => 'Our school',
            'hero_title_3' => 'One portal',
            'about_title' => 'About APACOPE',
            'section_nursery_title' => 'Nursery',
            'section_primary_title' => 'Primary',
            'section_olevel_title' => 'O-Level',
            'section_tvet_title' => 'TVET',
            'section_nursery_photo' => new UploadedFile(
                public_path('images/logo.png'),
                'nursery.png',
                'image/png',
                null,
                true,
            ),
        ])->assertRedirect();

        $photoPath = WebsiteContent::values()['section_nursery_photo'];
        Storage::disk('public')->assertExists($photoPath);

        $this->get('/')->assertOk()->assertSee('storage/'.$photoPath, false);
    }

    public function test_only_a_dos_can_create_teachers_for_their_section(): void
    {
        $dos = $this->user('dosb');
        $primary = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        $module = Module::create(['school_class_id' => $primary->id, 'name' => 'Mathematics']);

        $this->actingAs($dos)->post('/teachers', [
            'name' => 'Aline Teacher',
            'email' => 'aline.teacher@example.test',
            'module_id' => $module->id,
            'class_teacher' => '1',
        ])->assertRedirect();

        $trainer = User::where('email', 'aline.teacher@example.test')->firstOrFail();
        $this->assertSame('trainer', $trainer->role);
        $this->assertSame($trainer->id, $module->fresh()->trainer_id);
        $this->assertSame($trainer->id, $primary->fresh()->trainer_id);

        $tvetClass = SchoolClass::create(['name' => 'L5 SWD', 'section' => 'tvet']);
        $tvetModule = Module::create(['school_class_id' => $tvetClass->id, 'name' => 'PHP Programming']);
        $this->post('/teachers', [
            'name' => 'Out of Scope Teacher',
            'email' => 'out.of.scope@example.test',
            'module_id' => $tvetModule->id,
        ])->assertSessionHasErrors('module_id');

        $this->assertDatabaseMissing('users', ['email' => 'out.of.scope@example.test']);
    }

    public function test_student_registration_requires_a_unique_sdms_number_and_records_multiple_guardians(): void
    {
        $dos = $this->user('dosb');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        $registration = [
            'name' => 'Aline Student',
            'sdms_no' => 'SDMS-001',
            'school_class_id' => $schoolClass->id,
            'guardians' => [
                ['name' => 'Parent One', 'relation' => 'Mother', 'phone' => '0788000001'],
                ['name' => 'Parent Two', 'relation' => 'Father', 'phone' => '0788000002'],
            ],
        ];

        $this->actingAs($dos)->get('/students')->assertOk()->assertSee('Unique SDMS number');
        $this->post('/students', $registration)->assertRedirect();

        $student = Student::where('sdms_no', 'SDMS-001')->firstOrFail();
        $this->assertSame('APA-P1-00001', $student->admission_no);
        $this->assertDatabaseCount('guardians', 2);
        $this->assertDatabaseHas('fees', ['student_id' => $student->id]);

        $this->post('/students', $registration)->assertSessionHasErrors('sdms_no');
        $this->assertDatabaseCount('students', 1);
    }

    public function test_timetable_teacher_must_be_assigned_to_the_scheduled_subject(): void
    {
        $dos = $this->user('dosb');
        $primary = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        $mathTeacher = $this->user('trainer');
        $scienceTeacher = $this->user('trainer');
        Module::create([
            'school_class_id' => $primary->id,
            'name' => 'Mathematics',
            'trainer_id' => $mathTeacher->id,
        ]);
        Module::create([
            'school_class_id' => $primary->id,
            'name' => 'Science',
            'trainer_id' => $scienceTeacher->id,
        ]);
        $timetable = [
            'class_id' => $primary->id,
            'p' => [1 => ['start' => '08:00', 'end' => '09:00']],
            'c' => ['1-1' => ['subject' => 'Mathematics']],
        ];

        $this->actingAs($dos)->post('/timetable', array_replace($timetable, [
            'c' => ['1-1' => ['subject' => 'Mathematics', 'trainer' => $scienceTeacher->id]],
        ]))->assertSessionHas('err');
        $this->assertDatabaseCount('lessons', 0);

        $this->post('/timetable', array_replace($timetable, [
            'c' => ['1-1' => ['subject' => 'Mathematics', 'trainer' => $mathTeacher->id]],
        ]))->assertRedirect();
        $this->assertDatabaseHas('lessons', [
            'school_class_id' => $primary->id,
            'subject' => 'Mathematics',
            'trainer_id' => $mathTeacher->id,
        ]);
    }

    public function test_trainer_dashboard_only_shows_their_own_lessons(): void
    {
        $mathTeacher = $this->user('trainer');
        $scienceTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        $secondClass = SchoolClass::create(['name' => 'P2', 'section' => 'primary']);
        Module::create(['school_class_id' => $schoolClass->id, 'name' => 'Mathematics', 'trainer_id' => $mathTeacher->id]);
        Module::create(['school_class_id' => $schoolClass->id, 'name' => 'Science', 'trainer_id' => $scienceTeacher->id]);
        Module::create(['school_class_id' => $secondClass->id, 'name' => 'History', 'trainer_id' => $mathTeacher->id]);
        Lesson::create([
            'school_class_id' => $schoolClass->id,
            'day' => min(now()->dayOfWeekIso, 5),
            'slot' => 1,
            'subject' => 'Mathematics',
            'room' => 'Room 1',
            'trainer_id' => $mathTeacher->id,
        ]);
        Lesson::create([
            'school_class_id' => $schoolClass->id,
            'day' => min(now()->dayOfWeekIso, 5),
            'slot' => 2,
            'subject' => 'Science',
            'room' => 'Room 2',
            'trainer_id' => $scienceTeacher->id,
        ]);
        Lesson::create([
            'school_class_id' => $secondClass->id,
            'day' => min(now()->dayOfWeekIso, 5),
            'slot' => 3,
            'subject' => 'History',
            'room' => 'Room 3',
            'trainer_id' => $mathTeacher->id,
        ]);

        $this->actingAs($mathTeacher)->get('/home')
            ->assertOk()
            ->assertSee('Mathematics')
            ->assertSee('History')
            ->assertSee('P1')
            ->assertSee('P2')
            ->assertDontSee('Science');
    }

    public function test_subject_teacher_cannot_view_attendance(): void
    {
        $teacher = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        Module::create(['school_class_id' => $schoolClass->id, 'name' => 'Mathematics', 'trainer_id' => $teacher->id]);

        $this->actingAs($teacher)->get('/attendance')->assertStatus(403);
    }

    public function test_subject_teacher_dashboard_hides_attendance_actions_and_tasks(): void
    {
        $teacher = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        Module::create(['school_class_id' => $schoolClass->id, 'name' => 'Mathematics', 'trainer_id' => $teacher->id]);

        $this->actingAs($teacher)->get('/home')
            ->assertOk()
            ->assertDontSee('href="/attendance"', false);
        $attendanceTasks = collect(Tasks::for($teacher))
            ->filter(fn (array $task): bool => str_contains($task['t'], 'attendance'));

        $this->assertCount(0, $attendanceTasks);
    }

    public function test_subject_teacher_cannot_save_attendance_for_their_assigned_subject_class(): void
    {
        $teacher = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        Module::create(['school_class_id' => $schoolClass->id, 'name' => 'Mathematics', 'trainer_id' => $teacher->id]);
        $student = Student::create([
            'admission_no' => 'APA-P1-00001',
            'sdms_no' => 'SDMS-001',
            'name' => 'Aline Student',
            'school_class_id' => $schoolClass->id,
        ]);

        $this->actingAs($teacher)->post('/attendance', [
            'class_id' => $schoolClass->id,
            'date' => today()->toDateString(),
            's' => [$student->id => 'P'],
        ])->assertStatus(403);

        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id]);
    }

    public function test_class_teacher_can_save_attendance_for_their_designated_class(): void
    {
        $classTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create([
            'name' => 'P1',
            'section' => 'primary',
            'trainer_id' => $classTeacher->id,
        ]);
        $student = Student::create([
            'admission_no' => 'APA-P1-00001',
            'sdms_no' => 'SDMS-001',
            'name' => 'Aline Student',
            'school_class_id' => $schoolClass->id,
        ]);

        $this->actingAs($classTeacher)->post('/attendance', [
            'class_id' => $schoolClass->id,
            'date' => today()->toDateString(),
            's' => [$student->id => 'P'],
        ])->assertRedirect('/attendance?class='.$schoolClass->id.'&date='.today()->toDateString());

        $this->assertTrue(Attendance::where('student_id', $student->id)
            ->where('school_class_id', $schoolClass->id)
            ->whereDate('date', today())
            ->where('status', 'P')
            ->exists());
        $this->assertTrue(Register::where('school_class_id', $schoolClass->id)
            ->whereDate('date', today())
            ->where('user_id', $classTeacher->id)
            ->exists());
    }

    public function test_class_teacher_can_view_their_class_attendance_register(): void
    {
        $classTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create([
            'name' => 'P1',
            'section' => 'primary',
            'trainer_id' => $classTeacher->id,
        ]);

        $this->actingAs($classTeacher)->get('/attendance?class='.$schoolClass->id)
            ->assertOk()
            ->assertSee('Register · P1')
            ->assertSee('Save register');
    }

    public function test_class_teacher_cannot_take_attendance_for_a_subject_only_class(): void
    {
        $classTeacher = $this->user('trainer');
        $subjectTeacher = $this->user('trainer');
        $designatedClass = SchoolClass::create([
            'name' => 'P1',
            'section' => 'primary',
            'trainer_id' => $classTeacher->id,
        ]);
        $otherClass = SchoolClass::create(['name' => 'P2', 'section' => 'primary']);
        Module::create(['school_class_id' => $otherClass->id, 'name' => 'Mathematics', 'trainer_id' => $classTeacher->id]);
        Module::create(['school_class_id' => $otherClass->id, 'name' => 'Science', 'trainer_id' => $subjectTeacher->id]);
        $student = Student::create([
            'admission_no' => 'APA-P2-00001',
            'sdms_no' => 'SDMS-002',
            'name' => 'Eric Student',
            'school_class_id' => $otherClass->id,
        ]);

        $this->actingAs($classTeacher)->post('/attendance', [
            'class_id' => $otherClass->id,
            'date' => today()->toDateString(),
            's' => [$student->id => 'A'],
        ])->assertStatus(403);

        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id]);
    }

    public function test_dos_can_still_reopen_a_saved_attendance_register(): void
    {
        $dos = $this->user('dosb');
        $classTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create([
            'name' => 'P1',
            'section' => 'primary',
            'trainer_id' => $classTeacher->id,
        ]);
        $register = Register::create([
            'school_class_id' => $schoolClass->id,
            'date' => today()->toDateString(),
            'user_id' => $classTeacher->id,
            'saved_at' => now(),
        ]);

        $this->actingAs($dos)->post('/attendance/reopen', [
            'class_id' => $schoolClass->id,
            'date' => today()->toDateString(),
        ])->assertRedirect('/attendance?class='.$schoolClass->id.'&date='.today()->toDateString());

        $this->assertNull($register->fresh()->saved_at);
    }

    public function test_teacher_weekly_timetable_combines_assigned_classes_without_other_teachers_lessons(): void
    {
        $teacher = $this->user('trainer');
        $otherTeacher = $this->user('trainer');
        $firstClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        $secondClass = SchoolClass::create(['name' => 'P2', 'section' => 'primary']);
        Module::create(['school_class_id' => $firstClass->id, 'name' => 'Mathematics', 'trainer_id' => $teacher->id]);
        Module::create(['school_class_id' => $firstClass->id, 'name' => 'Science', 'trainer_id' => $otherTeacher->id]);
        Module::create(['school_class_id' => $secondClass->id, 'name' => 'History', 'trainer_id' => $teacher->id]);
        Lesson::create([
            'school_class_id' => $firstClass->id,
            'day' => 1,
            'slot' => 1,
            'subject' => 'Mathematics',
            'room' => 'Room 1',
            'trainer_id' => $teacher->id,
        ]);
        Lesson::create([
            'school_class_id' => $firstClass->id,
            'day' => 1,
            'slot' => 2,
            'subject' => 'Science',
            'room' => 'Room 2',
            'trainer_id' => $otherTeacher->id,
        ]);
        Lesson::create([
            'school_class_id' => $secondClass->id,
            'day' => 2,
            'slot' => 1,
            'subject' => 'History',
            'room' => 'Room 3',
            'trainer_id' => $teacher->id,
        ]);

        $this->actingAs($teacher)->get('/timetable')
            ->assertOk()
            ->assertSee('P1')
            ->assertSee('P2')
            ->assertSee('Mathematics')
            ->assertSee('History')
            ->assertDontSee('Science');
    }

    public function test_subject_teacher_sees_only_their_assigned_module_on_the_marks_page(): void
    {
        $teacher = $this->user('trainer');
        $otherTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        Module::create(['school_class_id' => $schoolClass->id, 'name' => 'Mathematics', 'trainer_id' => $teacher->id]);
        Module::create(['school_class_id' => $schoolClass->id, 'name' => 'Science', 'trainer_id' => $otherTeacher->id]);

        $this->actingAs($teacher)->get('/marks?class='.$schoolClass->id)
            ->assertOk()
            ->assertSee('Mathematics')
            ->assertDontSee('Science');
    }

    public function test_subject_teacher_sees_only_assignments_for_their_assigned_module(): void
    {
        $teacher = $this->user('trainer');
        $otherTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        $math = Module::create(['school_class_id' => $schoolClass->id, 'name' => 'Mathematics', 'trainer_id' => $teacher->id]);
        $science = Module::create(['school_class_id' => $schoolClass->id, 'name' => 'Science', 'trainer_id' => $otherTeacher->id]);
        Assignment::create([
            'school_class_id' => $schoolClass->id,
            'module' => $math->name,
            'title' => 'Math practice',
            'due_date' => today(),
            'user_id' => $teacher->id,
        ]);
        Assignment::create([
            'school_class_id' => $schoolClass->id,
            'module' => $science->name,
            'title' => 'Science practice',
            'due_date' => today(),
            'user_id' => $otherTeacher->id,
        ]);

        $this->actingAs($teacher)->get('/assignments')
            ->assertOk()
            ->assertSee('Math practice')
            ->assertDontSee('Science practice');
    }

    public function test_class_teacher_can_see_every_module_in_their_designated_class(): void
    {
        $classTeacher = $this->user('trainer');
        $subjectTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create([
            'name' => 'P1',
            'section' => 'primary',
            'trainer_id' => $classTeacher->id,
        ]);
        $module = Module::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Science',
            'trainer_id' => $subjectTeacher->id,
        ]);

        $this->actingAs($classTeacher)->get('/marks?class='.$schoolClass->id.'&module='.$module->id)
            ->assertOk()
            ->assertSee('Science');
    }

    public function test_class_teacher_can_create_an_assessment_for_any_module_in_their_designated_class(): void
    {
        $classTeacher = $this->user('trainer');
        $subjectTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create([
            'name' => 'P1',
            'section' => 'primary',
            'trainer_id' => $classTeacher->id,
        ]);
        $module = Module::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Science',
            'trainer_id' => $subjectTeacher->id,
        ]);

        $this->actingAs($classTeacher)->post('/marks/assessment', [
            'module_id' => $module->id,
            'title' => 'Class test',
            'kind' => 'Summative',
            'max' => 20,
            'date' => today()->toDateString(),
        ])->assertRedirect('/marks?class='.$schoolClass->id.'&module='.$module->id);

        $this->assertDatabaseHas('assessments', [
            'module_id' => $module->id,
            'title' => 'Class test',
            'user_id' => $classTeacher->id,
        ]);
    }

    public function test_class_teacher_can_create_an_assignment_for_any_module_in_their_designated_class(): void
    {
        $classTeacher = $this->user('trainer');
        $subjectTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create([
            'name' => 'P1',
            'section' => 'primary',
            'trainer_id' => $classTeacher->id,
        ]);
        $module = Module::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Science',
            'trainer_id' => $subjectTeacher->id,
        ]);

        $this->actingAs($classTeacher)->post('/assignments', [
            'module_id' => $module->id,
            'title' => 'Class practical',
            'instructions' => 'Complete the worksheet.',
            'due_date' => today()->addDays(7)->toDateString(),
            'max' => 20,
        ])->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'school_class_id' => $schoolClass->id,
            'module' => 'Science',
            'title' => 'Class practical',
            'user_id' => $classTeacher->id,
        ]);
    }

    public function test_class_teacher_can_enter_marks_for_another_teacher_module_in_their_class(): void
    {
        $classTeacher = $this->user('trainer');
        $subjectTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create([
            'name' => 'P1',
            'section' => 'primary',
            'trainer_id' => $classTeacher->id,
        ]);
        $module = Module::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Science',
            'trainer_id' => $subjectTeacher->id,
        ]);
        $student = Student::create([
            'admission_no' => 'APA-P1-00001',
            'sdms_no' => 'SDMS-001',
            'name' => 'Aline Student',
            'school_class_id' => $schoolClass->id,
        ]);
        $assessment = Assessment::create([
            'module_id' => $module->id,
            'school_class_id' => $schoolClass->id,
            'user_id' => $subjectTeacher->id,
            'title' => 'Science test',
            'kind' => 'Summative',
            'max' => 20,
            'date' => today(),
        ]);

        $this->actingAs($classTeacher)->post('/marks/'.$assessment->id.'/scores', [
            'm' => [$student->id => 17],
        ])->assertRedirect('/marks?class='.$schoolClass->id.'&module='.$module->id);

        $this->assertDatabaseHas('scores', [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => 17,
        ]);
    }

    public function test_subject_teacher_cannot_open_another_class_subjects_report_card(): void
    {
        $teacher = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        Module::create(['school_class_id' => $schoolClass->id, 'name' => 'Mathematics', 'trainer_id' => $teacher->id]);
        $student = Student::create([
            'admission_no' => 'APA-P1-00001',
            'sdms_no' => 'SDMS-001',
            'name' => 'Aline Student',
            'school_class_id' => $schoolClass->id,
        ]);

        $this->actingAs($teacher)->get('/report/'.$student->id)->assertStatus(403);
    }

    public function test_unassigned_trainer_sees_a_helpful_empty_state_instead_of_a_dashboard_error(): void
    {
        $this->actingAs($this->user('trainer'))
            ->get('/home')
            ->assertOk()
            ->assertSee('No classes assigned yet');
    }

    public function test_dos_without_classes_sees_an_empty_timetable_instead_of_an_error(): void
    {
        $this->actingAs($this->user('dost'))
            ->get('/timetable')
            ->assertOk()
            ->assertSee('No classes are available yet')
            ->assertDontSee('name="class_id"', false);
    }

    public function test_login_page_offers_seeded_subject_teacher_and_storekeeper_demo_accounts(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-fill="mathteacher@apacope.test"', false)
            ->assertSee('data-fill="scienceteacher@apacope.test"', false)
            ->assertSee('data-fill="storekeeper@apacope.test"', false);
    }

    public function test_student_and_parent_without_a_linked_student_see_linking_instructions(): void
    {
        $student = $this->user('student');
        $parent = $this->user('parent');

        foreach ([$student, $parent] as $user) {
            $this->actingAs($user)->get('/home')
                ->assertOk()
                ->assertSee('Ask the school administrator to link this account');
        }
    }

    public function test_report_rank_and_dos_approval_update_when_a_trainer_corrects_marks(): void
    {
        $dos = $this->user('dosb');
        $trainer = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        $module = Module::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Mathematics',
            'trainer_id' => $trainer->id,
        ]);
        $first = Student::create([
            'admission_no' => 'APA-P1-00001',
            'sdms_no' => 'SDMS-001',
            'name' => 'Aline Student',
            'school_class_id' => $schoolClass->id,
        ]);
        $second = Student::create([
            'admission_no' => 'APA-P1-00002',
            'sdms_no' => 'SDMS-002',
            'name' => 'Eric Student',
            'school_class_id' => $schoolClass->id,
        ]);
        $assessment = Assessment::create([
            'module_id' => $module->id,
            'school_class_id' => $schoolClass->id,
            'user_id' => $trainer->id,
            'title' => 'Term test',
            'kind' => 'Summative',
            'max' => 20,
            'date' => today(),
            'locked_at' => now(),
        ]);
        Score::create(['assessment_id' => $assessment->id, 'student_id' => $first->id, 'score' => 18]);
        Score::create(['assessment_id' => $assessment->id, 'student_id' => $second->id, 'score' => 10]);

        $this->actingAs($dos)->get('/report/'.$first->id)
            ->assertOk()
            ->assertSee('1 of 2')
            ->assertSee('Pending DOS approval')
            ->assertSee('report-subjects', false)
            ->assertSee('Term test');
        $this->post('/reports/classes/'.$schoolClass->id.'/approve')->assertRedirect();
        $this->assertDatabaseHas('report_approvals', ['school_class_id' => $schoolClass->id]);

        $this->actingAs($trainer)->post('/marks/'.$assessment->id.'/scores', [
            'm' => [$first->id => 8, $second->id => 10],
        ])->assertRedirect();

        $this->assertDatabaseMissing('report_approvals', ['school_class_id' => $schoolClass->id]);
        $this->actingAs($dos)->get('/report/'.$first->id)
            ->assertOk()
            ->assertSee('2 of 2')
            ->assertSee('Pending DOS approval');
    }

    public function test_assignment_marks_are_limited_to_the_assigned_trainer_and_update_reports(): void
    {
        $dos = $this->user('dosb');
        $trainer = $this->user('trainer');
        $otherTrainer = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        $module = Module::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Mathematics',
            'trainer_id' => $trainer->id,
        ]);
        $student = Student::create([
            'admission_no' => 'APA-P1-00001',
            'sdms_no' => 'SDMS-001',
            'name' => 'Aline Student',
            'school_class_id' => $schoolClass->id,
        ]);
        $assignment = Assignment::create([
            'school_class_id' => $schoolClass->id,
            'module' => $module->name,
            'title' => 'Practice work',
            'due_date' => today(),
            'user_id' => $trainer->id,
        ]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => 'Completed work',
        ]);
        ReportApproval::create([
            'school_class_id' => $schoolClass->id,
            'academic_year' => date('Y'),
            'term' => 'Term 2',
            'approved_by' => $dos->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($otherTrainer)->post('/assignments/submissions/'.$submission->id.'/grade', [
            'score' => 16,
        ])->assertNotFound();

        $this->actingAs($trainer)->post('/assignments/submissions/'.$submission->id.'/grade', [
            'score' => 16,
            'feedback' => 'Good work.',
        ])->assertRedirect();

        $this->assertDatabaseHas('scores', ['student_id' => $student->id, 'score' => 16]);
        $this->assertDatabaseHas('submissions', ['id' => $submission->id, 'grade' => '16/20']);
        $this->assertDatabaseMissing('report_approvals', ['school_class_id' => $schoolClass->id]);
    }

    public function test_dos_can_manage_modules_from_tss_records(): void
    {
        $dos = $this->user('dost');
        $schoolClass = SchoolClass::create(['name' => 'L5 SWD', 'section' => 'tvet']);

        $this->actingAs($dos)->get('/tss')
            ->assertOk()
            ->assertSee('Subjects · L5 SWD')
            ->assertSee('Add a subject')
            ->assertSee('Learning outcomes · L5 SWD');

        $this->from('/tss?class='.$schoolClass->id)->post('/marks/module', [
            'name' => 'Web Programming',
            'class_id' => $schoolClass->id,
            'context' => 'tss',
        ])->assertRedirect('/tss?class='.$schoolClass->id);

        $module = Module::where('school_class_id', $schoolClass->id)->where('name', 'Web Programming')->firstOrFail();
        $this->assertModelExists($module);

        $this->from('/tss?class='.$schoolClass->id)->post('/marks/modules/'.$module->id, [
            'name' => 'Advanced Web Programming',
        ])->assertRedirect('/tss?class='.$schoolClass->id);
        $this->assertDatabaseHas('modules', ['id' => $module->id, 'name' => 'Advanced Web Programming']);

        $this->from('/tss?class='.$schoolClass->id)->delete('/marks/modules/'.$module->id)
            ->assertRedirect('/tss?class='.$schoolClass->id);
        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    }

    public function test_only_tvet_dos_creates_outcomes_and_only_the_assigned_teacher_can_mark_them_covered(): void
    {
        $dos = $this->user('dost');
        $teacher = $this->user('trainer');
        $otherTeacher = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'L5 SWD', 'section' => 'tvet']);
        $module = Module::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Web Programming',
            'trainer_id' => $teacher->id,
        ]);

        $this->actingAs($teacher)->post('/tss/outcomes', [
            'module_id' => $module->id,
            'title' => 'Build a secure web form',
        ])->assertForbidden();

        $this->actingAs($dos)->from('/tss')->post('/tss/outcomes', [
            'module_id' => $module->id,
            'title' => 'Build a secure web form',
        ])->assertRedirect();

        $outcome = Outcome::where('module_id', $module->id)->firstOrFail();
        $this->actingAs($otherTeacher)->post('/tss/outcomes/'.$outcome->id.'/toggle')->assertForbidden();
        $this->actingAs($dos)->post('/tss/outcomes/'.$outcome->id.'/toggle')->assertForbidden();

        $this->actingAs($teacher)->from('/tss')->post('/tss/outcomes/'.$outcome->id.'/toggle')
            ->assertRedirect();
        $this->assertDatabaseHas('outcomes', ['id' => $outcome->id, 'covered' => true]);

        $this->actingAs($dos)->from('/tss')->post('/tss/outcomes/'.$outcome->id, [
            'title' => 'Create a secure web form',
        ])->assertRedirect();
        $this->assertDatabaseHas('outcomes', ['id' => $outcome->id, 'title' => 'Create a secure web form']);

        $this->delete('/tss/outcomes/'.$outcome->id)->assertRedirect();
        $this->assertDatabaseMissing('outcomes', ['id' => $outcome->id]);
    }

    public function test_non_tvet_dos_and_teachers_cannot_open_tss(): void
    {
        $dos = $this->user('dosb');
        $teacher = $this->user('trainer');
        $primary = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        Module::create(['school_class_id' => $primary->id, 'name' => 'Mathematics', 'trainer_id' => $teacher->id]);

        $this->actingAs($dos)->get('/tss')->assertForbidden();
        $this->actingAs($teacher)->get('/tss')->assertForbidden();
        $this->actingAs($teacher)->get('/home')->assertOk()->assertDontSee('href="/tss"', false);
    }

    public function test_new_module_starts_without_old_assessments_or_scores(): void
    {
        $dos = $this->user('dosb');
        $teacher = $this->user('trainer');
        $schoolClass = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        $student = Student::create([
            'admission_no' => 'APA-P1-00001',
            'sdms_no' => 'SDMS-001',
            'name' => 'Aline Student',
            'school_class_id' => $schoolClass->id,
        ]);
        $existingModule = Module::create([
            'school_class_id' => $schoolClass->id,
            'name' => 'Mathematics',
            'trainer_id' => $teacher->id,
        ]);
        $assessment = Assessment::create([
            'module_id' => $existingModule->id,
            'school_class_id' => $schoolClass->id,
            'user_id' => $teacher->id,
            'title' => 'Term test',
            'kind' => 'Summative',
            'max' => 20,
            'date' => today(),
            'locked_at' => now(),
        ]);
        Score::create(['assessment_id' => $assessment->id, 'student_id' => $student->id, 'score' => 16]);

        $this->actingAs($dos)->post('/marks/module', [
            'class_id' => $schoolClass->id,
            'name' => 'Science',
        ])->assertRedirect();

        $newModule = Module::where('school_class_id', $schoolClass->id)->where('name', 'Science')->firstOrFail();
        $this->assertDatabaseMissing('assessments', ['module_id' => $newModule->id]);
        $this->assertDatabaseHas('scores', ['assessment_id' => $assessment->id, 'student_id' => $student->id, 'score' => 16]);

        $this->actingAs($dos)->get('/marks?class='.$schoolClass->id.'&module='.$newModule->id)
            ->assertOk()
            ->assertSee('Science')
            ->assertSee('Mathematics')
            ->assertSee('80%')
            ->assertSee('—');
    }

    public function test_dos_can_assign_an_existing_cross_section_teacher_without_creating_a_duplicate(): void
    {
        $dos = $this->user('dosb');
        $teacher = $this->user('trainer');
        $primary = SchoolClass::create(['name' => 'P1', 'section' => 'primary']);
        $tvet = SchoolClass::create(['name' => 'L5 SWD', 'section' => 'tvet']);
        $math = Module::create(['school_class_id' => $primary->id, 'name' => 'Mathematics']);
        Module::create(['school_class_id' => $tvet->id, 'name' => 'PHP', 'trainer_id' => $teacher->id]);
        Lesson::create([
            'school_class_id' => $primary->id,
            'day' => 1,
            'slot' => 1,
            'subject' => 'Mathematics',
            'room' => 'Room 1',
        ]);
        Lesson::create([
            'school_class_id' => $tvet->id,
            'day' => 2,
            'slot' => 1,
            'subject' => 'PHP',
            'room' => 'Computer lab',
            'trainer_id' => $teacher->id,
        ]);

        $this->actingAs($dos)->get('/teachers')
            ->assertOk()
            ->assertSee('value="'.$teacher->id.'"', false)
            ->assertSee('L5 SWD · PHP');

        $this->actingAs($dos)->post('/teachers/assignments', [
            'module_id' => $math->id,
            'trainer_id' => $teacher->id,
        ])->assertRedirect();

        $this->assertSame(1, User::where('email', $teacher->email)->count());
        $this->assertSame($teacher->id, $math->fresh()->trainer_id);
        $this->actingAs($teacher)->get('/timetable')
            ->assertOk()
            ->assertSee('P1')
            ->assertSee('L5 SWD');
    }

    public function test_users_can_change_their_own_password_from_account_settings(): void
    {
        $user = $this->user('student');

        $this->actingAs($user)->get('/account')
            ->assertOk()
            ->assertSee('Change password');
        $this->post('/account/password', [
            'current_password' => 'password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect()->assertSessionHas('ok');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
        $this->post('/account/password', [
            'current_password' => 'password',
            'password' => 'another-secure-password',
            'password_confirmation' => 'another-secure-password',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_admin_password_reset_sends_a_secure_link_instead_of_showing_a_temporary_password(): void
    {
        config(['mail.default' => 'array']);
        Notification::fake();
        $admin = $this->user('admin');
        $account = $this->user('trainer');

        $this->actingAs($admin)->post('/admin/users/'.$account->id.'/reset')
            ->assertRedirect()
            ->assertSessionHas('ok')
            ->assertSessionMissing('password');

        Notification::assertSentTo($account, ResetPassword::class);
        $this->assertTrue(Hash::check('password', $account->fresh()->password));
    }

    public function test_any_account_can_use_a_reset_link_to_set_a_new_password(): void
    {
        config(['mail.default' => 'array']);
        Notification::fake();
        $user = $this->user('student');
        $token = null;

        $this->post('/forgot', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('ok');

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            }
        );

        $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))
            ->assertOk()
            ->assertSee('Reset password');
        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect('/login')->assertSessionHas('ok');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_website_contact_messages_are_sent_to_the_admin_configured_email(): void
    {
        config(['mail.default' => 'array']);
        Mail::fake();
        $admin = $this->user('admin');

        $this->actingAs($admin)->post('/website/contact', [
            'phone' => '+250 788 000 000',
            'email' => 'info@apacope.rw',
            'address' => 'Nyarugenge, Kigali',
            'communication_email' => 'school-inbox@example.test',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'key' => 'communication_email',
            'value' => 'school-inbox@example.test',
        ]);

        $this->post('/contact', [
            'name' => 'Aline Parent',
            'email' => 'parent@example.test',
            'message' => 'Please contact me about the school calendar.',
        ])->assertRedirect('/#contact')->assertSessionHas('sent');

        Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail): bool => $mail->hasTo('school-inbox@example.test'));
    }

    public function test_contact_form_sends_without_a_page_reload_and_confirms_the_gmail_recipient(): void
    {
        config(['mail.default' => 'array']);
        Mail::fake();

        $this->postJson('/contact', [
            'name' => 'Aline Parent',
            'email' => 'parent@example.test',
            'message' => 'Please contact me about the school calendar.',
        ])->assertOk()
            ->assertJsonPath('message', 'Thank you. Your message has been sent to the school.');

        Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail): bool => $mail->hasTo('mucyobilly3@gmail.com'));
    }

    public function test_contact_form_shows_a_friendly_message_when_email_is_not_configured(): void
    {
        config(['mail.default' => 'log']);

        $this->postJson('/contact', [
            'name' => 'Aline Parent',
            'email' => 'parent@example.test',
            'message' => 'Please contact me about the school calendar.',
        ])->assertStatus(503)
            ->assertJsonPath('message', 'Email is not available right now. Please try again later or call the school.');
    }

    public function test_contact_form_explains_when_gmail_app_password_is_missing(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.username' => 'mucyobilly3@gmail.com',
            'mail.mailers.smtp.password' => '',
        ]);

        $this->postJson('/contact', [
            'name' => 'Aline Parent',
            'email' => 'parent@example.test',
            'message' => 'Please contact me about the school calendar.',
        ])->assertStatus(503)
            ->assertJsonPath('message', 'The school email service is not ready yet. Please call the school or try again later.');
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' User',
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'password',
            'role' => $role,
            'active' => true,
        ]);
    }
}
