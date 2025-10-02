<?php

namespace App\Services\V1\Esaj;

use App\Models\Operator;
use App\Models\SessionOperator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class Mci implements Gateway
{
    const URL_TOKEN_AUTH = "https://mapi.kianiranian.com/v1.0/auth/token";
    const URL_CHECK_TOPUP = "https://mapi.kianiranian.com/v1.0/mci/top-up/check";
    const URL_CONFIRM_TOPUP = "https://mapi.kianiranian.com/v1.0/mci/top-up/confirm";

    const URL_CHECK_TOPUP_PACKAGE = "https://mapi.kianiranian.com/v1.0/mci/internet-package/check";
    const URL_CONFIRM_TOPUP_PACKAGE = "https://mapi.kianiranian.com/v1.0/mci/internet-package/confirm";
    const REFRESH_TOKEN_IGAP = "7ac81f9e-3826-4439-a4a7-04e05d2b2905";

    const WEBSERVICE_TOKEN_URL_ACCESS = 'https://edge.live.radintlm.ir/api/Auth/Login';
    const USERNAME = 'esaj';
    const PASSWORD = 'EjiR@MBADsaZi';
    const PARTY = 'EsajIR';
    const PROVIDER_RADINI = 'radin';
    const PROVIDER_IGAP = 'igap';

    private string $mobileNumber;
    private string $profile_id;
    private string $order_id;

    private int $price;
    private string $provider;

    private string $session_id;
    private bool $is_pre_paid = true;

    public function __construct()
    {
    }

    /**
     * @param string $mobileNumber
     * @param int $price
     * @param string $order_id
     * @param string $type
     * @param string $national_code
     * @param string $store_name
     * @param string $profile_id
     * @param string $ext_id
     * @return array
     */
    public function topUp(
        string $mobileNumber,
        int $price,
        string $order_id,
        string $type,
        string $national_code,
        string $store_name,
        string $profile_id = '',
        string $ext_id = ''
    ): array {
        $parts = explode("|", $profile_id);
        $this->order_id = $order_id;
        $this->price = $price;
        $this->selectingProvider('charge'); 
        if ($this->provider === self::PROVIDER_IGAP) {
            $this->profile_id = $parts[0] ?? '';
            $this->mobileNumber = $mobileNumber;
            return $this->execute("charge");
        } else {
            $this->profile_id = $parts[1] ?? '';
            $this->mobileNumber = $mobileNumber;
            return $this->executeRadin("charge");
        }
    }

    /**
     * @param string $mobileNumber
     * @param int $price
     * @param string $order_id
     * @param string $type
     * @param string $national_code
     * @param string $store_name
     * @param string $profile_id
     * @param string $ext_id
     * @param string $offerCode
     * @param string $offerType
     * @return array
     */
    public function topUpPackage(
        string $mobileNumber,
        int $price,
        string $order_id,
        string $type,
        string $national_code,
        string $store_name,
        string $profile_id,
        string $ext_id = '',
        string $offerCode = '',
        string $offerType = '',
    ): array {
        $parts = explode("|", $profile_id);
        $this->order_id = $order_id;
        $this->price = $price;
        $this->selectingProvider('package');
        if ($this->provider === self::PROVIDER_IGAP) {
            $this->profile_id = $parts[0] ?? '';
            $this->mobileNumber = $mobileNumber;
            return $this->execute("package");
        } else {
            $this->profile_id = $parts[1] ?? '';
            $this->mobileNumber = $mobileNumber;
            return $this->executeRadin("package");
        }
    }

    public function getRemaining(): array
    {
        $res = [];

        $radin = $this->executeRadin("balanceEnquiry");
        $igap = $this->executeIgapRemining("package");

        $res['MCIPackagesRadin'] = $radin['MCIPackagesRadin'];
        $res['MCIChargeRadin'] = $radin['MCIChargeRadin'];
        $res['MCIIgap'] = $igap['MCIIgap'];

        return $res;
    }

    /**
     * @return array
     */
    public function packageList(): array
    {
        return [];
    }

    /**
     * @return mixed
     */
    private function ws_auth(): mixed
    {
        $url = self::URL_TOKEN_AUTH;
        $REFRESH_TOKEN_IGAP = env('MCI_REFRESH_TOKEN_IGAP', self::REFRESH_TOKEN_IGAP);
        $ch = curl_init();
        $payload = json_encode(array("refresh_token" => $REFRESH_TOKEN_IGAP));

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $response = curl_exec($ch);
        $text_res_for_jason = str_replace("'", "\"", $response);
        $res = json_decode($text_res_for_jason);

        return $res;
    }

    /**
     * @param string $token
     * @param string $mobileNumber
     * @param string $tel_charger
     * @param int $amount
     * @param string $charge_type
     * @return mixed
     */
    private function ws_check_charge_direct(string $token, string $mobileNumber, string $tel_charger, int $amount, string $charge_type): mixed
    {

        $url = self::URL_CHECK_TOPUP;

        $payload = json_encode(
            array(
                "tel_num" => $mobileNumber,
                "tel_charger" => $mobileNumber,
                "amount" => $amount,
                "charge_type" => $charge_type,
            )
        );

        return $this->connectingToMci($token, $payload, $url);
    }


    private function ws_get_igap_get_remining(string $token): mixed
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://mapi.kianiranian.com/v1.0/client/balance',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token"
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        $result = [];
        $result['MCIIgap'] = null;
        if (isset($data['balance'])) {
            $result['MCIIgap'] = (int)$data['balance'];
        } else {
            $result['MCIIgap'] = null;
        }

        return $result;
    }



    /**
     * @param string $token
     * @param string $provider_id
     * @return mixed
     */
    private function ws_get_charge_direct_providerID(string $token, string $provider_id): mixed
    {
        $url = self::URL_CONFIRM_TOPUP;

        $payload = json_encode(
            array(
                "provider_id" => $provider_id,
            )
        );

        return $this->connectingToMci($token, $payload, $url);
    }

    /**
     * @param string $token
     * @param string $tel_num
     * @param string $tel_charger
     * @param string $package_type
     * @return mixed
     */
    private function ws_check_packages_internet(string $token, string $tel_num, string $tel_charger, string $package_type): mixed
    {
        $url = self::URL_CHECK_TOPUP_PACKAGE;

        $payload = json_encode(
            array(
                "tel_num" => $tel_num,
                "tel_charger" => $tel_charger,
                "package_type" => $package_type,
            )
        );

        return $this->connectingToMci($token, $payload, $url);
    }

    /**
     * @param string $token
     * @param string $provider_id
     * @return mixed
     */
    private function ws_get_package_internet_providerID(string $token, string $provider_id): mixed
    {
        $url = self::URL_CONFIRM_TOPUP_PACKAGE;

        $payload = json_encode(
            array(
                "provider_id" => $provider_id,
            )
        );

        return $this->connectingToMci($token, $payload, $url);
    }

    /**
     * @param string $token
     * @param $payload
     * @param string $url
     * @return mixed
     */
    private function connectingToMci(string $token, $payload, string $url): mixed
    {
        $ch = curl_init();
        $authorization = "Authorization: Bearer " . $token;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        $result = curl_exec($ch);

        $text_res_for_json = str_replace("'", "\"", $result);
        return json_decode($text_res_for_json);
    }

    /**
     * @param string $type
     * @return array
     */
    private function execute(string $type): array
    {
        $result_provider = $this->mci($type);
        if ($result_provider) {
            if (isset($result_provider->provider_id)) {
                $result = $this->mci('get-' . $type, $result_provider->provider_id);
                if (isset($result->done) && $result->done) {
                    $data_result['status'] = true;
                    $data_result['transactionID'] = $result_provider->provider_id;
                    $data_result['message'] = $result->message;
                } else {
                    $data_result['status'] = false;
                    $data_result['transactionID'] = $result_provider->provider_id;
                    $data_result['message'] = $result->message ?? "Operation Mci Charge Unsuccessful";
                }
            } else {
                $data_result['status'] = false;
                $data_result['transactionID'] = '';
                $data_result['message'] = $result->message ?? "Operation Mci Get ProviderID Unsuccessful";
            }
            $data_result['mci'] = $result_provider;
        } else {
            $data_result['status'] = false;
            $data_result['transactionID'] = '';
            $data_result['message'] = $result->message ?? "Operation Mci Auth Unsuccessful";
        }
        $data_result['saleID'] = $this->order_id;
        $data_result['provider'] = 'iGap';

        return $data_result;
    }



    private function executeIgapRemining(): array
    {
        $result_auth = $this->ws_auth();
        if (isset($result_auth->access_token)) {
            $token = $result_auth->access_token;
            return $this->ws_get_igap_get_remining($token);
        }
        $result['MCIIgap'] = null;
        return $result;
    }

    /**
     * @param string $type
     * @param string $provider_id
     * @return mixed
     */
    private function mci(string $type, string $provider_id = ''): mixed
    {
        $result_auth = $this->ws_auth();

        if (isset($result_auth->access_token)) {
            $token = $result_auth->access_token;
            if ($type === "charge") {
                $charge_type = "DIRECT";
                return $this->ws_check_charge_direct($token, $this->mobileNumber, $this->mobileNumber, $this->price, $charge_type);
            } elseif ($type === "get-charge") {
                return $this->ws_get_charge_direct_providerID($token, $provider_id);
            } elseif ($type === "package") {
                return $this->ws_check_packages_internet($token, $this->mobileNumber, $this->mobileNumber, $this->profile_id);
            } elseif ($type === "get-package") {
                return $this->ws_get_package_internet_providerID($token, $provider_id);
            }
        }

        return false;
    }

    /**
     * @param string $orderId
     * @return string
     */
    public function checkStatus(string $orderId): string
    {
        return 'error';
    }

    /**
     * @param string $type
     * @return array
     */
    private function executeRadin(string $type): array
    {
        $data_result = [];
        $Token = $this->radinToken();
        if ($type === "charge") {
            $result = $this->doTopup($Token);
            $data_result['status'] = isset($result->Status) && $result->Status === 'Ok';
            $data_result['resultCode'] = $result->Code ?? '';
            $data_result['message'] = $result->Result->Description ?? '';
            $data_result['ResponseId'] = '';
            $data_result['RequestId'] = $this->order_id ?? '';
            $data_result['saleCode'] = $this->order_id ?? '';
        } elseif ($type === "package") {
            $result = $this->doTopupPackage($Token);
            $data_result['status'] = isset($result->Status) && $result->Status === 'Ok';
            $data_result['resultCode'] = $result->Code ?? '';
            $data_result['message'] = $result->Result->Description ?? '';
            $data_result['ResponseId'] = '';
            $data_result['RequestId'] = $this->order_id ?? '';
            $data_result['saleCode'] = $this->order_id ?? '';
        } elseif ($type === "balanceEnquiry") {
            $data_result = $this->getRemining($Token);
        }
        $data_result['provider'] = 'radin';
        return $data_result;
    }

    private function radinToken()
    {
        $this->session_id = SessionOperator::getSession_id('mci-radin',120);
        if ($this->session_id  == '') {
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => self::WEBSERVICE_TOKEN_URL_ACCESS,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                "Username": "' . self::USERNAME . '",
                "Password": "' . self::PASSWORD . '",
                "APIKEY": ""
            }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
                CURLOPT_SSL_VERIFYPEER => false
            ));

            $response = curl_exec($curl);
            $result = json_decode($response);

            curl_close($curl);
            $Token = isset($result->Token) ? $result->Token : '';
            $this->session_id = $Token;
            


            $sessionOperator = new SessionOperator;
            $sessionOperator->session = $this->session_id;
            $sessionOperator->operator = 'mci-radin';
            $sessionOperator->save();

            return $Token;
        }
        return $this->session_id;
    }

    private function getRemining($Token)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://edge.live.radintlm.ir/api/TopUp/GetBalances',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Accept: text/plain',
                'Content-Type: text/json ',
                'Authorization: Bearer ' . $Token
            ),
            CURLOPT_SSL_VERIFYPEER => false
        ));

        $response = curl_exec($curl);
        $response = json_decode($response);

        if (
            !is_object($response) ||
            !isset($response->Completed) || $response->Completed !== true ||
            !isset($response->Result->Debit)
        ) {
            return [
                'MCIPackagesRadin' => null,
                'MCIChargeRadin'   => null
            ];
        }

        $debit = $response->Result->Debit;

        return [
            'MCIPackagesRadin' => $debit->MCIPackages ?? 0,
            'MCIChargeRadin'   => $debit->MCICharge ?? 0
        ];
    }



    private function doTopup($Token)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://edge.live.radintlm.ir/api/TopUp/RequestOrder',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "Party": "' . self::PARTY . '",
                "Issuer": "' . $this->mobileNumber . '",
                "Consumer": "' . $this->mobileNumber . '",
                "Operator": "MCI",
                "Product": "8",
                "ProductType": "charge",
                "Origin": "Portal",
                "UniqueEventID": "",
                "AbsoluteAmount": "' . $this->price . '"
            }',
            CURLOPT_HTTPHEADER => array(
                'Accept: text/plain',
                'Content-Type: text/json ',
                'Authorization: Bearer ' . $Token
            ),
            CURLOPT_SSL_VERIFYPEER => false
        ));

        $response = curl_exec($curl);
        $result = json_decode($response);

        if (isset($result->Status) && $result->Status == 'Ok') {
            $ws_order_id = isset($result->Result->OrderID) ? $result->Result->OrderID : 0;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://edge.live.radintlm.ir/api/TopUp/ConfirmOrder',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "Party": "' . self::PARTY . '",
                    "rrn": "' . $this->order_id . '",
                    "OrderID": "' . $ws_order_id . '",
                    "BankCode": "0"
                }',
                CURLOPT_HTTPHEADER => array(
                    'Accept: text/plain',
                    'Content-Type: text/json ',
                    'Authorization: Bearer ' . $Token
                ),
                CURLOPT_SSL_VERIFYPEER => false
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }

        return $result;
    }

    private function doTopupPackage($Token)
    {

        $price =(10*$this->price)/11;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://edge.live.radintlm.ir/api/TopUp/RequestOrder',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "Party": "' . self::PARTY . '",
                "Issuer": "' . $this->mobileNumber . '",
                "Consumer": "' . $this->mobileNumber . '",
                "Operator": "MCI",
                "Product": "' . $this->profile_id . '",
                "ProductType": "package",
                "Origin": "Portal",
                "UniqueEventID": "",
                "AbsoluteAmount": "' . $price . '"
            }',
            CURLOPT_HTTPHEADER => array(
                'Accept: text/plain',
                'Content-Type: text/json ',
                'Authorization: Bearer ' . $Token
            ),
            CURLOPT_SSL_VERIFYPEER => false
        ));

        $response = curl_exec($curl);
        $result = json_decode($response);

        if (isset($result->Status) && $result->Status == 'Ok') {
            $ws_order_id = isset($result->Result->OrderID) ? $result->Result->OrderID : 0;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://edge.live.radintlm.ir/api/TopUp/ConfirmOrder',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "Party": "' . self::PARTY . '",
                    "rrn": "' . $this->order_id . '",
                    "OrderID": "' . $ws_order_id . '",
                    "BankCode": "0"
                }',
                CURLOPT_HTTPHEADER => array(
                    'Accept: text/plain',
                    'Content-Type: text/json ',
                    'Authorization: Bearer ' . $Token
                ),
                CURLOPT_SSL_VERIFYPEER => false
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            return json_decode($response);
        }

        return $result;
    }

    /**
     * Selects the appropriate provider based on configuration and counter values.
     *
     * @return void
     */
    private function selectingProvider(string $type = 'charge'): void
    {
        $providerKey = "mci_provider_{$type}";
        $counterKey  = "mci_counter_{$type}";

        $this->provider = Redis::get($providerKey) ?? self::PROVIDER_IGAP;
        $operator = Operator::where('name', 'Mci')->first();

        if (!$operator || empty($operator->setting['radin_status'])) {
            $this->provider = self::PROVIDER_IGAP;
            return;
        }


        if ($type === 'charge') {
            $radinLimit = (int) ($operator->setting['radin_limit'] ?? 0);
            $igapLimit  = (int) ($operator->setting['igap_limit'] ?? 0);
        } else {
            $radinLimit = (int) ($operator->setting['radin_limit_package'] ?? 0);
            $igapLimit  = (int) ($operator->setting['igap_limit_package'] ?? 0);
        }
       
       if ($radinLimit === 0 && $igapLimit > 0) {
            $this->provider = self::PROVIDER_IGAP;
            Redis::set($providerKey, $this->provider);
            return;
        }
        if ($igapLimit === 0 && $radinLimit > 0) {
            $this->provider = self::PROVIDER_RADINI;
            Redis::set($providerKey, $this->provider);
            return;
        }

        if ($radinLimit === 0 && $igapLimit === 0) {
            return;
        }


        $counter = (int) (Redis::get($counterKey) ?? 0);
        $limit   = $this->provider === self::PROVIDER_IGAP ? $igapLimit : $radinLimit;


        if ($counter >= $limit) {
            $this->provider = $this->provider === self::PROVIDER_IGAP 
                ? self::PROVIDER_RADINI 
                : self::PROVIDER_IGAP;
            $counter = 0;
        }

        $counter++;
        Redis::set($providerKey, $this->provider);
        Redis::set($counterKey, $counter);
    }
}
