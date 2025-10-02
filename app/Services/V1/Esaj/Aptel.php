<?php

namespace App\Services\V1\Esaj;

use App\Models\SessionOperator;

class Aptel implements Gateway
{

    const WEBSERVICE_TOKEN_URL_ACCESS = 'https://services.negintel.com/public/Security/CreateTokenByUser';

    const USERNAME = 'isaj';
    const PASSWORD = 'biKVLn!h,tH?P4cuibv#8:';

    const NUMBER_PRE_PAID_SAMPLE = '9991413143';
    const NUMBER_POST_PAID_SAMPLE = '9991079009';

    private string $mobileNumber;
    private string $profile_id;
    private string $order_id;
    private string $session_id;
    private bool $is_pre_paid = true;
    private int $price;

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
        $this->mobileNumber = $mobileNumber;
        $this->profile_id = $profile_id;
        $this->order_id = $order_id;
        $this->price = $price;
        return $this->execute("charge");
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
        $this->mobileNumber = $mobileNumber;
        $this->profile_id = $profile_id;
        $this->order_id = $order_id;
        $this->price = $price;
        return $this->execute("package");
    }


    /**
     * @return mixed
     */
    public function packageList(): array
    {
        $this->is_pre_paid = true;
        $response = $this->execute("packagesList");
        $response = array($response);

        return $response;
    }


    public function getRemaining(): array
    {
        $response = $this->execute("balanceEnquiry");
        return $response;
    }


    /**
     * @param string $type
     * @return array
     */
    private function execute(string $type): mixed
    {
        $result_provider = $this->aptel($type);
        return $result_provider;
    }

    private function aptel(string $type, string $provider_id = ''): mixed
    {
        $data_result = [];
        $this->session_id = SessionOperator::getSession_id('aptel');
        if ($this->session_id  == '') {
            $this->session_id = $this->open_session_id();;
            $sessionOperator = new SessionOperator;
            $sessionOperator->session = $this->session_id;
            $sessionOperator->operator = 'aptel';
            $sessionOperator->save();
        }



        if ($type === "charge") {
            $result = $this->doTopup();
            $data_result['provider'] = 'aptel';
            $data_result['resultCode'] = $result->Code ?? '';
            $data_result['HasError']    = $result->HasError ?? '';
            $data_result['status'] = $result !== null && !$result->HasError ? true : false;
            $data_result['ResponseId'] = $result->ResponseId ?? '';
            $data_result['RequestId']  = $this->order_id;
            $data_result['saleCode']   = $this->order_id ?? '';
            return $data_result;
        } elseif ($type === "package") {
            $result = $this->doTopupPackage();
            $data_result['provider'] = 'aptel';
            $data_result['resultCode'] = $result->Code ?? '';
            $data_result['HasError']    = $result->HasError ?? '';
            $data_result['status'] = $result !== null && !$result->HasError ? true : false;
            $data_result['message']    = $result->Message ?? '';
            $data_result['ResponseId'] = $result->ResponseId ?? '';
            $data_result['RequestId']  = $this->order_id;
            $data_result['saleCode']   = $this->order_id ?? '';
            return $data_result;
        } elseif ($type === "packagesList") {
            $result = $this->getPackagesList();
            return $result;
        } elseif ($type === "balanceEnquiry") {
            $result = $this->getRemainingArr();
            return $result;
        }



        return $data_result;
    }


    private function open_session_id()
    {


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
              "password": "' . self::PASSWORD . '",
              "username": "' . self::USERNAME . '",
              "expiretimebyminute": 36000,
              "captcha": "string"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $result = json_decode($response);
        if (isset($result->Code) && $result->Code == 200) {
            return $result->Result->token;
        }
    }


    private function doTopup()
    {
        $number = substr($this->mobileNumber, 1);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://services.negintel.com/topup/DCPurchase/ReserveCharge',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
              "bankinfo": {
                "cardno": "string",
                "refrenceno": "string",
                "terminalid": "string",
                "bankname": "string"
              },
              "msisdn": ' . $number . ',
              "trackingcode": "' . $this->order_id . '",
              "chargeamount": ' . $this->price . '
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->session_id,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $result = json_decode($response);


        if (isset($result->Code) && $result->Code == 200) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://services.negintel.com/topup/DCPurchase/ConfirmReserveCharge',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                  "trackingcode": "' . $this->order_id . '",
                  "reserveid": "' . $result->Result . '"
                }',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $this->session_id,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $resultnew = json_decode($response);
            return $resultnew;
        }
    }


    private function doTopupPackage()
    {
        $number = substr($this->mobileNumber, 1);
        $curl = curl_init();


        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://services.negintel.com/topup/EPPurchase/ReservePackage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
  "bankinfo": {
    "cardno": "string",
    "refrenceno": "string",
    "terminalid": "string",
    "bankname": "string"
  },
   "msisdn": ' . $number . ',
   "trackingcode": "' . $this->order_id . '",
  "packageid": "' . $this->profile_id . '" ,
  "PaidType" : 0
}',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->session_id,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);


        $result = json_decode($response);




        if (isset($result->Code) && $result->Code == 200) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://services.negintel.com/topup/EPPurchase/ConfirmReservePackage',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                  "trackingcode": "' . $this->order_id . '",
                  "reserveid": "' . $result->Result . '"
                }',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $this->session_id,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $resultnew = json_decode($response);
            return $resultnew;
        }
    }

    private function getPackagesList()
    {


        $curl = curl_init();
        $msisdn = $this->is_pre_paid ? self::NUMBER_PRE_PAID_SAMPLE : self::NUMBER_POST_PAID_SAMPLE;

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://services.negintel.com/topup/Packages/QueryPackage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
              "msisdn": ' . $msisdn . ',
              "trackingcode": "1"
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->session_id,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);



        $result = json_decode($response);

        return $result;
    }


    private function getRemainingArr()
    {


        $curl = curl_init();
        $msisdn = $this->is_pre_paid ? self::NUMBER_PRE_PAID_SAMPLE : self::NUMBER_POST_PAID_SAMPLE;

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://services.negintel.com/topup/Wallet/QueryWallet',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
              "trackingcode": "1"
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->session_id,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);



        $response = json_decode($response);

        if (!is_object($response) || !isset($response->Result)) {
            return [
            'Aptelccwallet' => null,
            'Aptelcpwallet' => null,
        ];
        }

        return [
            'Aptelccwallet' => (float) ($response->Result->ccwalletamount ?? null),
            'Aptelcpwallet' => (float) ($response->Result->cpwalletamount ?? null),
        ];
    }

    /**
     * @param string $orderId
     * @return string
     */
    public function checkStatus(string $orderId): string
    {
        return 'error';
    }
}
