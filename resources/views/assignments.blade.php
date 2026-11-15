@extends('layouts.app')@section('t','Assignments and Assessments')@section('c')
@if($tr)<form method="post" action="/assignments" class="card" id="assignmentForm">@csrf<h3>Create New Work</h3><p class="mu" style="margin-bottom:12px">Choose the type of work you want to create. Assignments allow file uploads, assessments are for manual grading, and quizzes are auto-graded with instant results.</p><div class="toolbar"><div><label>Module (lesson)</label><select name="module_id" required>@foreach($classes as $c)<optgroup label="{{ $c->name }}">@foreach($mods[$c->id]??[] as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</optgroup>@endforeach</select></div><div style="flex:1;min-width:220px"><label>Title</label><input name="title" required style="width:100%" placeholder="e.g., Chapter 5 Test, Homework 3"></div><div><label>Type</label><select name="type" id="workType" onchange="toggleWorkType()" style="width:140px"><option value="assignment">Assignment</option><option value="assessment">Assessment</option><option value="quiz">Quiz (Auto-graded)</option></select></div></div>
<div class="toolbar" style="margin-top:14px"><div><label>Total marks</label><input type="number" name="max" value="20" min="1" max="100" style="width:100px"></div><div><label>Deadline</label><select name="deadline_type" id="deadlineType" onchange="toggleDeadline()" style="width:120px;margin-right:8px"><option value="date">Specific Date</option><option value="minutes">Minutes</option><option value="hours">Hours</option><option value="days">Days</option></select><span id="deadlineValueContainer" style="display:none"><input type="number" name="deadline_value" id="deadlineValue" value="30" min="1" style="width:80px" placeholder="30"></span><input type="date" name="due_date" id="dueDate" value="{{ today()->addDays(7)->toDateString() }}"></div></div>
<div class="toolbar" style="margin-top:14px"><label>Instructions / Description</label></div>
<textarea name="instructions" rows="3" placeholder="Describe what students need to do. For quizzes, explain the topic and any special instructions." style="margin-top:6px"></textarea>
<div id="quizBuilder" style="display:none;margin-top:20px;border-top:1px solid var(--ln);padding-top:20px"><h4>Quiz Questions</h4><p class="mu" style="margin-bottom:12px">Add questions for auto-grading. Students see results immediately after submission. Supports multiple choice, true/false, and matching questions.</p><div id="questionsContainer"></div><button type="button" class="btn" onclick="addQuestion()" style="margin-top:12px">+ Add Question</button></div>
<p style="margin-top:14px"><button class="btn">Publish</button></p><p class="mu" style="margin:0">Marks are automatically added to the module and report cards. Students who miss deadlines will receive 0 marks automatically.</p></form>
<script>
let questionCount = 0;
function toggleWorkType(){const type=document.getElementById('workType').value;document.getElementById('quizBuilder').style.display=type==='quiz'?'block':'none';if(type!=='quiz')document.getElementById('questionsContainer').innerHTML=''}
function toggleDeadline(){const type=document.getElementById('deadlineType').value;const valueContainer=document.getElementById('deadlineValueContainer');const dateInput=document.getElementById('dueDate');const valueInput=document.getElementById('deadlineValue');if(type==='date'){valueContainer.style.display='none';dateInput.style.display='inline-block'}else{valueContainer.style.display='inline-block';dateInput.style.display='none';if(type==='minutes')valueInput.placeholder='30';else if(type==='hours')valueInput.placeholder='2';else valueInput.placeholder='7'}}
function addQuestion(){questionCount++;const container=document.getElementById('questionsContainer');const div=document.createElement('div');div.className='card';div.style.marginTop='12px';div.id='question-'+questionCount;div.innerHTML='<div class="toolbar"><div style="flex:1"><label>Question '+questionCount+'</label><input type="text" name="questions['+questionCount+'][question]" required style="width:100%" placeholder="Enter your question"></div><div><label>Points</label><input type="number" name="questions['+questionCount+'][points]" value="1" min="1" style="width:80px"></div></div><div class="toolbar" style="margin-top:8px"><label>Question Type:</label><select name="questions['+questionCount+'][type]" onchange="toggleQuestionType('+questionCount+',this.value)" style="width:180px;margin-right:12px"><option value="multiple_choice">Multiple Choice</option><option value="true_false">True / False</option><option value="matching">Matching</option></select><button type="button" class="btn ghost" onclick="removeQuestion('+questionCount+')" style="color:var(--ab);border-color:var(--ab)">Remove</button></div><div id="multipleChoice-'+questionCount+'" style="margin-top:12px"><label>Number of choices:</label><select name="questions['+questionCount+'][choice_count]" onchange="renderChoices('+questionCount+',this.value)" style="width:120px;margin-top:6px"><option value="2">2 choices</option><option value="3">3 choices</option><option value="4" selected>4 choices</option><option value="5">5 choices</option></select><div id="choices-'+questionCount+'" style="margin-top:12px"></div><div style="margin-top:12px"><label>Correct Answer:</label><select name="questions['+questionCount+'][correct_answer]" id="correctAnswer-'+questionCount+'" style="width:200px;margin-top:6px"></select></div></div><div id="trueFalse-'+questionCount+'" style="display:none;margin-top:12px"><label>Correct Answer:</label><select name="questions['+questionCount+'][correct_answer]" style="width:150px;margin-top:6px"><option value="True">True</option><option value="False">False</option></select></div><div id="matching-'+questionCount+'" style="display:none;margin-top:12px"><label>Matching Pairs (one pair per line, format: item1 - match1):</label><textarea name="questions['+questionCount+'][matching_pairs]" rows="4" placeholder="Apple - Fruit&#10;Carrot - Vegetable&#10;Dog - Animal" style="width:100%;margin-top:6px"></textarea></div>';container.appendChild(div);renderChoices(questionCount,4)}
function toggleQuestionType(id,type){document.getElementById('multipleChoice-'+id).style.display=type==='multiple_choice'?'block':'none';document.getElementById('trueFalse-'+id).style.display=type==='true_false'?'block':'none';document.getElementById('matching-'+id).style.display=type==='matching'?'block':'none'}
function renderChoices(id,count){const container=document.getElementById('choices-'+id);const correctSelect=document.getElementById('correctAnswer-'+id);let html='';for(let i=1;i<=count;i++){html+='<div style="margin-bottom:8px"><label>Choice '+i+':</label><input type="text" name="questions['+id+'][choices]['+i+']" required style="width:100%;margin-top:4px" placeholder="Enter choice '+i+'"></div>'}container.innerHTML=html;let selectHtml='<option value="">Select correct answer</option>';for(let i=1;i<=count;i++){selectHtml+='<option value="'+i+'">Choice '+i+'</option>'}correctSelect.innerHTML=selectHtml}
function removeQuestion(id){document.getElementById('question-'+id).remove()}
</script>@endif
@forelse($list as $a)@php($my=$mine[$a->id]??null)@php($mx=$as[$a->assessment_id]->max??20)@php($sc=$scores[$a->assessment_id]??null)<div class="card"><h3>{{ $a->title }} <span class="b info">{{ $a->module }}</span> <span class="b">{{ $mx }} marks</span> <span class="b {{ $a->due_date->isPast()?'bad':'warn' }}">Due {{ $a->due_date->format('d M') }}</span></h3><p>{{ $a->instructions }}</p><p class="mu">{{ $a->schoolClass->name }} · {{ $a->user->name }}</p>
@if($tr)<details class="itm"><summary>Submissions ({{ $a->submissions->count() }}) and marking</summary>@forelse($a->submissions as $s)<div class="ann" style="margin-top:14px"><b>{{ $s->student->name }}</b> <span class="mu">{{ $s->updated_at->diffForHumans() }}</span>@if($s->content)<p style="margin:4px 0">{{ $s->content }}</p>@endif<div class="row" style="margin:6px 0">@if($s->link)<a href="{{ $s->link }}" target="_blank">Link</a>@endif @if($s->path)<a href="{{ url('/files/submission/'.$s->id) }}" target="_blank">File</a>@endif</div><form method="post" action="/assignments/submissions/{{ $s->id }}/grade" class="toolbar">@csrf<div><label>Marks</label><input type="number" name="score" step="0.5" min="0" max="{{ $mx }}" value="{{ $s->grade?explode('/',$s->grade)[0]:'' }}" required style="width:110px"> / {{ $mx }}</div><input name="feedback" value="{{ $s->feedback }}" placeholder="Feedback (optional)" style="flex:1;min-width:200px"><button class="btn navy">{{ $s->grade?'Update marks':'Save marks' }}</button></form></div>@empty<p class="mu" style="margin-top:12px">No submissions yet.</p>@endforelse<p class="mu" style="margin:16px 0 0">You can also enter marks for the whole class in <a href="/marks?class={{ $a->school_class_id }}">Marks &amp; assessment</a>, under this module.</p></details>
@elseif($st)
    @if($sc!==null)
        <p><span class="b ok">Marks: {{ rtrim(rtrim(number_format($sc,1),'0'),'.') }} / {{ $mx }}</span> {{ $my->feedback??'' }}</p>
    @else
        @if($my)
            <p><span class="b info">✓ Submitted</span> You can replace it until it is marked.</p>
        @endif
        @if($a->is_quiz)
            @php($questions=\App\Models\QuizQuestion::where('assignment_id',$a->id)->get())
            @if($questions->isEmpty())
                <p class="mu">No quiz questions available.</p>
            @else
                <div id="deadline-warning-{{ $a->id }}" class="flash" style="display:none;background:#fdecec;color:#b42318;border-color:#f5c2c0;margin-bottom:12px">This quiz has expired. You can no longer submit your answers.</div>
                <div id="countdown-{{ $a->id }}" class="okmsg" style="display:none;margin-bottom:12px">Time remaining: <strong id="timer-{{ $a->id }}"></strong></div>
                <form method="post" action="/assignments/{{ $a->id }}/quiz" id="quizForm_{{ $a->id }}">
                    @csrf
                    @foreach($questions as $q)
                        <div class="card" style="margin-top:14px">
                            <p><strong>Q{{ $loop->iteration }}:</strong> {{ $q->question }} <span class="mu">({{ $q->points }} point{{ $q->points>1?'s':'' }})</span></p>
                            @if($q->type==='multiple_choice')
                                @foreach($q->options??[] as $opt)
                                    <label style="display:block;margin:6px 0"><input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt }}" required> {{ $opt }}</label>
                                @endforeach
                            @elseif($q->type==='true_false')
                                <label style="display:inline-block;margin:6px 12px 6px 0"><input type="radio" name="answers[{{ $q->id }}]" value="True" required> True</label>
                                <label style="display:inline-block;margin:6px 0"><input type="radio" name="answers[{{ $q->id }}]" value="False"> False</label>
                            @elseif($q->type==='matching')
                                @php($pairs=json_decode($q->correct_answer,true)??[])
                                @foreach($pairs as $item=>$match)
                                    <div style="margin:8px 0"><strong>{{ $item }}</strong> matches with: <select name="answers[{{ $q->id }}][{{ $item }}]" required style="width:150px;margin-left:8px">@foreach($pairs as $key=>$val)<option value="{{ $val }}">{{ $val }}</option>@endforeach</select></div>
                                @endforeach
                            @else
                                <input type="text" name="answers[{{ $q->id }}]" required style="width:100%;margin-top:8px" placeholder="Your answer">
                            @endif
                        </div>
                    @endforeach
                    <button class="btn" id="submitBtn-{{ $a->id }}" style="margin-top:14px">Submit Quiz</button>
                </form>
                <script>
                    (function(){const deadline=new Date('{{ $a->due_date->toIso8601String() }}');const now=new Date();const countdownEl=document.getElementById('countdown-{{ $a->id }}');const timerEl=document.getElementById('timer-{{ $a->id }}');const warningEl=document.getElementById('deadline-warning-{{ $a->id }}');const submitBtn=document.getElementById('submitBtn-{{ $a->id }}');const form=document.getElementById('quizForm-{{ $a->id }}');if(deadline>now){countdownEl.style.display='block';function updateTimer(){const current=new Date();const diff=deadline-current;if(diff<=0){countdownEl.style.display='none';warningEl.style.display='block';submitBtn.disabled=true;submitBtn.textContent='Expired';form.querySelectorAll('input,select,button').forEach(el=>el.disabled=true);return}const minutes=Math.floor(diff/60000);const seconds=Math.floor((diff%60000)/1000);timerEl.textContent=minutes+'m '+seconds+'s';setTimeout(updateTimer,1000)}updateTimer()}else{warningEl.style.display='block';submitBtn.disabled=true;submitBtn.textContent='Expired';form.querySelectorAll('input,select,button').forEach(el=>el.disabled=true)}})();
                </script>
            @endif
        @else
            <form method="post" action="/assignments/{{ $a->id }}/submit" enctype="multipart/form-data">
                @csrf
                <textarea name="content" rows="3" placeholder="Your answer (optional)">{{ $my->content??'' }}</textarea>
                <div class="toolbar" style="margin-top:12px">
                    <div style="flex:1;min-width:220px"><label>Link</label><input name="link" value="{{ $my->link??'' }}" style="width:100%"></div>
                    <div><label>Or a file (PDF or Word only)</label><input type="file" name="file" accept=".pdf,.doc,.docx"></div>
                    <button class="btn">Submit work</button>
                </div>
            </form>
        @endif
    @endif
@else<p>@if($sc!==null)<span class="b ok">Marks: {{ rtrim(rtrim(number_format($sc,1),'0'),'.') }} / {{ $mx }}</span> {{ $my->feedback??'' }}@elseif($my)<span class="b info">Submitted</span>@else<span class="b {{ $a->due_date->isPast()?'bad':'warn' }}">Not submitted</span>@endif</p>@endif</div>@empty<div class="card"><p class="mu">No assignments yet.</p></div>@endforelse
@endsection
