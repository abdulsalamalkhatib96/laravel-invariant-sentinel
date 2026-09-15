<?php

namespace Evolvex\InvariantSentinel\Http;

use Evolvex\InvariantSentinel\Models\Incident;
use Evolvex\InvariantSentinel\Models\InvariantState;
use Evolvex\InvariantSentinel\Models\Observation;
use Illuminate\Routing\Controller;

final class DashboardController extends Controller
{
    public function index()
    {
        $summary = InvariantState::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $incidents = Incident::query()->whereIn('status', ['open','acknowledged','resolving'])->latest('opened_at')->limit(50)->get();
        $recent = Observation::query()->latest('created_at')->limit(50)->get();
        return view('sentinel::dashboard', compact('summary', 'incidents', 'recent'));
    }
}
