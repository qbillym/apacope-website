<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\FeeMessage;
use App\Models\Module;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Submission;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['trainer', 'student', 'parent'], true), 403);
        $classIds = $user->classes()->pluck('id');
        $classTeacherIds = $user->role === 'trainer'
            ? SchoolClass::where('trainer_id', $user->id)->pluck('id')
            : collect();
        $isStudent = in_array($user->role, ['student', 'parent'], true);
        $modules = Module::whereIn('school_class_id', $classIds)
            ->when($user->role === 'trainer', fn ($query) => $query->where(fn ($moduleQuery) => $moduleQuery
                ->where('trainer_id', $user->id)
                ->orWhereIn('school_class_id', $classTeacherIds)))
            ->orderBy('name')
            ->get();
        $assignmentsQuery = Assignment::with(['schoolClass', 'user', 'submissions.student'])
            ->whereIn('school_class_id', $classIds);
        if ($user->role === 'trainer') {
            $assignmentsQuery->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('modules')
                ->whereColumn('modules.school_class_id', 'assignments.school_class_id')
                ->whereColumn('modules.name', 'assignments.module')
                ->where(fn ($moduleQuery) => $moduleQuery
                    ->where('modules.trainer_id', $user->id)
                    ->orWhereIn('modules.school_class_id', $classTeacherIds)));
        }
        $assignments = $assignmentsQuery->orderByDesc('due_date')->get();

        return view('assignments', [
            'list' => $assignments,
            'as' => Assessment::whereIn('id', $assignments->pluck('assessment_id')->filter())->get()->keyBy('id'),
            'mine' => $isStudent ? Submission::where('student_id', $user->student_id)->get()->keyBy('assignment_id') : collect(),
            'scores' => $isStudent ? Score::where('student_id', $user->student_id)->pluck('score', 'assessment_id') : collect(),
            'classes' => $user->classes()->orderBy('id')->get(),
            'mods' => $modules->groupBy('school_class_id'),
            'tr' => $user->role === 'trainer',
            'st' => $user->role === 'student',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'trainer', 403);
        $values = $request->validate([
            'module_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'instructions' => ['nullable', 'string', 'max:1500'],
            'type' => ['required', 'in:assignment,assessment,quiz'],
            'max' => ['required', 'numeric', 'min:1', 'max:100'],
            'deadline_type' => ['required', 'in:date,minutes,hours,days'],
            'deadline_value' => ['nullable', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'questions' => ['nullable', 'array'],
        ]);

        $isQuiz = $values['type'] === 'quiz';
        
        if ($values['deadline_type'] === 'date') {
            $dueDate = Carbon::parse($values['due_date'])->endOfDay();
        } else {
            $value = (int) ($values['deadline_value'] ?? 1);
            $dueDate = match ($values['deadline_type']) {
                'minutes' => now()->addMinutes($value),
                'hours' => now()->addHours($value),
                'days' => now()->addDays($value)->endOfDay(),
            };
        }
        $module = Module::findOrFail($values['module_id']);
        abort_unless((int) $module->trainer_id === $user->id || $user->isClassTeacherFor($module->school_class_id), 404);

        DB::transaction(function () use ($values, $module, $user, $dueDate, $isQuiz): void {
            $assessment = Assessment::create([
                'module_id' => $module->id,
                'school_class_id' => $module->school_class_id,
                'user_id' => $user->id,
                'title' => $values['title'],
                'kind' => ucfirst($values['type']),
                'max' => (int) $values['max'],
                'date' => today()->toDateString(),
                'deadline' => $dueDate,
            ]);
            $assignment = Assignment::create([
                'school_class_id' => $module->school_class_id,
                'module' => $module->name,
                'title' => $values['title'],
                'instructions' => $values['instructions'] ?? null,
                'due_date' => $dueDate,
                'user_id' => $user->id,
                'assessment_id' => $assessment->id,
                'is_quiz' => $isQuiz,
            ]);

            if ($isQuiz) {
                foreach ($values['questions'] ?? [] as $questionData) {
                    $options = null;
                    $correctAnswer = $questionData['correct_answer'] ?? '';
                    
                    if ($questionData['type'] === 'multiple_choice') {
                        $choices = $questionData['choices'] ?? [];
                        $options = array_values($choices);
                        $correctIndex = (int) $correctAnswer;
                        $correctAnswer = $options[$correctIndex - 1] ?? '';
                    } elseif ($questionData['type'] === 'matching') {
                        $pairs = array_filter(array_map('trim', explode("\n", $questionData['matching_pairs'] ?? '')));
                        $options = [];
                        foreach ($pairs as $pair) {
                            if (str_contains($pair, '-')) {
                                [$item, $match] = explode('-', $pair, 2);
                                $options[trim($item)] = trim($match);
                            }
                        }
                        $correctAnswer = json_encode($options);
                    }

                    QuizQuestion::create([
                        'assignment_id' => $assignment->id,
                        'question' => $questionData['question'],
                        'type' => $questionData['type'],
                        'options' => $options,
                        'correct_answer' => $correctAnswer,
                        'points' => (int) ($questionData['points'] ?? 1),
                    ]);
                }
            }

            Announcement::create([
                'user_id' => $user->id,
                'title' => 'New '.ucfirst($values['type']).': '.$values['title'],
                'body' => $module->name.', out of '.(int) $values['max'].' marks. Due '.Carbon::parse($dueDate)->format('d M Y H:i').'. Open Assignments to read the instructions.',
                'audience' => $module->schoolClass->name,
            ]);
        });

        return back()->with('ok', 'Assignment created for '.$module->name.'. Its marks appear in the module and report cards.');
    }

    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'student' && $user->classes()->whereKey($assignment->school_class_id)->exists(), 403);

        if ($assignment->assessment_id && Score::where([
            'assessment_id' => $assignment->assessment_id,
            'student_id' => $user->student_id,
        ])->exists()) {
            return back()->with('err', 'This work was already marked.');
        }

        $values = $request->validate([
            'content' => ['nullable', 'string', 'max:3000'],
            'link' => ['nullable', 'url'],
            'file' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png,txt,py,php,js,html'],
        ]);
        unset($values['file']);
        if ($request->hasFile('file')) {
            $values['path'] = $request->file('file')->store('submissions', 'local');
        }
        if (empty($values['content']) && empty($values['link']) && empty($values['path'])) {
            return back()->with('err', 'Write something, add a link, or choose a file.')->withInput();
        }

        Submission::updateOrCreate(['assignment_id' => $assignment->id, 'student_id' => $user->student_id], $values);

        return back()->with('ok', 'Your work was submitted.');
    }

    public function submitQuiz(Request $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'student' && $user->classes()->whereKey($assignment->school_class_id)->exists(), 403);
        abort_unless($assignment->is_quiz, 404);

        if ($assignment->due_date->isPast()) {
            return back()->with('err', 'This quiz has expired. You can no longer submit your answers.');
        }

        if ($assignment->assessment_id && Score::where([
            'assessment_id' => $assignment->assessment_id,
            'student_id' => $user->student_id,
        ])->exists()) {
            return back()->with('err', 'You have already submitted this quiz.');
        }

        $values = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $questions = QuizQuestion::where('assignment_id', $assignment->id)->get();
        $totalPoints = 0;
        $earnedPoints = 0;
        $percentage = 0;

        DB::transaction(function () use ($assignment, $user, $values, $questions, &$totalPoints, &$earnedPoints, &$percentage) {
            foreach ($questions as $question) {
                $totalPoints += $question->points;
                $studentAnswer = $values['answers'][$question->id] ?? null;
                
                $isCorrect = false;
                $pointsEarned = 0;

                if ($question->type === 'multiple_choice' || $question->type === 'true_false') {
                    $isCorrect = trim($studentAnswer) === trim($question->correct_answer);
                } elseif ($question->type === 'matching') {
                    $correctPairs = json_decode($question->correct_answer, true);
                    $studentPairs = is_array($studentAnswer) ? $studentAnswer : [];
                    $isCorrect = true;
                    foreach ($correctPairs as $item => $match) {
                        if (($studentPairs[$item] ?? '') !== $match) {
                            $isCorrect = false;
                            break;
                        }
                    }
                } else {
                    $isCorrect = strtolower(trim($studentAnswer)) === strtolower(trim($question->correct_answer));
                }

                if ($isCorrect) {
                    $pointsEarned = $question->points;
                    $earnedPoints += $pointsEarned;
                }

                QuizAnswer::updateOrCreate([
                    'quiz_question_id' => $question->id,
                    'student_id' => $user->student_id,
                ], [
                    'answer' => $studentAnswer,
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                ]);
            }

            $module = Module::where('school_class_id', $assignment->school_class_id)
                ->where('name', $assignment->module)
                ->firstOrFail();

            $assessment = $assignment->assessment_id
                ? Assessment::where('module_id', $module->id)->findOrFail($assignment->assessment_id)
                : Assessment::create([
                    'module_id' => $module->id,
                    'school_class_id' => $module->school_class_id,
                    'user_id' => $assignment->user_id,
                    'title' => $assignment->title,
                    'kind' => 'Quiz',
                    'max' => $totalPoints,
                    'date' => today()->toDateString(),
                    'deadline' => $assignment->due_date?->toDateString() ?? today()->toDateString(),
                    'locked_at' => now(),
                ]);

            if (!$assignment->assessment_id) {
                $assignment->update(['assessment_id' => $assessment->id]);
            }

            $percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;
            Score::updateOrCreate(
                ['assessment_id' => $assessment->id, 'student_id' => $user->student_id],
                ['score' => $percentage]
            );

            Submission::updateOrCreate([
                'assignment_id' => $assignment->id,
                'student_id' => $user->student_id,
            ], [
                'content' => "Quiz completed. Score: {$earnedPoints}/{$totalPoints} ({$percentage}%)",
            ]);
        });

        return redirect('/assignments/' . $assignment->id . '/quiz/results')->with('ok', 'Quiz submitted! Your score: ' . $percentage . '%');
    }

    public function quizResults(Request $request, Assignment $assignment): View
    {
        $user = $request->user();
        abort_unless($user->role === 'student' && $user->classes()->whereKey($assignment->school_class_id)->exists(), 403);
        abort_unless($assignment->is_quiz, 404);

        $questions = QuizQuestion::where('assignment_id', $assignment->id)->get();
        $answers = QuizAnswer::where('student_id', $user->student_id)
            ->whereIn('quiz_question_id', $questions->pluck('id'))
            ->get()
            ->keyBy('quiz_question_id');

        $totalPoints = $questions->sum('points');
        $earnedPoints = $answers->sum('points_earned');

        return view('quiz_results', [
            'assignment' => $assignment,
            'questions' => $questions,
            'answers' => $answers,
            'totalPoints' => $totalPoints,
            'earnedPoints' => $earnedPoints,
            'user' => $user,
        ]);
    }

    public function grade(Request $request, Submission $submission): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'trainer', 403);
        $assignment = $submission->assignment;
        $module = Module::where('school_class_id', $assignment->school_class_id)
            ->where('name', $assignment->module)
            ->firstOrFail();
        abort_unless((int) $module->trainer_id === $user->id || $user->isClassTeacherFor($module->school_class_id), 404);
        abort_unless(Student::where('id', $submission->student_id)->where('school_class_id', $assignment->school_class_id)->exists(), 404);

        $assessment = $assignment->assessment_id
            ? Assessment::where('module_id', $module->id)->findOrFail($assignment->assessment_id)
            : null;

        $values = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.($assessment?->max ?? 20)],
            'feedback' => ['nullable', 'string', 'max:255'],
        ]);
        $score = (float) $values['score'];
        DB::transaction(function () use ($assignment, &$assessment, $module, $submission, $user, $values, $score): void {
            if ($assessment === null) {
                $assessment = Assessment::create([
                    'module_id' => $module->id,
                    'school_class_id' => $module->school_class_id,
                    'user_id' => $user->id,
                    'title' => $assignment->title,
                    'kind' => 'Assignment',
                    'max' => 20,
                    'date' => today()->toDateString(),
                    'deadline' => $assignment->due_date?->toDateString() ?? today()->toDateString(),
                ]);
                $assignment->update(['assessment_id' => $assessment->id]);
            }

            Score::updateOrCreate(
                ['assessment_id' => $assessment->id, 'student_id' => $submission->student_id],
                ['score' => $score]
            );
            if (! $assessment->locked_at) {
                $assessment->update(['locked_at' => now()]);
            }
            $scoreText = rtrim(rtrim(number_format($score, 1), '0'), '.').'/'.$assessment->max;
            $submission->update(['grade' => $scoreText, 'feedback' => $values['feedback'] ?? null]);
            $student = Student::findOrFail($submission->student_id);
            FeeMessage::create([
                'student_id' => $student->id,
                'user_id' => $user->id,
                'channel' => 'Marks',
                'body' => $student->name.' scored '.$scoreText.' in the assignment "'.$assignment->title.'".'.(! empty($values['feedback']) ? ' Feedback: '.$values['feedback'] : ''),
            ]);
        });

        return back()->with('ok', 'Marks saved. Results and report cards have been updated.');
    }
}
