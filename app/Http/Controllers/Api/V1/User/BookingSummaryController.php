<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingSummaryController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Get booking summary with pricing breakdown
     */
    public function getSummary(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => ['required', 'uuid', 'exists:travel_packages,id'],
            'number_of_adults' => ['required', 'integer', 'min:1'],
            'number_of_children' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $summary = $this->bookingService->getBookingSummary(
                $request->package_id,
                $request->number_of_adults,
                $request->number_of_children ?? 0
            );

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
