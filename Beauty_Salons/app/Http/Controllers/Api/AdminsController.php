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
        if (!$admins) {
            return response()->json('there is no admins yet');
        }
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
        DB::transaction(function () use ($request) {
            $request->validate(
                [
                    'name' => 'required|unique:salons,name',
                    'description' => 'required',
                    'status' => 'in:active,inactive',
                    'logo_image' => 'required',
                    'latitude' => 'required|unique:salons,latitude|numeric|between:-90,90',
                    'longitude' => 'required|unique:salons,longitude|numeric|between:-180,180',
                ],
                [
                    'name.unique' => 'this salon name is already exist'
                ]
            );
            $data = $request->except('logo_image');
            $file = $request->file('logo_image');
            $path = $file->store('salons', [
                'disk' => 'uploads'
            ]);
            $data['logo_image'] = $path;

            $request->validate(
                [
                    'user_name' => 'required|unique:admins,user_name|string|max:255',
                    'password' => 'required',
                ],
                [
                    'user_name.unique' => 'this user name is already exist for this admin'


                ]
            );
            $salon = Salon::create($data);

            $admin = Admin::create([
                'user_name' => $request->user_name,
                'password' => Hash::make($request->password),
                'salon_id' => $salon->id,
            ]);
            $role = Role::findByName('admin', 'admin');
            $admin->assignRole($role);
        });

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
        $admin = Admin::with(['services', 'salon', 'products', 'employees'])->find($id);
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
        $validatedData = $request->validate([
            'user_name' => 'sometimes|string|unique:admins,user_name,' . $admin->id . '|max:255',
            'password' => 'sometimes|string|min:6',
        ]);

        if ($request->has('password')) {
            $admin->password = Hash::make($request->password);
        }
        if ($request->has('user_name')) {
            $admin->user_name = $request->user_name;
        }
        $admin->save();
        return response()->json(['message' => 'admin Info updated successfully'], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
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

        DB::transaction(function () use ($admin) {
            $salon = $admin->salon;
            $products = $admin->products();
            $services = $admin->services();
            $employees = $admin->employees();
            $services->each(function ($service) {
                if ($service && $service->image) {
                    if (Storage::disk('uploads')->exists($service->image)) {
                        Storage::disk('uploads')->delete($service->image);
                    } else {
                    }
                }
            });


            $products->each(function ($product) {
                if ($product && $product->image) {
                    if (Storage::disk('uploads')->exists($product->image)) {
                        Storage::disk('uploads')->delete($product->image);
                    }
                }
            });



            $employees->each(function ($employee) {
                if ($employee && $employee->image) {
                    if (Storage::disk('uploads')->exists($employee->image)) {
                        Storage::disk('uploads')->delete($employee->image);
                    }
                }
            });
            $services->each(function ($service) {
                $service->delete();
            });

            $products->each(function ($product) {
                $product->delete();
            });

            $employees->each(function ($employee) {
                $employee->delete();
            });
            if ($salon->logo_image) {
                if (Storage::disk('uploads')->exists($salon->logo_image)) {
                    Storage::disk('uploads')->delete($salon->logo_image);
                }
            }

            $salon->delete();

            $admin->delete();
        });

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
        $results = admin::with(['salon', 'services', 'products'])->where('user_name', 'like', "%{$name}%")->get();
        if ($results->isEmpty()) {
            return Response::json([
                'Admin Not Found'
            ]);
        }
        return  AdminResource::collection($results);
    }
}
