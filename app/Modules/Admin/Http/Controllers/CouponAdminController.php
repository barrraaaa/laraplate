<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Billing\Models\Coupon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Coupon management (PRD 5.5 P1) — the blueprint's coupon screen with
 * codes, discount rules, limits and the redemption counter.
 */
class CouponAdminController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        return Inertia::render('admin/Coupons', [
            'coupons' => Coupon::query()
                ->withCount('redeemedBy')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Coupon $coupon) => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'description' => $coupon->description,
                    'summary' => $coupon->summary(),
                    'percentOff' => $coupon->percent_off,
                    'amountOff' => $coupon->amount_off,
                    'stripeCouponId' => $coupon->stripe_coupon_id,
                    'maxRedemptions' => $coupon->max_redemptions,
                    'redemptions' => $coupon->redeemed_by_count,
                    'expiresAt' => $coupon->expires_at?->toIso8601String(),
                    'active' => $coupon->active,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:60', 'unique:coupons,code'],
            'description' => ['nullable', 'string', 'max:255'],
            'percent_off' => ['nullable', 'integer', 'between:1,100'],
            'amount_off' => ['nullable', 'integer', 'min:100'],
            'stripe_coupon_id' => ['nullable', 'string', 'max:255'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $hasPercent = isset($data['percent_off']);
        $hasAmount = isset($data['amount_off']);

        if ($hasPercent === $hasAmount) {
            throw ValidationException::withMessages([
                'percent_off' => 'Set exactly one of percent off or amount off.',
            ]);
        }

        Coupon::query()->create([
            'code' => $data['code'] ?? strtoupper(Str::random(8)),
            'description' => $data['description'] ?? null,
            'percent_off' => $data['percent_off'] ?? null,
            'amount_off' => $data['amount_off'] ?? null,
            'stripe_coupon_id' => $data['stripe_coupon_id'] ?? null,
            'max_redemptions' => $data['max_redemptions'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'active' => true,
        ]);

        return back()->with('status', 'coupon-created');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'stripe_coupon_id' => ['nullable', 'string', 'max:255'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $coupon->update($data);

        return back()->with('status', 'coupon-updated');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return back()->with('status', 'coupon-deleted');
    }
}
