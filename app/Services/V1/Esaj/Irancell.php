<?php

namespace App\Services\V1\Esaj;

use App\Models\IrancellOfferPackage;
use Illuminate\Support\Str;
use SoapClient;

class Irancell implements Gateway
{

    const CLIENT_ID = '989331142071';
    const PASSWORD = '39098';
    const VENDOR_MSISDN = '989331142071';
    const PIN = '61790'; //'49364';

    const WEBSERVICE_URL_INTERNET = 'https://erefill.mtnirancell.ir:6039/erefill_bl/GenericService';
    const WEBSERVICE_URL          = 'https://erefill.mtnirancell.ir:6039/erefill_bl/ETIBankService';
    private $webservice = '';
    private array $result;

    private string $mobileNumber;
    private string $profile_id;
    private string $order_id;
    private string $national_code;
    private string $store_name;
    private string $function_name;
    private string $offer_code;
    private string $offer_type;
    private string $category;
    private string $ext_id;

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
     * @param string $offer_code
     * @param string $offer_type
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
        string $offer_code = '',
        string $offer_type = ''
    ): array {
        if ($type == 4) {
            $this->function_name = 'PAYBILL';
        } else {
            $this->function_name = 'TOPUP';
        }

        $this->mobileNumber = $mobileNumber;
        $this->profile_id = $profile_id;
        $this->order_id = $order_id;
        $this->price = $price;
        $this->national_code = $national_code;
        $this->store_name = $store_name;
        $this->offer_code = $offer_code;
        $this->offer_type = $offer_type;
        $this->ext_id = $ext_id;

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
        string $offerType = ''
    ): array {
        $this->mobileNumber = $mobileNumber;
        $this->profile_id = $profile_id;
        $this->order_id = $order_id;
        $this->price = $price;
        $this->national_code = $national_code;
        $this->offer_code = $offerCode;
        $this->offer_type = $offerType;
        $this->ext_id = $ext_id;
        $this->store_name = $store_name;
        return $this->execute("package");
    }

    /**
     * @param string $orderId
     * @return string
     */
    public function checkStatus(string $orderId): string
    {
        $this->order_id = $orderId;
        return $this->executeCheckStatus();
    }

    private function executeCheckStatus(): string
    {
        $this->webservice = $this->connectToService();

        $xml = $this->XmlContentForGetStatus();

        $result = $this->webservice->__doRequest($xml, self::WEBSERVICE_URL_INTERNET, '', 1);

        $resultProcessed = $this->set_result_status_transaction($result);
        return $resultProcessed['STATUS'];
    }
    public function set_result_status_transaction($result_xml)
    {
        // Enable internal error handling for malformed XML
        libxml_use_internal_errors(true);

        // Attempt to parse XML
        $xml = simplexml_load_string($result_xml);
        if (!$xml) {
            // Fallback: extract values via regex if XML is invalid or malformed
            $responseData = [];
            // Extract responsemessage
            if (preg_match('/<responsemessage>(.*?)<\/responsemessage>/is', $result_xml, $msgMatch)) {
                $message = $msgMatch[1];
            } else {
                $message = '';
            }
            // Extract commandstatus
            if (preg_match('/<commandstatus>(.*?)<\/commandstatus>/is', $result_xml, $cmdMatch)) {
                $commandStatus = $cmdMatch[1];
            } else {
                $commandStatus = null;
            }
            // Extract resultcode
            if (preg_match('/<resultcode>(.*?)<\/resultcode>/is', $result_xml, $resMatch)) {
                $resultCode = $resMatch[1];
            } else {
                $resultCode = null;
            }
            // Extract numeric flags
            preg_match('/CHECK:\s*(\d+)/', $message, $checkMatch);
            preg_match('/PROCESS:\s*(\d+)/', $message, $processMatch);
            preg_match('/NOTIFY:\s*(\d+)/', $message, $notifyMatch);

            $check = $checkMatch[1] ?? null;
            $process = $processMatch[1] ?? null;
            $notify = $notifyMatch[1] ?? null;

            // Determine status
            if ((int) $resultCode === 0 && (int) $check === 1 && (int) $process === 1) {
                $status = 'true';
            } elseif ((int) $resultCode === 24 || ((int) $resultCode === 0 && ((int) $check !== 1 || (int) $process !== 1))) {
                $status = 'false';
            } else {
                $status = 'error';
            }

            $responseData = [
                'CHECK'         => $check,
                'PROCESS'       => $process,
                'NOTIFY'        => $notify,
                'COMMANDSTATUS' => $commandStatus,
                'RESULTCODE'    => $resultCode,
                'STATUS'        => $status
            ];
        }

        return $responseData;
    }


    /**
     * @return array
     */
    public function packageList(): array
    {
        return [];
    }

    public function XmlContentForGetStatus()
    {
        $xml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.adapter.imp.erefill.sixdee.com/">

   <soapenv:Header/>

   <soapenv:Body>

      <ws:processRequest>

         <!--Optional:-->

         <ClientRequest>

            <FeatureId>GETSTATUS</FeatureId>

            <ClientTxnId>' . $this->order_id . '</ClientTxnId>

            <ChannelId>6</ChannelId>

           <ClientUsername>' . self::CLIENT_ID . '</ClientUsername>
            <ClientPassword>' . self::PASSWORD . '</ClientPassword>

            <RequestTimestamp>' . date("Y-m-d H:i:s") . '</RequestTimestamp>

            <OriginatingMsisdn>' . self::VENDOR_MSISDN . '</OriginatingMsisdn>

            <TransactionPin>' . self::PIN . '</TransactionPin>

             <Details>

               <Param>

                  <Name>ext_tid</Name>

                  <Value>' . $this->order_id . '</Value>

               </Param>

            </Details>

         </ClientRequest>

      </ws:processRequest>

   </soapenv:Body>

</soapenv:Envelope>';
        return $xml;
    }



    public function XmlContentForGetRemining()
    {
        $xml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.adapter.imp.erefill.sixdee.com/">

   <soapenv:Header/>

   <soapenv:Body>

      <ws:processRequest>

         <!--Optional:-->

         <ClientRequest>

            <FeatureId>BALANCEENQ</FeatureId>

            <ClientTxnId>' . uniqid() . 'getremining' . uniqid() . '</ClientTxnId>

            <ChannelId>6</ChannelId>

           <ClientUsername>' . self::CLIENT_ID . '</ClientUsername>

            <ClientPassword>' . self::PASSWORD . '</ClientPassword>

            <RequestTimestamp>' . date("Y-m-d H:i:s") . '</RequestTimestamp>

            <OriginatingMsisdn>' . self::VENDOR_MSISDN . '</OriginatingMsisdn>

            <TransactionPin>' . self::PIN . '</TransactionPin>

         </ClientRequest>

      </ws:processRequest>

   </soapenv:Body>

</soapenv:Envelope>';
        return $xml;
    }


    /**
     * @param string $mobileNumber
     * @return array
     */
    public function getOfferPackage(string $mobileNumber): array
    {
        $this->mobileNumber = $mobileNumber;
        $this->order_id = time() . Str::random(7);
        $this->category = 'All Categories';
        return $this->execute("offerPackage");
    }

    /**
     * @param string $mobileNumber
     * @return array
     */
    public function getBillInquiry(string $mobileNumber): array
    {
        $this->mobileNumber = $mobileNumber;

        return $this->getBillInquiryIrancellWsEsaj();

//        return $this->getBillInquiryIrancellSite();
//        return $this->executeBillInquiry();
    }

    /**
     * @return array
     */
    public function getBillInquiryIrancellSite(): array
    {
        return $this->executeBillInquiryIrancellSite();
    }


    /**
     * Undocumented function
     *
     * @return array
     */
    public function getBillInquiryIrancellWsEsaj(): array
    {
        return $this->executeBillInquiryIrancellWsEsaj();
    }




    public function getSimType(string $mobileNumber): array
    {
        $this->mobileNumber = $mobileNumber;
        $this->order_id = time() . Str::random(7);
        return $this->execute("SimType");
    }


    public function getRemaining(): array
    {
        $result = [] ;
        $irancellArr = $this->executeGetRemining();
        $result['irancell'] = $irancellArr['balance']??null;
        return $result ;
    }

    /**
     * @return array
     */
    private function executeBillInquiry(): array
    {
        $result = [];
        $result['mobile'] = $this->mobileNumber;
        $result['amount'] = 0;
        $result['current_amount'] = 0;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apisale.irancell.ir:8500/production/services/api/v1/get_postpaid_balance',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "msisdn": "' . $this->mobileNumber . '",
            "sim_type": "postpaid",
            "channel": "Esaj",
            "userID": "",
            "userIDType": ""
        }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic NmM1NTQwZThlZmU0ZmRkZDFmZDc6YWI5ZjRkYjg1Mzk5NmM4NjM3YTk5MTJiYjIwNTkyYWFhMTg4MWIxNQ==',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $responseObj = json_decode($response);

        if (isset($responseObj->current_balance)) {
            $result['amount'] = $responseObj->outstanding_balance;
            $result['current_amount'] = $responseObj->current_balance;
        }
        return $result;
    }

    private function executeGetRemining(): array
    {
        $this->webservice = $this->connectToService();

        $xml = $this->XmlContentForGetRemining();

        $result = $this->webservice->__doRequest($xml, self::WEBSERVICE_URL_INTERNET, '', 1);
        $resultProcessed = $this->set_result_get_remining($result);
        return $resultProcessed;
    }


    private function set_result_get_remining($result_xml)
    {
        $result = [];



        $resultArr = [];
        $resultArr['balance'] = null; // فقط عدد موجودی
        if (!$result_xml) {
            $resultArr['errors'] = 'Error in sent request to Irancel webservice';
            return $resultArr;
        }

        // حذف namespace ها برای ساده‌تر کردن parsing
        $data = str_ireplace("ns2:", "", $result_xml);
        $data = str_ireplace("soap:", "", $data);

        // تبدیل XML به شیء
        $xmlsimpleDataObject = simplexml_load_string($data);

        if (!$xmlsimpleDataObject || !isset($xmlsimpleDataObject->Body->processRequestResponse->ClientResponse)) {
            $resultArr['status'] = false;
            $resultArr['message'] = 'Invalid or empty SOAP response';
            return $resultArr;
        }

        $clientResponse = $xmlsimpleDataObject->Body->processRequestResponse->ClientResponse;

        // وضعیت درخواست
        if ($clientResponse->ResultCode) {
            $resultArr['ResultCode'] = (int) $clientResponse->ResultCode;
            $resultArr['status'] = ($resultArr['ResultCode'] === 0);
        }

        // داده‌های اصلی
        $resultArr['TransactionID']       = (string) $clientResponse->ClientTxnId;
        $resultArr['CommandStatus']       = (string) $clientResponse->CommandStatus;
        $resultArr['OrigResponseMessage'] = (string) $clientResponse->ResponseMessage;
        $resultArr['message']             = (string) $clientResponse->ResponseMessage;

        // استخراج موجودی از پیام
        if (preg_match('/balance is (\d+)/i', $resultArr['OrigResponseMessage'], $matches)) {
            $resultArr['balance'] = $matches[1]; // فقط عدد موجودی
        }

        return $resultArr ;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    private function executeBillInquiryIrancellWsEsaj(): array
    {
        $result = [];
        $result['mobile'] = $this->mobileNumber;
        $result['amount'] = '';
        $result['current_amount'] = '';


        // ساخت SoapClient بر اساس WSDL
        $client = new SoapClient(
            'https://wstopup.esaj.ir/apibill/irancell?wsdl',
            [
                'trace'      => true,             // برای دیباگ
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,  // در تولید: WSDL_CACHE_BOTH
                // اختیاری: اجباری نیست ولی صریح می‌گوید rpc/encoded هست
                // 'style'      => SOAP_RPC,
                // 'use'        => SOAP_ENCODED,
            ]
        );

        // متغیّرها
        $username = 'hesamshafiee';
        $password = '123456';

        // ۱) روش position-based (متداول در rpc/encoded)
        try {
            $response = $client->__soapCall('getIrancell', [
                $username,
                $password,
                $this->mobileNumber,
            ]);
        } catch (\Exception $e) {
            $result['amount'] = '';
            $result['current_amount'] = '';
        }
        // خروجی را بررسی و دی‌کُد می‌کنیم
        if (isset($response)) {        // ← نام دقیق پراپرتی خروجی
            $payload = json_decode($response, true);
            // اگر JSON به‌درستی دیکُد شد و عملیات موفق بود
            if (json_last_error() === JSON_ERROR_NONE && ($payload['status'] ?? 0) == 1) {
                $result['amount']         = $payload['price']           ?? '';
                $result['current_amount'] = $payload['current_amount']  ?? '';
            }
        }

        return $result;
    }





    /**
     * @return array
     */
    private function executeBillInquiryIrancellSite(): array
    {
        $result = [];
        $result['mobile'] = $this->mobileNumber;
        $result['amount'] = '';
        $result['current_amount'] = '';

        $proxy = 'http://esajuser:5wa9xMSGUOiinCdtM3LZlM2@188.121.117.225:80';
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://apishop.irancell.ir/bill_payment/api/v2/validate_and_get_banks',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{"channel":"eShop","msisdn":"' . $this->mobileNumber . '","invoice_id":"","payment_id":"","payment_ref_number":"","customer_type":"INDIVIDUAL"}',
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded"
            ),
            // اضافه کردن پروکسی به درخواست
            CURLOPT_PROXY => $proxy,
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $responseJson = json_decode($response, true);

        if (isset($responseJson['amount']) && isset($responseJson['current_amount'])) {
            $result['amount'] = $responseJson['amount'];
            $result['current_amount'] = $responseJson['current_amount'];
        }

        return $result;
    }

    /**
     * @param string $type
     * @return array
     */
    private function execute(string $type): array
    {

        $result = $this->irancell($type);
        if ($type == 'offerPackage') {
            $processedResult =  $this->set_result_offer_package($result);
             if (is_array($processedResult) && isset($processedResult['offersList']) && is_array($processedResult['offersList'])){
                foreach ($processedResult['offersList'] as $key => $value) {
                    $offerPackage = new IrancellOfferPackage();
                    $offerPackage->mobile_number = $this->mobileNumber;
                    $offerPackage->offerCode   = $value['offerCode'];
                    $offerPackage->name = $value['name'];
                    $offerPackage->amount =  $value['amount'];
                    $offerPackage->offerType =  $value['offerType'];
                    $offerPackage->validityDays =  $value['validityDays'];
                    $offerPackage->offerDesc =  $value['offerDesc'];
                    $offerPackage->save();
                }
            }

            return $processedResult;
        }
        if ($type == 'SimType') {
            return $this->set_result_sim_type($result);
        } else {
            return $this->set_result($result);
        }
    }

    /**
     * Retry a callback on failure (exception or false/null return).
     * @param callable $fn
     * @param int $maxAttempts
     * @param int $delayMs
     * @return mixed
     * @throws \Exception
     */
    private function retryOnFailure(callable $fn, int $maxAttempts = 3, int $delayMs = 2000)
    {
        $attempt = 0;
        do {
            try {
                $result = $fn();
                if ($result !== false && $result !== null) {
                    return $result;
                }
            } catch (\Throwable $e) {
                if ($attempt >= $maxAttempts - 1) {
                    throw $e;
                }
            }
            usleep($delayMs * 1000);
            $attempt++;
        } while ($attempt < $maxAttempts);
        return false;
    }

    /**
     * @param string $type
     * @param string $provider_id
     * @return mixed
     */
    private function irancell(string $type, string $provider_id = ''): mixed
    {
        // Use retry for SOAP client connection
        $this->webservice = $this->retryOnFailure(function () {
            return $this->connectToService();
        });

        $doRequest = function ($xml) {
            return $this->retryOnFailure(function () use ($xml) {
                return $this->webservice->__doRequest($xml, self::WEBSERVICE_URL_INTERNET, '', 1);
            });
        };

        if ($type === "charge") {
            $xml = $this->XmlContentCreateTopupExceptInternet();
            return $doRequest($xml);
        } elseif ($type === "package" && $this->profile_id != '54') {
            $xml = $this->XmlContentCreateInternetPackageNew();
            return $doRequest($xml);
        } elseif ($type === "package" && $this->profile_id == '54') {
            if (!$this->offer_type) {
                $xml = $this->XmlContentCreateTopupBuyableOffer();
            } else {
                $xml = $this->XmlContentCreateTopup();
            }
            return $doRequest($xml);
        } elseif ($type === "offerPackage") {
            $xml = $this->XmlContentGetInternetPackageNew();
            return $doRequest($xml);
        } elseif ($type === "SimType") {
            $xml = $this->XmlContentGetSimType();
            return $doRequest($xml);
        }

        return false;
    }

    private function connectToService()
    {
        $context = stream_context_create(array(
            'http' => array(
                'user_agent' => 'PHPSoapClient',
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                // 'allow_self_signed' => true
            )
        ));
        // Wrap SoapClient creation in retry as well
        return $this->retryOnFailure(function () use ($context) {
            return new \SoapClient(self::WEBSERVICE_URL_INTERNET . '?WSDL', array(
                'stream_context' => $context,
                'uri' => 'urn:xmethods-delayed-quotes',
                'trace' => 1
            ));
        });
    }


    private function XmlContentCreateInternetPackageNew()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.adapter.imp.erefill.sixdee.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:processRequest>
         <!--Optional:-->
         <ClientRequest>
            <FeatureId>OfferActivation</FeatureId>
            <ClientTxnId>' . $this->order_id . '</ClientTxnId>
            <ChannelId>6</ChannelId>
            <ClientUsername>' . self::CLIENT_ID . '</ClientUsername>
            <ClientPassword>' . self::PASSWORD . '</ClientPassword>
            <OriginatingMsisdn>' . self::VENDOR_MSISDN . '</OriginatingMsisdn>
            <DestinationMsisdn>' . $this->mobileNumber . '</DestinationMsisdn>
            <TransactionPin>' . self::PIN . '</TransactionPin>
            <TransactionAmount>' . $this->price . '</TransactionAmount>
            <ProfileId>' . $this->profile_id . '</ProfileId>
            <Details>
               <Param>
                  <Name>ext_id</Name>
                  <Value>' . $this->ext_id . '</Value>
               </Param>
               <Param>
                  <Name>national_id</Name>
                  <Value>' . $this->national_code . '</Value>
               </Param>
             <Param>
                  <Name>offer_id</Name>
                  <Value>' . $this->profile_id . '</Value>
               </Param>

            </Details>
         </ClientRequest>
      </ws:processRequest>
   </soapenv:Body>
