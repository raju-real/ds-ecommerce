<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\LocationRepository;

class LocationController extends Controller
{
    public function thanas(int $districtId, LocationRepository $locations)
    {
        $d = $locations->district($districtId) ?? abort(404);

        return response()->json([
            'delivery_charge' => $d->delivery_charge,
            'thanas' => $locations->thanas($districtId)->map(fn ($t) => ['id' => $t->id, 'text' => $t->name])->values(),
        ])->setPublic()->setMaxAge(3600);
    }
}
