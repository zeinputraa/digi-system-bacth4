<?php

namespace App\Http\Controllers;

use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Holiday;
use App\Models\IncidentReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminConfigController extends Controller
{
    /**
     * Display admin configuration panel.
     */
    public function index(Request $request): View
    {
        $query = User::with('role');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $roleName = $request->input('role');
            $query->whereHas('role', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        $users = $query->paginate(12)->withQueryString();
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Display a specific user's detail for role management.
     */
    public function show(string $id): View
    {
        $user = User::with('role')->findOrFail($id);
        $roles = Role::all();

        $totalPinjam = Borrowing::where('user_id', $user->id)->count();
        $berjalan = Borrowing::where('user_id', $user->id)
            ->whereIn('status', [StatusBorrowing::Disetujui->value, StatusBorrowing::Berjalan->value])
            ->count();

        $terlambat = BorrowingDetail::whereHas('borrowing', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('status', StatusBorrowingDetail::Terlambat->value)->count();

        $insiden = IncidentReport::where('reported_by', $user->id)->count();

        $borrowings = Borrowing::where('user_id', $user->id)
            ->with(['details.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_pinjam' => $totalPinjam,
            'berjalan' => $berjalan,
            'terlambat' => $terlambat,
            'insiden' => $insiden,
        ];

        return view('admin.users.show', compact('user', 'roles', 'stats', 'borrowings'));
    }

    /**
     * Display holidays management page.
     */
    public function holidaysIndex(): View
    {
        $holidays = Holiday::orderBy('tanggal', 'asc')->get();

        return view('admin.holidays.index', compact('holidays'));
    }

    /**
     * Store a new holiday manually.
     */
    public function storeHoliday(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => 'required|date|unique:holidays,tanggal',
            'keterangan' => 'required|string|max:200',
            'jenis' => 'required|string|max:50',
        ]);

        Holiday::create([
            'tanggal' => $validated['tanggal'],
            'keterangan' => $validated['keterangan'],
            'jenis' => $validated['jenis'],
            'sumber' => 'manual',
        ]);

        return redirect()->back()->with('success', 'Hari libur baru berhasil ditambahkan.');
    }

    /**
     * Delete a holiday manually.
     */
    public function destroyHoliday(string $id): RedirectResponse
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return redirect()->back()->with('success', 'Hari libur berhasil dihapus.');
    }

    /**
     * Display Sanctum API tokens management page.
     */
    public function tokensIndex(): View
    {
        $tokens = DB::table('personal_access_tokens')
            ->select('personal_access_tokens.id', 'personal_access_tokens.name', 'personal_access_tokens.abilities', 'personal_access_tokens.last_used_at', 'personal_access_tokens.created_at', 'users.name as user_name')
            ->join('users', 'personal_access_tokens.tokenable_id', '=', 'users.id')
            ->get();

        return view('admin.tokens.index', compact('tokens'));
    }

    /**
     * Update user role.
     */
    public function updateUserRole(Request $request, string $userId): RedirectResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat mengubah role Anda sendiri.');
        }

        $user->update([
            'role_id' => $request->role_id,
        ]);

        return redirect()->route('admin.users.index')->with('success', "Role user '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Sync national holidays manually via Artisan trigger.
     */
    public function syncHolidays(Request $request): RedirectResponse
    {
        Artisan::call('holidays:sync', [
            'year' => $request->input('year') ?? date('Y'),
        ]);

        return redirect()->back()->with('success', 'Sinkronisasi data hari libur nasional berhasil dijalankan!');
    }

    /**
     * Generate Laravel Sanctum Personal Access Token.
     */
    public function generateToken(Request $request): RedirectResponse
    {
        $request->validate([
            'token_name' => 'required|string|max:100',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'string|in:read,write,admin',
        ]);

        $user = auth()->user();

        // Create token with specific abilities
        $token = $user->createToken($request->token_name, $request->abilities);

        return redirect()->back()->with('success', "Token API baru berhasil digenerate: '{$token->plainTextToken}'. Simpan token ini baik-baik karena hanya akan ditampilkan sekali.");
    }

    /**
     * Revoke Sanctum Token.
     */
    public function revokeToken(string $tokenId): RedirectResponse
    {
        DB::table('personal_access_tokens')->where('id', $tokenId)->delete();

        return redirect()->back()->with('success', 'Token API berhasil dicabut.');
    }
}