</soapenv:Envelope>';
        return $xml;
    }


    private function XmlContentCreateTopupExceptInternet()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.adapter.imp.erefill.sixdee.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:processRequest>
         <!--Optional:-->
         <ClientRequest>
            <FeatureId>' . $this->function_name . '</FeatureId>
            <ClientUsername>' . self::CLIENT_ID . '</ClientUsername>
            <ClientPassword>' . self::PASSWORD . '</ClientPassword>
            <ClientTxnId>' . $this->order_id . '</ClientTxnId>
            <RequestTimestamp>20190530140639</RequestTimestamp>
            <ChannelId>2</ChannelId>
            <OriginatingMsisdn>' . self::VENDOR_MSISDN . '</OriginatingMsisdn>
            <DestinationMsisdn>' . $this->mobileNumber . '</DestinationMsisdn>
            <TransactionPin>' . self::PIN . '</TransactionPin>
            <TransactionAmount>' . $this->price . '</TransactionAmount>
            <ProfileId>' . $this->profile_id . '</ProfileId>
            <Details>
               <Param>
                  <Name>ext_id</Name>
                  <Value>' . $this->ext_id . '</Value>
               </Param>

               <Param>
                  <Name>national_id</Name>
                  <Value>' . $this->national_code . '</Value>
               </Param>

               <Param>
                  <Name>ext_name</Name>
                  <Value>' . $this->store_name . '</Value>
               </Param>
             <Param>
                  <Name>offer_id</Name>
                  <Value>' . $this->offer_code . '</Value>
               </Param>
                        <Param>
                  <Name>autoRenewal_flag</Name>
                  <Value>N</Value>
               </Param>
            </Details>
         </ClientRequest>
      </ws:processRequest>
   </soapenv:Body>
