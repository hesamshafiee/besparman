<?php

namespace App\Services\V1\Wallet;

use App\Events\UserNotification;
use App\Models\ChargedMobile;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\Services\V1\Esaj\EsajService;
use App\Services\V1\Financial\Financial;
use App\Services\V1\metrics\MetricsService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Prometheus\Exception\MetricsRegistrationException;

class PayWithoutCartBuilder implements Builder
{
    private Order $order;
    private User $esajUser;
    private Product $product;
    private string $mobile;
    private string $profileId;
    private string $storeName;
    private string $nationalCode;
    private string $webserviceCode;
    private string $esajPrice;
    private string $buyerPrice;
    private string $originalPrice;
    private string $esajprofit;
    private string $buyerProfit;
    private bool $fakeResponse;
    private array $ips;

    private string $takenValue;
    private string $mobileNumber;
    private bool $third_party_status;
    private array|null $third_party_info;

    private Authenticatable|user $user;

    private string $operatorType;
    private string $operatorExtId;
    private string $offerCode;
    private string $offerType;

    private string $userMobile;
    private string $operatorName;
    private bool $mainPage;
    private int|null $groupId;
    private string|null $multipleTopupId;


    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $this->initialize($data);
        $result = $this->check();
        if ($result !== true) {
            return $result;
        }

        $finalResult = null;

        DB::transaction(function () use (&$finalResult) {
            if (!$this->beforeTopUp()) {
                throw new \Exception("Pre-top-up checks failed");
            }

            $this->order->status = Order::STATUSPAID;

            if (!$this->order->save()) {
                throw new \Exception("Failed to save order");
            }

            DB::afterCommit(function () use (&$finalResult) {
                DB::transaction(function () use (&$finalResult) {
                    $this->operatorName = optional($this->product->operator)->name;
                    $operator = 'App\Services\V1\Esaj\\' . $this->operatorName;
                    $response = null;

                    $esajService = new EsajService();
                    $esajService->setGateway(new $operator());

                    $startTime = microtime(true);
                    if ($this->product->type === Product::TYPE_CELL_DIRECT_CHARGE || $this->product->type === Product::TYPE_CELL_AMAZING_DIRECT_CHARGE) {
                        $response = $esajService->topUp(
                            $this->mobileNumber,
                            $this->order->final_price,
                            $this->order->id_for_operator,
                            $this->operatorType,
                            $this->nationalCode,
                            $this->storeName,
                            $this->profileId,
                            $this->operatorExtId,
                            $this->fakeResponse
                        );

                    } elseif (!empty($this->profileId) && ($this->product->type === Product::TYPE_CELL_INTERNET_PACKAGE || $this->product->type === Product::TYPE_TD_LTE_INTERNET_PACKAGE)) {
                        $response = $esajService->topUpPackage(
                            $this->mobileNumber,
                            $this->order->final_price,
                            $this->order->id_for_operator,
                            $this->operatorType,
                            $this->nationalCode,
                            $this->storeName,
                            $this->profileId,
                            '',
                            $this->offerCode,
                            $this->offerType,
                            $this->fakeResponse
                        );
                    }

                    $executionTime = microtime(true) - $startTime;
                    $executionTimeMs = round($executionTime * 1000, 2);

                    $this->third_party_status = $response['status'] ?? false;
                    $this->third_party_info = $response;

                    $afterTransactionResponse = Financial::transactionsAfterTopUp(
                        $this->user->id,
                        $this->order->id,
                        $this->third_party_info,
                        $this->third_party_status,
                        $this->mobileNumber,
                        $this->product,
                        $this->takenValue,
                        $executionTimeMs
                    );

                    $transactionFromUser = $afterTransactionResponse['transaction'] ?? null;

                    $finalResult = [
                        'status'  => $this->third_party_status,
                        'mobile'  => $this->mobile,
                        'order_id'=> $this->order->id,
                        'price'   => $this->buyerPrice,
                        'productName' => $this->product->name,
                        'taken_value' => $this->takenValue,
                        'profit'  => $this->buyerProfit,
                        'wallet_value_after_transaction' => $transactionFromUser->wallet_value_after_transaction ?? null,
                        'transaction_id' => $transactionFromUser->id ?? null,
                        'group_charge_id'=> $transactionFromUser->group_charge_id ?? null,
                        'charged_mobile' => $transactionFromUser->charged_mobile ?? null,
                        'points'  => $this->user->points,
                    ];

                    $this->metrics($this->third_party_status);

                    if ($this->third_party_status === false) {
                        return Financial::operatorStatusFalse($this->order, $this->user);
                    }

                    Financial::handleEsajProfit(
                        $this->esajPrice,
                        $this->esajprofit,
                        $this->order,
                        $this->product,
                        $this->mainPage,
                        $this->groupId ?? null,
                        $this->third_party_info ?? null,
                        $this->third_party_status ?? null,
                        $this->multipleTopupId ?? null
                    );

                    Financial::calculateUserPoints($this->user, $this->product, $this->order);
                });
            });

            ChargedMobile::firstOrCreate([
                'user_id' => $this->user->id,
                'mobile'  => $this->mobile,
            ]);
        });

