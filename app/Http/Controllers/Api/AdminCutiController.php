<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewCutiRequest;
use App\Models\Cuti;
use App\Models\User;
use App\Services\CutiService;
use Illuminate\Http\Request;

class AdminCutiController extends Controller
{
    public function __construct(private CutiService $service) {}

    public function index(Request $request)
    {
        $query = Cuti::with(['user:id,name,email', 'reviewer:id,name'])
            ->latest();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json($query->paginate(15));
    }

    public function show(Cuti $cuti)
    {
        return response()->json($cuti->load(['user:id,name,email', 'reviewer:id,name']));
    }

    public function review(ReviewCutiRequest $request, Cuti $cuti)
    {
        try {
            $updated = $this->service->reviewCutiRequest(
                $cuti,
                $request->user(),
                $request->action,
                $request->note
            );

            return response()->json([
                'message' => 'Pengajuan berhasil di-' . $request->action . '.',
                'data'    => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function karyawan()
    {
        $karyawan = User::role('karyawan')
            ->with(['kuota_cuti'])
            ->get()
            ->map(fn($u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'email'           => $u->email,
                'remaining_quota' => $u->kuota_cuti?->remaining_quota ?? 12,
                'kuota_digunakan'      => $u->kuota_cuti?->kuota_digunakan ?? 0,
            ]);

        return response()->json($karyawan);
    }
}