</soapenv:Envelope>';
        return $xml;
    }


    private function XmlContentCreateTopupBuyableOffer()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.adapter.imp.erefill.sixdee.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:processRequest>
         <!--Optional:-->
         <ClientRequest>
            <FeatureId>buyableofferactivation</FeatureId>
            <ClientUsername>' . self::CLIENT_ID . '</ClientUsername>
            <ClientPassword>' . self::PASSWORD . '</ClientPassword>
            <ClientTxnId>' . $this->order_id . '</ClientTxnId>
            <RequestTimestamp>20190530140639</RequestTimestamp>
            <ChannelId>6</ChannelId>
            <OriginatingMsisdn>' . self::VENDOR_MSISDN . '</OriginatingMsisdn>
            <DestinationMsisdn>' . $this->mobileNumber . '</DestinationMsisdn>
            <TransactionPin>' . self::PIN . '</TransactionPin>
            <TransactionAmount>' . $this->price . '</TransactionAmount>
            <ProfileId>' . $this->profile_id . '</ProfileId>
            <Details>
               <Param>
                  <Name>ext_id</Name>
                  <Value>' . $this->ext_id . '</Value>
               </Param>

               <Param>
                  <Name>national_id</Name>
                  <Value>' . $this->national_code . '</Value>
               </Param>

               <Param>
                  <Name>ext_name</Name>
                  <Value>' . $this->store_name . '</Value>
               </Param>
             <Param>
                  <Name>offer_id</Name>
                  <Value>' . $this->offer_code . '</Value>
               </Param>
                        <Param>
                  <Name>autoRenewal_flag</Name>
                  <Value>N</Value>
               </Param>
            </Details>
         </ClientRequest>
      </ws:processRequest>
   </soapenv:Body>
