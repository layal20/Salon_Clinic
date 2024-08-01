<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmployeesController extends Controller
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
        if (!$user->can('view all services')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($super_admin) {
            Log::info('Logged in as Super Admin:', ['super_admin_id' => $super_admin->id]);
            $employees = Employee::with('admin')->get();
            return EmployeeResource::collection($employees);
        } elseif ($admin) {
            Log::info('Logged in as Admin:', ['admin_id' => $admin->id]);
            $admin = Auth::guard('admin')->user();
            $employees = Employee::where('admin_id', $admin->id)->get();
            return EmployeeResource::collection($employees);
        } elseif ($customer) {
            Log::info('Logged in as Customer:', ['customer_id' => $customer->id]);
            $employees = Employee::query()->get();
            return EmployeeResource::collection($employees);
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
        if (!$user->can('add employee')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'salary' => 'required|numeric',

        ]);

        if (!$request->hasFile('image')) {
            return;
        }
        $data = $request->except('image');
        $file = $request->file('image');
        $path = $file->store('employees', [
            'disk' => 'uploads'
        ]);
        $data['image'] = $path;
        $employee = Employee::create([
            'name' => $request->name,
            'salary' => $request->salary,
            'image' => $path,
            'admin_id' => $admin->id,
            'salon_id' => $admin->salon_id,
        ]);
        return response()->json(['message' => 'Employee added to salon successfully'], 201);
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

        if (!$super_admin && !$admin && !$customer) {
            return response()->json(['message' => 'Not Authenticated'], 401);
        }

        if (($super_admin && !$super_admin->can('view employee')) || ($admin && !$admin->can('view employee')) || ($customer && !$customer->can('view employee'))) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($super_admin) {
            Log::info('Logged in as Super Admin:', ['super_admin_id' => $super_admin->id]);

            $employee = Employee::with(['service', 'admin'])->find($id);
            if (!$employee) {
                return response()->json(['message' => 'Employee not found for super admin'], 404);
            }
            return new EmployeeResource($employee);
        }

        if ($admin) {
            Log::info('Logged in as Admin:', ['admin_id' => $admin->id]);

            $employee = Employee::with(['service', 'admin'])->where('admin_id', $admin->id)->find($id);
            if (!$employee) {
                return response()->json(['message' => 'Employee not found for admin'], 404);
            }
            return new EmployeeResource($employee);
        }

        if ($customer) {
            Log::info('Logged in as Customer:', ['customer_id' => $customer->id]);

            $employee = Employee::with('service')->find($id);
            if (!$employee) {
                return response()->json(['message' => 'Employee not found for customer'], 404);
            }
            return new EmployeeResource($employee);
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
        if (!$user->can('update employee info')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $employee = Employee::query()->where('admin_id', $admin->id)->find($id);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found for admin'], 404);
        }
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'image' => 'sometimes',
            'salary' => 'sometimes|numeric',
        ]);
        if ($request->hasFile('image')) {
            $old_image = $employee->image;
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
            $employee->update($data);
        } else
            $employee->update($request->all());
        return response()->json(['message' => 'Employee Info updated successfully'], 201);
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
        if (!$user->can('delete employee')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee = Employee::query()->where('admin_id', $admin->id)->find($id);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
        $employee->delete();
        if ($employee->image) {
            Storage::disk('uploads')->delete($employee->image);
        }
        return response()->json([
            'message' => 'Employee deleted successfully',
        ], 200);
        // return response()->json(['message' => 'Employee Deleted successfully'], 201);
    }
}
