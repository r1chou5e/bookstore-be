<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Http\Resources\OrderDetailResource;
use App\Models\Discount;
use App\Models\Shipping;
use Illuminate\Database\Console\DbCommand;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use Ramsey\Collection\Collection;


class OrderController extends Controller
{
    //
    public function index() {
        $user = auth()->user();
        $orders = Order::with([
            'orderDetails.book:id,name,isbn,price,book_image',
            'payment:id,type,status,total'
        ])->where('user_id', $user->id)->where('is_deleted', false)->get();
        return response(['orders' => new OrderResource($orders), 'message' => 'Retrieved successfully'], 200);
    }

    public function allOrders() {
        $order_list = Order::all();
        return response(['orders' => $order_list]);
    }

    public function show(Order $order) {
        $orders_details = Order::with([
            'orderDetails.book:id,name,isbn,price,book_image',
            'payment'
        ])->find($order->id);
        $discount = Discount::where('id',$orders_details->payment->discount_id)->first(['name','value']);
        $shipping = Shipping::where('id',$orders_details->payment->shipping_id)->first(['name','address_id','phone','value','shipping_on']);
        $orders_details->shipping = $shipping;
        $orders_details->discount = $discount;
        return response()->json(new OrderDetailResource($orders_details), 200);
    }

    public static function store($payment_id) {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $user = auth()->user();
        $data = [
            'status' => 0,
            'order_on' => date('Y-m-d H:i:s', time()),
            'user_id' => $user->id,
            'payment_id' => $payment_id,
            'is_deleted' => false
        ];
        try {
            $order = Order::create($data);
            $cart = Cart::where('user_id', $user->id)->where('is_checked', 1)->get();
            $order_detail = [];
            foreach ($cart as $item) {
                $data = [
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'order_id' => $order->id,
                    'book_id' => $item->book_id
                ];
                $order_detail[] = OrderDetail::create($data);
            }
            return collect([$order, $order_detail]);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Order $order) {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        DB::beginTransaction();
        try {
            $order->update([
                'is_deleted' => true,
                'deleted_at' => date('Y-m-d H:i:s', time())
            ]);
            DB::commit();
            return response(['message' => 'Order deleted successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, Order $order) {
        $order = Order::find($order->id);
        $status = $request->status;
        try {
            $order->update(['status' => $status]);
            return response(['order' => Order::find($order->id)]);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }
}
