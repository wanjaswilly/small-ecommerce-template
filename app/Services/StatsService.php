<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteStat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class StatsService
{
    public function aggregatedStats() : array 
    {
        return  [
            'total_visits' => SiteStat::count(),
            'unique_visitors' => SiteStat::distinct('ip')->count('ip'),
            'page_views' => (int) (SiteStat::distinct('ip')->count('ip') / SiteStat::distinct('url')->count('url')),
            'device_stats' => SiteStat::select('device', DB::raw('count(*) as count'))
                ->groupBy('device')
                ->get(),
            'browser_stats' => SiteStat::select('browser', DB::raw('count(*) as count'))
                ->groupBy('browser')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
            'recent_visits' => SiteStat::latest()->limit(5)->get(),
            'platform_stats' => [
                'windows' => SiteStat::where('platform', 'Windows')->count(),
                'mac' => SiteStat::where('platform', 'Macintosh')->count(),
                'linux' => SiteStat::where('platform', 'Linux')->count(),
                'mobile' => SiteStat::whereIn('platform', ['iOS', 'Android'])->count(),
            ],
        ];
    }
}