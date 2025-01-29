<?php

namespace App\Http\Controllers;

use App\Http\Resources\PurchaseResource;
use App\Models\MediaLibrary;
use App\Models\Purchase;
use Illuminate\Http\Request;

use App\Http\Requests\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserMessage;
use App\Models\Post;
use Validator;
use \Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\Sendmail;
use App\Mail\ForgotMail;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UserResource;
use App\Models\Alert;
use Carbon\Carbon;

class UserController extends BaseController
{
    private $out;

    public function __construct()
    {
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    public function getAlert()
    {
        $alert = Alert::take(1)->first();
        return $this->sendResponse($alert, 'Alert successfully retrieved.');
    }

    /**
     * @OA\Post(
     ** path="/resetpassword",
     *   tags={"User Controller"},
     *   summary="ResetPassword",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Pass user credentials",
     *          @OA\JsonContent(
     *              required={"email","password", "password_confirmation", "token"},
     *              @OA\Property(property="email", type="string", format="email", example="user1@gmail.com"),
     *              @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *              @OA\Property(property="password_confirmation", type="string", format="password", example="PassWord12345"),
     *              @OA\Property(property="token", type="string"),
     *          ),
     *      ),
     *
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *)
     **/
    /**
     * reset password api
     *
     * @return Response
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:10|regex:/(?=.*[a-zA-Z])(?=.*[0-9])/',
            'password_confirmation' => 'required|same:password',
           // 'token' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $email = $request->email;
        $token = $request->token;
        $password = Hash::make($request->password);

        $emailcheck = DB::table('password_resets')->where('email', $email)->first();
        $pincheck = DB::table('password_resets')->where('token', $token)->where('email', $email)->first();

        if (!$pincheck) {
            return $this->sendError('Email not found', "404");
        }

        $created_date = Carbon::parse($pincheck->created_at);
        $now = Carbon::now();

        if(abs($created_date->getTimestamp() - $now->getTimestamp()) > 300){
            return $this->sendError('Token already expired. Send another mail !', "404");
        }

        if (!$pincheck) {
            return $this->sendError('Token not valid', "404");
        }

        DB::table('users')->where('email', $email)->update(['password' => $password]);
        DB::table('password_resets')->where('email', $email)->delete();

        $user = User::where('email', $email)->first();

        return $this->sendResponse($user, 'Password successfully changed !', 200);

    }

    /**
     * @OA\Post(
     ** path="/forgetpassword",
     *   tags={"User Controller"},
     *   summary="ForgetPassword",
     *   operationId="ForgetPassword",
     *
     *   @OA\Parameter(
     *      name="email",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *)
     **/
    /**
     * reset password api
     *
     * @return Response
     */
    public function forgetPassword(Request $request)
    {

        $email = $request->email;

        if (User::where('email', $email)->doesntExist()) {
            return $this->sendError('Email not found', "404");
        }

        $user = User::where('email', $email)->first();
        //generate Randow token
        $token = rand(10, 10000000);

        try {
            DB::table('password_resets')->insert([
                'email' => $email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            $details = [
                'title' => 'Forget Password',
                'body' => 'Click on the link below to change your password',
                'token' => $token,
                'user' => $user,
            ];

            Mail::to($email)->send(new ForgotMail($details));

            return $this->sendResponse('Reset password mail Send !', 'Reset password mail send on your email !');

        } catch (Exception $exception) {
            return $this->sendError('Mail send error.', $exception->getMessage(), 400);
        }
    }


    /**
     * @OA\Post(
     *      path="/update/{id}",
     *      tags={"User Controller"},
     *      summary="Update one user by id",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="first_name", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="last_name", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="address", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="gender", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="about_me", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="username", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="city", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="country", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="phone", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     */
    public function updateInformation(Request $request, int $id)
    {
        try {
            $user = User::find($id);

            if (is_null($user)) {
                return $this->sendError("User not found", 500);
            }

            if ($request->first_name != "") {
                $user->first_name = $request->first_name;
            }
            if ($request->last_name != "") {
                $user->last_name = $request->last_name;
            }

            if ($request->username != "") {
                $user->username = $request->username;
            }
            if ($request->phone != "") {
                $user->phone = $request->phone;
            }
            if ($request->gender != "") {
                $user->gender = $request->gender;
            }

            if ($request->address != "") {
                $user->address = $request->address;
            }

            if ($request->city != "") {
                $user->city = $request->city;
            }
            if ($request->country != "") {
                $user->country = $request->country;
            }

            if ($request->about_me != "") {
                $user->about_me = $request->about_me;
            }

            if ($request->avatar != "") {
                $user->avatar = $request->avatar;
            }

            $user->save();

            return $this->sendResponse($user, 'Information successfully updated !', 200);

        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage(), 500);
        }
    }


    /**
     * @OA\Post(
     *      path="/user/{id}/avatar",
     *      tags={"User Controller"},
     *      summary="Update user avatar by id",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     * @param int $id
     */
    public function changeAvatar(Request $request, $id)
    {
        $this->out->writeln("From Laravel API : " . $id);
        //$this->out->writeln("From Laravel API : " . implode(" ", $request->all()));
        $user = User::find($id);

        if (is_null($user)) {
            return $this->sendError("User not found", 500);
        }

        //if ($request->file('avatar') == null) {
        //    return $this->sendResponse($user, 'Avatar successfully updated !', 200);
        //}

        if($request->hasfile('avatar'))
        {
            $this->out->writeln("From Laravel API : has avatarr");
            try {
                $file = $request->file('avatar');

                $name = time() . '.' . $file->getClientOriginalName();
                $file->move(public_path() . '/media/', $name);

                $media = new MediaLibrary;
                $media->path = $name;
                $media->referral = 1;
                $media->save();

                $user->avatar = $name;
                $user->save();
                return $this->sendResponse($user, 'Avatar successfully updated !', 200);
            } catch (Exception $exception) {
                return $this->sendError($exception->getMessage(), 500);
            }
        }

    }

        /**
     * @OA\Post(
     *      path="/user/{id}/change/password",
     *      tags={"User Controller"},
     *      summary="Change user password by id",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     * @param int $id
     */
    public function changePassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:10|regex:/(?=.*[a-zA-Z])(?=.*[0-9])/',
            'new_password' => 'required|min:10|regex:/(?=.*[a-zA-Z])(?=.*[0-9])/',
            'confirm_new_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $email = $request->email;
        $token = $request->token;
        $password = Hash::make($request->new_password);

        DB::table('users')->where('id', $id)->update(['password' => $password]);

        $user = User::where('id', $id)->first();

        return $this->sendResponse($user, 'Password successfully changed !', 200);
    }

    public function EditPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:10|regex:/(?=.*[a-zA-Z])(?=.*[0-9])/',
            'password_confirmation' => 'required|same:password',
           // 'token' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $email = $request->email;
        //$token = $request->token;
        //$password = Hash::make($request->password);

        $user = User::where('email', $email)->first();

        if (!$user) {
            return $this->sendError('Email not found', "404");
        }

        $created_date = Carbon::parse($user->created_at);
        $now = Carbon::now();

        /* if(abs($created_date->getTimestamp() - $now->getTimestamp()) > 300){
            return $this->sendError('Password already expired. Send another mail !', "404");
        } */

        if (!$user) {
            return $this->sendError('Token not valid', "404");
        }

        $user->password = bcrypt($request->password);
        $user->is_first_connection = 0;

        $user->save();

        //$token = DB::table('oauth_access_tokens')->where('user_id', $user->id)->first();

        $objToken = $user->createToken('MyApp');
        $strToken = $objToken->accessToken;

        //$token = $token == null? $strToken : $token;
        //if($token){
            $success['token'] = $strToken;
            $success['user_email'] = $email;
            $success['user_id'] = $user->id;
            $success['user'] = $user;
            $success['success'] = true;

            return $this->sendResponse($success, 'Password successfully changed !', 200);
        //}

        //return $this->sendError($token, 500);

    }
    /**
     * @OA\Get(
     ** path="/me",
     *   tags={"User Controller"},
     *   summary="Retur connected user",
     *
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *)
     * me api
     *
     * @return Response
     */
    public function me()
    {
        $user = Auth::user();
        //$user = session('authUser');

        if ($user == null) {
            return $this->sendError('Unauthorised', 401);
        }
        return $this->sendResponse(new UserResource($user), 'Connected user successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/get-max-price",
     *   tags={"User Controller"},
     *   summary="Get post max price",
     *
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *)
     *
     * @return Response
     */
    public function getMaxPrice()
    {
        //$post = DB::table('posts')->max('unit_price');

        $post = Post::orderBy('unit_price', 'desc')->first();

        $maxPrice = $post->unit_price;

        return $this->sendResponse($maxPrice, 'Max price successfully retreived.');
    }


    /**
     * @OA\Put(
     ** path="/status/user/{id}",
     *   tags={"User Controller"},
     *   summary="change admin status inside activated and desactivated ",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *)
     *
     * @return Response
     */
    public function updateUserStatus($id): Response
    {

        $user = User::find($id);


        if ($user->status !== 'BANNED') {
            $user->status = 'BANNED';
        } else {
            $user->status = 'ACCEPTED';
        }

        $user->save();

        return response([
            'message' => 'User status updated',
        ], 200);

        //return $this->sendResponse($user, 'Admin activated status updated');
    }

    /**
     * @OA\Post(
     ** path="/privacy/user/{id}",
     *   tags={"User Controller"},
     *   summary="change privacy about comments",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="privcay", in="query", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *)
     *
     * @return Response
     */
    public function updatePrivacy($id, Request $request)
    {

        $user = User::find($id);

        if ($user == null) {
            return $this->sendError('User not found', 404);
        }

        $user->comment_privacy = (int)$request->privacy;

        $user->save();

        return $this->sendResponse($user, 'Privacy successfully updated');
    }

    public function getPurchases(Request $request, $id)
    {
        $numberElement = $request->numberElement;

        if($numberElement <= 0){
            $numberElement = 25;
        }
        $purchases = Purchase::where('user_id', $id)->where('status', 'SUCCESS')->paginate($numberElement);
        return $this->sendResponse(PurchaseResource::collection($purchases)->response()->getData(true), 'Purchases successfully retrieved.');
    }

    public function getUserPurchase($userId, $purchaseId)
    {
        $purchase = Purchase::where('user_id', '=', $userId)
                                ->where('id', '=', $purchaseId)
                                ->first();
        return $this->sendResponse(new PurchaseResource($purchase), 'Purchase successfully retrieved.');
    }

    /**
     * @OA\Post(
     ** path="/send/user/mail",
     *   tags={"User Controller"},
     *   summary="Send user mail",
     *
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function sendUserMail(Request $request)
    {
        $userMessage = new UserMessage;
        $userMessage->subject = $request->subject;
        $userMessage->message = $request->message;
        $userMessage->email = $request->email;
        $userMessage->phone = $request->phone;

        $userMessage->save();

        $admins = User::where('is_admin', 1)->get();

        $details = [
            'title' => $request->subject,
            'body' => "User email: " . $userMessage->email . ", user phone number: " . $userMessage->phone . " " . $request->message
        ];

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new Sendmail($details)); // send mail to admin
        }

        return $this->sendResponse($userMessage, 'Mail successfully send.');
    }

    public function getUserByMail(Request $request){
        $mail = $request->email;

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $user= User::where('email', $mail)->first();
        if(isset($user)){
            Auth::login($user);
            $objToken = $user->createToken('MyApp');
            $strToken = $objToken->accessToken;
            $success['token'] = $strToken;
            $success['user_id'] = $user->id;
            $success['user'] = $user;
        }
        
        return $this->sendResponse($success,'The user informations has been retreived',200);
    }

    public function removeUserAvatar(Request $request, $id)
    {

        $user = User::find($id);

        if ($user == null) {
            return response([
                'message' => 'User not found',
                'admin' => null
            ], 404);
            //return $this->sendError('Not found', 404);
        }

        if (is_null($user)) {
            return response([
                'message' => 'Unauthorized'
            ], 401);
        }

        try {

            $user->avatar = "user.png";
            $user->save();

            return response([
                'message' => 'Updated Successfull',
                'user' => $user
            ], 200);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 400);
        }
    }

    public function upload(Request $request, $id)
        {
            $user= User::find($id);

            if($request->hasfile('image')){
                $file= $request->file('image');
                    $name = time().'.'.$file->getClientOriginalName();
                    $file->move(public_path().'/media/', $name);
    
                    $media = New MediaLibrary;
                    $media->path = $name;
                    $media->type = 1;
                    $media->referral = 1;
                    $media->user()->associate($user);
                    $media->save();
                
            }
            return $this->sendResponse($name, 'Image added successfully.');
            // Extraire la chaîne base64 de l'image
            //$base64Image = $request->input('image');

            // Décoder la chaîne base64
            //$imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));

            // Générer un nom de fichier unique
            //$imageName = time() . '.jpeg';

            // Sauvegarder l'image dans le dossier public/images
            //Storage::disk('public')->put('images/' . $imageName, $imageData);

            // Retourner le chemin de l'image
            //$imageUrl = url('api/storage/images/' . $imageName);

            //return response()->json(['imageUrl' => $imageUrl]);
        }

        public function downloadImage($imageName)
        {
            $filePath = Storage::disk('public')->path('images/' . $imageName);

            if (!Storage::disk('public')->exists('images/' . $imageName)) {
                return response()->json(['message' => 'Image not found'], 404);
            }
    
            $imageData = base64_encode(file_get_contents($filePath));
            $base64Image = 'data:' . mime_content_type($filePath) . ';base64,' . $imageData;
    
            return response()->json(['image' => $base64Image], 200);
        }
        
    }
