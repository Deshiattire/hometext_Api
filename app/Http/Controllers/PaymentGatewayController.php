<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentGatewayController extends Controller
{
    /*
     * Please change it as per your integrated site
     */
	const BASE_URL = 'https://pay.hometexbd.ltd/';
	//const BASE_URL = 'http://127.0.0.1:8000/';

	/*
	 *  Application URL
	 */
    const SUBMIT_URL = self::BASE_URL.'api/v1.0/pay';
    const TOKEN_URL = self::BASE_URL.'api/v1.0/login';
    const CLIENT_ID = 3;  // Required


    /** this is success url, after payment success  will redirect this link
     * THIS LINK IS REQUIRED
     * */
    const SUCCESS_URL = 'paysuccess.php';

    /** this is fail url, after payment success  will redirect this link
     * THIS LINK IS REQUIRED
     * */
    const FAIL_URL = 'payfail.php';

    /** this is cancel url, after payment success  will redirect this link
     * THIS LINK IS REQUIRED
     * */
    const CANCEL_URL =  'paycancel.php';

    /** this is ipn url, after payment success  will redirect this link
     * THIS LINK IS REQUIRED
     * THIS IS OPTIONAL , IF YOU USE IT YOU PAYMENT THEN IT'S REQUIRED
     * */
    const IPN_URL = 'payipn.php';

    const CLIENT_USER_NAME = 'info@hometexbd.com'; // Required
    const CLIENT_PASSWORD = 'Open@123'; // Required


    public function getToken(){
        try {
            $requestParams = [
                'email' => self::CLIENT_USER_NAME,
                'password' => self::CLIENT_PASSWORD
            ];

            $payload = json_encode($requestParams);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::TOKEN_URL);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            // For certificate verification
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            // Set HTTP Header for POST request
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload))
            );

            $response = curl_exec($ch);
            curl_close($ch);
            $content = json_decode((string) $response, true);
            if($content['status'] != "success"){
                echo $content['message'];
                return '';
            }
            return $content['data']['token'];
        } catch (\Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    // public function getDetailsByTrx(Request $request){
    //     $fields = [
    //         'trxid' => 'required'
    //     ];

    //     $validator = Validator::make($request->all(), $fields);
    //     if ($validator->fails()) {
    //         return response()->json(['status' => 400, 'message' => 'validation_err', 'error' => $validator->errors()], 400);
    //     }

    //     try{


    //     }catch(\Exception $e){

    //     }
    // }

}
