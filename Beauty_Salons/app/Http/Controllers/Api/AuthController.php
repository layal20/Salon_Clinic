<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    public function registerCustomer(Request $request)
    {
        $request->validate([
            'user_name' => 'required',
            'password' => 'required'
        ]);
        $admin = Admin::create([
            'user_name' => $request->user_name,
            'password' => Hash::make($request->password)
        ]);
        $token = $admin->createToken('Admin Access Token');

        return response()->json([
            'Token' => $token->accessToken
        ], 200);
    }
    public function loginAdmin(Request $request)
    {
        $credentials = $request->only('user_name', 'password');
        $admin = admin::query()->where('user_name', $credentials['user_name'])->first();
        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            $token = $admin->createToken('Admin Access Token', ['admin'])->accessToken;
            return response()->json([
                'token' => $token
            ], 200);
        }
        return response()->json([
            'error' => 'Unauthorized'
        ], 401);
    }

    public function loginSuperAdmin(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $super_admin = SuperAdmin::query()->where('email', $credentials['email'])->first();
        if ($super_admin && Hash::check($credentials['password'], $super_admin->password)) {
            $token = $super_admin->createToken('Super Admin Access Token', ['super_admin'])->accessToken;
            return response()->json([
                'token' => $token
            ], 200);
        }
        return response()->json([
            'error' => 'Unauthorized'
        ], 401);
    }

    public function CustomerRegister(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'image' => 'sometimes',
            'phone_number' => 'required'
        ]);
        $password = Hash::make($request->password);
        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
            'phone_number' => $request->phone_number,
        ]);
        if ($request->hasFile('image')) {
            $image_name = 'default.png';
            $destenationPath = 'public/images/users';
            $image = $request->image;
            $image_name = implode('.', [
                md5_file($image->getPathname()),
                $image->getClientOriginalExtension()
            ]);
            $path = $request->file('image')->storeAs($destenationPath, $image_name);
            $customer->image = $image_name;
            $customer->save();
        }

        $token = $customer->createToken('Customer Access Token', ['customer'])->accessToken;
        return response()->json([
            'token' => $token
        ], 200);
    }

    public function customerLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $customer = Customer::query()->where('email', $credentials['email'])->first();
        if ($customer && Hash::check($credentials['password'], $customer->password)) {
            $token = $customer->createToken('Customer Access Token', ['customer'])->accessToken;
            return response()->json([
                'token' => $token
            ], 200);
        }
        return response()->json([
            'error' => 'Unauthorized'
        ], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Successfully logged out']);
    }
}
