<?php

namespace App\Services;

use App\Models\Cuti;
use App\Models\KuotaCuti;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CutiService
{
  /**
   * Hitung jumlah hari kerja (exclude weekend)
   */
  public function countWorkDays(string $startDate, string $endDate): int
  {
    $start = Carbon::parse($startDate);
    $end   = Carbon::parse($endDate);
    $days  = 0;

    while ($start->lte($end)) {
      if ($start->isWeekday()) {
        $days++;
      }
      $start->addDay();
    }

    return $days;
  }

  /**
   * Ambil atau buat kuota cuti user untuk tahun ini
   */
  public function getOrCreateQuota(int $userId, string $year = null): KuotaCuti
  {
    $year = $year ?? now()->year;

    return KuotaCuti::firstOrCreate(
      ['user_id' => $userId, 'tahun' => $year],
      ['total_kuota' => 12, 'kuota_digunakan' => 0]
    );
  }

  /**
   * Validasi dan buat leave request
   */
  public function createCuti(User $user, array $data, $lampiran): Cuti
  {
    $totalDays = $this->countWorkDays($data['tanggal_mulai'], $data['tanggal_selesai']);

    // Ambil kuota
    $kuota = $this->getOrCreateQuota($user->id);

    if ($kuota->remaining_quota < $totalDays) {
      throw new \Exception(
        "Kuota cuti tidak mencukupi. Sisa kuota: {$kuota->remaining_quota} hari, dibutuhkan: {$totalDays} hari."
      );
    }

    // Cek overlap dengan request yang sudah approved/pending
    $overlap = Cuti::where('user_id', $user->id)
      ->whereIn('status', ['pending', 'approved'])
      ->where(function ($q) use ($data) {
        $q->whereBetween('tanggal_mulai', [$data['tanggal_mulai'], $data['tanggal_selesai']])
          ->orWhereBetween('tanggal_selesai', [$data['tanggal_mulai'], $data['tanggal_selesai']])
          ->orWhere(function ($q2) use ($data) {
            $q2->where('tanggal_mulai', '<=', $data['tanggal_mulai'])
              ->where('tanggal_selesai', '>=', $data['tanggal_selesai']);
          });
      })->exists();

    if ($overlap) {
      throw new \Exception('Terdapat pengajuan cuti yang bertabrakan dengan tanggal tersebut.');
    }

    return Cuti::create([
      'user_id'    => $user->id,
      'tanggal_mulai' => $data['tanggal_mulai'],
      'tanggal_selesai' => $data['tanggal_selesai'],
      'total_hari' => $totalDays,
      'alasan'     => $data['alasan'],
      'lampiran' => $lampiran,
      'status'     => 'pending',
    ]);
  }

  /**
   * Approve atau Reject cuti (Admin)
   */
  public function reviewCutiRequest(Cuti $cutiRequest, User $admin, string $action, ?string $note = null): Cuti
  {
    if ($cutiRequest->status !== 'pending') {
      throw new \Exception('Pengajuan ini sudah diproses sebelumnya.');
    }

    DB::transaction(function () use ($cutiRequest, $admin, $action, $note) {
      $cutiRequest->update([
        'status'      => $action,
        'reviewed_by' => $admin->id,
        'review_note' => $note,
        'reviewed_at' => now(),
      ]);

      // Jika approved, kurangi kuota
      if ($action === 'approved') {
        $kuota = $this->getOrCreateQuota($cutiRequest->user_id, $cutiRequest->tanggal_mulai->year);
        $kuota->increment('kuota_digunakan', $cutiRequest->total_hari);
      }
    });

    return $cutiRequest->fresh();
  }
}