</soapenv:Envelope>';

        return $xml;
    }


    private function XmlContentCreateTopup()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.adapter.imp.erefill.sixdee.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:processRequest>
         <!--Optional:-->
         <ClientRequest>
            <FeatureId>TOPUP</FeatureId>
            <ClientUsername>' . self::CLIENT_ID . '</ClientUsername>
            <ClientPassword>' . self::PASSWORD . '</ClientPassword>
            <ClientTxnId>' . $this->order_id . '</ClientTxnId>
            <RequestTimestamp>20190530140639</RequestTimestamp>
            <ChannelId>6</ChannelId>
            <OriginatingMsisdn>' . self::VENDOR_MSISDN . '</OriginatingMsisdn>
            <DestinationMsisdn>' . $this->mobileNumber . '</DestinationMsisdn>
            <TransactionPin>' . self::PIN . '</TransactionPin>
            <TransactionAmount>' . $this->price . '</TransactionAmount>
            <ProfileId>' . $this->profile_id . '</ProfileId>
            <Details>
               <Param>
                  <Name>ext_id</Name>
                  <Value>' . $this->ext_id . '</Value>
               </Param>

               <Param>
                  <Name>national_id</Name>
                  <Value>' . $this->national_code . '</Value>
               </Param>

               <Param>
                  <Name>ext_name</Name>
                  <Value>' . $this->store_name . '</Value>
               </Param>
             <Param>
                  <Name>offer_id</Name>
                  <Value>' . $this->offer_code . '</Value>
               </Param>
                        <Param>
                  <Name>autoRenewal_flag</Name>
                  <Value>N</Value>
               </Param>
            </Details>
         </ClientRequest>
      </ws:processRequest>
   </soapenv:Body>
