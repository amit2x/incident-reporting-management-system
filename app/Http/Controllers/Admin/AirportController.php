<?php

// app/Http/Controllers/Admin/AirportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class AirportController extends Controller
{
    /**
     * Display a listing of airports.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $airports = Airport::query()
            ->when($search, function ($query, $search) {
                return $query->search($search);
            })
            ->when($status !== null, function ($query) use ($status) {
                return $query->where('is_active', $status === 'active');
            })
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return view('admin.airports.index', compact('airports'));
    }

    /**
     * Show the form for creating a new airport.
     */
    public function create()
    {
        return view('admin.airports.create');
    }

    /**
     * Store a newly created airport.
     */
    public function store(Request $request)
    {
        $validated = $this->validateAirport($request);

        $airport = Airport::create([
            ...$validated,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'settings' => $validated['settings'] ?? [
                'incident_id_prefix' => $validated['code'],
                'default_sla_hours' => 24,
                'allow_anonymous_reporting' => true,
                'timezone' => $validated['timezone'] ?? 'Asia/Kolkata',
            ],
        ]);

        return redirect()
            ->route('admin.airports.index')
            ->with('success', "Airport '{$airport->name}' created successfully.");
    }

    /**
     * Display the specified airport.
     */
    public function show(Airport $airport)
    {
        $statistics = $airport->getStatistics();

        // Get recent incidents for this airport
        $recentIncidents = $airport->incidents()
            ->with(['category', 'reporter'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.airports.show', compact('airport', 'statistics', 'recentIncidents'));
    }

    /**
     * Show the form for editing the specified airport.
     */
    public function edit(Airport $airport)
    {
        return view('admin.airports.edit', compact('airport'));
    }

    /**
     * Update the specified airport.
     */
    public function update(Request $request, Airport $airport)
    {
        $validated = $this->validateAirport($request, $airport);

        $airport->update([
            ...$validated,
            'updated_by' => auth()->id(),
            'settings' => array_merge(
                $airport->settings ?? [],
                $validated['settings'] ?? []
            ),
        ]);

        return redirect()
            ->route('admin.airports.index')
            ->with('success', "Airport '{$airport->name}' updated successfully.");
    }

    /**
     * Remove the specified airport.
     */
    public function destroy(Airport $airport)
    {
        // Check if airport has any related data
        if ($airport->users()->count() > 0) {
            return redirect()
                ->route('admin.airports.index')
                ->with('error', "Cannot delete airport '{$airport->name}' because it has associated users.");
        }

        if ($airport->incidents()->count() > 0) {
            return redirect()
                ->route('admin.airports.index')
                ->with('error', "Cannot delete airport '{$airport->name}' because it has associated incidents.");
        }

        $airport->delete();

        return redirect()
            ->route('admin.airports.index')
            ->with('success', "Airport '{$airport->name}' deleted successfully.");
    }

    /**
     * Toggle airport active status.
     */
    public function toggleStatus(Airport $airport)
    {
        if ($airport->is_active) {
            $airport->deactivate();
            $message = "Airport '{$airport->name}' deactivated successfully.";
        } else {
            $airport->activate();
            $message = "Airport '{$airport->name}' activated successfully.";
        }

        return redirect()
            ->route('admin.airports.index')
            ->with('success', $message);
    }

    /**
     * Display airport statistics.
     */
    public function statistics(Airport $airport)
    {
        $statistics = $airport->getStatistics();

        // Additional statistics
        $statistics['departments'] = $airport->departments()->count();
        $statistics['categories'] = $airport->categories()->count();
        $statistics['open_incidents'] = $airport->incidents()->where('status', 'open')->count();
        $statistics['in_progress_incidents'] = $airport->incidents()->where('status', 'in_progress')->count();
        $statistics['resolved_incidents'] = $airport->incidents()->where('status', 'resolved')->count();
        $statistics['closed_incidents'] = $airport->incidents()->where('status', 'closed')->count();

        // Severity distribution
        $severityDistribution = $airport->incidents()
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        $statistics['severity_distribution'] = $severityDistribution;

        return response()->json([
            'airport' => $airport->only(['id', 'code', 'name']),
            'statistics' => $statistics,
        ]);
    }

    /**
     * Validate airport data.
     */
       protected function validateAirport(Request $request, ?Airport $airport = null)
    {
        $rules = [
            'code' => [
                'required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('airports', 'code')->ignore($airport),
            ],
            'icao_code' => [
                'nullable', 'string', 'max:4', 'regex:/^[A-Za-z]+$/',
                Rule::unique('airports', 'icao_code')->ignore($airport),
            ],
            'iata_code' => [
                'nullable', 'string', 'max:3', 'regex:/^[A-Za-z]+$/',
                Rule::unique('airports', 'iata_code')->ignore($airport),
            ],
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'timezone' => 'required|string|max:50',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'contact_email' => 'nullable|email|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'primary_color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',

            // Attendance Settings Validation
            'settings.attendance.allow_self_mark' => 'nullable|boolean',
            'settings.attendance.location_check_required' => 'nullable|boolean',
            'settings.attendance.geofence_lat' => 'nullable|numeric|between:-90,90',
            'settings.attendance.geofence_lng' => 'nullable|numeric|between:-180,180',
            'settings.attendance.geofence_radius_meters' => 'nullable|integer|min:10|max:5000',
            'settings.attendance.self_mark_start_time' => 'nullable|date_format:H:i',
            'settings.attendance.self_mark_end_time' => 'nullable|date_format:H:i',
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            if (empty($request->icao_code) && empty($request->iata_code)) {
                $validator->errors()->add('icao_code', 'At least one of ICAO code or IATA code is required.');
                $validator->errors()->add('iata_code', 'At least one of ICAO code or IATA code is required.');
            }
            
            // Ensure end time is after start time
            if ($request->filled('settings.attendance.self_mark_start_time') && 
                $request->filled('settings.attendance.self_mark_end_time')) {
                $start = strtotime($request->input('settings.attendance.self_mark_start_time'));
                $end = strtotime($request->input('settings.attendance.self_mark_end_time'));
                if ($end <= $start) {
                    $validator->errors()->add('settings.attendance.self_mark_end_time', 'End time must be after start time.');
                }
            }
        });

        $validated = $validator->validate();

        // Ensure settings array is formatted correctly for JSON storage
        if (isset($validated['settings'])) {
            $currentSettings = $airport->settings ?? [];
            
            // Merge deeply to avoid overwriting other settings like 'incident_id_prefix'
            $validated['settings'] = array_merge($currentSettings, $validated['settings']);
            
            // Cast booleans explicitly
            if (isset($validated['settings']['attendance'])) {
                $validated['settings']['attendance']['allow_self_mark'] = (bool) $request->input('settings.attendance.allow_self_mark', false);
                $validated['settings']['attendance']['location_check_required'] = (bool) $request->input('settings.attendance.location_check_required', false);
            }
        }

        return $validated;
    }
}
