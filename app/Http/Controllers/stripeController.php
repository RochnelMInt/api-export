<?php

namespace App\Http\Controllers;

use App\Mail\PurchaseMail;
use App\Models\Article;
use App\Models\Cart;
use App\Models\Purchase;
use App\Models\PurchaseCart;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class stripeController extends Controller
{
    private $out;

    public function __construct()
    {
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    public function test(Request $request): JsonResponse
    {
        return response()->json([
            "message" => "Bonjour tout se passe bien",
            "success" => true
        ]);
    }

    public function getNewClient(): StripeClient
    {
        return new StripeClient(
            env('STRIPE_SK_KEY')
        );
    }

    public function createPaymentMethod(): \Stripe\PaymentMethod
    {
        $stripe = $this->getNewClient();

        $paymentMethod = $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 8,
                'exp_year' => 2024,
                'cvc' => '314',
            ],
        ]);
        return $paymentMethod;
    }

    public function createCustomer($name, $email): Customer
    {

        $stripe = $this->getNewClient();

        $customer = $stripe->customers->create([
            'name' => $name,
            // 'description' => 'My First Test Customer',
            'email' => $email,
            //'payment_method' => 'pm_1MQTzwKeHcCj15fGpK5JJh2o'
        ]);
        return $customer;
    }

    public function createPaymentIntent(Request $request): PaymentIntent
    {

        $user = null;
        $name = null;
        $email = null;
        $price = null;

        if ($request->input('user_id') != null) {
            $userId = $request->input('user_id');
            $user = User::find($userId);

            if ($user == null) {
                return $this->sendError('User not found', 400);
            }
        }

        $name = $user->first_name.$user->last_name;
        $email = $user->email;

        $order_total = 0;

        $carts = Cart::where('user_id', $user->id)->where('status', 1)->get();
        foreach($carts as $cart)
        {
            $sum = $cart->getActualPrice();

            $order_total = $order_total + $sum;
        }

        $this->out->writeln("From Laravel API is stripe price : " . $order_total);

        if ($order_total < 400) {
            $price = 400;
        }else{
            $price = $order_total;
        }

        $stripe = $this->getNewClient();

        $customer = $this->createCustomer($name, $email);

        //TODO Convert the price to a flot value before times it bu 100
        $paymentIntent = $stripe->paymentIntents->create([
            //'amount' => is_float($price) ? $price * 100 : $price."00",
            'amount' =>  (int)$price, //(sprintf("%.2f", $price))* 100,
            'currency' => 'xaf',
            'payment_method_types' => ['card'],
            'off_session' => false,
            'customer' => $customer->id,
            // 'payment_method' => $paymentMethod->id,
        ]);

        return $paymentIntent;
    }

    public function retrievePaymentIntent(Request $request)
    {
        $stripe = $this->getNewClient();

        $clientSecret = null;
        $user = null;

        if ($request->input('client_secret') != null) {
            $clientSecret = $request->input('client_secret');
        }

        if ($request->input('user_id') != null) {
            $userId = $request->input('user_id');
            $user = User::find($userId);

            if ($user == null) {
                return $this->sendError('User not found', 400);
            }
        }

        $paymentIntent = $stripe->paymentIntents->retrieve(
            $clientSecret,
            []
        );

        if ($paymentIntent->status == "succeeded") {
            $trans_id = "transaction_id_" . uniqid();

            $purchase = new Purchase;
            $purchase->purchase_uid = uniqid();
            $purchase->status = 2;
            $purchase->amount = ($paymentIntent->amount/100);
            $purchase->payment_method = "card";
            $purchase->last_name = $user->last_name;
            $purchase->first_name = $user->first_name;
            $purchase->city = $user->city;
            $purchase->country = $user->country;
            $purchase->address = $user->address;
            $purchase->email = $user->email;
            $purchase->phone =$user->phone;
            $purchase->is_shipped = true;
            $purchase->transaction_id = $trans_id;

            $purchase->user()->associate($user);
            $purchase->save();


            $carts = Cart::where('user_id', $user->id)->where('status', 1)->get();

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
            $user = User::findOrFail((int)$request->input('user_id'));

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

            return 'payment-success?purchaseUid=' . $purchase->purchase_uid;
        }
        return null;
    }

    public function confirmPaymentIntent(Request $request): PaymentIntent
    {
        $clientSecret = null;
        $paymentMethod = null;

        $stripe = $this->getNewClient();

        if ($request->input('client_secret') != null) {
            $clientSecret = $request->input('client_secret');
        }

        if ($request->input('payment_method') != null) {
            $paymentMethod = $request->input('payment_method');
        }

        $paymentIntent = $stripe->paymentIntents->confirm(
            $clientSecret,
            $paymentMethod
        );

        return $paymentIntent;

    }

//    public function chargeClient(Request $request): JsonResponse
//    {
//        $paymentIntent = $this->createPaymentIntent();
//        $clientSecret = $paymentIntent->client_secret;
//
//        $paymentIntentConfirm = $this->confirmPaymentIntent()
//
//
//    }
}
