<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InspectionItem;
use Illuminate\Http\Request;

class InspectionItemApiController extends Controller
{
    /**
     * Get list of active inspection items
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'both'); // 'incoming', 'outgoing', or 'both'
        
        $query = InspectionItem::active()
            ->ordered();

        if ($type !== 'both') {
            $query->forType($type);
        }

        $items = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'code' => $item->code,
                'label' => $item->label,
                'category' => $item->category,
                'input_type' => $item->input_type,
                'description' => $item->description,
                'order' => $item->order,
                'is_required' => $item->is_required,
                'applies_to' => $item->applies_to,
                'options' => $item->options,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Inspection items retrieved successfully',
            'data' => $items
        ]);
    }
}
