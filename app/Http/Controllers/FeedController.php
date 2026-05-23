<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    /**
     * Show public incident feed.
     */
    public function index(Request $request)
    {
        $query = Incident::with([
                'reporter',
                'department',
                'category',
                'media',
                'assignedTo'
            ])
            ->withCount(['comments', 'likes'])
            ->where('is_anonymous', false)
            ->whereIn('status', ['open', 'acknowledged', 'in_progress', 'escalated', 'resolved', 'closed'])
            ->latest();

        // Apply filters
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Cursor pagination
        $perPage = 10;
        $incidents = $query->cursorPaginate($perPage);

        if ($request->ajax() || $request->wantsJson()) {
            $html = '';
            foreach ($incidents as $incident) {
                $html .= view('guest.partials.incident-feed-card', compact('incident'))->render();
            }

            return response()->json([
                'success' => true,
                'html' => $html,
                'has_more' => $incidents->hasMorePages(),
                'next_cursor' => $incidents->nextCursor()?->encode(),
                'count' => $incidents->count(),
            ]);
        }

        $departments = \App\Models\Department::active()->ordered()->get();

        return view('guest.home', compact('incidents', 'departments'));
    }

    /**
     * Show incident details for public view.
     */
    public function showIncident(Incident $incident)
    {
        if ($incident->is_anonymous) {
            abort(404);
        }

        $incident->load([
            'reporter',
            'department',
            'category',
            'media',
            'assignedTo',
            'comments' => function ($query) {
                $query->with('user')->latest()->limit(50);
            }
        ]);
        $incident->loadCount(['comments', 'likes']);
        $incident->increment('views_count');

        // Check if current user liked this incident
        $isLiked = false;
        if (auth()->check()) {
            $isLiked = $incident->isLikedBy(auth()->user());
        }

        return view('guest.incident-public', compact('incident', 'isLiked'));
    }

    /**
     * Search incidents.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        $incidents = Incident::with(['reporter', 'department', 'category', 'media'])
            ->withCount(['comments', 'likes'])
            ->where('is_anonymous', false)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('incident_id', 'like', "%{$query}%")
                  ->orWhere('location', 'like', "%{$query}%");
            })
            ->latest()
            ->cursorPaginate(10);

        if ($request->ajax() || $request->wantsJson()) {
            $html = '';
            foreach ($incidents as $incident) {
                $html .= view('guest.partials.incident-feed-card', compact('incident'))->render();
            }

            return response()->json([
                'success' => true,
                'html' => $html,
                'has_more' => $incidents->hasMorePages(),
                'next_cursor' => $incidents->nextCursor()?->encode(),
            ]);
        }

        return view('guest.home', compact('incidents'));
    }

    /**
     * Toggle like on an incident (AJAX).
     */
    public function toggleLike(Request $request, Incident $incident)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to like incidents.',
                'redirect' => route('login')
            ], 401);
        }

        $user = auth()->user();

        if ($incident->isLikedBy($user)) {
            // Unlike
            $incident->likes()->detach($user->id);
            $liked = false;
        } else {
            // Like
            $incident->likes()->attach($user->id);
            $liked = true;
        }

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $incident->likes()->count()
        ]);
    }
}
