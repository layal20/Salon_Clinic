<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Salon;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ServicesController extends Controller
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
        Log::info('Logged in as Customer:', ['customer_id' => $customer->id]);

        if (!$user->can('view all services')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            Log::info('Logged in as Super Admin:', ['super_admin_id' => $super_admin->id]);
            $services = service::with('admin')->active()->get();
            return ServiceResource::collection($services);
        } elseif ($admin) {
            Log::info('Logged in as Admin:', ['admin_id' => $admin->id]);
            $admin = Auth::guard('admin')->user();
            $services = Service::active()->where('admin_id', $admin->id)->get();
            return ServiceResource::collection($services);
        } elseif ($customer) {
            Log::info('Logged in as Customer:', ['customer_id' => $customer->id]);
            $services = service::active()->get();
            return ServiceResource::collection($services);
        }
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
        Log::info('Current user:', ['user' => $user]);

        if (!$user->can('add service')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'status' => 'required|string',
            'date' => 'required',
            'time' => 'date_format:H:i:s',
            'employee_id' => 'required|exists:employees,id',
            'image' => 'required'
        ]);
        $data = $request->except('image');
        $file = $request->file('image');
        $path = $file->store('services', [
            'disk' => 'uploads'
        ]);
        $data['image'] = $path;
        $service = Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'status' => $request->status,
            'image' => $path,
            'admin_id' => $admin->id,
            'date' => $request->date,
            'time' => $request->time,
            'employee_id' => $request->employee_id
        ]);
        $salon_id = $admin->salon_id;
        $salon = Salon::query()->find($salon_id);
        $salon->services()->attach($service->id);
        return response()->json(['message' => 'Service created and added to salon successfully'], 201);
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
        if (!$user->can('view service')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            Log::info('Logged in as Super Admin:', ['super_admin_id' => $super_admin->id]);
            $service = Service::with(['admin', 'salons'])->active()->find($id);
            if (!$service) {
                return response()->json(['message' => 'Service not found'], 404);
            }

            return new ServiceResource($service);
        } elseif ($admin) {
            Log::info('Logged in as Admin:', ['admin_id' => $admin->id]);
            $service = Service::with('salons')->active()->where('admin_id', $admin->id)->find($id);
            if (!$service) {
                return response()->json(['message' => 'Service not found'], 404);
            }
            return new ServiceResource($service);
        } elseif ($customer) {
            Log::info('Logged in as Customer:', ['customer_id' => $customer->id]);
            $service = Service::with('salons')->active()->find($id);
            if (!$service) {
                return response()->json(['message' => 'Service not found'], 404);
            }

            return new ServiceResource($service);
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
        if (!$user->can('update service details')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $service = Service::query()->where('admin_id', $admin->id)->find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'status' => 'sometimes|string',
            'image' => 'sometimes',
            'date' => 'sometimes|',
            'time' => 'sometimes|date_format:H:i:s',
            'employee_id' => 'sometimes|exists:employees,id',
            'image' => 'sometimes'
        ]);
        if ($request->hasFile('image')) {
            $old_image = $service->image;
            $data = $request->except('image');
            $file = $request->file('image');
            $path = $file->store('services', [
                'disk' => 'uploads'
            ]);
            $new_image = $path;
            if ($new_image) {
                $data['image']  = $new_image;
            }

            if ($old_image && isset($new_image)) {
                Storage::disk('uploads')->delete($old_image);
            }
            $service->update($data);
        } else
            $service->update($request->all());
        return response()->json(['message' => 'Service updated successfully'], 200);
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
        if (!$user->can('delete service')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $service = Service::query()->where('admin_id', $admin->id)->find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        $service->delete();
        if ($service->image) {
            Storage::disk('uploads')->delete($service->image);
        }
        return response()->json([
            'message' => 'Service deleted successfully',
        ], 200);
    }
}
