<?php

namespace App\Http\Controllers;

use App\Models\Bookings;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    /**
     * Display a listing of all bookings with relationships.
     */
    public function index()
    {
        try {
            $bookings = Bookings::with(['car', 'user'])->get();
            return response()->json([
                'status' => 'success',
                'data' => $bookings,
                'total' => $bookings->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch bookings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created booking in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate incoming request
            $validated = $request->validate([
                'car_id' => 'required|integer|exists:cars,id',
                'user_id' => 'required|integer|exists:users,id',
                'pickup_date' => 'required|date_format:Y-m-d H:i:s|after:now',
                'return_date' => 'required|date_format:Y-m-d H:i:s|after:pickup_date',
                'status' => 'nullable|in:pending,confirmed,completed,cancelled',
                'price' => 'required|numeric|min:0'
            ]);

            // Check if car exists and is available
            $car = Car::find($validated['car_id']);
            if (!$car || !$car->is_available) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Selected car is not available for booking'
                ], 422);
            }

            // Check for existing bookings on the same dates
            $existingBooking = Bookings::where('car_id', $validated['car_id'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('pickup_date', [$validated['pickup_date'], $validated['return_date']])
                        ->orWhereBetween('return_date', [$validated['pickup_date'], $validated['return_date']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('pickup_date', '<=', $validated['pickup_date'])
                                ->where('return_date', '>=', $validated['return_date']);
                        });
                })
                ->where('status', '!=', 'cancelled')
                ->exists();

            if ($existingBooking) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Car is already booked for the selected dates'
                ], 409);
            }

            // Create the booking
            $booking = Bookings::create($validated);
            $booking->load(['car', 'user']);

            return response()->json([
                'status' => 'success',
                'message' => 'Booking created successfully',
                'data' => $booking
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified booking.
     */
    public function show(string $id)
    {
        try {
            $booking = Bookings::with(['car', 'user'])->find($id);

            if (!$booking) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Booking not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $booking
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified booking in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $booking = Bookings::find($id);

            if (!$booking) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Booking not found'
                ], 404);
            }

            // Validate incoming request
            $validated = $request->validate([
                'car_id' => 'nullable|integer|exists:cars,id',
                'user_id' => 'nullable|integer|exists:users,id',
                'pickup_date' => 'nullable|date_format:Y-m-d H:i:s',
                'return_date' => 'nullable|date_format:Y-m-d H:i:s',
                'status' => 'nullable|in:pending,confirmed,completed,cancelled',
                'price' => 'nullable|numeric|min:0'
            ]);

            // Remove null values
            $validated = array_filter($validated, fn($value) => $value !== null);

            // Check date validity if dates are being updated
            if (isset($validated['pickup_date']) && isset($validated['return_date'])) {
                if ($validated['return_date'] <= $validated['pickup_date']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Return date must be after pickup date'
                    ], 422);
                }
            }

            // Check car availability if car_id is being changed
            if (isset($validated['car_id']) && $validated['car_id'] !== $booking->car_id) {
                $car = Car::find($validated['car_id']);
                if (!$car || !$car->is_available) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Selected car is not available'
                    ], 422);
                }
            }

            // Update the booking
            $booking->update($validated);
            $booking->load(['car', 'user']);

            return response()->json([
                'status' => 'success',
                'message' => 'Booking updated successfully',
                'data' => $booking
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified booking from storage.
     */
    public function destroy(string $id)
    {
        try {
            $booking = Bookings::find($id);

            if (!$booking) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Booking not found'
                ], 404);
            }

            // Prevent deletion of completed bookings
            if ($booking->status === 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete completed bookings'
                ], 403);
            }

            $booking->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Booking deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
