<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cars = Car::all();
        return response()->json($cars);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $path = $request->file('image')->store('cars', 'public');
        $url = asset('storage/' . $path);

        $cars = Car::create([
            'brand' => $request->brand,
            'model' => $request->model,
            'year' => $request->year,
            'category' => $request->category,
            'seating_capacity' => $request->seating_capacity,
            'fuel_type' => $request->fuel_type,
            'transmission' => $request->transmission,
            'price_per_day' => $request->price_per_day,
            'location' => $request->location,
            'description' => $request->description,
            'is_available' => $request->is_available === 'true' || $request->is_available === '1' ? 1 : 0,
            'image' => $url,
        ]);

        return response([
            'message' => 'Car Add Successfull',
            'cars' => $cars
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Car $car)
    {
        return response()->json($car);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Car $car)
    {
        $data = $request->only([
            'brand',
            'model',
            'year',
            'category',
            'seating_capacity',
            'fuel_type',
            'transmission',
            'price_per_day',
            'location',
            'description',
            'is_available',
        ]);

        if (isset($data['is_available'])) {
            $data['is_available'] = $data['is_available'] === 'true' || $data['is_available'] === '1' ? 1 : 0;
        }

        if ($request->hasFile('image')) {
            if ($car->image) {
                $oldPath = str_replace(asset('storage/') . '/', '', $car->image);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('image')->store('cars', 'public');
            $data['image'] = asset('storage/' . $path);
        }

        $car->update($data);

        return response()->json([
            'message' => 'Car Updated Successfully',
            'car' => $car->fresh() // 👈 reload from DB
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Car $car)
    {
        // Delete image from disk if exists
        if ($car->image) {
            $imagePath = str_replace(asset('storage/'), '', $car->image);
            Storage::disk('public')->delete($imagePath);
        }

        $car->delete();

        return response()->json([
            'message' => 'Car Deleted Successfully'
        ], 200);
    }
}