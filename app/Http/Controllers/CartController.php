<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\CartResource;
use App\Http\Resources\PurchaseResource;
use App\Models\Feature;
use App\Models\Article;
use App\Models\Variant;
use App\Models\Cart;
use App\Models\User;
use App\Models\Purchase;
use App\Models\PurchaseCart;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseMail;
use App\Models\NewsLetter;
use App\Services\ExchangeRateService;
use Session;

use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\ShippingAddress;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\URL as FacadesURL;
use Redirect;
use URL;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

use Illuminate\Support\Facades\Log;

class CartController extends BaseController
{
    private $out;
    protected $zip;
    private $_api_context;
    protected $exchangeRateService;
    protected $paypal;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
/*         $paypal_configuration = \Config::get('paypal');
        $this->_api_context = new ApiContext(new OAuthTokenCredential($paypal_configuration['client_id'], $paypal_configuration['secret']));
        $this->_api_context->setConfig($paypal_configuration['settings']); */

        $this->exchangeRateService = $exchangeRateService;

        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }


    /**
     * @OA\Post(
     ** path="/add/article/{article_id}/cart/user/{user_id}",
     *   tags={"Cart Controller"},
     *   summary="Add article to Cart by user",
     *      @OA\Parameter(name="article_id", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="user_id", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function addCart(Request $request, $article_id, $user_id)
    {

        $message = "Article successfully added to cart !";

        $article = Article::findOrFail((int)$article_id);

        if ($article == null) {
            return $this->sendError('Article with the id ' . $article_id . ' not found', 400);
        }

        $user = User::findOrFail((int)$user_id);

        if ($user == null) {
            return $this->sendError('User with the id ' . $user_id . ' not found', 400);
        }

        $cart = Cart::where('user_id', (int)$user_id)->where('article_id', (int)$article_id)->where('status', 'PENDING')->first();

        if ($cart !== null) {
            $message = " This article already exist in your cart !";
            return $this->sendError($message, 400);
        } else {

            $actualPrice = 0;

            if ($article->reduction_type === "AMOUNT") {
              $actualPrice = $article->price - $article->reduction_price;
            } else if ($article->reduction_type === "PERCENTAGE") {
              $discount = $article->price * ($article->reduction_price / 100);
              $actualPrice = $article->price - $discount;
            } else {
              $actualPrice = $article->price;
            }

            $this->out->writeln("From Laravel API actual price : " . $actualPrice);
            $cart = new Cart;
            $cart->amount = $actualPrice;
            $cart->status = 1;
            $cart->user()->associate($user);
            $cart->article()->associate($article);
        }
        $cart->save();

        return $this->sendResponse(new CartResource($cart), $message);
    }

    /**
     * @OA\Get(
     ** path="/get/carts/user/{id}",
     *   tags={"Cart Controller"},
     *   summary="Get carts of user",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getCartsOfUser($id)
    {
        $carts = Cart::where('user_id', (int)$id)->where('status', 1)->get();
        return $this->sendResponse(CartResource::collection($carts), 'Carts successfully retreived.');
    }

    /**
     * @OA\Delete(
     ** path="/article/{article_id}/cart/user/{user_id}",
     *   tags={"Cart Controller"},
     *   summary="Delete a cart of user",
     *   @OA\Parameter(name="article_id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="user_id", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function removeCartsElement($article_id, $user_id)
    {
        $cart = Cart::where('user_id', (int)$user_id)->where('article_id', (int)$article_id)->delete();

        return $this->sendResponse(true, 'Cart element successfully removed.');
    }

    /**
     * @OA\Delete(
     ** path="/carts/user/{id}",
     *   tags={"Cart Controller"},
     *   summary="Delete carts of user",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function clearCart($id)
    {
        $carts = Cart::where('user_id', (int)$id)->where('status', 1)->delete();

        return $this->sendResponse(true, 'Article successfully removed.');
    }

    public function updateCartArticleQuantity(Request $request, $article_id, $user_id)
    {
        $message = "Article successfully added to cart !";

        $article = Article::findOrFail((int)$article_id);

        if ($article == null) {
            return $this->sendError('Article with the id ' . $article_id . ' not found', 400);
        }

        $user = User::findOrFail((int)$user_id);

        if ($user == null) {
            return $this->sendError('User with the id ' . $user_id . ' not found', 400);
        }

        $cart = Cart::where('user_id', (int)$user_id)->where('article_id', (int)$article_id)->where('status', 'PENDING')->first();

        if ($cart !== null) {
            $quantity = (int)$request->get('quantity');
            if ($quantity <= $article->quantity) {
                $cart->quantity = (int)$request->get('quantity');
            } else {
                $cart->quantity = $article->quantity;
                $details = [
                    'message' => " Maximum available quantity has been reached !",
                    'cart' => $cart
                ];
                //$message = " Maximum available quantity has been reached !";
                return $this->sendError($details, 400);
            }
            //$message = "We entered there ! ".$request->get('quantity');
            $cart->save();
        }

        return $this->sendResponse(new CartResource($cart), $message);
    }

    /**
     * @OA\Get(
     ** path="/payment",
     *   tags={"Cart Controller"},
     *   summary="Add article to Cart by user",
     *      @OA\Parameter(name="article_id", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="user_id", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="order_total", in="query", required=true, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="payment_method", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="is_shipped", in="query", required=true, @OA\Schema(type="boolean")),
     *      @OA\Parameter(name="email", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="city", in="query", required=false,@OA\Schema(type="string")),
     *      @OA\Parameter(name="country", in="query", required=false,@OA\Schema(type="string")),
     *      @OA\Parameter(name="address", in="query", required=false,@OA\Schema(type="string")),
     *      @OA\Parameter(name="first_name", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="last_name", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="email", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="phone", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function pay(Request $request)
    {

        $user = User::findOrFail((int)$request->get('user_id'));

        if ($user == null) {
            return $this->sendError('User with the id ' . $request->get('user_id') . ' not found', 400);
        }

        $trans_id = "transaction_id_" . uniqid();

        $order_total = 0;

        $carts = Cart::where('user_id', $user->id)->where('status', 1)->get();
        foreach($carts as $cart)
        {
            $sum = $cart->getActualPrice();

            $order_total = $order_total + $sum;
        }

        //$exchangeRate = $this->exchangeRateService->getRate('XAF', 'USD');

        //$this->out->writeln("From Laravel API exchange rate : " . $exchangeRate);

        $purchase = new Purchase;
        $purchase->status = 1;
        $purchase->purchase_uid = uniqid();
        $purchase->amount = floatval($order_total);
        $purchase->payment_method = $request->get('payment_method');

        $this->out->writeln("From Laravel API is shipped: " . $request->get('is_shipped'));

        //$shippingAddress = new ShippingAddress();

        if ($request->get('is_shipped') == true) {

            $this->out->writeln("From Laravel API is shipped is true"); 

            $purchase->last_name = $request->get('last_name');
            $purchase->first_name = $request->get('first_name');
            $purchase->city = $request->get('city');
            $purchase->country = $request->get('country');
            $purchase->address = $request->get('address');
            $purchase->email = $request->get('email');
            $purchase->phone = $request->get('phone');
            $purchase->is_shipped = true;

            //$shippingAddress->setCity('University of Dschang');//University of Wollongong
            //$shippingAddress->setCountryCode("AU");
            //$shippingAddress->setPostalCode('Dschang 96');//U146
            //$shippingAddress->setLine1("Foto");//Wollongong New South Wales 2522
            //$shippingAddress->setState($request->get('Austalia'));
            //$shippingAddress->setRecipientName($request->get('last_name') . " " . $request->get('first_name'));

        }else{
            $this->out->writeln("From Laravel API is shipped is false"); 
            //$shippingAddress->setCity($user->city);
            //$shippingAddress->setCountryCode("CMR");
            //$shippingAddress->setPostalCode('200');
            //$shippingAddress->setLine1($user->address);
            //$shippingAddress->setState($user->country);
            //$shippingAddress->setRecipientName( $user->first_name . " " . $user->last_name);
        }

        $purchase->user()->associate($user);
        $purchase->save();

        $total = floatval($order_total) * 0.0017;

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();
        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('payment.status'),
                "cancel_url" => route('payment.status'),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => floatval($total)
                    ]
                ]
            ]
        ]);
        if (isset($response['id']) && $response['id'] != null) {
            // redirect to approve href
            foreach ($response['links'] as $links) {
                if ($links['rel'] == 'approve') {
                    return $this->sendResponse($links['href'], 'Redirect to paypal  !');
                    //return redirect()->away($links['href']);
                }
            }
            //return redirect()->route('createTransaction')->with('error', 'Something went wrong.');
            return $this->sendError('Something went wrong.', 500);
        } else {
            //return redirect()->route('createTransaction')->with('error', $response['message'] ?? 'Something went wrong.');
            return $this->sendError('Something went wrong.', 500);
        }


/*         $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        

        $item_1 = new Item();
        $item_1->setName('Media Interlligence Shop Payment')
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice(floatval($order_total * $exchangeRate));

        $item_list = new ItemList();
        $item_list->setItems(array($item_1));

        $inputFields = new InputFields();
        $inputFields->setNoShipping(1)->setAddressOverride(0);

        $webProfile = new WebProfile();
        $webProfile->setName('test' . uniqid())->setInputFields($inputFields);

        //$item_list->setShippingAddress($shippingAddress);

        $paypalAmount = new Amount();
        $paypalAmount->setCurrency('USD')->setTotal(floatval($order_total * $exchangeRate));

        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(URL::route('payment.status'))->setCancelUrl(URL::route('payment.status'));

        $transaction = new Transaction();
        $transaction->setAmount($paypalAmount)
            ->setItemList($item_list)
            ->setDescription('Media Intelligence payment via PayPal');

        $webProfileId = $webProfile->create($this->_api_context)->getId();

        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setExperienceProfileId($webProfileId)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));
        try {
            $payment->create($this->_api_context);
        } catch (\PayPal\Exception\PPConnectionException $ex) {
            if (\Config::get('app.debug')) {
                return $this->sendError('Connection timeout', 400);
            } else {
                return $this->sendError('Some error occur, sorry for inconvenient !');
            }
        }

        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        if (isset($redirect_url)) {
            $purchase->transaction_id = $payment->getId();
            $purchase->save();
            return $this->sendResponse($redirect_url, 'Redirect to paypal  !');
        } */

        $message = "Unknown error occurred !";
        return $this->sendError('Unknown error occurred !', 500);
    }

    public function getPaymentStatus(Request $request)
    {
        $purchase = Purchase::where('transaction_id', $request->input('paymentId'))->first();

        if (empty($request->input('PayerID')) || empty($request->input('token'))) {
            return Redirect(env('FRONT_URL') . '/payment-failed');
        }

        //$payment = Payment::get($request->input('paymentId'), $this->_api_context);

        //$execution = new PaymentExecution();
        //$execution->setPayerId($request->input('PayerID'));
        //$result = $payment->execute($execution, $this->_api_context);

        //if ($result->getState() == 'approved') {
            $purchase->status = 2;
            $purchase->save();

            $user_id = (int)$purchase->user_id;

            $carts = Cart::where('user_id', $user_id)->where('status', 1)->get();
            foreach($carts as $cart)
            {
                $purchaseCart = New PurchaseCart;
                $purchaseCart->cart()->associate($cart);
                $purchaseCart->purchase()->associate($purchase);
                $purchaseCart->save();

                //$purchase->quantity = $purchase->quantity + $cart->quantity;
                $purchase->save();

                $article = Article::find($cart->article_id);

                if($article){
                    //$article->quantity -= $cart->quantity;
                    $article->save();
                }

                $cart->status = 'PURCHASED';
                $cart->save();
            }

            $admins = User::where('is_admin', 1)->get();
            $user = User::findOrFail($user_id);

            $adminMessage = "L'Utilisateur " . $user->username . " vient d'effectuer un payement d'une somme de $ " . $purchase->amount . " pour la commande : " . $purchase->transaction_id;

            $userMessage = "Vous venez d'effectuer un payement d'une somme de $ " . $purchase->amount . " pour la commande : " . $purchase->transaction_id;

            $adminDetails = [
                'title' => "Validation d'une commande",
                'body' => $adminMessage
            ];



            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new PurchaseMail($adminDetails, null)); // send mail to admin
            }

            $files = [];
            $all_articles_names = "";
            foreach($carts as $cart)
            {
                $article = Article::find($cart->article_id);
                $files[] = [
                    'path' => $article->path,
                ];

                $all_articles_names = $all_articles_names . " " . $article->name;
            }


            $userDetails = [
                'title' => "Validation d'une commande",
                'body' => $userMessage . " Files attached : " . $all_articles_names
            ];
            
            Mail::to($user->email)->send(new PurchaseMail($userDetails, $files)); // send mail to user

            $message = "Payment success !";
            return Redirect(env('FRONT_URL') . '/payment-success?purchaseUid=' . $purchase->purchase_uid);
            //return $this->sendResponse($purchase, $message);
       // } 

        $purchase->status = 3;
        $purchase->save();
        //return $this->sendError('Payment failed !');
        return Redirect(env('FRONT_URL') . '/payment-failed');
    }

    public function initiateCinetPayment(Request $request)
    {
        $user = User::findOrFail((int)$request->get('user_id'));

        if ($user == null) {
            return $this->sendError('User with the id ' . $request->get('user_id') . ' not found', 400);
        }

        $trans_id = uniqid();

        $order_total = 0;

        $carts = Cart::where('user_id', $user->id)->where('status', 1)->get();
        foreach($carts as $cart)
        {
            $sum = $cart->getActualPrice();
            $order_total = $order_total + $sum;
        }

        $purchase = new Purchase;
        $purchase->status = 1;
        $purchase->purchase_uid = $trans_id;
        $purchase->transaction_id = $trans_id;
        $purchase->amount = floatval($order_total);
        $purchase->payment_method = $request->get('payment_method');
        $purchase->user()->associate($user);
        $purchase->save();

        $apiKey = env('API_KEY');
        $siteId = env('SITE_ID');
        $transactionId = $trans_id;
        $amount = 100;
        $currency = 'XAF';
        $description = 'TEST INTEGRATION';
        $customerId = $user->id;
        $customerName = $user->first_name;
        $customerSurname = $user->last_name;
        $customerEmail = $user->email;
        $customerPhoneNumber = $user->phone;
        $customerAddress = $user->address;
        $customerCity = "Douala";
        $customerCountry = $request->get('country');
        $customerState = '';
        $customerZipCode = '065100';
        $notifyUrl = env('API_URL') . '/api/notify-cinet-pay';
        $returnUrl = env('FRONT_URL') . '/'; //api/return-cinet-pay'; //. '/payment-success?purchaseUid=' . $purchase->purchase_uid;
        $channels = 'ALL';
        $metadata = 'user1';
        $lang = 'FR';

        $data = [
            'apikey' => $apiKey,
            'site_id' => $siteId,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'alternative_currency' => '',
            'description' => $description,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'customer_surname' => $customerSurname,
            'customer_email' => $customerEmail,
            'customer_phone_number' => $customerPhoneNumber,
            'customer_address' => $customerAddress,
            'customer_city' => $customerCity,
            'customer_country' => $customerCountry,
            'customer_state' => $customerState,
            'customer_zip_code' => $customerZipCode,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'channels' => $channels,
            'metadata' => $metadata,
            'lang' => $lang,
            'invoice_data' => [
                'Donnee1' => '',
                'Donnee2' => '',
                'Donnee3' => '',
            ],
        ];

        $response = Http::post('https://api-checkout.cinetpay.com/v2/payment', $data);

        if ($response->successful()) {
            return $response->json();
        } else {
            return $response->throw();
        }
    }

    public function notifyCinetPay(Request $request)
    {
        Log::info('API endpoint called function notifyCinetPay');
        $purchase = Purchase::where('transaction_id', $request->input('cpm_trans_id'))->first();
		
		$data = $request->all();
        $s_data = (json_encode($data));

        if ($request->input('cpm_error_message') == "PAYMENT_FAILED") {
            Log::info('API endpoint called function notifyCinetPay PAYMENT_FAILED');
            $purchase->status = 3;
        	$purchase->save();
			return;
        }else{

        //$apiKey = env('API_KEY');
        //$siteId = env('SITE_ID');
        //$transactionId = $request->input('cpm_trans_id');

        //$data = [
        //    'apikey' => $apiKey,
        //    'site_id' => $siteId,
        //    'transaction_id' => $transactionId,
        //];

        //$response = Http::post('https://api-checkout.cinetpay.com/v2/payment/check', $data);
        //$result = json_decode($response->getContent(), true);

        //if ($result['message'] == "SUCCES") {
            Log::info('API endpoint called function notifyCinetPay PAYMENT_SUCCES : ' .  $s_data);
            $purchase->status = 2;
            $purchase->save();

            $user_id = (int)$purchase->user_id;

            $carts = Cart::where('user_id', $user_id)->where('status', 1)->get();
            foreach($carts as $cart)
            {
                $purchaseCart = New PurchaseCart;
                $purchaseCart->cart()->associate($cart);
                $purchaseCart->purchase()->associate($purchase);
                $purchaseCart->save();
                $purchase->save();

                $article = Article::find($cart->article_id);
                $cart->status = 'PURCHASED';
                $cart->save();
            }

            $admins = User::where('is_admin', 1)->get();
            $user = User::findOrFail($user_id);

            $adminMessage = "L'Utilisateur " . $user->username . " vient d'effectuer un payement d'une somme de $ " . $purchase->amount . " pour la commande : " . $purchase->transaction_id;

            $userMessage = "Vous venez d'effectuer un payement d'une somme de $ " . $purchase->amount . " pour la commande : " . $purchase->transaction_id;

            $adminDetails = [
                'title' => "Validation d'une commande",
                'body' => $adminMessage
            ];

            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new PurchaseMail($adminDetails, null)); // send mail to admin
            }

            $files = [];
            $all_articles_names = "";
            foreach($carts as $cart)
            {
                $article = Article::find($cart->article_id);
                $files[] = [
                    'path' => $article->path,
                ];

                $all_articles_names = $all_articles_names . " " . $article->name;
            }

            $userDetails = [
                'title' => "Validation d'une commande",
                'body' => $userMessage . " Files attached : " . $all_articles_names
            ];
            
            Mail::to($user->email)->send(new PurchaseMail($userDetails, $files)); // send mail to user
			return;
        } 

        $purchase->status = 3;
        $purchase->save();
		return;
    }

    public function returnCinetPay(Request $request)
    {
        $data = $request->all();
        $s_data = (json_encode($data));
        $this->out->writeln("Return Cinet pay: " . $s_data);
    }

    public function getInvoice($uid, $lang)
    {
        $purchase = Purchase::where('purchase_uid', $uid)->first();
        if ($purchase == null) {
            return Redirect(env('FRONT_URL') . '/page-not-found');
        }
        $user = User::findOrFail($purchase->user_id);

        $this->out->writeln("From Laravel API language : " . $lang);

        //return view('receipt', compact('user', 'purchase'));
        if($lang == "en"){
            return $this->sendResponse(view('receipt', compact('user', 'purchase'))->render(), 'great !');
        }

        if($lang == "fr"){
            return $this->sendResponse(view('receipt_fr', compact('user', 'purchase'))->render(), 'great !');
        }

        if($lang == "de"){
            return $this->sendResponse(view('receipt_de', compact('user', 'purchase'))->render(), 'great !');
        }
        
    }


    public function getPaginatedPurchases()
    {

        $purchases = Purchase::where('status', 2)->orWhere('status', 4)->paginate(25);
        return $this->sendResponse(PurchaseResource::collection($purchases)->response()->getData(true), 'Purchases successfully Deleted.');
    }

    public function ShowPaginatedPurchases(Request $request)
    {
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }
        $purchases = Purchase::where('status', 2)->orWhere('status', 4)->paginate($numberElement);
        return $this->sendResponse(PurchaseResource::collection($purchases)->response()->getData(true), 'Purchases successfully Deleted.');
    }

    public function allPaginatedPurchases(Request $request)
    {
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }
        
        $purchases = Purchase::where('status', 2)->orWhere('status', 4)->orWhere('status', 5)->orWhere('status', 6)
        ->orWhere('status', 7)->orWhere('status', 8)->paginate($numberElement);
        return $this->sendResponse(PurchaseResource::collection($purchases)->response()->getData(true), 'Purchases successfully Deleted.');
    }

    /**
     * @OA\Get(
     ** path="/get/purchase/{id}",
     *   tags={"Cart Controller"},
     *   summary="Get purchase by id",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getPurchase($id)
    {
        $purchase = Purchase::findOrFail($id);
        return $this->sendResponse(new PurchaseResource($purchase), 'Purchase successfully retreived.');
    }

     /**
     * @OA\Get(
     ** path="/change/status/{status}/purchase/{id}",
     *   tags={"Cart Controller"},
     *   summary="Get purchasevariantid",
     *      @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */ 
    public function changePurchaseStatus($status, $id)
    {
        $purchase = Purchase::findOrFail((int)$id);
        $purchase->status = (int)$status;
        $purchase->save();

        $admins = User::where('is_admin', 1)->get();
        $user = User::findOrFail($purchase->user_id);

        $userMessage = "";
        $details = [];

        if($status == "2"){
            $userMessage = "La commande : " . $purchase->transaction_id . " a été payée avec succes !";

            $details = [
                'title' => "Commande Payée",
                'body' => $userMessage
            ];
        }

        if($status == "4"){
            $userMessage = "La commande : " . $purchase->transaction_id . " a été livré avec succes !";

            $details = [
                'title' => "Commande livrée",
                'body' => $userMessage
            ];
        }

        if($status == "5"){
            $userMessage = "La commande : " . $purchase->transaction_id . " a été reçu avec succes !";

            $details = [
                'title' => "Commande reçue",
                'body' => $userMessage
            ];
        }

        if($status == "6"){
            $userMessage = "La commande : " . $purchase->transaction_id . " a été retourné avec succes !";

            $details = [
                'title' => "Commande retournée",
                'body' => $userMessage
            ];
        }

        if($status == "7"){
            $userMessage = "La commande : " . $purchase->transaction_id . " a été annulé avec succes !";

            $details = [
                'title' => "Commande annulée",
                'body' => $userMessage
            ];
        }

        if($status == "8"){
            $userMessage = "La commande : " . $purchase->transaction_id . " n'a pas été livré !";

            $details = [
                'title' => "Commande n'a pas été livré",
                'body' => $userMessage
            ];
        }


        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new PurchaseMail($details)); // send mail to admin
        }

        Mail::to($user->email)->send(new PurchaseMail($details)); // send mail to admin


        return $this->sendResponse(new PurchaseResource($purchase), 'Purchase successfully retreived.');
    }
}
