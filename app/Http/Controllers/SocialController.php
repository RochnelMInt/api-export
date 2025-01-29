<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Socialite;
use App\Models\User;
use Validator;

class SocialController extends BaseController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function redirectToProvider($provider)
    {
        $success['provider_redirect'] = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
   
        return $this->sendResponse($success, "Provider '".$provider."' redirect url.");
    }
        
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function handleProviderCallback($provider)
    {
        try {
            $providerUser = Socialite::driver($provider)->stateless()->user();
            
            if ($providerUser) {

                $user = User::where('provider', $provider)->where('provider_id', $providerUser->getId())->first();

                if ($user) {
                    $success['token'] =  $user->createToken('MyApp')->accessToken;
                    $success['user_id'] =  $user->id;
                    $success['remember'] =  false;

                    return $this->sendResponse($success, 'User register successfully with social account.');
                } else {
                    $user = null;
        
                    if ($email = $providerUser->getEmail()) {
                        $user = User::where('email', $email)->first();
                    }
        
                    if (! $user) {
        
                        $user = User::create([                            
                            'username' => $providerUser->getName(),
                            'email' => $providerUser->getEmail(),           
                        ]);

                        $user->provider_id = $providerUser->getId();
                        $user->provider = $provider;
                        $user->status = 2;
                        $user->user_uid = uniqid();
                        $user->email_verified_at = now();
                        $user->save();
                    }
                }
                $success['token'] =  $user->createToken('MyApp')->accessToken;
                $success['user_id'] =  $user->id;
                $success['remember'] =  false;

                return $this->sendResponse($success, 'User register successfully with social account.');
            }

        } catch (Exception $exception) {
            return $this->sendError($exception, 500);
        }        
    }
}
