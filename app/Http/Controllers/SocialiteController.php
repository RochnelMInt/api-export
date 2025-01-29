<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Socialite;
use App\Models\User;
use App\Helpers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;


class SocialiteController extends BaseController
{
    //
    // Les tableaux des providers autorisés
    protected $providers = [ "google", "github", "facebook" ];

    //On récupère le mot de passe généré aléatoirement
    protected $pwd;

    public function __construct()
    {
        $this->pwd = Helpers::generateRandomPassword();
        $this->parameterValue = null;
    }

    public function loginRegister (Request $request){

    }

    public function callBack(Request $request){
        $provider = $request->provider;


          // Les informations provenant du provider
          $userSocial = Socialite::driver('google')->stateless()->setHttpClient(
            new \GuzzleHttp\Client(['verify' => false])
        )->user();

            // Les informations de l'utilisateur
            //$user = $data->user;

            // voir les informations de l'utilisateur
            //dd($user);

            $token= $userSocial->token;
            $googleUser = $userSocial->user;
            // Les informations de l'utilisateur
            $id = $userSocial->getId();
            $nickname = $googleUser['given_name']; //$userSocial->getNickname();
            $name = $googleUser['family_name'];  //$userSocial->getName();
            $email = $userSocial->getEmail();
            $avatar = $userSocial->getAvatar();
            echo $nickname;
             # 1. On récupère les information de l'utilisateur s'il existe 
            $user = User::where("email", "$email")->first();




            # 2. Si l'utilisateur existe
            if (isset($user)) {
                
                // Mise à jour des informations de l'utilisateur
                if ($user->is_first_connection) {
                    $data['is_first_connection'] = 1;
                    $user->is_first_connection = 0;
                    $user->save();
                    $success['user'] = $data;
                    Auth::login($user); //Connecter l'utilisateur
                    return Redirect::to(env('SSO_IP_ADRESS_REDIRECTION') . '?email=' . urlencode($email) . '&LastUrl=' . $this->parameterValue);
                    //return $this->sendResponse($success, 'Connection redirection.');
                }
    
                //$user = Auth::user();
                //$user->count_bad_request = 0;
                //$user->save();
      
                if ($request->remember == 'true') {

                    $objToken = $user->createToken('MyApp');
                    $strToken = $objToken->accessToken;
                    $success['token'] = $strToken;
                    $success['user_id'] = $user->id;
                    $success['user'] = $user;
                    $success['remember'] = true;

                    Auth::login($user); //Connecter l'utilisateur

                } else {
                    //Auth::login($user); //Connecter l'utilisateur

    
                    $objToken = $user->createToken('MyApp');
                    $strToken = $objToken->accessToken;
                    $success['token'] = $strToken;
                    $success['user_id'] = $user->id;
                    $success['user'] = $user;
                    $success['remember'] = false;
                }

                // Créer une réponse de redirection
                //$response = Response::redirectTo(env('SSO_IP_ADRESS_REDIRECTION') . '?email=' . urlencode($email));
                Auth::login($user); //Connecter l'utilisateur
                return Redirect::to(env('SSO_IP_ADRESS_REDIRECTION') . '?email=' . urlencode($email) . '&LastUrl=' . $this->parameterValue);
            # 3. Si l'utilisateur n'existe pas, on l'enregistre
            } else {
                //Auth::login($userSocial); //Connecter l'utilisateur

                $googleUser = $userSocial->user;

                // Enregistrement de l'utilisateur
                $newUser = User::create([
                    'username' => $nickname . $name,
                    'first_name' => $nickname,
                    'last_name'=> $name,
                    'email' => $email,
                    'password' => bcrypt($this->pwd) // On attribue un mot de passe aléatoirement provenant du constructeur
                ]);

                //$user = User::create($input);
                //$user->password = bcrypt($this->$pwd); // On attribue un mot de passe aléatoirement provenant du constructeur

                $newUser->save();
                Auth::login($newUser); //Connecter l'utilisateur

                //$success['token'] = $user->createToken('MyApp')->accessToken;
                //$mytoken = $user->createToken('MyApp')->accessToken;
                //$success['user'] = $user;

                // Créer une réponse JSON avec le token
                /*$response = Response::json([
                    'token' => $mytoken,
                    'redirect_url' => 'http://example.com/page',
                ]);*/

                // Ajouter le header 'Authorization' à la réponse
                //$response->header('Authorization', 'Bearer ' . $token);

                // Créer une réponse de redirection
                //$response = Response::redirectTo(env('SSO_IP_ADRESS_REDIRECTION') . '?email=' . urlencode($email));
                
                return Redirect::to(env('SSO_IP_ADRESS_REDIRECTION') . '?email=' . urlencode($email) . '&LastUrl=' . $this->parameterValue);
                //return $this->sendResponse($success, 'User register successfully.');
            }


    }
    

    public function redirect(Request $request){
        $provider = $request->provider;
        // On vérifie si le provider est autorisé
            return Socialite::driver('google')->stateless()->redirect(); // On redirige vers le provider
        //abort(404); // Si le provider n'est pas autorisé    
    }
}
