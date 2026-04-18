<?php

namespace App\Http\Controllers\School\Houses;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HousePoint;
use Inertia\Inertia;

class HouseLeaderboardController extends Controller
{
    public function index()
    {
        $schoolId       = app('current_school_id');
        $academicYearId = app()->bound('current_academic_year_id') ? app('current_academic_year_id') : null;

        $categories = ['sports', 'academic', 'cultural', 'discipline', 'general'];

        $houses = House::where('school_id', $schoolId)
            ->with(['incharge:id,name'])
            ->withCount(['houseStudents as student_count' => function ($q) use ($academicYearId) {
                if ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId);
                }
            }])
            ->get()
            ->map(function ($house) use ($academicYearId, $categories) {
                $pointsQuery = $house->points()->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId));

                $house->total_points = (int) $pointsQuery->sum('points');

                $breakdown = [];
                foreach ($categories as $cat) {
                    $breakdown[$cat] = (int) $house->points()
                        ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
                        ->where('category', $cat)
                        ->sum('points');
                }
                $house->breakdown = $breakdown;

                return $house;
            })
            ->sortByDesc('total_points')
            ->values();

        return Inertia::render('School/Houses/Leaderboard', [
            'houses'     => $houses,
            'categories' => $categories,
        ]);
    }
}