</soapenv:Envelope>';
        return $xml;
    }
    public function XmlContentGetInternetPackageNew()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.adapter.imp.erefill.sixdee.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:processRequest>
         <!--Optional:-->
         <ClientRequest xmlns="">

        <FeatureId>GETBUYABLEOFFER</FeatureId>

        <ClientTxnId>' . $this->order_id . '</ClientTxnId>

        <ChannelId>6</ChannelId>

        <ClientUsername>' . self::CLIENT_ID . '</ClientUsername>

        <ClientPassword>' . self::PASSWORD . '</ClientPassword>

        <RequestTimestamp>20190530140639</RequestTimestamp>

        <OriginatingMsisdn>' . self::VENDOR_MSISDN . '</OriginatingMsisdn>

        <DestinationMsisdn>' . $this->mobileNumber . '</DestinationMsisdn>

        <TransactionPin>' . self::PIN . '</TransactionPin>

        <ProfileId>54</ProfileId>

        <Details>

          <Param>

            <Name>ext_tid</Name>

            <Value>1212423520000052</Value>

         </Param>

          <Param>

            <Name>ext_id</Name>

            <Value>6</Value>

            </Param>

          <Param>

            <Name>ext_name</Name>

            <Value>SAFIR</Value>

          </Param>

          <Param>

            <Name>category</Name>

            <Value>' . $this->category . '</Value>

          </Param>

        </Details>

      </ClientRequest>
      </ws:processRequest>
   </soapenv:Body>
