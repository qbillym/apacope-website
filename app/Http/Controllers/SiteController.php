<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\Audit;
use App\Models\Setting;
use App\Models\SiteItem;
use App\Models\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SiteController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('website', [
            'notices' => SiteItem::where('type', 'notice')->latest()->get(),
            'events' => SiteItem::where('type', 'event')->orderBy('date')->get(),
            'welcome' => WebsiteContent::values(),
            'site' => [
                'phone' => Setting::val('site_phone', '+250 788 000 000'),
                'email' => Setting::val('site_email', 'info@apacope.rw'),
                'address' => Setting::val('site_address', 'Groupe Scolaire APACOPE, Nyarugenge, Kigali, Rwanda'),
                'communication_email' => Setting::val('communication_email', 'mucyobilly3@gmail.com'),
            ],
        ]);
    }

    public function content(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $photos = [
            'managing_director_photo',
            'headmaster_photo',
            'student_photo',
            'section_nursery_photo',
            'section_primary_photo',
            'section_olevel_photo',
            'section_tvet_photo',
        ];
        $textKeys = array_values(array_diff(WebsiteContent::contentKeys(), $photos));
        $rules = array_fill_keys($textKeys, ['nullable', 'string', 'max:1500']);

        foreach ([
            'hero_title_1', 'hero_title_2', 'hero_title_3', 'about_title',
            'section_nursery_title', 'section_primary_title', 'section_olevel_title', 'section_tvet_title',
        ] as $key) {
            $rules[$key] = ['required', 'string', 'max:250'];
        }

        foreach ($photos as $key) {
            $rules[$key] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
            $rules['remove_'.$key] = ['sometimes', 'boolean'];
        }

        $values = $request->validate($rules);

        foreach ($textKeys as $key) {
            WebsiteContent::updateOrCreate(['key' => $key], ['value' => $values[$key] ?? '']);
        }

        foreach ($photos as $key) {
            $existingPath = WebsiteContent::where('key', $key)->value('value');
            if ($request->boolean('remove_'.$key)) {
                WebsiteContent::updateOrCreate(['key' => $key], ['value' => '']);
                if ($existingPath) {
                    Storage::disk('public')->delete($existingPath);
                }

                continue;
            }

            if ($request->hasFile($key)) {
                $path = $request->file($key)->store('welcome', 'public');
                WebsiteContent::updateOrCreate(['key' => $key], ['value' => $path]);
                if ($existingPath && $existingPath !== $path) {
                    Storage::disk('public')->delete($existingPath);
                }
            }
        }

        Audit::log('Welcome page content updated');

        return back()->with('ok', 'Welcome page content saved.');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $values = $request->validate([
            'type' => ['required', 'in:notice,event'],
            'title' => ['required', 'max:150'],
            'body' => ['nullable', 'max:1000'],
            'date' => ['nullable', 'date', 'required_if:type,event'],
            'kind' => ['nullable', 'max:40'],
        ]);
        SiteItem::create($values);
        Audit::log('Welcome page '.$values['type'].' added', $values['title']);

        return back()->with('ok', 'Added to the welcome page.');
    }

    public function update(Request $request, SiteItem $item): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $values = $request->validate([
            'title' => ['required', 'max:150'],
            'body' => ['nullable', 'max:1000'],
            'date' => ['nullable', 'date'],
            'kind' => ['nullable', 'max:40'],
        ]);
        $item->update($values);
        Audit::log('Welcome page item updated', $item->title);

        return back()->with('ok', 'Updated.');
    }

    public function destroy(Request $request, SiteItem $item): RedirectResponse
    {
        $this->authorizeAdmin($request);
        Audit::log('Welcome page item deleted', $item->title);
        $item->delete();

        return back()->with('ok', 'Deleted from the welcome page.');
    }

    public function contact(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $values = $request->validate([
            'phone' => ['required', 'max:60'],
            'email' => ['required', 'email', 'max:100'],
            'address' => ['required', 'max:200'],
            'communication_email' => ['required', 'email', 'max:100'],
        ]);

        foreach (['phone', 'email', 'address'] as $key) {
            $value = $values[$key];
            Setting::updateOrCreate(['key' => 'site_'.$key], ['value' => $value]);
        }
        Setting::updateOrCreate(
            ['key' => 'communication_email'],
            ['value' => $values['communication_email']]
        );

        Audit::log('Contact details updated');

        return back()->with('ok', 'Contact details saved.');
    }

    public function sendMessage(Request $request): RedirectResponse|JsonResponse
    {
        $values = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'message' => ['required', 'string', 'max:1500'],
        ]);

        $gmailNeedsCredentials = config('mail.default') === 'smtp'
            && config('mail.mailers.smtp.host') === 'smtp.gmail.com'
            && (! filled(config('mail.mailers.smtp.username')) || ! filled(config('mail.mailers.smtp.password')));

        if (config('mail.default') === 'log' || $gmailNeedsCredentials) {
            $message = $gmailNeedsCredentials
                ? 'The school email service is not ready yet. Please call the school or try again later.'
                : 'Email is not available right now. Please try again later or call the school.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 503);
            }

            return back()->withErrors([
                'contact' => $message,
            ])->withInput();
        }

        $recipient = Setting::val('communication_email', 'mucyobilly3@gmail.com');

        try {
            Mail::to($recipient)->send(
                (new ContactMessageMail($values['name'], $values['email'], $values['message']))
                    ->replyTo($values['email'], $values['name'])
            );
        } catch (Throwable $exception) {
            Log::error('Could not send a website contact message.', [
                'recipient' => $recipient,
                'sender_email' => $values['email'],
                'exception' => $exception->getMessage(),
            ]);

            $message = 'Your message could not be sent right now. Please try again later or call the school.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 503);
            }

            return back()->withErrors([
                'contact' => $message,
            ])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Thank you. Your message has been sent to the school.',
            ]);
        }

        return redirect('/#contact')->with('sent', 'Thank you. Your message has been emailed to the school.');
    }
}
