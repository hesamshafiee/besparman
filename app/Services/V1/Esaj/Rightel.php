<?php

namespace App\Services\V1\Esaj;

use App\Models\Product;

class Rightel implements Gateway
{
    const URL_LIST_PACKAGES = 'http://185.24.139.24:80/rightel_topup/api/c2s-rest-receiver/chcgenreq';
    const URL_GET_CHARGE = 'http://185.24.139.24:80/rightel_topup/api/c2s-rest-receiver/rctrf';
    const URL_GET_PACKAGE = 'http://185.24.139.24:80/rightel_topup/api/c2s-rest-receiver/vrctrf';
    const URL_GET_BALANCE_ENQUIRY = 'http://185.24.139.24:80/rightel_topup/api/c2s-rest-receiver/exusrbalreq';
    const URL_GET_INQUIRY_CHARGE  = 'http://185.24.139.24:80/rightel_topup/api/c2s-rest-receiver/c2strfenq';
    const USER = 'safir';
    const PASSWORD = '123@esaj';
    const EXTERNAL_CODE = '1127';
    const MSISDN  = '9028000024';
    const PIN  = '2024';
    const CURLOPT_TIMEOUT = 90;
    const TYPE_NORMAL  = '1';
    const TYPE_AMAZING = '2';
    const STATUS_SUCCESS = true;
    const STATUS_FAILED  = false;


    private string $mobileNumber;
    private string $order_id;
    private string $profile_id;
    private string $type;
    private string $national_code;
    private string $ext_id;

    private int $price;

