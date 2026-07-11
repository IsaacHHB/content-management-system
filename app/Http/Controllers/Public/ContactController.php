<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactSubmissionReceived;
use App\Models\ContactSubmission;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:50'], 'subject' => ['required', 'string', 'max:255'], 'message' => ['required', 'string', 'max:10000'], 'website' => ['prohibited'], 'form_started_at' => ['required', 'integer']]);
        abort_if((int) now()->timestamp - (int) $data['form_started_at'] < 3, 422, 'The form was submitted too quickly.');
        unset($data['website'],$data['form_started_at']);
        $submission = ContactSubmission::create([...$data, 'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'))]);
        $recipient = Setting::where('key', 'contact_email')->first()?->value;
        if (is_string($recipient) && $recipient !== '') {
            Mail::to($recipient)->queue(new ContactSubmissionReceived($submission));
        }

        return response()->json(['message' => 'Thank you. Your message has been received.'], 201);
    }
}
