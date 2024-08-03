<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Salon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductsController extends Controller
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

        if (!$user->can('view all products')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($super_admin) {
            $products = Product::with('admin')->get();
            return ProductResource::collection($products);
        } elseif ($admin) {
            $products = Product::where('admin_id', $admin->id)->get();
            return ProductResource::collection($products);
        } else {
            $products = Product::get();
            return ProductResource::collection($products);
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
        if (!$user->can('add product')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'name' => 'required|unique:products,name|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
            'image' => 'required'

        ]);
        $data = $request->except('image');
        $file = $request->file('image');
        $path = $file->store('products', [
            'disk' => 'uploads'
        ]);
        $data['image'] = $path;
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image' => $path,
            'admin_id' => $admin->id,
        ]);
        $salon_id = $admin->salon_id;
        $salon = Salon::query()->find($salon_id);
        $salon->products()->attach($product->id, ['quantity' => $request->quantity]);
        return response()->json(['message' => 'Product created and added to salon successfully'], 201);
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

        //log::info('Current user:', ['user' => $user]);

        if ($super_admin) {
            //Log::info('Logged in as Super Admin:', ['super_admin_id' => $super_admin->id]);
            $product = Product::find($id);
            if (!$product) {
                //Log::info('Product not found for super admin', ['product_id' => $id]);
                return response()->json(['message' => 'Product not found for super admin'], 404);
            }
            return new ProductResource($product);
        } elseif ($admin) {
            $product = Product::where('admin_id', $admin->id)->find($id);
            if (!$product) {
                //Log::info('Product not found for admin', ['admin_id' => $admin->id, 'product_id' => $id]);
                return response()->json(['message' => 'Product not found for admin'], 404);
            }
            return new ProductResource($product);
        } elseif ($customer) {
            $product = Product::with(
                ['salons' => function ($query) {
                    $query->withPivot('quantity');
                }]
            )->find($id);
            if (!$product) {
                return response()->json(['message' => 'Product not found for customer '], 404);
            }
            return new ProductResource($product);
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
        if (!$user->can('update product details')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $product = Product::query()->find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        $request->validate([
            'name' => 'sometimes|unique:products,name|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'image' => 'sometimes',
            'quantity' => 'sometimes'
        ]);
        if ($request->hasFile('image')) {
            $old_image = $product->image;
            $data = $request->except('image');
            $file = $request->file('image');
            $path = $file->store('salons', [
                'disk' => 'uploads'
            ]);
            $new_image = $path;
            if ($new_image) {
                $data['image']  = $new_image;
            }

            if ($old_image && isset($new_image)) {
                Storage::disk('uploads')->delete($old_image);
            }

            $product->update($data);
        } else

            if ($request->has('quantity')) {
            $salon_id = $admin->salon_id;
            $salon = Salon::find($salon_id);

            if (!$salon) {
                return response()->json(['message' => 'Salon not found'], 404);
            }

            $productInSalon = $salon->products->find($id);

            if ($productInSalon) {
                $salon->products()->updateExistingPivot($id, ['quantity' => $request->quantity]);
                return response()->json(['message' => 'Product quantity updated successfully'], 200);
            } else {
                return response()->json(['message' => 'Product not found in this salon'], 404);
            }
        }
        $product->update($request->all());
        return response()->json(['message' => 'Product updated successfully'], 200);
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
        $product = Product::query()->where('admin_id', $admin->id)->find($id);

        if (!$product) {
            if (!$product) {
                Log::info('Product not found for admin', ['admin_id' => $admin->id, 'product_id' => $id]);
                return response()->json(['message' => 'Product not found for admin'], 404);
            }
        }
        $product->delete();
        if ($product->image) {
            Storage::disk('uploads')->delete($product->image);
        }
        return response()->json([
            'message' => 'product deleted successfully',
        ], 200);
    }
}
