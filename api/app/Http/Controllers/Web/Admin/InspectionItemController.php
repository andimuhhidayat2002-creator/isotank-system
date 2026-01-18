<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\InspectionItem;
use Illuminate\Http\Request;

class InspectionItemController extends Controller
{
    /**
     * Display a listing of inspection items
     */
    public function index()
    {
        $items = InspectionItem::orderBy('order')->orderBy('label')->get();
        
        $categories = [
            'external' => 'B. General Condition',
            'valve' => 'C. Valve & Piping',
            'measurement' => 'D. IBOX & E. Instruments',
            'vacuum' => 'F. Vacuum System', // Added missing specific key if needed, though 'measurement' often covers it
            'safety' => 'G. PSV & Safety',
            'internal' => 'Internal Inspection (Other)',
        ];
        
        $inputTypes = [
            'condition' => 'Condition (Good/Not Good/Need Attention/NA)',
            'text' => 'Text Input',
            'number' => 'Number Input',
            'date' => 'Date Picker',
            'boolean' => 'Yes/No',
        ];
        
        return view('admin.inspection_items.index', compact('items', 'categories', 'inputTypes'));
    }

    /**
     * Store a newly created inspection item
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:inspection_items,code|max:50',
            'label' => 'required|string|max:255',
            'category' => 'nullable|string|max:50',
            'input_type' => 'required|in:condition,text,number,date,boolean',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'applies_to' => 'required|in:both,incoming,outgoing',
        ]);

        InspectionItem::create($validated);

        return back()->with('success', 'Inspection item created successfully!');
    }

    /**
     * Update the specified inspection item
     */
    public function update(Request $request, $id)
    {
        $item = InspectionItem::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:inspection_items,code,' . $id,
            'label' => 'required|string|max:255',
            'category' => 'nullable|string|max:50',
            'input_type' => 'required|in:condition,text,number,date,boolean',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'applies_to' => 'required|in:both,incoming,outgoing',
        ]);

        $item->update($validated);

        return back()->with('success', 'Inspection item updated successfully!');
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        $item = InspectionItem::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);

        $status = $item->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Inspection item {$status} successfully!");
    }

    /**
     * Remove the specified inspection item
     */
    public function destroy($id)
    {
        $item = InspectionItem::findOrFail($id);
        
        // Optional: Check if item is used in any inspection logs
        // This would require checking the JSON data which is complex
        // For now, we'll allow deletion
        
        $item->delete();

        return back()->with('success', 'Inspection item deleted successfully!');
    }

    /**
     * Reorder items
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:inspection_items,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $itemData) {
            InspectionItem::where('id', $itemData['id'])
                ->update(['order' => $itemData['order']]);
        }

        return back()->with('success', 'Items reordered successfully!');
    }
}
