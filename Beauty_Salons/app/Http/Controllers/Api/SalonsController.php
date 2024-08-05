<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalonResource;
use App\Models\Admin;
use App\Models\Salon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class SalonsController extends Controller
{
    $super_admin = Auth::guard('super_admin')->check() ? Auth::guard('super_admin')->user() : null;
        $admin = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : null;
        $customer = Auth::guard('customer')->check() ? Auth::guard('customer')->user() : null;
        $user = $super_admin ?: $admin ?: $customer;
        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }
        if (!$user->can('add salon')) {
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


        return Response::json('Salon Added Successfully', 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::guard('super_admin')->user() ?: Auth::guard('admin')->user() ?: Auth::guard('customer')->user();

        if (!$user) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }

        if (Auth::guard('super_admin')->check()) {
            $salon = Salon::with(['products' => function ($query) {
                $query->wherePivot('quantity', '>', 0)->withPivot('quantity');
            }, 'services', 'employees', 'admin'])
                ->active()
                ->find($id);

            if (!$salon) {
                return response()->json(['message' => 'Salon not found for super admin'], 404);
            }

            return new SalonResource($salon);
        } elseif (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            $salon = Salon::with(['products' => function ($query) {
                $query->wherePivot('quantity', '>', 0)->withPivot('quantity');
            }, 'services', 'employees', 'admin'])
                ->active()
                ->where('id', $admin->salon_id)
                ->find($id);

            if (!$salon) {
                return response()->json(['message' => 'Salon not found for admin'], 404);
            }

            return new SalonResource($salon);
        } elseif (Auth::guard('customer')->check()) {
            $salon = Salon::with(['products' => function ($query) {
                $query->wherePivot('quantity', '>', 0)->withPivot('quantity');
            }, 'services', 'employees', 'admin'])
                ->active()
                ->find($id);

            if (!$salon) {
                return response()->json(['message' => 'Salon not found for non-admin'], 404);
            }

            return new SalonResource($salon);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
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
        if (!$user->can('update salon info')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $salon = Salon::active()->find($id);
        if (!$salon) {
            return response()->json(['message' => 'Salon not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|unique:salons,name',
            'description' => 'sometimes',
            'logo_image' => 'sometimes',
            'status' => 'sometimes|in:active,inactive',
            'latitude' => 'sometimes|unique:salons,latitude|numeric|between:-90,90',
            'longitude' => 'sometimes|unique:salons,longitude|numeric|between:-180,180',
        ]);
        if ($request->hasFile('logo_image')) {
            $old_image = $salon->logo_image;
            $data = $request->except('logo_image');
            $file = $request->file('logo_image');
            $path = $file->store('salons', [
                'disk' => 'uploads'
            ]);
            $new_image = $path;
            if ($new_image) {
                $data['logo_image']  = $new_image;
            }

            if ($old_image && isset($new_image)) {
               
                    Storage::disk('uploads')->delete($salon->logo_image);
                 
            }
            $salon->update($data);
        } else
            $salon->update($request->all());
        return response()->json(['message' => 'Salon updated successfully'], 200);
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
        if (!$user->can('delete salon')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $salon = Salon::active()->find($id);
        if (!$salon) {
            return response()->json(['message' => 'Salon not found'], 404);
        }
        DB::transaction(function () use ($admin, $salon) {
            $admin = $salon->admin;
            $products = $salon->products();
            $services = $salon->services();
            $employees = $salon->employees();

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
                    } else {
                    }
                }
            });



            $employees->each(function ($employee) {
                if ($employee && $employee->image) {
                    if (Storage::disk('uploads')->exists($employee->image)) {
                        Storage::disk('uploads')->delete($employee->image);
                    } else {
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
                } else {
                    Log::error("Salon logo image not found: " . $salon->logo_image);
                }
            }

            $salon->delete();

            $admin->delete();
        });
        return Response::json([
            'message' => 'salon deleted successfully',
        ], 200);
    }
}
