<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Show the contact form.
     */
    public function showForm()
    {
        return view('contact.index');
    }

    /**
     * Submit the contact form and send email.
     */
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'subject' => 'required|string|max:200',
            'category' => 'required|in:account,incident,technical,access,other',
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        try {
            // Store attachment if any
            $attachmentPath = null;
            $attachmentOriginalName = null;
            $attachmentMime = null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentPath = $file->store('contact-attachments', 'local');
                $attachmentOriginalName = $file->getClientOriginalName();
                $attachmentMime = $file->getMimeType();
            }

            // Prepare email data
            $emailData = [
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'category' => $request->category,
                'userMessage' => $request->message,
                'isAuthenticated' => auth()->check(),
                'user' => auth()->user(),
            ];

            $adminEmail = config('app.email');

            // Log attempt
            Log::info('Attempting to send contact email', [
                'to' => $adminEmail,
                'from' => $request->email,
                'subject' => $request->subject,
            ]);

            // Send email using Mailable class
            Mail::to($adminEmail)->send(
                new ContactFormMail(
                    $emailData,
                    $attachmentPath,
                    $attachmentOriginalName,
                    $attachmentMime
                )
            );

            // Log success (if we reach here, mail was sent without exceptions)
            Log::info('Contact email sent successfully', [
                'to' => $adminEmail,
                'from' => $request->email,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Your message has been sent successfully! We will get back to you soon.',
                ]);
            }

            return back()->with('success', 'Your message has been sent successfully! We will get back to you soon.');

        } catch (\Exception $e) {
            Log::error('Contact form submission failed: '.$e->getMessage(), [
                'name' => $request->name,
                'email' => $request->email,
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send message. Please try again later.',
                ], 500);
            }

            return back()->with('error', 'Failed to send message. Please try again later.')->withInput();
        }
    }
}