        return $finalResult ?? [
            'status' => false,
            'message' => 'post-commit step did not complete'
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    private function initialize(array $data): void
    {
        $this->product = $data['product'];
        $this->mobile = $data['mobile'];
        $this->userMobile = empty($data['userMobile']) ? $data['mobile'] : $data['userMobile'];
        $this->order = $data['order'];
        $this->webserviceCode = $data['webserviceCode'];
        $this->fakeResponse = $data['fakeResponse'];
        $this->profileId = $this->product->profile_id ?? '';
        $this->user = User::getLoggedInUserOrGetFromGivenMobile($this->userMobile);
        $profile = $this->user->profile;
        $this->storeName = $profile ? $profile->store_name : 'esaj';
        $this->nationalCode = $profile ? $profile->national_code : '';
        $this->ips = empty($profile->ips) ? [] : explode('-', $profile->ips);
        $this->esajUser = User::where('mobile', User::MOBILE_ESAJ)->first();
        $this->mobileNumber = 0 . substr($this->mobile, 2);
        $this->operatorType = $data['type'];
        $this->operatorExtId = $data['ext_id'];
        $this->offerCode = $data['offerCode'];
        $this->offerType = $data['offerType'];
        $this->originalPrice = $data['value'];
        $this->mainPage = $data['mainPage'];
        $this->groupId = $data['groupId'];
        $this->multipleTopupId = $data['multipleTopupId'];
        $this->takenValue = $data['takenValue'] ?? $this->originalPrice;
    }


    /**
     * @return array|true
     */
    private function check(): array|true
    {
        if ($this->product->private && !$this->user->private) {
            return Financial::cancellingOrder($this->order, 'This product can not be purchased / p');
        }

        if (!$this->product->status) {
            return Financial::cancellingOrder($this->order, 'Product is inactive');
        }

        $wallet = $this->user->wallet;
        if (!$wallet) {
            return Financial::cancellingOrder($this->order, 'Problem with wallet please contact support / e1');
        } elseif ($wallet->value < $this->order->final_price) {
            return Financial::cancellingOrder($this->order, 'Not enough money / e1');
        }

        $settingName = $this->product->sim_card_type . '_' . $this->product->type;

        if (!$this->product->operator || !$this->product->operator->status || !$this->product->operator->setting->$settingName) {
            return Financial::cancellingOrder($this->order, 'No Operator or status is inactive');
        }

        $typeStatus = $this->product->sim_card_type . '_' . $this->product->type;

        if (! (int) $this->product->operator->setting[$typeStatus]) {
            return Financial::cancellingOrder($this->order, 'Operator status is inactive');
        }

        if (!empty($this->ips) && $this->user->isWebservice() && !in_array(Request::ip(), $this->ips)) {
            return Financial::cancellingOrder($this->order, 'IP check');
        }

        return true;
    }

    /**
     * @return bool
     */
    private function beforeTopUp(): bool
    {
        $prices = Financial::calculateProfit($this->product, $this->userMobile, $this->originalPrice, $this->mainPage);
        $this->esajPrice = $prices['esaj_price'];
        $this->esajprofit = $prices['esaj_profit'];
        $this->buyerPrice = $prices['buyer_price'];
        $this->buyerProfit = $prices['buyer_profit'];

        $buyerStatus = Financial::handleBuyerTransaction(
            $this->buyerPrice,
            $this->buyerProfit,
            $this->user,
            $this->order,
            $this->originalPrice,
            $this->takenValue ?? null,
            $this->mainPage,
            $this->product,
            $this->mobile,
            $this->groupId,
            $this->webserviceCode,
            $this->multipleTopupId
        );

        if($buyerStatus) {
            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    private function notifications(): void
    {
//        Log::alert('Unsuccessful purchase for operator: {operator}', ['operator' => optional($this->product->operator)->name]);
// $this->esajUser->notify(new SmsSystem(__('sms.operatorProblem'), 'force'));
// $this->esajUser->notify(new MailSystem(__('email.operatorProblem'), 'force'));
    }

    /**
     * @param bool $thirdStatus
     * @return void
     * @throws MetricsRegistrationException
     */
    public function metrics(bool $thirdStatus): void
    {
        $registry = MetricsService::getRegistry();

        $status = $thirdStatus ? 'successful' : 'unsuccessful';

        $registry->getOrRegisterCounter(
            'esaj',
            $status . '_purchases_total',
            'Total number of successful purchases'
        )->inc();

        $registry->getOrRegisterCounter(
            'esaj',
            $status . '_purchases_' . $this->operatorName,
            'Total number of ' . $status . ' purchases for ' . $this->operatorName
        )->inc();

        $registry->getOrRegisterCounter(
            'esaj',
            $status . '_purchases_' . $this->operatorName . '_detail',
            'Total number of ' . $status . ' purchases for ' . $this->operatorName,
            ['product_type']
        )->inc([$this->product->type]);
    }
}
