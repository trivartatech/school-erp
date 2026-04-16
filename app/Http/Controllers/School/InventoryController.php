<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\AssetMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    // ── Asset Dashboard ───────────────────────────────────────────────────
    public function index(Request $request)
    {
        $schoolId = app('current_school_id');

        $query = Asset::where('school_id', $schoolId)
            ->with(['category', 'activeAssignment'])
            ->withCount('maintenanceLogs');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('asset_code', 'like', "%$s%")
                ->orWhere('serial_no', 'like', "%$s%"));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assets     = $query->latest()->paginate(25)->withQueryString();
        $categories = AssetCategory::where('school_id', $schoolId)->withCount('assets')->get();

        // Summary stats
        $stats = Asset::where('school_id', $schoolId)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(purchase_cost) as total_cost'))
            ->groupBy('status')
            ->pluck(null, 'status');

        $openMaintenance = AssetMaintenance::where('school_id', $schoolId)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        return Inertia::render('School/Inventory/Index', compact('assets', 'categories', 'stats', 'openMaintenance'));
    }

    // ── Store asset ───────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $schoolId = app('current_school_id');

        $validated = $request->validate([
            'category_id'       => 'required|exists:asset_categories,id',
            'name'              => 'required|string|max:200',
            'asset_code'        => 'nullable|string|max:50',
            'brand'             => 'nullable|string|max:100',
            'model_no'          => 'nullable|string|max:100',
            'serial_no'         => 'nullable|string|max:100',
            'purchase_date'     => 'nullable|date',
            'purchase_cost'     => 'nullable|numeric|min:0',
            'supplier'          => 'nullable|string|max:200',
            'warranty_until'    => 'nullable|string|max:20',
            'useful_life_years' => 'nullable|integer|min:1|max:50',
            'condition'         => 'in:excellent,good,fair,poor,condemned',
            'notes'             => 'nullable|string',
        ]);

        Asset::create(array_merge($validated, ['school_id' => $schoolId, 'status' => 'available']));

        return back()->with('success', 'Asset added to inventory.');
    }

    // ── Update asset ──────────────────────────────────────────────────────
    public function update(Request $request, Asset $asset)
    {
        abort_if($asset->school_id !== app('current_school_id'), 403);

        $validated = $request->validate([
            'name'              => 'required|string|max:200',
            'brand'             => 'nullable|string|max:100',
            'condition'         => 'in:excellent,good,fair,poor,condemned',
            'status'            => 'in:available,assigned,under_maintenance,disposed',
            'warranty_until'    => 'nullable|string|max:20',
            'notes'             => 'nullable|string',
        ]);

        $asset->update($validated);
        return back()->with('success', 'Asset updated.');
    }

    // ── Assign / Return ───────────────────────────────────────────────────
    public function assign(Request $request, Asset $asset)
    {
        abort_if($asset->school_id !== app('current_school_id'), 403);

        $validated = $request->validate([
            'assignee_type' => 'nullable|string|max:50',
            'assignee_id'   => 'nullable|integer',
            'location'      => 'required|string|max:200',
            'assigned_on'   => 'required|date',
            'notes'         => 'nullable|string',
        ]);

        DB::transaction(function () use ($asset, $validated) {
            // Close any existing open assignment
            AssetAssignment::where('asset_id', $asset->id)->whereNull('returned_on')
                ->update(['returned_on' => now()->toDateString()]);

            AssetAssignment::create(array_merge($validated, [
                'asset_id'    => $asset->id,
                'school_id'   => $asset->school_id,
                'assigned_by' => auth()->id(),
            ]));

            $asset->update(['status' => 'assigned']);
        });

        return back()->with('success', 'Asset assigned.');
    }

    public function returnAsset(Asset $asset)
    {
        abort_if($asset->school_id !== app('current_school_id'), 403);

        AssetAssignment::where('asset_id', $asset->id)->whereNull('returned_on')
            ->update(['returned_on' => now()->toDateString()]);

        $asset->update(['status' => 'available']);

        return back()->with('success', 'Asset returned to inventory.');
    }

    // ── Maintenance log ───────────────────────────────────────────────────
    public function maintenance(Request $request, Asset $asset)
    {
        abort_if($asset->school_id !== app('current_school_id'), 403);

        $validated = $request->validate([
            'issue_description' => 'required|string',
            'type'              => 'required|in:preventive,corrective,inspection',
            'reported_on'       => 'required|date',
        ]);

        AssetMaintenance::create(array_merge($validated, [
            'asset_id'    => $asset->id,
            'school_id'   => $asset->school_id,
            'reported_by' => auth()->id(),
            'status'      => 'open',
        ]));

        $asset->update(['status' => 'under_maintenance']);

        return back()->with('success', 'Maintenance request logged.');
    }

    public function resolveMaintenance(Request $request, AssetMaintenance $record)
    {
        abort_if($record->school_id !== app('current_school_id'), 403);

        $validated = $request->validate([
            'resolution_notes' => 'nullable|string',
            'cost'             => 'nullable|numeric|min:0',
            'vendor'           => 'nullable|string|max:200',
        ]);

        $record->update(array_merge($validated, [
            'status'      => 'resolved',
            'resolved_on' => now()->toDateString(),
        ]));

        // Set asset back to available if no other open maintenance
        $openCount = AssetMaintenance::where('asset_id', $record->asset_id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        if ($openCount === 0) {
            $record->asset->update(['status' => 'available']);
        }

        return back()->with('success', 'Maintenance resolved.');
    }

    // ── Category CRUD ─────────────────────────────────────────────────────
    public function storeCategory(Request $request)
    {
        $schoolId = app('current_school_id');
        $request->validate(['name' => 'required|string|max:100']);
        AssetCategory::create(['school_id' => $schoolId, 'name' => $request->name, 'description' => $request->description]);
        return back()->with('success', 'Category created.');
    }
}
