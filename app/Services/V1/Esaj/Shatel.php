<?php

namespace App\Services\V1\Esaj;

use App\Models\Product;

class Shatel implements Gateway
{

    const WEB_SERVICE_URL = 'http://79.175.181.213/api/topup' ;
    const USERNAME = 'esajnew' ;
    const PASSWORD = 'hsh1022hsh1022' ;
    const PRODUCT_WEBSERVICE = '8' ;


    private string $mobileNumber;
    private string $profile_id;
    private string $order_id;
    private string $store_name;
    private string $ext_id;
    private string $type;

    private int $price;


    private $params  = [];

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
    ): array
    {
        $this->mobileNumber = $mobileNumber;
        $this->profile_id = $profile_id;
        $this->order_id = $order_id;
        $this->ext_id = $ext_id;
        $this->price = $price;
        $this->type = $type;
        $this->store_name = $store_name;
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
    ): array
    {
        $this->mobileNumber = $mobileNumber;
        $this->profile_id = $profile_id;
        $this->order_id = $order_id;
        $this->ext_id = $ext_id;
        $this->price = $price;
        $this->type = $type;
        $this->store_name = $store_name;
        return $this->execute("package");
    }

    /**
     * @return array
     */
    public function packageList(): array
    {
        return [];
    }

    private function execute(string $type): array
    {
        $res = $this->shatel($type);
        if (isset($res->status)){
            if($res->status == 1){
                $data_result['status'] = true;
                $data_result['transactionID'] = $res->web_service_order_id;
                $data_result['message'] = $res->message;
            } else {
                $data_result['status'] = false;
                $data_result['transactionID'] = $res->web_service_order_id;
                $data_result['message'] = $res->message;
            }
        } else {
            $data_result['status'] = false;
            $data_result['transactionID'] = '';
            $data_result['message'] = $res->message ?? "Operation shatel Failed";
        }
        $data_result['saleID'] = $this->order_id;
        $data_result['provider'] = 'shatel';

        return $data_result;
    }

    /**
     * @param string $type
     * @param string $provider_id
     * @return mixed
     */
    private function shatel($type): mixed
    {
        if ($type === "charge" || $type === "package") {
            $this->setParams() ;
            $res = $this->doShatelTopup() ;
            return json_decode($res);
        } else {
            return [];
        }
    }

    private function setParams(){
        $this->params = [
            'username' => self::USERNAME ,
            'password' => self::PASSWORD,
            'MobileNumber' => $this->mobileNumber ,
            'price' => $this->price ,
            'product' => self::PRODUCT_WEBSERVICE ,
            'order_id' => $this->order_id ,
            'type' => (int) $this->type ,
            'profile_id' => $this->profile_id ,
            'ext_id' => $this->ext_id ,
            'national_code' => '' ,
            'store_name' => $this->store_name ,
        ];


    }

    private function doShatelTopup(){
        $client = new \SoapClient(self::WEB_SERVICE_URL);
        $res = $client->__soapCall('getTopup', $this->params) ;
        return $res ;
    }

    /**
     * @param string $orderId
     * @return string
     */
    public function checkStatus(string $orderId): string
    {
        return 'error';
    }

    public function getRemaining(): array
    {
        return ['Shatel'=>null];
    }
}
