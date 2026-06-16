<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $user = User::all();
        return response()->json($user);
    }

    // sample in postmen // {
                        //     "name":"jing",
                        //     "email":"jing123@gmail.com",
                        //     "password":"12345",
                        //     "role": "admin"
                        // }
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user->id === auth()->user()->id) {
            return response()->json([
                'message' => "User Not Found"
            ], 404);
        }
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role
        ]);

        return response([
            'message' => "User Updated Successfully",
            'user' => $user
        ], 200);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user->id === auth()->user()->id) {
            return response()->json([
                'message' => "User Not Found"
            ], 404);
        }

        $user->delete();

        return response([
            'message' => "User Deleted Successfully",
        ], 200);
    }
}