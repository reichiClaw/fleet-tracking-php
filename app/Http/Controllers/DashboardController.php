<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Vehicle;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'counts' => Vehicle::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'vehiclesTotal' => Vehicle::count(),
            'activeLoans' => Loan::with('vehicle')->where('status', Loan::STATUS_ACTIVE)->latest('loaned_at')->limit(10)->get(),
            'overdueLoans' => Loan::with('vehicle')->where('status', Loan::STATUS_ACTIVE)->where('planned_return_at', '<', now())->get(),
        ]);
    }
}
