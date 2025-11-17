<?php
declare(strict_types=1);

namespace App\Modules\Team\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TeamController manages member listings and invitations.
 */
class TeamController extends Controller
{
    /**
     * Display the team roster.
     */
    public function index(): Response
    {
        abort_unless(Auth::user()->role === 'admin', 403, 'Only administrators can view team management.');
        
        $members = User::orderBy('name')->get()->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'lastActiveAt' => $user->last_active_at?->toIso8601String(),
            ];
        });

        $stats = [
            'total' => $members->count(),
            'admins' => $members->where('role', 'admin')->count(),
            'active' => $members->where('status', 'active')->count(),
            'pending' => $members->where('status', 'pending')->count(),
        ];

        $currentUser = Auth::user();
        
        return Inertia::render('Team/Pages/Index', [
            'members' => $members,
            'stats' => $stats,
            'currentUser' => [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
                'role' => $currentUser->role,
            ],
        ]);
    }

    /**
     * Invite a new member by creating a pending account.
     */
    public function invite(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->role === 'admin', 403, 'Only administrators can invite team members.');
        
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'in:member,manager,admin'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $password = Str::random(12);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'role' => $data['role'],
            'status' => 'pending',
        ]);

        Mail::raw(
            ($data['message'] ?? 'You have been invited to DashPilot.').
            "\n\nTemporary password: {$password}",
            function ($mail) use ($user): void {
                $mail->to($user->email)->subject('DashPilot Invitation');
            }
        );

        return back()->with('success', 'Invitation sent.');
    }

    /**
     * Approve a pending team member.
     */
    public function approve(User $user): RedirectResponse
    {
        abort_unless(Auth::user()->role === 'admin', 403, 'Only administrators can approve team members.');
        
        abort_if($user->status !== 'pending', 400, 'User is not pending approval.');

        $user->update([
            'status' => 'active',
        ]);

        // Send notification email
        Mail::raw(
            "Your DashPilot account has been approved. You can now log in and access the dashboard.\n\n" .
            "Login: " . route('login', absolute: false),
            function ($mail) use ($user): void {
                $mail->to($user->email)->subject('DashPilot Account Approved');
            }
        );

        return back()->with('success', 'Member approved successfully.');
    }

    /**
     * Remove a team member.
     */
    public function destroy(User $user): RedirectResponse
    {
        abort_unless(Auth::user()->role === 'admin', 403, 'Only administrators can remove team members.');
        
        abort_if($user->id === Auth::id(), 403, 'You cannot remove yourself.');

        $user->delete();

        return back()->with('success', 'Member removed.');
    }
}

