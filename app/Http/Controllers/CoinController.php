<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoinController extends Controller
{
    private function state(User $u): array
    {
        return [
            'coins' => (int) $u->coins,
            'daily_claimed' => $u->daily_claimed_at ? $u->daily_claimed_at->isToday() : false,
        ];
    }

    public function show(Request $r)
    {
        return $this->state($r->user());
    }

    /** POST /coins/claim-daily : +10 koin, sekali sehari */
    public function claimDaily(Request $r)
    {
        return DB::transaction(function () use ($r) {
            $u = User::lockForUpdate()->findOrFail($r->user()->id);

            if ($u->daily_claimed_at && $u->daily_claimed_at->isToday()) {
                return response()->json(['message' => 'Hadiah harian sudah diambil'] + $this->state($u), 409);
            }

            $u->coins += 10;
            $u->daily_claimed_at = now()->toDateString();
            $u->save();

            return $this->state($u) + ['added' => 10];
        });
    }

    /** POST /coins/purchase {amount} : pembelian simulasi (demo, tanpa pembayaran nyata) */
    public function purchase(Request $r)
    {
        $data = $r->validate(['amount' => 'required|integer|min:1|max:10000']);

        $u = User::lockForUpdate()->findOrFail($r->user()->id);
        $u->coins += $data['amount'];
        $u->save();

        return $this->state($u) + ['added' => $data['amount']];
    }

    /** POST /coins/spend {amount} */
    public function spend(Request $r)
    {
        $data = $r->validate(['amount' => 'required|integer|min:1']);

        return DB::transaction(function () use ($r, $data) {
            $u = User::lockForUpdate()->findOrFail($r->user()->id);

            if ($u->coins < $data['amount']) {
                return response()->json(['message' => 'Koin tidak cukup'] + $this->state($u), 422);
            }

            $u->coins -= $data['amount'];
            $u->save();

            return $this->state($u);
        });
    }
}
