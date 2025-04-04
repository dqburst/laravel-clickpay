<?php


namespace Dqburst\Laravel_clickpay;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;


class paypage
{

    public  $clickpayinit,
        $clickpay_core,
        $clickpay_api,
        $follow_transaction,
        $laravel_version,
        $package_version;
    function __construct()
    {
        $this->clickpayinit = new clickpay_core();
        $this->clickpay_core = new ClickpayRequestHolder();
        $this->clickpay_core_token = new ClickpayTokenHolder();
        $this->clickpay_api = ClickpayApi::getInstance(config('clickpay.region'), config('clickpay.profile_id'), config('clickpay.server_key'));
        $this->follow_transaction = new ClickpayFollowupHolder();
        $this->laravel_version = app()::VERSION;
        $this->package_version = '1.3.3';
    }

    public function sendPaymentCode($code)
    {
        $this->clickpay_core->set01PaymentCode($code);
        return $this;
    }

    public function sendTransaction($transaction)
    {
        $this->clickpay_core->set02Transaction($transaction);
        return $this;
    }

    public function sendCart($cart_id, $amount, $cart_description)
    {
        $this->clickpay_core->set03Cart($cart_id, config('clickpay.currency'), $amount, $cart_description);
        return $this;
    }

    public function sendCustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip)
    {
        $this->clickpay_core->set04CustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip);
        return $this;
    }

    public function sendShippingDetails($same_as_billing, $name = null, $email = null, $phone = null, $address = null, $city = null, $state = null, $country = null, $zip = null, $ip = null)
    {
        $this->clickpay_core->set05ShippingDetails($same_as_billing, $name, $email, $phone, $address, $city, $state, $country, $zip, $ip);
        return $this;
    }

    public function sendHideShipping($on = false)
    {
        $this->clickpay_core->set06HideShipping($on);
        return $this;
    }

    public function sendURLs($return_url, $callback_url)
    {
        $this->clickpay_core->set07URLs($return_url, $callback_url);
        return $this;
    }

    public function sendLanguage($language)
    {
        $this->clickpay_core->set08Lang($language);
        return $this;
    }

    public function sendFramed($on = false)
    {
        $this->clickpay_core->set09Framed($on);
        return $this;
    }

    public function sendTokinse($on = false)
    {
        $this->clickpay_core->set10Tokenise($on);
        return $this;
    }

    public function sendToken($token, $tran_ref)
    {
        $this->clickpay_core_token->set20Token($token, $tran_ref);
        return $this; 
    }

    public function sendUserDefined(array $user_defined = [])
    {
        $this->clickpay_core->set100userDefined($user_defined);
        return $this; 
    }

    public function create_pay_page()
    {
        $this->clickpay_core->set99PluginInfo('Laravel',8,'1.3.3');
        $pp_params = $this->clickpay_core->pt_build();
        $response = $this->clickpay_api->create_pay_page($pp_params);

        if ($response->success) {
            $redirect_url = $response->redirect_url;
            if (isset($pp_params['framed']) &&  $pp_params['framed'] == true)
            {
                return $redirect_url;
            }
            return Redirect::to($redirect_url);
        }
        else {
            Log::channel('Clickpay')->info(json_encode($response));
            print_r(json_encode($response));
        }
    }


    public function refund($tran_ref,$order_id,$amount,$refund_reason)
    {
        $this->follow_transaction->set02Transaction(ClickpayEnum::TRAN_TYPE_REFUND)
            ->set03Cart($order_id, config('clickpay.currency'), $amount, $refund_reason)
            ->set30TransactionInfo($tran_ref)
            ->set99PluginInfo('Laravel', $this->laravel_version, $this->package_version);

        $refund_params = $this->follow_transaction->pt_build();
        $result = $this->clickpay_api->request_followup($refund_params);

        $success = $result->success;
        $message = $result->message;
        $pending_success = $result->pending_success;

        if ($success) {
            $payment = $this->clickpay_api->verify_payment($tran_ref);
            if ((float)$amount < (float)$payment->cart_amount) {
                $status = 'partially_refunded';
            } else {
                $status = 'refunded';
            }
            return response()->json(['status' => $status], 200);
        } else if ($pending_success) {
            Log::channel('Clickpay')->info(json_encode($result));
            print_r('some thing went wrong with integration' . $message);
        }

    }

    public function capture($tran_ref,$order_id,$amount,$capture_description)
    {
        $this->follow_transaction->set02Transaction(ClickpayEnum::TRAN_TYPE_CAPTURE)
            ->set03Cart($order_id, config('clickpay.currency'), $amount, $capture_description)
            ->set30TransactionInfo($tran_ref)
            ->set99PluginInfo('Laravel', $this->laravel_version, $this->package_version);

        $capture_params = $this->follow_transaction->pt_build();
        $result = $this->clickpay_api->request_followup($capture_params);

        $success = $result->success;
        $message = $result->message;
        $pending_success = $result->pending_success;

        if ($success) {
            $payment = $this->clickpay_api->verify_payment($tran_ref);
            if ((float)$amount < (float)$payment->cart_amount) {
                $status = 'partially_captured';
            } else {
                $status = 'captured';
            }
            return response()->json(['status' => $status], 200);
        } else if ($pending_success) {
            Log::channel('Clickpay')->info(json_encode($result));
            print_r('some thing went wrong with integration' . $message);
        }
    }

    public function void($tran_ref,$order_id,$amount,$void_description)
    {
        $this->follow_transaction->set02Transaction(ClickpayEnum::TRAN_TYPE_VOID)
            ->set03Cart($order_id, config('clickpay.currency'), $amount, $void_description)
            ->set30TransactionInfo($tran_ref)
            ->set99PluginInfo('Laravel', $this->laravel_version, $this->package_version);

        $void_params = $this->follow_transaction->pt_build();
        $result = $this->clickpay_api->request_followup($void_params);

        $success = $result->success;
        $message = $result->message;
        $pending_success = $result->pending_success;

        if ($success) {
            $payment = $this->clickpay_api->verify_payment($tran_ref);
            if ((float)$amount < (float)$payment->cart_amount) {
                $status = 'partially_voided';
            } else {
                $status = 'voided';
            }
            return response()->json(['status' => $status], 200);
        } else if ($pending_success) {
            Log::channel('Clickpay')->info(json_encode($result));
            print_r('some thing went wrong with integration' . $message);
        }
    }

    public function queryTransaction($tran_ref)
    {
        $transaction = $this->clickpay_api->verify_payment($tran_ref);
        return $transaction;
    }
}