    private $params  = [];
    private $result  = [];
    private $errors  = [];

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
        string $ext_id = '',

    ): array {

        $this->mobileNumber = $mobileNumber;
        $this->order_id = $order_id;
        $this->price = $price;
        $this->type = $type;
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
     * @param string $ext_id .
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
        $this->national_code = $national_code;
        $this->ext_id = $ext_id;
        return $this->execute("package");
    }

    /**
     * @return array
     */
    public function packageList(): array
    {
        $response = $this->execute("listPackages");

        return $response;
    }

    public function getRemaining(): array
    {
        $result = [];
        $rightelArr = $this->execute('balanceEnquiry');

        $result['rightel'] = $rightelArr['balance'];
        return $result;
    }


    private function execute(string $type): array
    {


        if ($type == 'charge') {
            $this->set_topup_params();
            $result = $this->call(self::URL_GET_CHARGE);


            if (isset($result->status) && $result->status == '1') {

                if (isset($result->dataObject->txnstatus) && $result->dataObject->txnstatus == '200') {

                    return $this->set_result_topup($result, self::STATUS_SUCCESS);
                } else {
                    return $this->set_result_topup($result, self::STATUS_FAILED);
                }
            } elseif ((isset($result->curl_has_error) && $result->curl_has_error == 1) || (isset($result->dataObject->errorcode) && $result->dataObject->errorcode == '250')) {

                $this->inquiry_topup();
                $result = $this->call(self::URL_GET_INQUIRY_CHARGE);

                if (isset($result->status) && $result->status == '1') {

                    if (isset($result->dataObject->message) && strpos($result->dataObject->message, 'transfer status:SUCCESS') !== false) {
                        return $this->set_result_topup($result, self::STATUS_SUCCESS);
                    }
                }
            }
        } elseif ($type == 'package') {

            $this->set_package_topup_params();



            $result = $this->call(self::URL_GET_PACKAGE);


            if (isset($result->status) && $result->status == '1') {

                if (isset($result->dataObject->txnstatus) && $result->dataObject->txnstatus == '200') {
                    return $this->set_result_topup($result, self::STATUS_SUCCESS);
                } else {
                    return $this->set_result_topup($result, self::STATUS_FAILED);
                }
            } elseif ((isset($result->curl_has_error) && $result->curl_has_error == 1) || (isset($result->dataObject->errorcode) && $result->dataObject->errorcode == '250')) {

                $this->inquiry_topup();
                $result_inquiry = $this->call(self::URL_GET_INQUIRY_CHARGE);

                if (isset($result_inquiry->status) && $result_inquiry->status == '1') {

                    if (isset($result_inquiry->dataObject->message) && strpos($result_inquiry->dataObject->message, 'transfer status:SUCCESS') !== false) {
                        return $this->set_result_topup($result_inquiry, self::STATUS_SUCCESS);
                    } else {
                        return $this->set_result_topup($result_inquiry, self::STATUS_FAILED);
                    }
                } else {
                    return $this->set_result_topup($result_inquiry, self::STATUS_FAILED);
                }
            }
        } elseif ($type == 'balanceEnquiry') {
            $this->set_balance_enquiry_params();
            $result = $this->Call(self::URL_GET_BALANCE_ENQUIRY);
            return $this->set_result_get_remining($result);
        } elseif ($type == 'listPackages') {
            $this->set_list_packages_params();
            $result = $this->call(self::URL_LIST_PACKAGES);
            return (array) ($result->dataObject->details ?? []);
        } else {
            $this->result['status']        = false;
            $this->result['message']        = '';
        }
        return $this->result;
    }

    private  function set_package_topup_params()
    {

        $data = array(
            "DATE"      => "",
            "extnwcode" => "IR",
            "msisdn"    => self::MSISDN,
            "loginid"   => self::USER,
            "password"  => self::PASSWORD,
            "pin"       => self::PIN,
            "extcode"   => "",
            "extrefnum" => "$this->order_id",
            "msisdn2"   => "$this->mobileNumber",
            "amount"    => "VAS_BLNK_AMT",
            "selector"  => "$this->profile_id",
            "cellId"    => "",
            "switchId"  => "",
            "language1" => "",
            "language2" => "",
            "info1"     => "",
            "info2"     => "",
            "info3"     => "",
            "info5"     => ""
        );
        $this->params = array(
            "reqGatewayLoginId"  => "pretups",
            "reqGatewayPassword" => "1357",
            "reqGatewayCode"     => "REST",
            "reqGatewayType"     => "REST",
            "servicePort"        => "190",
            "sourceType"         => "JSON",
        );

        $this->params['data'] = $data;
    }

    private  function set_topup_params()
    {

        $data = array(
            "DATE"      => "",
            "extnwcode" => "IR",
            "msisdn"    => self::MSISDN,
            "pin"       => self::PIN,
            "loginid"   => self::USER,
            "password"  => self::PASSWORD,
            "extcode"   => self::EXTERNAL_CODE,
            "extrefnum" => "$this->order_id",
            "msisdn2"   => "$this->mobileNumber",
            "amount"    => "$this->price",
            "selector"  => "$this->type",
            "language1" => "",
            "language2" => "",
            "cellId"    => "",
            "switchId"  => "",
            "info2"     => "",
            "info3"     => "",
            "info4"     => "",
            "info5"     => ""
        );


        $this->params = array(
            "reqGatewayLoginId"  => "pretups",
            "reqGatewayPassword" => "1357",
            "reqGatewayCode"     => "REST",
            "reqGatewayType"     => "REST",
            "servicePort"        => "190",
            "sourceType"         => "JSON",
        );
        $this->params['data'] = $data;
    }

    private function Call($url)
    {

        $ch = curl_init();

        $payload = json_encode($this->params);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type:application/json',
            )
        );

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::CURLOPT_TIMEOUT);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $errors['curl_has_error'] = 1;
            $errors['error_message'] = curl_error($ch);
            $errors['curl_errno'] = curl_errno($ch);
            return (object) $errors;
        }

        $text_res_for_jason = str_replace("'", "\"", $result);
        $res = json_decode($text_res_for_jason);

        curl_close($ch);
        return $res;
    }

    public function set_list_packages_params()
    {
        $this->params['data'] = array(
            "DATE" => "",
            "extnwcode" => "IR",
            "msisdn" => self::MSISDN,
            "pin" => self::PIN,
            "extcode" => self::EXTERNAL_CODE,
            "servicetype" => "VAS"
        );
    }
    public function set_balance_enquiry_params()
    {
        $extrefnum =  'gr' . uniqid();


        $data = array(
            "date" => "",
            "extnwcode" => "IR",
            "msisdn" => self::MSISDN,
            "pin" => self::PIN,
            "loginid" => self::USER,
            "password" => self::PASSWORD,
            "extcode" => self::EXTERNAL_CODE,
            "extrefnum" => "$extrefnum",
            "language1" => "1"
        );


        $this->params = array(
            "reqGatewayLoginId"  => "pretups",
            "reqGatewayPassword" => "1357",
            "reqGatewayCode"     => "REST",
            "reqGatewayType"     => "REST",
            "servicePort"        => "190",
            "sourceType"         => "JSON",
        );
        $this->params['data'] = $data;
    }
    public function inquiry_topup($transactionid = '')
    {

        $data = array(
            "DATE"      => "",
            "extnwcode" => "IR",
            "msisdn"    => self::MSISDN,
            "loginid"   => self::USER,
            "password"  => self::PASSWORD,
            "pin"       => self::PIN,
            "extcode"   => self::EXTERNAL_CODE,
            "extrefnum" => "$this->order_id",
            "transactionid"     => "$transactionid",
            "language1" => ""
        );

        $this->params['data'] = $data;
    }

    private function set_result_topup($data, $status)
    {

        $this->errors['errorCode']     = isset($data->curl_errno) ? (int)$data->curl_errno : '';
        $this->errors['message']       = isset($data->error_message) ? (string)$data->error_message : '';

        $this->result['errorCode']     = isset($data->dataObject->errorcode) ? (string)$data->dataObject->errorcode : $this->errors['errorCode'];
        $this->result['message']       = isset($data->dataObject->message) ? (string)$data->dataObject->message : $this->errors['message'];
        $this->result['transactionId'] = isset($data->dataObject->txnid) ? (string)$data->dataObject->txnid : '';
        $this->result['doc_num']       = isset($data->dataObject->extrefnum) ? (string)$data->dataObject->extrefnum : '';
        $this->result['status']        = $status;
        $this->result['provider']        = 'rightel';

        return $this->result;
    }


    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return array
     */
    private function set_result_get_remining($data): array
    {
        $result = ['balance' => null];

        if (is_object($data) && isset($data->status) && $data->status === true && isset($data->dataObject->message)) {
            $message = $data->dataObject->message;
            if (preg_match('/balance of user \d+ is (\d+)/i', $message, $matches)) {
                $result['balance'] = (int)$matches[1];
            }
        }

        return $result;
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
