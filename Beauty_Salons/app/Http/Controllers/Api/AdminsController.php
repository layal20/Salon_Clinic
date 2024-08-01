<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Salon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
class AdminsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;
        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->hasPermissionTo('view all admins')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $admins = Admin::query()->get();
        return AdminResource::collection($admins);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;
        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->hasPermissionTo('add admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'user_name' => 'required|string|max:255',
            'password' => 'required',
            'salon_id' => 'required'

        ]);

        $admin = Admin::create([
            'user_name' => $request->user_name,
            'password' => Hash::make($request->password),
            'salon_id' => $request->salon_id,
        ]);

        return response()->json(['message' => 'admin added to salon successfully'], 201);


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;
        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->hasPermissionTo('view admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $admin = Admin::with(['services', 'salon'])->find($id);
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }
        return new AdminResource($admin);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;
        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->hasPermissionTo('update admin info')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $admin = Admin::query()->find($id);
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }
        $request->validate([
            'user_name' => 'sometimes|string|max:255',
            'password' => 'sometimes',
            'salon_id' => 'sometimes'

        ]);
        if ($request->has('salon_id')) {
            $salon = Salon::find($request->salon_id);
            if (!$salon) {
                return response()->json(['message' => 'Salon not found'], 404);
                $admin->salon_id = $request->salon_id;
                $admin->save();
            }
        }
        if ($request->has('password')) {
            $admin->password = Hash::make($request->password);
            $admin->save();
        }
        if ($request->has('user_name')) {
            $admin->user_name = $request->user_name;
            $admin->save();
        }


        return response()->json(['message' => 'admin Info updated successfully'], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;
        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->hasPermissionTo('delete admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $admin = Admin::query()->find($id);
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }
        $salon = $admin->salon;
        $salon->delete();
        $admin->delete();
        return response()->json(['message' => 'Admin Deleted successfully'], 201);
    }


    public function searchAboutAdmin($name)
    {
        $super_admin = Auth::guard('super_admin')->user();
        if (!$super_admin) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$super_admin->can('search about admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $results = admin::with('salon')->where('user_name', 'like', "%{$name}%")->get();
        if ($results->isEmpty()) {
            return Response::json([
                'Admin Not Found'
            ]);
        }
        return  AdminResource::collection($results);
    }
}
