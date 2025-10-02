<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;

    /**
     * @param int $orderId
     * @return bool
     */
    public static function createDelivery(int $orderId): bool
    {
        $cart = \App\Services\V1\Cart\Cart::instance('esaj');
        $delivery = $cart->getDelivery();

        if ($delivery) {
            $logisticId = $delivery['logisticId'];
            $date = $delivery['date'];
            $deliveryBetweenStart = $delivery['deliveryBetweenStart'];
            $deliveryBetweenEnd = $delivery['deliveryBetweenEnd'];
            $title = $delivery['title'];
            $province = $delivery['province'];
            $city = $delivery['city'];
            $address = $delivery['address'];
            $postal_code = $delivery['postal_code'];
            $phone = $delivery['phone'];
            $mobile = $delivery['mobile'];

            $delivery = new Delivery();
            $delivery->order_id = $orderId;
            $delivery->logistic_id = $logisticId;
            $delivery->date = $date;
            $delivery->delivery_between_start = $deliveryBetweenStart;
            $delivery->delivery_between_end = $deliveryBetweenEnd;
            $delivery->title = $title;
            $delivery->province = $province;
            $delivery->city = $city;
            $delivery->address = $address;
            $delivery->postal_code = $postal_code;
            $delivery->phone = $phone;
            $delivery->mobile = $mobile;
            


            if ($delivery->save()) {
                return true;
            }
        }

        return false;
    }
}