</soapenv:Envelope>';


        return $xml;
    }

    public function XmlContentGetSimType()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws.adapter.imp.erefill.sixdee.com/">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:processRequest>
         <!--Optional:-->
         <ClientRequest>
            <FeatureId>GETSUBSCRIBERTYPE</FeatureId>
            <ClientUsername>' . self::CLIENT_ID . '</ClientUsername>
            <ClientPassword>' . self::PASSWORD . '</ClientPassword>
            <ClientTxnId>' . $this->order_id . '</ClientTxnId>
            <RequestTimestamp>20190530140639</RequestTimestamp>
            <ChannelId>6</ChannelId>
			<TransactionPin>' . self::PIN . '</TransactionPin>
            <OriginatingMsisdn>' . self::VENDOR_MSISDN . '</OriginatingMsisdn>
            <DestinationMsisdn>' . $this->mobileNumber . '</DestinationMsisdn>
            <Details>
               <Param>
                  <Name>ext_id</Name>
                  <Value>59</Value>
               </Param>
			   <Param>
                  <Name>ext_tid</Name>
                  <Value>213436070001</Value>
               </Param>


               <Param>
                  <Name>ext_name</Name>
                  <Value>test</Value>
               </Param>

            </Details>
         </ClientRequest>
      </ws:processRequest>
   </soapenv:Body>
