<?php

namespace App\Http\Controllers\School\Houses;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HousePoint;
use Illuminate\Http\Request;

class HousePointController extends Controller
{
    public function store(Request $request, House $house)
    {
        abort_if($house->school_id !== app('current_school_id'), 403);

        $academicYearId = app()->bound('current_academic_year_id') ? app('current_academic_year_id') : null;
        abort_if(!$academicYearId, 422, 'No active academic year found.');

        $validated = $request->validate([
            'category'    => 'required|in:sports,academic,cultural,discipline,general',
            'points'      => 'required|integer|not_in:0|between:-999,999',
            'description' => 'required|string|max:255',
        ]);

        HousePoint::create([
            'school_id'       => app('current_school_id'),
            'house_id'        => $house->id,
            'academic_year_id' => $academicYearId,
            'category'        => $validated['category'],
            'points'          => $validated['points'],
            'description'     => $validated['description'],
            'awarded_by'      => auth()->id(),
        ]);

        $label = $validated['points'] > 0 ? 'awarded' : 'deducted';
        return back()->with('success', abs($validated['points']) . ' point(s) ' . $label . '.');
    }

    public function destroy(House $house, HousePoint $point)
    {
        abort_if($house->school_id !== app('current_school_id'), 403);
        abort_if($point->house_id !== $house->id, 403);

        $point->delete();

        return back()->with('success', 'Points entry removed.');
    }
}
