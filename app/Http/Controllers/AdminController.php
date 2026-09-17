<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AmortizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * GET /admin/users — Staff management table
     */
    public function users()
    {
        $staff = User::orderByRaw("FIELD(role, 'admin', 'manager', 'agent')")
            ->orderBy('name')
            ->get();

        $roleCounts = User::selectRaw('role, COUNT(*) as cnt')
            ->groupBy('role')
            ->pluck('cnt', 'role');

        return view('admin.users', compact('staff', 'roleCounts'));
    }

    /**
     * POST /admin/users — Create new staff account
     */
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'role'        => ['required', Rule::in(['admin', 'manager', 'agent'])],
            'branch_name' => 'nullable|string|max:100',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()
            ->route('admin.users')
            ->with('success', "Staff account for {$validated['name']} ({$validated['role']}) created successfully!");
    }

    /**
     * PUT /admin/users/{id} — Update staff account
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'role'        => ['required', Rule::in(['admin', 'manager', 'agent'])],
            'branch_name' => 'nullable|string|max:100',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6']);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return redirect()
            ->route('admin.users')
            ->with('success', "Staff account for {$validated['name']} updated successfully!");
    }

    /**
     * DELETE /admin/users/{id} — Delete staff account
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users')
            ->with('success', "Staff account for {$name} deleted successfully!");
    }

    /**
     * GET /admin/config — System configuration (RBI thresholds)
     */
    public function config()
    {
        $params = [
            [
                'label'       => 'Max Annual Household Income',
                'value'       => '₹' . number_format(AmortizationService::RBI_INCOME_CAP, 0),
                'source'      => 'RBI Microfinance Directions 2022',
                'description' => 'Maximum annual household income for microfinance eligibility.',
                'icon'        => '🏠',
            ],
            [
                'label'       => 'FOIR Cap (%)',
                'value'       => AmortizationService::RBI_FOIR_MAX_PCT . '%',
                'source'      => 'RBI Microfinance Directions 2022',
                'description' => 'Maximum 50% of monthly household income towards loan repayment obligations.',
                'icon'        => '📊',
            ],
            [
                'label'       => 'Standard Bounce Penalty',
                'value'       => '₹' . number_format(AmortizationService::PENAL_CHARGE_PER_BOUNCE, 0),
                'source'      => 'RBI Fair Lending Practice (Penal Charges)',
                'description' => 'Per-bounce penal charge. Not compounded, not added to principal.',
                'icon'        => '💰',
            ],
            [
                'label'       => 'Penal GST Rate',
                'value'       => (AmortizationService::PENAL_GST_RATE * 100) . '%',
                'source'      => 'CGST + SGST',
                'description' => '18% GST applicable on penal charges.',
                'icon'        => '🧾',
            ],
            [
                'label'       => 'Recovery Contact Hours',
                'value'       => '08:00 AM – 07:00 PM',
                'source'      => 'RBI Fair Practices Code',
                'description' => 'Borrower contact strictly permitted only within these hours.',
                'icon'        => '🕐',
            ],
            [
                'label'       => 'Prepayment Penalty',
                'value'       => '₹0 (Zero)',
                'source'      => 'RBI Microfinance Directions 2022',
                'description' => 'Zero prepayment penalties on all microfinance loans.',
                'icon'        => '🚫',
            ],
        ];

        return view('admin.config', compact('params'));
    }
}