</soapenv:Envelope>';
        return $xml;
    }

    private function set_result($result_xml)
    {
        $resultArr = [];
        $resultArr['provider'] = 'irancell';
        if (!$result_xml) {
            $resultArr['errors'] = 'Error in sent request to Irancel webservice';
            return $resultArr;
        }


        $data = str_ireplace("ns2:", "", $result_xml);
        $xmlsimpleDataObject = simplexml_load_string(str_ireplace("soap:", "", $data));

        if (!$xmlsimpleDataObject || !isset($xmlsimpleDataObject->Body->processRequestResponse->ClientResponse)) {
            $resultArr['status'] = false;
            $resultArr['message'] = 'Invalid or empty SOAP response';
            return $resultArr;
        }

        if ($xmlsimpleDataObject->Body->processRequestResponse->ClientResponse->ResultCode) {
            $resultArr['ResultCode'] = (int) $xmlsimpleDataObject->Body->processRequestResponse->ClientResponse->ResultCode; // 0 :: OK
            $resultArr['status'] = !$resultArr['ResultCode'] ? true : false;
        }

        $resultArr['TransactionID'] = (string) $xmlsimpleDataObject->Body->processRequestResponse->ClientResponse->ClientTxnId;
        $resultArr['CommandStatus'] = (string) $xmlsimpleDataObject->Body->processRequestResponse->ClientResponse->CommandStatus;
        $resultArr['OrigResponseMessage'] = (string) $xmlsimpleDataObject->Body->processRequestResponse->ClientResponse->ResponseMessage;
        $resultArr['message'] = (string) $xmlsimpleDataObject->Body->processRequestResponse->ClientResponse->ResponseMessage;
        return $resultArr;
    }

    private function set_result_offer_package(mixed $result_xml)
    {
        $resultArr = [];
        $resultArr['provider'] = 'irancell';
        if (!$result_xml) {
            $resultArr['errors'] = 'Error in sent request to Irancel webservice';
            $resultArr['status'] = false;
            return $resultArr;
        }

        try {
            $data = str_ireplace("ns2:", "", $result_xml);
            $xmlsimpleDataObject = simplexml_load_string(str_ireplace("soap:", "", $data));

            if (!$xmlsimpleDataObject || !isset($xmlsimpleDataObject->Body->processRequestResponse->ClientResponse)) {
                $resultArr['errors'] = 'Invalid or unexpected SOAP response structure';
                $resultArr['status'] = false;
                return $resultArr;
            }

            $clientResponse = $xmlsimpleDataObject->Body->processRequestResponse->ClientResponse;

            if (isset($clientResponse->ResultCode)) {
                $resultArr['ResultCode'] = (int) $clientResponse->ResultCode;
                $resultArr['status'] = !$resultArr['ResultCode'] ? true : false;
            } else {
                $resultArr['ResultCode'] = null;
                $resultArr['status'] = false; // Assume failure if ResultCode is missing
            }

            $resultArr['TransactionID'] = (string) ($clientResponse->ClientTxnId ?? '');
            $resultArr['CommandStatus'] = (string) ($clientResponse->CommandStatus ?? '');
            $resultArr['OrigResponseMessage'] = (string) ($clientResponse->ResponseMessage ?? '');
            $resultArr['message'] = (string) ($clientResponse->ResponseMessage ?? '');

            $array = array();
            $counter = 0;
            if (isset($clientResponse->offersList)) {
                foreach ($clientResponse->offersList as $key => $val) {
                    $array[$counter]['offerCode'] = (string)($val->offerCode ?? '');
                    $array[$counter]['offerDesc'] = (string)($val->offerDesc ?? '');
                    $array[$counter]['offerDescLN'] = (string)($val->offerDescLN ?? '');
                    $array[$counter]['validityDays'] = (string)($val->validityDays ?? '');
                    $array[$counter]['offerType'] = (string)($val->offerType ?? '');
                    $array[$counter]['amount'] = (string)($val->amount ?? '');
                    $array[$counter]['name'] = (string)($val->detailsDescLnV ?? '');
                    $counter++;
                }
            }
            $resultArr['offersList'] = $array;
        } catch (\Exception $e) {
            $resultArr['errors'] = 'Error processing response: ' . $e->getMessage();
            $resultArr['status'] = false;
        }

        return $resultArr;
    }

    private function set_result_sim_type(mixed $result_xml)
    {
        $resultArr = [];
        $resultArr['provider'] = 'irancell';
        if (!$result_xml) {
            $resultArr['errors'] = 'Error in sent request to Irancel webservice';
            $resultArr['status'] = false;
            return $resultArr;
        }

        try {
            $data = str_ireplace("ns2:", "", $result_xml);
            $xmlsimpleDataObject = simplexml_load_string(str_ireplace("soap:", "", $data));

            if (!$xmlsimpleDataObject || !isset($xmlsimpleDataObject->Body->processRequestResponse->ClientResponse)) {
                $resultArr['errors'] = 'Invalid or unexpected SOAP response structure';
                $resultArr['status'] = false;
                return $resultArr;
            }

            $clientResponse = $xmlsimpleDataObject->Body->processRequestResponse->ClientResponse;

            if (isset($clientResponse->ResultCode)) {
                $resultArr['ResultCode'] = (int) $clientResponse->ResultCode;
            } else {
                $resultArr['ResultCode'] = null;
            }

            $resultArr['TransactionID'] = (string) ($clientResponse->ClientTxnId ?? '');
            $resultArr['CommandStatus'] = (string) ($clientResponse->CommandStatus ?? '');
            $resultArr['OrigResponseMessage'] = (string) ($clientResponse->ResponseMessage ?? '');
            $resultArr['subscriber_type'] = (string) ($clientResponse->Subscriber_Type ?? '');
        } catch (\Exception $e) {
            $resultArr['errors'] = 'Error processing response: ' . $e->getMessage();
            $resultArr['status'] = false;
        }

        return $resultArr;
    }
}
