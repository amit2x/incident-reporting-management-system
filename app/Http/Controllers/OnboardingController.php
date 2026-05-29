<?php

// app/Http/Controllers/OnboardingController.php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\EscalationMatrix;
use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding form.
     */
    public function create()
    {
        return view('onboarding.create');
    }

    /**
     * Process the onboarding submission.
     */
    public function submit(Request $request)
    {
        $request->validate([
            'department_name' => 'required|string|max:100',
            'department_code' => 'required|string|max:20|unique:departments,code',
            'department_description' => 'nullable|string',
            'department_email' => 'nullable|email',
            'department_phone' => 'nullable|string',
            'department_color' => 'nullable|string|max:7',
            'department_location' => 'nullable|string',
            'users' => 'required|array|min:2',
            'users.*.name' => 'required|string|max:255',
            'users.*.email' => 'required|email|unique:users,email',
            'users.*.employee_id' => 'required|string|unique:users,employee_id',
            'users.*.designation' => 'nullable|string',
            'users.*.role' => 'required|in:hod,supervisor,staff',
            'categories' => 'nullable|array',
            'escalation' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // 1. CREATE DEPARTMENT
            $department = Department::create([
                'name' => $request->department_name,
                'code' => strtoupper($request->department_code),
                'description' => $request->department_description,
                'color' => $request->department_color ?? '#3B82F6',
                'icon' => 'fas fa-building',
                'email' => $request->department_email,
                'phone' => $request->department_phone,
                'location' => $request->department_location,
                'is_active' => true,
                'sort_order' => Department::count() + 1,
            ]);

            // 2. CREATE USERS
            $createdUsers = [];
            foreach ($request->users as $userData) {
                $username = Str::slug($userData['name'].'.'.$userData['employee_id'], '.');

                $user = User::create([
                    'name' => $userData['name'],
                    'username' => $username,
                    'email' => $userData['email'],
                    'password' => Hash::make('Welcome@123'), // Default password
                    'employee_id' => $userData['employee_id'],
                    'designation' => $userData['designation'] ?? null,
                    'department_id' => $department->id,
                    'status' => 'active',
                ]);

                $user->assignRole($userData['role']);
                $createdUsers[] = $user;
            }

            // 3. CREATE/ASSIGN CATEGORIES
            $categoryIds = [];
            if ($request->has('categories')) {
                foreach ($request->categories as $catName) {
                    $category = IncidentCategory::firstOrCreate(
                        ['name' => $catName],
                        [
                            'slug' => Str::slug($catName),
                            'color' => '#6B7280',
                            'icon' => 'fas fa-tag',
                            'default_priority' => 2,
                            'sla_minutes' => 120,
                            'is_active' => true,
                        ]
                    );
                    $categoryIds[] = $category->id;
                }
            }

            // 4. CREATE DEFAULT ESCALATION MATRIX
            if ($request->has('escalation')) {
                foreach ($request->escalation as $escData) {
                    if (! empty($escData['escalate_to_name'])) {
                        // Try to find user by name or email
                        $escalateUser = User::where('name', 'like', '%'.$escData['escalate_to_name'].'%')
                            ->orWhere('email', $escData['escalate_to_name'])
                            ->first();

                        if ($escalateUser) {
                            EscalationMatrix::create([
                                'department_id' => $department->id,
                                'category_id' => null, // Default for all categories
                                'level' => $escData['level'],
                                'timeout_minutes' => $escData['timeout_minutes'] ?? 30,
                                'escalate_to_user_id' => $escalateUser->id,
                                'escalate_to_department_id' => $department->id,
                                'notify_via_email' => true,
                                'notify_via_push' => true,
                                'is_active' => true,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            // Send confirmation email to admin
            $adminEmail = env('ADMIN_EMAIL', 'admin@irmsystem.com');
            Mail::send('emails.onboarding-confirmation', [
                'department' => $department,
                'users' => $createdUsers,
                'categories' => $categoryIds,
            ], function ($message) use ($adminEmail, $department) {
                $message->to($adminEmail)
                    ->subject('[IRMS] New Department Onboarding: '.$department->name);
            });

            Log::info('Department onboarded successfully', [
                'department' => $department->name,
                'users_count' => count($createdUsers),
            ]);

            return redirect()->back()->with('success',
                'Onboarding request submitted successfully! Department "'.$department->name.'" has been created with '.count($createdUsers).' users. Default password for all users: Welcome@123');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Onboarding failed: '.$e->getMessage());

            return back()->with('error', 'Onboarding failed: '.$e->getMessage())->withInput();
        }
    }
}
