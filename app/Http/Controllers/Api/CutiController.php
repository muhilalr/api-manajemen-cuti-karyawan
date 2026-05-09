<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CutiRequest;
use App\Models\Cuti;
use App\Services\CutiService;
use Illuminate\Http\Request;

class CutiController extends Controller
{
    public function __construct(private CutiService $service) {}

    public function index(Request $request)
    {
        $cuti = Cuti::where('user_id', $request->user()->id)
            ->with('reviewer:id,name')
            ->latest()
            ->paginate(10);

        return response()->json($cuti);
    }

    public function store(CutiRequest $request)
    {
        try {
            // Upload lampiran
            $path = $request->file('lampiran')->store('lampiran', 'public');

            $leave = $this->service->createCuti(
                $request->user(),
                $request->only('tanggal_mulai', 'tanggal_selesai', 'alasan'),
                $path
            );

            return response()->json([
                'message' => 'Pengajuan cuti berhasil dikirim.',
                'data'    => $leave,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Request $request, Cuti $cuti)
    {
        // Pastikan hanya milik sendiri
        if ($cuti->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($cuti->load('reviewer:id,name'));
    }

    public function kuota(Request $request)
    {
        $kuota = $this->service->getOrCreateQuota($request->user()->id);

        return response()->json([
            'tahun'            => $kuota->tahun,
            'total_kuota'     => $kuota->total_kuota,
            'kuota_digunakan'      => $kuota->kuota_digunakan,
            'remaining_quota' => $kuota->remaining_quota,
        ]);
    }
}
