<?php

namespace App\Services\V1\Cart;

use App\Jobs\CartJob;
use App\Models\Discount;
use App\Models\Logistic;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use App\Models\Cart;
use App\Models\Work;


class CartService
{
    protected array $cart = [];
    public string|null $cartKey;
    private string $dbCartKey;
    private string $discount;

    /**
     * @return array
     */
    public function all(): array
    {
        $price = 0;
        $finalPrice = 0;
        $totalDiscount = 0;
        $totalSale = 0;
        $orderItems = [];
        $detailArray = [];

        $discountCheck = $this->discountCheck();
        $saleCheck = $this->saleCheck();

        foreach ($this->cart['items'] as $key => $item) {
            $item = $this->withRelationshipIfExist($item);
            if (isset($item['product']['work_id'])) {
                $work = Work::find($item['product']['work_id']);
                if (!$work || $work->is_published !== Work::IS_PUBLISHED_TRUE) {
                    unset($this->cart['items'][$key]);
                    continue;
                }
            }

            if ($item['product'] && $item['product']['status']){ //  && in_array($item['product']['type'], [Product::TYPE_CART, Product::TYPE_CARD_CHARGE, Product::TYPE_PHYSICAL_CARD_CHARGE])
                $eachItem = $this->checkDiscountForEachProduct($item, $discountCheck, $saleCheck);
                $this->cart['items'][$key] = $eachItem;

                $unitPrice = $eachItem['product']['price'];
                $discount = $eachItem['discount_price'] ?? 0;
                $sale = $eachItem['sale_price'] ?? 0;

                $finalUnitPrice = $unitPrice - $discount - $sale;

                $finalPrice += $finalUnitPrice * $eachItem['quantity'];
                $totalDiscount += $discount * $eachItem['quantity'];
                $totalSale += $sale * $eachItem['quantity'];
                $price += $unitPrice * $eachItem['quantity'];

                $orderItems['product'][$eachItem['product']['id']] = [
                    'quantity'         => $eachItem['quantity'],
                    'discount'         => $discount + $sale,
                    'price'            => $finalUnitPrice,

                    // اسنپ‌شات محصول (اگر خودت موقع addToCart گذاشتی، همونو بردار،
                    // وگرنه از همون $eachItem['product'] پرش کن)
                    'product_snapshot' => $eachItem['product_snapshot'] ?? $eachItem['product'] ?? null,

                    // config آیتم در سبد
                    'config' => [
                        'settings' => $eachItem['settings'] ?? null,
                        'mockup'   => $eachItem['mockup']   ?? null,
                        'preview'  => $eachItem['preview']  ?? null,
                        'options'  => $eachItem['options']  ?? null,
                        'meta'     => $eachItem['meta']     ?? null,
                    ],
                ];
            } else {
                unset($this->cart['items'][$key]);
            }
        }


        $delivery = $this->getDelivery();
        $deliveryPrice = 0;

        if ($delivery) {
            $logistic = Logistic::find($delivery['logisticId']);

            if ($logistic) {
                $deliveryPrice = $logistic->price;
            }
        }


        $detailArray['saleId'] = $saleCheck['id'] ?? null;
        $detailArray['discountId'] = $discountCheck['id'] ?? null;
        $detailArray['price'] = $price;
        $detailArray['finalPrice'] = $finalPrice;
        $detailArray['finalPriceWithDelivery'] = $finalPrice + $deliveryPrice;
        $detailArray['orderItems'] = $orderItems;
        $detailArray['totalDiscount'] = $totalDiscount;
        $detailArray['totalSale'] = $totalSale;
        $detailArray['deliveryPrice'] = $deliveryPrice;


        return array_merge($this->cart, ['details' => $detailArray]);
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function delete(mixed $key): bool
    {
        if ($this->has($key)) {
            $cart = $this->cart['items'];
            $productKey = $this->productKey($key);
            if ($cart[$productKey]['quantity'] > 1) {
                $cart[$productKey]['quantity'] -= 1;
            } else {
                unset($cart[$productKey]);
            }
            $this->cart['items'] = $cart;
            $this->dbSet();
            return true;
        }
        return false;
    }

    /**
     * @return void
     */
    public function flush(): void
    {
        $this->cart = [
            'items' => [],
            'discount' => null,
            'delivery' => null
        ];

        $this->dbSet();
    }

    /**
     * @param string $name
     * @param string|null $cartKey
     * @return $this
     */
    public function instance(string $name, string $cartKey = null): CartService
    {
        if (empty($cartKey)) {
            $this->cartKey = 'cart-' . $name . '-' . time() . Str::random(20);
        } else {
            $this->cartKey = $cartKey;
        }

        $this->dbCartKey = Auth::check() ? 'cart-db-' . $name . Auth::id() : 'false';

        $this->redisGet();

        return $this;
    }

    /**
     * @param string $discount
     * @return array
     */
    public function addDiscount(string $discount): array
    {
        $this->discount = $discount;
        $discountCheck = $this->discountCheck();

        if ($discountCheck['status']) {
            $this->cart['discount'] = $discount;
            $this->dbSet();
            $discountCheck['message'] = __('general.discountAdded');
        } elseif (empty($discount)) {
            $this->cart['discount'] = null;
            $this->dbSet();
            return ['status' => true, 'message' => __('general.discountRemoved')];
        }

        return $discountCheck;
    }

    /**
     * @return Discount|null
     */
    public function getDiscount(): Discount|null
    {
        $discount = $this->discount ?? $this->cart['discount'];
        if (!is_null($discount)) {
            return Discount::where('code', $discount)->where('status', Discount::STATUS_ACTIVE)->first();
        }
        return null;
    }

    /**
     * @param mixed $product
     * @param int $quantity
     * @return array
     */
    public function addToCart(mixed $product, int $quantity = 1, array $config = []): array
    {
         $work = $product->work ?? null; 

          if ($work && $work->is_published !== Work::IS_PUBLISHED_TRUE) {
                return [
                    'status'  => false,
                    'message' => 'این اثر هنوز منتشر نشده است و امکان افزودن به سبد خرید وجود ندارد.',
                ];
            }

        // اگر قبلاً تو سبد هست، فعلاً فقط quantity رو زیاد می‌کنیم
        if ($this->has($product)) {
            $this->update($product, $quantity);
        } else {
            // اینجا اسنپ‌شات محصول + config رو ذخیره می‌کنیم
            $base = [
                'quantity'         => $quantity,
                'product_snapshot' => [
                    'id'           => $product->id,
                    'name'         => $product->name,
                    'slug'         => $product->slug,
                    'price'        => $product->price,
                    'currency'     => $product->currency ?? 'IRR',
                    'preview_path' => $product->preview_path,
                    'settings'     => $product->settings,
                ],
            ];

            $payload = array_merge($base, $config);

            $this->put($payload, $product);
        }

        $this->dbSet();

        return [
            'status'  => true,
            'message' => 'added to cart successfully',
        ];
    }


    /**
     * @param string $name
     * @return void
     */
    public function dbGet(string $name): void
    {
        $cartDb = Cart::where('user_id', Auth::id())->first();
        if ($cartDb && !empty($cartDb->cart_detail)) {
            Redis::set('cart-' . $name . Auth::id(), $cartDb->cart_detail);
            $this->cart = json_decode($cartDb->cart_detail, true);
        }
    }

    /**
     * @param string $logisticId
     * @param string $date
     * @param int|null $deliveryBetweenStart
     * @param int|null $deliveryBetweenEnd
     * @param int|null $address_id
     * @param string|null $title
     * @param string|null $province
     * @param string $city
     * @param string $address
     * @param string|null $postal_code
     * @param string|null $phone
     * @param string|null $mobile
     * @return bool
     */
    public function addDelivery(string $logisticId, string $date, ?int $deliveryBetweenStart, ?int $deliveryBetweenEnd, ?int $address_id, ?string $title, ?string $province, string $city, string $address, ?string $postal_code, ?string $phone, ?string $mobile): bool
    {
        $this->cart['delivery'] = [
            'logisticId' => $logisticId,
            'date' => $date,
            'deliveryBetweenStart' => $deliveryBetweenStart,
            'deliveryBetweenEnd' => $deliveryBetweenEnd,
            'title' => $title,
            'address_id' => $address_id,
            'province' => $province,
            'city' => $city,
            'address' => $address,
            'postal_code' => $postal_code,
            'phone' => $phone,
            'mobile' => $mobile
        ];
        $this->dbSet();
        return true;
    }

    public function getDelivery(): null|array
    {
        return $this->cart['delivery'] ?? null;
    }

    /**
     * @param mixed $key
     * @return bool
     */
    private function has(mixed $key): bool
    {
        $productKey = is_string($key) ? $key : $this->productKey($key);
        return isset($this->cart['items'][$productKey]);
    }

    /**
     * @param mixed $key
     * @return array|bool
     */
    private function get(mixed $key): array|bool
    {
        $productKey = is_string($key) ? $key : $this->productKey($key);
        if (isset($this->cart['items'][$productKey])) {
            return $this->cart['items'][$productKey];
        }

        return false;
    }

    /**
     * @param array $value
     * @param Model|null $obj
     * @return void
     */
    private function put(array $value, Model $obj = null): void
    {
        if ($obj instanceof Model) {
            $value = array_merge($value, [
                'id' => null,
                'subject_id' => $obj->id,
                'subject_type' => get_class($obj),
                'discount_value' => 0
            ]);
        }

        $key = $this->productKey($value);
        $value['id'] = $key;

        $this->cart['items'][$key] = $value;

        $this->dbSet();
    }

    /**
     * @param mixed $key
     * @param int|array $options
     * @return void
     */
    private function update(mixed $key, int|array $options): void
    {
        $item = $this->get($key);

        if (is_numeric($options)) {
            $item['quantity'] = $item['quantity'] + $options;
        }

        if (is_array($options)) {
            $item = array_merge($item, $options);
        }

        $this->put($item);
    }

    /**
     * @param mixed $key
     * @return int
     */
    private function count(mixed $key): int
    {
        if (!$this->has($key)) return 0;

        return $this->get($key)['quantity'];
    }

    /**
     * @param array $item
     * @return array
     */
    private function withRelationshipIfExist(array $item): array
    {
        if (isset($item['subject_id']) && isset($item['subject_type'])) {
            $class = $item['subject_type'];
            $subject = (new $class())->find($item['subject_id']);
            $item['product'] = optional($subject)->getAttributes();

            unset($item['subject_id']);
            unset($item['subject_type']);
        }

        return $item;
    }

    /**
     * @param array $item
     * @param array $discountCheck
     * @param array $saleCheck
     * @return array
     */
    private function checkDiscountForEachProduct(array $item, array $discountCheck, array $saleCheck): array
    {
        $isDiscountEligible = $discountCheck['status'] &&
            (empty($discountCheck['product_with_discount']) || in_array($item['product']['id'], $discountCheck['product_with_discount']));

        $isSaleEligible = $saleCheck['status'] &&
            (empty($saleCheck['product_with_sale']) || in_array($item['product']['id'], $saleCheck['product_with_sale']));


        if ($isSaleEligible) {
            $saleTypePercent = $saleCheck['sale_type'] === 'percent';
            $item['sale_price'] = $saleTypePercent
                ? $item['product']['price'] * $saleCheck['sale_value'] / 100
                : $saleCheck['sale_value'];
        }


        if ($isDiscountEligible) {
            $discountTypePercent = $discountCheck['discount_type'] === 'percent';
            if ($discountTypePercent) {
                $item['discount_price'] = $item['product']['price'] * $discountCheck['discount_value'] / 100;
            } else {
                $item['discount_price'] = $discountCheck['discount_value'];
            }
        }

        $item['final_unit_price'] = $item['product']['price']
            - ($item['discount_price'] ?? 0)
            - ($item['sale_price'] ?? 0);

        return $item;
    }

    /**
     * @return void
     */
    private function dbSet(): void
    {
        $key = Auth::check() ? $this->dbCartKey : $this->cartKey;
        Redis::set($key, json_encode($this->cart));

        if (Auth::check()) {
            CartJob::dispatch(json_encode($this->cart), Auth::user());
        }
    }

    /**
     * @return void
     */
    private function redisGet(): void
    {
        $cartDb = json_decode(Redis::get($this->dbCartKey), true);
        $cartRedis = $this->cartKey === $this->dbCartKey ? null : json_decode(Redis::get($this->cartKey), true);

        $cart = $cartRedis;
        $changeFlag = true;

        if (!empty($cartDb['items'])) {
            if (!empty($cart['items'])) {
                foreach ($cartDb['items'] as $key => $dbItem) {
                    if (isset($cart['items'][$key])) {
                        $cart['items'][$key]['quantity'] += $dbItem['quantity'];
                    } else {
                        $cart['items'][$key] = $dbItem;
                    }
                }
            } else {
                $cart = $cartDb;
                $changeFlag = false;
            }
        }

        if ($cartRedis && Auth::check()) {
            Redis::del($this->cartKey);
        }

        $this->cart = !empty($cart) ? $cart : [
            'items' => [],
            'discount' => null,
            'delivery' => null
        ];

        if ($changeFlag) {
            $this->dbSet();
        }
    }

    /**
     * @return array
     */
    private function discountCheck(): array
    {
        $discount = $this->getDiscount();
        $productWithDiscount = [];

        if (!$discount) {
            return [
                'status' => false,
                'message' => __('general.discountInvalid')
            ];
        }

        if (!$discount->reusable) {
            $usedDiscounts = Order::where('status', Order::STATUSPAID)
                ->where('user_id', Auth::id())->where('discount_id', $discount->id)->count();
            if ($usedDiscounts) {
                return [
                    'status' => false,
                    'message' => __('general.reusableDiscountError')
                ];
            }
        }

        if (!is_null($discount->expire_at) && $discount->expire_at < now()) {
            return [
                'status' => false,
                'message' => __('general.discountInvalidTime')
            ];
        }

        if ($discount->users()->count()) {
            if (!in_array(Auth::id(), $discount->users->pluck('id')->toArray())) {
                return [
                    'status' => false,
                    'message' => __('general.discountNotAble')
                ];
            }
        }
        if ($discount->products()->count()) {
            $flag = true;

            foreach ($this->cart['items'] as $item) {
                if (in_array($item['product']['id'], $discount->products->pluck('id')->toArray())) {
                    $flag = false;
                    $productWithDiscount[] = $item['product']['id'];
                }
            }

            if ($flag) {
                return [
                    'status' => false,
                    'message' => __('general.discountNotAble')
                ];
            }
        }

        return [
            'status' => true,
            'id' => $discount->id,
            'message' => '',
            'discount_type' => $discount->type,
            'discount_value' => $discount->value,
            'product_with_discount' => $productWithDiscount,
        ];
    }

    /**
     * @return array
     */
    private function saleCheck(): array
    {
        $activeSale = Sale::where('status', Sale::STATUS_ACTIVE)->where('start_date', '<=', now())->where('end_date', '>=', now())->first();
        $activeSales = [];

        if ($activeSale) {
            foreach ($activeSale->products as $item) {
                $activeSales[] = $item->pivot->saleable_id;
            }
        }

        return [
            'status' => !empty($activeSale),
            'id' => $activeSale->id ?? null,
            'sale_type' => $activeSale->type ?? null,
            'sale_value' => $activeSale->value ?? null,
            'product_with_sale' => $activeSales ?? null,
        ];
    }

    /**
     * @param mixed $key
     * @return string
     */
    private function productKey(mixed $key): string
    {
        if ($key instanceof Model) {
            $objectPath = get_class($key);
            $objectId = $key->id;
        } else {
            $objectPath = $key['subject_type'];
            $objectId = $key['subject_id'];
        }

        $explode = explode('\\', $objectPath);
        return strtolower(end($explode)) . '-' . $objectId;
    }
}
