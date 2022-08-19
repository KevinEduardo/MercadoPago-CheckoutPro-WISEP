<?php
// SDK do Mercado Pago
require __DIR__ .  '/vendor/autoload.php';

    class MercadoPago {
        // MercadoPago Checkout Pro Module
        public $checkout_id,$checkout;
        public $name,$commission=true;
        public $config=[],$lang=[],$page_type = "in-page",$callback_type="server-sided";
        public $payform=false;

        function __construct(){
            $this->config     = Modules::Config("Payment",__CLASS__);
            $this->lang       = Modules::Lang("Payment",__CLASS__);
            $this->name       = __CLASS__;
            $this->payform   = __DIR__.DS."pages".DS."payform";
        }

        public function get_auth_token(){
            $syskey = Config::get("crypt/system");
            $token  = md5(Crypt::encode("MercadoPago-Auth-Token=".$syskey,$syskey));
            return $token;
        }

        public function set_checkout($checkout){
            $this->checkout_id = $checkout["id"];
            $this->checkout    = $checkout;
        }

        public function commission_fee_calculator($amount){
            $rate = $this->get_commission_rate();
            if(!$rate) return 0;
            $calculate = Money::get_discount_amount($amount,$rate);
            return $calculate;
        }


        public function get_commission_rate(){
            return $this->config["settings"]["commission_rate"];
        }

        public function cid_convert_code($id=0){
            Helper::Load("Money");
            $currency   = Money::Currency($id);
            if($currency) return $currency["code"];
            return false;
        }

        public function get_ip(){
            return UserManager::GetIP();
        }

        public function get_public_key(){
            return $this->config["settings"]["publicKey"];
        }

        // Validate CPF number format
        public function validarCPF($cpf) {
            // Extrai somente os números
            $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
            
            // Verifica se foi informado todos os digitos corretamente
            if (strlen($cpf) != 11) {
                return false;
            }
        
            // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
            if (preg_match('/(\d)\1{10}/', $cpf)) {
                return false;
            }
        
            // Faz o calculo para validar o CPF
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) {
                    return false;
                }
            }
            return true;
        }

        public function get_mercadopago_preference($successful='',$failed=''){

            $checkout_items         = $this->checkout["items"];
            $checkout_data          = $this->checkout["data"];
            $user_data              = $checkout_data["user_data"];

            $callback_url           = Controllers::$init->CRLink("payment",['MercadoPago',$this->get_auth_token(),'callback']);

            // Retrieve CPF or CNPJ from user data
            $query = WDB::select("*");
            $query->from("users_informations");
            $query->where("owner_id", "=", $user_data["id"], "AND");
            $query->where("name", "=", $this->config["settings"]["cpfcnpjfield"]);
            $query = $query->build(true)->fetch_assoc();
            $cpfcnpjfield = $query[0]['content'];

            // set Access Token for MercadoPago SDK
            MercadoPago\SDK::setAccessToken($this->config["settings"]["accessToken"]);

            $payer = new MercadoPago\Payer();
            $payer->name = $user_data["name"];
            $payer->surname = $user_data["surname"];
            $payer->email = $user_data["email"];
                
            $payer->identification = array(
                "type" => $this->validarCPF($cpfcnpjfield) ? "CPF" : "CNPJ",
                "number" => $cpfcnpjfield
            );
            

            $items = array();

            foreach ($checkout_items as $key => $value) {
                $item = new MercadoPago\Item();
                $item->id = $checkout_items[$key]['id'];
                $item->title = $checkout_items[$key]['name'];
                $item->quantity = $checkout_items[$key]['quantity'];
                $item->unit_price = number_format($checkout_items[$key]['total_amount'], 2, '.', '');
                array_push($items, $item);
            }

            // Create a preference object
            $preference = new MercadoPago\Preference();
            $preference->items = $items;
            $preference->notification_url = $callback_url;
            $preference->payer = $payer;
            $preference->external_reference = $this->checkout_id;
            $preference->back_urls = array(
                "success" => $successful,
                "pending" => $failed
            );
            $preference->auto_return = "approved";
            $preference->statement_descriptor = $this->config["settings"]["statement_descriptor"];
            $preference->save();

            return $preference;
        }

        public function payment_result(){

            // Debug methods
            $data = file_get_contents('php://input') . "\n" . http_build_query($_GET);
            $filename = date('YmdHis').".txt";
            if (!file_exists($filename)) {
                $fh = fopen($filename, 'w') or die("Can't create file");
            }
            $ret = file_put_contents($filename, $data, FILE_APPEND | LOCK_EX);

            $debugmsg = file_get_contents('php://input');

            MercadoPago\SDK::setAccessToken($this->config["settings"]["accessToken"]);

            $merchant_order = null;
            $checkout_id = null;

            if(isset($_GET["topic"])) {
                switch($_GET["topic"]) {
                    case "payment":
                        try {
                            $payment = MercadoPago\Payment::find_by_id($_GET["id"]);
                            // Get the payment and the corresponding merchant_order reported by the IPN.
                            $merchant_order = MercadoPago\MerchantOrder::find_by_id($payment->order->id);
                            $checkout_id = $merchant_order->external_reference;
                        } catch (Exception $e) {
                            return [
                                'status' => "ERROR",
                                'status_msg' => $e,
                            ];
                        }
                        break;
                    case "merchant_order":
                        try {
                            $merchant_order = MercadoPago\MerchantOrder::find_by_id($_GET["id"]);
                            $checkout_id = $merchant_order->external_reference;
                        } catch (Exception $e) {
                            return [
                                'status' => "ERROR",
                                'status_msg' => $e,
                            ];
                        }
                        break;
                }
            }

            if(isset($_GET["external_reference"]) && $_GET['external_reference'] != "") {
                $payment = MercadoPago\Payment::find_by_id($_GET["payment_id"]);
                // Get the payment and the corresponding merchant_order reported by the IPN.
                $merchant_order = MercadoPago\MerchantOrder::find_by_id($payment->order->id);
                $checkout_id = $_GET['external_reference'];
            }

            $checkout = false;
            if($checkout_id != null) {
                $checkout = Basket::get_checkout($checkout_id);
            }

            if(!$checkout)
                return [
                    'status' => "ERROR",
                    'status_msg' => Bootstrap::$lang->get("errors/error6",Config::get("general/local")),
                ];
            
            $this->set_checkout($checkout);

            $paid_amount = 0;
            $status = null;
            foreach ($merchant_order->payments as $payment) {
                switch ($payment->status) {
                    case 'approved': // The payment has been approved and accredited.
                        $status = "APPROVED";
                        $paid_amount += $payment->transaction_amount;
                        break;
                    case 'pending': // The user has not yet completed the payment process.
                        $status = "PENDING";
                        break;
                    case 'authorized': // The payment has been authorized but not captured yet.
                        $status = "AUTHORIZED";
                        break;
                    case 'in_process': //  Payment is being reviewed.
                        $status = "IN_PROCESS";
                        break;
                    case 'rejected': // Payment was rejected. The user may retry payment.
                        $status = "REJECTED";
                        break;
                    case 'refunded': //  Payment was refunded to the user.
                        $status = "REFUNDED";
                        break;
                    case 'cancelled': // Payment was cancelled by one of the parties or because time for payment has expired
                        $status = "CANCELLED";
                        break;
                    case 'in_mediation': // Users have initiated a dispute.
                        $status = "IN_MEDIATION";
                        break;
                    case 'charged_back': // Was made a chargeback in the buyer’s credit card.
                        $status = "CHARGED_BACK";
                        break;
                    default:
                        $status = "ERROR";
                        break;
                }
            }

            $mo_id = $merchant_order->id;

            $invoice = Invoices::search_pmethod_msg('"mo_id":"'.$mo_id.'"');

            if($invoice){
                $checkout["data"]["invoice_id"] = $invoice;
                $invoice = Invoices::get($invoice);
            }

            if($invoice && $invoice["status"] == "paid"){
                Basket::set_checkout($checkout["id"],['status' => "paid"]);
                return [
                    'status' => "SUCCESS",
                    'return_msg' => "OK",
                ];
            }
            
            // If the payment's transaction amount is equal (or bigger) than the merchant_order's amount you can release your items
            if($paid_amount >= $merchant_order->total_amount){
                // Payment approved and successfully paid!
                Basket::set_checkout($checkout["id"],['status' => "paid"]);
                if($invoice){
                    Invoices::paid($checkout,"SUCCESS",$invoice["pmethod_msg"]);
                    return [
                        'status' => "SUCCESS",
                        'return_msg' => "OK",
                    ];
                }else
                    return [
                        'status' => "SUCCESS",
                        'checkout'    => $checkout,
                        'status_msg' => $debugmsg,
                        'return_msg' => "OK",
                    ];
            } else {
                // Handle other payment statuses
                switch ($status) {
                    case 'PENDING':
                        if($invoice)
                            return [
                                'status' => "PENDING",
                                'return_msg' => "Pending",
                            ];
                        else
                            return [
                                'status' => "PAPPROVAL",
                                'checkout'    => $checkout,
                                'status_msg' => $debugmsg,
                                'return_msg' => "Pending",
                            ];
                        break;
                }
            }

        }

    }
