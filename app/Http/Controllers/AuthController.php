<?php

namespace App\Http\Controllers;

use App\Models\OneTimePassword;
use DateTime;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Validator;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Laravel\Passport\Client as OClient;
use Illuminate\Auth\Events\Registered;
use function React\Promise\Stream\first;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClientAccountCreation;
use Illuminate\Support\Facades\Log;

class AuthController extends BaseController
{

    const OTP_LIFE_TIME =  300; //364 days life time, we remove one second for safe purposes
    public function getTokenAndRefreshToken(OClient $oClient, $email, $password)
    {
        $oClient = OClient::where('password_client', 1)->first();

        $data = [
            'grant_type' => 'password',
            'client_id' => $oClient->id,
            'client_secret' => $oClient->secret,
            'username' => $email,
            'password' => $password,
            'scope' => '*',
        ];
        $request = Request::create('/oauth/token', 'POST', $data);
        $content = json_decode(app()->handle($request)->getContent());

        return response()->json($content, 200);
    }

    /**
     * @OA\Post(
     *      path="/register",
     *      summary="Create a new user",
     *      tags={"Auth Controller"},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Register a new user",
     *          @OA\JsonContent(
     *              required={"email","password","username"},
     *              @OA\Property(property="username", type="string"),
     *              @OA\Property(property="first_name", type="string"),
     *              @OA\Property(property="last_name", type="string"),
     *              @OA\Property(property="email", type="string"),
     *              @OA\Property(property="phone", type="string"),
     *              @OA\Property(property="password", type="string"),
     *              @OA\Property(property="password_confirmation", type="string"),
     *              @OA\Property(property="question", type="string"),
     *              @OA\Property(property="answer", type="string"),
     *          ),
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users',
            'email' => 'required|unique:users|email',
            'password' => 'required|min:10|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&^#_-])[A-Za-z\d@$!%*?&^#_-]{10,}$/',
            'password_confirmation' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $input = $request->all();

        $user = User::create($input);
        $user->password = bcrypt($input['password']);
        $user->comment_privacy = 1;
        $user->status = 2;
        $user->user_uid = uniqid();
        $user->save();

        event(new Registered($user));

        $success['token'] = $user->createToken('MyApp')->accessToken;
        $success['user'] = $user;

        return $this->sendResponse($success, 'User register successfully.');
    }


    /**
     * @OA\Post(
     *      path="/login",
     *      tags={"Auth Controller"},
     *      summary="Login a registered user",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Pass user credentials",
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="user1@gmail.com"),
     *              @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *              @OA\Property(property="remember", type="boolean", example="true"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     */
    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
            'remember' => 'required',
        ]);

        /* if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }
 */
        $user = User::where('email', $request['email'])->first();

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {

            /* if ($user->status !== 'ACCEPTED') {
                return $this->sendError('This user is banned. Please contact an administrator', 403);
            }
 */
            if ($user->is_first_connection) {
                $data['is_first_connection'] = 1;
                $user->is_first_connection = 0;
                $user->save();
                $success['user'] = $data;
                return $this->sendResponse($success, 'Connection redirection.');
            }

            $user = Auth::user();
            $user->count_bad_request = 0;
            $user->save();

            /* if (!$user->hasVerifiedEmail()) {
                return $this->sendError($request->email, 403);
            } */


            if ($request->remember == 'true') {
                $objToken = $user->createToken('MyApp');
                $strToken = $objToken->accessToken;
                $success['token'] = $strToken;
                $success['user_id'] = $user->id;
                $success['user'] = $user;
                $success['remember'] = true;
            } else {

                $objToken = $user->createToken('MyApp');
                $strToken = $objToken->accessToken;
                $success['token'] = $strToken;
                $success['user_id'] = $user->id;
                $success['user'] = $user;
                $success['remember'] = false;
            }

            return $this->sendResponse($success, 'User login successfully.');
        }


        /* if ($user != null && $user->count_bad_request >= 5) {
            $user->status = "BANNED";
            $user->save();
            return $this->sendError('This user is banned. Please contact an administrator', 400);
        }
 */
        if ($user != null) {
            $user->count_bad_request++;
            $user->save();
        }

        return $this->sendError('Email or Password not valid !', 400);
    }

    public function resentEmail(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        $user->sendEmailVerificationNotification();

        return $this->sendResponse(true, 'Email send successfully.');
    }

    /**
     * @OA\Post(
     *      path="/login/admin",
     *      tags={"Auth Controller"},
     *      summary="Login a registered user",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Pass user credentials",
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="user1@gmail.com"),
     *              @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *              @OA\Property(property="remember", type="boolean", example="true"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     */
    public function loginAdmin(Request $request)
    {
        // Log the request payload (excluding sensitive data)
        Log::info('loginAdmin invoked', [
            'email'    => $request->get('email'),
            'remember' => $request->get('remember')
            // Excluding 'password' from logs for security reasons.
        ]);
    
        // Logging: Starting validation
        Log::info('Starting validation for loginAdmin');
        $validator = Validator::make($request->all(), [
            'email'    => 'required',
            'password' => 'required',
            'remember' => 'required',
        ]);
    
        if ($validator->fails()) {
            // Log validation error
            $error = $validator->errors()->first();
            Log::warning('Validation failed for loginAdmin', ['error' => $error]);
            return $this->sendError($error, 500);
        }
        // Logging: Validation passed
        Log::info('Validation passed for loginAdmin');
    
        // Attempt to authenticate the user
        Log::info('Attempting to authenticate user');
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Log success
            Log::info('User authenticated successfully');
    
            $user = Auth::user();
            Log::info('Authenticated user retrieved', [
                'user_id'    => $user->id,
                'user_email' => $user->email,
                'status'     => $user->status
            ]);
    
            // Reset or update user data
            $user->count_bad_request = 0;
            if ($user->status !== 'ACCEPTED') {
                $user->status = 'ACCEPTED';
                Log::info('User status updated to ACCEPTED', [
                    'user_id' => $user->id
                ]);
            }
    
            $user->login_well_on = new DateTime();
            $user->save();
            Log::info('User login timestamp updated', ['user_id' => $user->id]);
    
            // Token generation
            $objToken = $user->createToken('MyApp');
            $strToken = $objToken->accessToken;
    
            $success['token']    = $strToken;
            $success['user_id']  = $user->id;
            $success['remember'] = ($request->remember === 'true');
    
            if ($request->remember === 'true') {
                Log::info('Remember me is true, token generated', $success);
            } else {
                Log::info('Remember me is false, token generated', $success);
            }
    
            // Check admin or super admin access
            if ($user->is_admin == 1 || $user->is_super_admin == 1) {
                Log::info('User is admin/super admin. Sending success response', [
                    'user_id' => $user->id
                ]);
                return $this->sendResponse($success, 'User login successfully.');
            } else {
                Log::warning('Unauthorized access attempt - not admin/super admin', [
                    'user_id' => $user->id
                ]);
                return $this->sendError('Unauthorised You do not have access !', 401);
            }
        }
    
        // If authentication fails:
        Log::warning('Authentication failed for loginAdmin');
        $user = User::where('email', $request->email)->first();
    
        if ($user !== null) {
            // If user is already banned or has repeated bad requests
            if ($user->count_bad_request >= 5) {
                $user->status = "BANNED";
                $user->save();
                Log::warning('User is banned due to repeated bad requests', [
                    'user_id' => $user->id
                ]);
                return $this->sendError('This user is banned. Please contact an administrator', 400);
            }
    
            // Increment bad request count
            $user->count_bad_request++;
            $user->save();
            Log::info('Incremented count_bad_request for user', [
                'user_id'           => $user->id,
                'count_bad_request' => $user->count_bad_request
            ]);
    
            return $this->sendError('Email or Password not valid !', 400);
        }
    
        // Optional fallback if user record doesn't exist at all
        Log::warning('No user found with provided email');
        return $this->sendError('Email or password not valid !', 400);
    }

    /**
     * @OA\Get(
     ** path="/people",
     *   tags={"Auth Controller"},
     *   summary="Get all people",
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
     *
     * @return Response
     */
    public function getAllUserUsers()
    {

        $users = User::where("is_admin", 1)->get();

        return $this->sendResponse($users, 'Admins successfully retreived.');
    }

    public function generateOtp($id)
    {

        $authUser = User::findOrFail($id);

        $user = User::where('email', $authUser->email)->first();

        if ($authUser) {

            $otp = $this->generateOtpService($authUser);
            // dd(strlen($otp->getOtp()));
            //return $this->sendResponse($otp, 'Feature value successfully added.');
            return $this->sendResponse(['success' => true, "otp" => $otp], 200);
        }
        // return $this->json(['success' => false, "message" => "UNAUNTHORIZED ACCESS: Please login"], 403);
        return $this->sendError("UNAUNTHORIZED ACCESS: Please login", 403);
    }

    public function validateOtp(Request $request)
    {
        $data = $request->all();

        if (array_key_exists("otp", $data) && ($data['otp'] != null)) {
            $otp = $data['otp'];
            $response = $this->validateOtpservice($otp);
            return $this->sendResponse($response, 'Generated.');

            //return $this->json($response, 200);
        }
        return $this->sendError("Bad request", 400);

        // return $this->json(["success" => false, "user_email" => null, "message" => "OTP is not available in request"]);
    }

    public function generateOtpService($user)
    {
        $now = new \DateTimeImmutable();
        // $time = $this->createdAt->getTimestamp();
        $time = $now->getTimestamp();
        $time += self::OTP_LIFE_TIME;

        $otp = new OneTimePassword();
        $otp->otp = strtoupper(bin2hex(random_bytes(10)));
        $otp->user_email = $user->email;
        $otp->expired_at = (new \DateTimeImmutable())->setTimestamp($time);
        $otp->save();
        return $otp;
    }


    public function validateOtpservice(string $otp)
    {
        $otpObject = OneTimePassword::where('otp', $otp)->first();

        if ($otpObject == null) {
            return (["success" => false, "user_email" => null, "message" => "OTP does not exists"]);
        }

        $now = new DateTime();

        // if ($now > $otpObject->expired_at) {
        //  return (["success" => false, "user_email" => null, "message" => "OTP has expired"]);
        // }

        $user = User::where('email', $otpObject->user_email)->first();

        $email = $otpObject->user_email;



        $objToken = $user->createToken('MyApp');
        $token = $objToken->accessToken;

        $success['token'] = $token;
        $success['user_email'] = $email;
        $success['user_id'] = $user->id;
        $success['user'] = $user;
        $success['success'] = true;

       // $otpObject->delete();
        return $success;
    }

    public function getAAllUsers()
    {

        $users = User::all();

        return $this->sendResponse($users, 'Admins successfully retreived.');
    }

    public function CreateClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'firstName' => 'required',
            'phoneNumber' => 'required',
            'email' => 'required|unique:users|email',
            'company' => 'required',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $user = new User();
        $user->last_name =  $request->name;
        $user->first_name = $request->firstName;
        $user->phone = $request->phoneNumber;
        $user->email = $request->email;
        $user->about_me = $request->company;
        $user->status = 'ACCEPTED';
        $password = bin2hex(random_bytes(10));
        $user->password = bcrypt($password);
        $user->save();

        $details = [
            'title' => "Client account creation",
            'email' => $user->email,
            'password' =>  $password ,
        ];

        Mail::to($user->email)->send(new ClientAccountCreation($details));

        return $this->sendResponse(true, 'Email send successfully.');
    }

    public function UserConnected()
{
    if (Auth::check()) {
        // L'utilisateur est authentifié
        return response()->json(['message' => 'User is authenticated'], 200);
    } else {
        // L'utilisateur n'est pas authentifié
        return response()->json(['message' => 'User is not authenticated'], 401);
    }
}


    /**
     * Génère un token JWT pour l'utilisateur authentifié.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateToken(Request $request)
    {
        // Vérifier si l'utilisateur est authentifié
        /*if (!Auth::guard('web')->check()) {
            return response()->json(['error' => 'Non authentifié'], 405);
        }*/

        // Récupérer l'utilisateur authentifié
        $user = Auth::user();

        $objToken = $user->createToken('MyApp');
        $token = $objToken->accessToken;

        $success['token'] = $token;
        $success['user_email'] = $email;
        $success['user_id'] = $user->id;
        $success['user'] = $user;
        $success['success'] = true;

        // Renvoyer le token dans la réponse
        return $success;
    }




}
