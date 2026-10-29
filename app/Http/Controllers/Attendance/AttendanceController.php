<?php

// app/Http/Controllers/Attendance/AttendanceController.php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    /**
     * Show "Mark My Attendance" page.
     */
    public function markPage(Request $request)
    {
        $user = $request->user();
        $today = now($user->airport->attendanceSettings()['timezone'])->toDateString();

        $todayAttendance = Attendance::forUser($user->id)
            ->forDate($today)
            ->first();

        $settings = $user->airport->attendanceSettings();

        return view('attendance.mark', compact('todayAttendance', 'settings'));
    }

    /**
     * Handle self-mark POST.
     */
    public function markSelf(Request $request)
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $user = $request->user();

        try {
            $attendance = $this->service->markSelf(
                $user,
                (float) $data['latitude'],
                (float) $data['longitude'],
                $user->airport->attendanceSettings()['timezone']
            );
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully at '.$attendance->marked_at->format('h:i A'),
                'attendance' => $attendance,
            ]);
        }

        return back()->with('success', 'Attendance marked successfully.');
    }

    /**
     * Show my attendance history.
     */
    public function myAttendance(Request $request)
    {
        $user = $request->user();

        $attendances = Attendance::forUser($user->id)
            ->with('markedBy')
            ->orderByDesc('attendance_date')
            ->paginate(30);

        $stats = [
            'present' => Attendance::forUser($user->id)->where('status', 'present')->count(),
            'absent' => Attendance::forUser($user->id)->where('status', 'absent')->count(),
            'late' => Attendance::forUser($user->id)->where('status', 'late')->count(),
        ];

        return view('attendance.my', compact('attendances', 'stats'));
    }
}
