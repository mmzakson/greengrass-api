<?php

namespace App\Services;

use App\Models\Traveler;
use App\Models\Booking;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class TravelerService
{
    /**
     * Add traveler to booking
     */
    public function addTravelerToBooking(string $bookingId, array $data): Traveler
    {
        $booking = Booking::findOrFail($bookingId);

        // Verify booking capacity
        $currentTravelers = $booking->travelers()->count();
        if ($currentTravelers >= $booking->number_of_travelers) {
            throw new \Exception('Maximum number of travelers reached for this booking');
        }

        // Handle passport upload
        if (isset($data['passport_copy']) && $data['passport_copy'] instanceof UploadedFile) {
            $data['passport_copy'] = $this->uploadPassport($data['passport_copy']);
        }

        // Handle emergency contact JSON
        if (isset($data['emergency_contact']) && is_array($data['emergency_contact'])) {
            $data['emergency_contact'] = $data['emergency_contact'];
        }

        return $booking->travelers()->create($data);
    }

    /**
     * Update traveler information
     */
    public function updateTraveler(string $travelerId, array $data): Traveler
    {
        $traveler = Traveler::findOrFail($travelerId);

        // Handle passport upload
        if (isset($data['passport_copy']) && $data['passport_copy'] instanceof UploadedFile) {
            // Delete old passport
            if ($traveler->passport_copy) {
                Storage::disk('passports')->delete($traveler->passport_copy);
            }
            
            $data['passport_copy'] = $this->uploadPassport($data['passport_copy']);
        }

        $traveler->update($data);

        return $traveler->fresh();
    }

    /**
     * Delete traveler
     */
    public function deleteTraveler(string $travelerId): bool
    {
        $traveler = Traveler::findOrFail($travelerId);

        // Check if booking allows traveler removal
        if ($traveler->booking->booking_status === 'confirmed') {
            throw new \Exception('Cannot remove travelers from confirmed bookings');
        }

        return $traveler->delete();
    }

    /**
     * Get traveler by ID
     */
    public function getTravelerById(string $id): ?Traveler
    {
        return Traveler::find($id);
    }

    /**
     * Get all travelers for a booking
     */
    public function getBookingTravelers(string $bookingId)
    {
        return Traveler::where('booking_id', $bookingId)->get();
    }

    /**
     * Upload passport file
     */
    protected function uploadPassport(UploadedFile $file): string
    {
        // Validate file
        $this->validatePassportFile($file);

        // Generate unique filename
        $filename = uniqid('passport_') . '_' . time() . '.' . $file->getClientOriginalExtension();

        // Store file
        $path = $file->storeAs('/', $filename, 'passports');

        return $path;
    }

    /**
     * Validate passport file
     */
    protected function validatePassportFile(UploadedFile $file): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $maxSize = 5 * 1024; // 5MB in KB

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid file type. Only JPG, PNG, and PDF files are allowed.');
        }

        if ($file->getSize() > ($maxSize * 1024)) {
            throw new \Exception('File size must not exceed 5MB.');
        }
    }

    /**
     * Download passport
     */
    public function downloadPassport(string $travelerId): array
    {
        $traveler = Traveler::findOrFail($travelerId);

        if (!$traveler->passport_copy) {
            throw new \Exception('No passport file found for this traveler');
        }

        $path = Storage::disk('passports')->path($traveler->passport_copy);

        if (!file_exists($path)) {
            throw new \Exception('Passport file not found');
        }

        return [
            'path' => $path,
            'filename' => $traveler->passport_copy,
            'mimetype' => Storage::disk('passports')->mimeType($traveler->passport_copy),
        ];
    }

    /**
     * Validate all travelers have required documents
     */
    public function validateTravelersDocuments(string $bookingId): array
    {
        $travelers = $this->getBookingTravelers($bookingId);
        $issues = [];

        foreach ($travelers as $traveler) {
            $travelerIssues = [];

            if (!$traveler->passport_number) {
                $travelerIssues[] = 'Passport number missing';
            }

            if (!$traveler->passport_expiry) {
                $travelerIssues[] = 'Passport expiry date missing';
            } elseif (!$traveler->is_passport_valid) {
                $travelerIssues[] = 'Passport has expired';
            }

            if (!$traveler->passport_copy) {
                $travelerIssues[] = 'Passport copy not uploaded';
            }

            if (!$traveler->date_of_birth) {
                $travelerIssues[] = 'Date of birth missing';
            }

            if (count($travelerIssues) > 0) {
                $issues[$traveler->full_name] = $travelerIssues;
            }
        }

        return $issues;
    }

    /**
     * Auto-determine traveler type based on age
     */
    public function determineTravelerType(\DateTime $dateOfBirth): string
    {
        $age = $dateOfBirth->diff(new \DateTime())->y;

        if ($age >= 18) {
            return 'adult';
        } elseif ($age >= 2) {
            return 'child';
        } else {
            return 'infant';
        }
    }
}