<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('orderItems')->get();
        return response()->json($orders);
    }

    public function show($id)
    {
        $order = Order::with('orderItems')->find($id);
        if ($order) {
            return response()->json(
                [
                    'order' => $order,
                    'status' => 'success'
                ]
            );
        }
        return response()->json(['error' => 'Order not found'], 404);
    }

    public function store(Request $request)
    {
        // validate request
        $validator = Validator::make($request->all(), [
            'total_amount' => 'required|numeric',
            'status' => 'required|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer',
            'items.*.price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $request->all();
        $order = Order::create($data);
        foreach ($data['items'] as $item) {
            $order->orderItems()->create($item);
        }
        return response()->json($order, 201);
    }

    public function update(Request $request, $id)
    {
        // validate request
        $validator = Validator::make($request->all(), [
            'total_amount' => 'numeric',
            'status' => 'string',
            'items' => 'array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'integer',
            'items.*.price' => 'numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $request->all();
        $order = Order::find($id);
        if ($order) {
            $order->update($data);
            if (isset($data['items'])) {
                $order->orderItems()->delete();
                foreach ($data['items'] as $item) {
                    $order->orderItems()->create($item);
                }
            }
            return response()->json($order);
        }
        return response()->json(['error' => 'Order not found'], 404);
    }

    public function destroy($id)
    {
        $order = Order::find($id);
        if ($order) {
            $order->orderItems()->delete();
            $order->delete();
            return response()->json(['message' => 'Order deleted']);
        }
        return response()->json(['error' => 'Order not found'], 404);
    }

    // Optional: Update order status endpoint
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $order = Order::find($id);
        if ($order) {
            $order->update(['status' => $request->status]);
            return response()->json($order);
        }
        return response()->json(['error' => 'Order not found'], 404);
    }
}
