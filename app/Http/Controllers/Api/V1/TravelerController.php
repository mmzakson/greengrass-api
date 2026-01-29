<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Traveler\CreateTravelerRequest;
use App\Http\Requests\Traveler\UpdateTravelerRequest;
use App\Http\Resources\Traveler\TravelerResource;
use App\Services\TravelerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravelerController extends Controller
{
    public function __construct(
        protected TravelerService $travelerService
    ) {}

    /**
     * Add traveler to booking
     */
    public function store(string $bookingId, CreateTravelerRequest $request): JsonResponse
    {
        try {
            $traveler = $this->travelerService->addTravelerToBooking(
                $bookingId,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Traveler added successfully',
                'data' => new TravelerResource($traveler),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update traveler information
     */
    public function update(string $travelerId, UpdateTravelerRequest $request): JsonResponse
    {
        try {
            $traveler = $this->travelerService->updateTraveler(
                $travelerId,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Traveler updated successfully',
                'data' => new TravelerResource($traveler),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete traveler
     */
    public function destroy(string $travelerId): JsonResponse
    {
        try {
            $this->travelerService->deleteTraveler($travelerId);

            return response()->json([
                'success' => true,
                'message' => 'Traveler removed successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get traveler details
     */
    public function show(string $travelerId): JsonResponse
    {
        try {
            $traveler = $this->travelerService->getTravelerById($travelerId);

            if (!$traveler) {
                return response()->json([
                    'success' => false,
                    'message' => 'Traveler not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new TravelerResource($traveler),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download passport file
     */
    public function downloadPassport(string $travelerId): mixed
    {
        try {
            $file = $this->travelerService->downloadPassport($travelerId);

            return response()->download(
                $file['path'],
                $file['filename'],
                ['Content-Type' => $file['mimetype']]
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Validate booking travelers documents
     */
    public function validateDocuments(string $bookingId): JsonResponse
    {
        try {
            $issues = $this->travelerService->validateTravelersDocuments($bookingId);

            if (empty($issues)) {
                return response()->json([
                    'success' => true,
                    'message' => 'All traveler documents are complete',
                    'all_valid' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Some travelers have incomplete documents',
                'all_valid' => false,
                'issues' => $issues,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
