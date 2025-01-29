<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminResource;
use App\Http\Resources\UserResource;
use App\Mail\AdminMail;
use App\Mail\SendAdminmail;
use App\Mail\Sendmail;
use App\Models\Article;
use App\Models\Comment;
use App\Models\MediaLibrary;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Faq;
use App\Models\Agb;
use App\Models\Post;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Validator;

class AdminController extends BaseController
{
    /**
     * @OA\Get(
     ** path="/users",
     *   tags={"Admin Controller"},
     *   summary="Get all users",
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
    public function getAllUsers()
    {

        $users = User::where('is_admin', 0)
            ->where('is_super_admin', 0)
            ->get();

        return $this->sendResponse($users, 'Users successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/paginate/admins",
     *   tags={"Admin Controller"},
     *   summary="Get paginated users",
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
    public function getPaginatedAdmins(Request $request)
    {

        $page = $request->page;
        $numberElement = $request->numberElement;

        if($numberElement == null){
            $numberElement = 25;
        }

        $admins = User::where('is_admin', 1)
            ->where('is_super_admin', 0)
            ->paginate($numberElement);


        //$admins = User::paginate(25);

        return $this->sendResponse($admins, 'Users successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/paginate/users",
     *   tags={"Admin Controller"},
     *   summary="Get paginated users",
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
    public function getPaginatedUsersWithRange(Request $request)
    {

        $numberElement = (int)$request->numberElement;

        if($numberElement <= 0){
            $numberElement = 25;
        }

        $users = User::where('is_admin', 0)
           // ->where('is_super_admin', 0)
            ->paginate($numberElement);


        //$admins = User::paginate(25);

        return $this->sendResponse($users, 'Users successfully retreived.');
    }

    public function getPaginatedUsers(Request $request)
    {

        $numberElement = (int)$request->numberElement;

        if($numberElement <= 0){
            $numberElement = 25;
        }

        $users = User::where('is_admin', 0)
           // ->where('is_super_admin', 0)
            ->orderBy('id','DESC')
            ->paginate($numberElement);


        //$admins = User::paginate(25);

        return $this->sendResponse($users, 'Users successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/admins",
     *   tags={"Admin Controller"},
     *   summary="Get all admins",
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
    public function getAllAdmins()
    {

        $admins = User::where("is_admin", 1)->get();

        $result = array();

        foreach ($admins as $admin) {

            $news = Post::select('*')
                ->where('post_type', 'NEWS')
                ->where('user_id', $admin->id)->get()->toArray();
            $articles = Post::select('*')
                ->where('post_type', 'ARTICLE')
                ->where('user_id', $admin->id)->get()->toArray();
            $vehicles = Post::select('*')
                ->where('post_type', 'VEHICLE')
                ->where('user_id', $admin->id)->get()->toArray();

            $result1 = array(
                'admin' => $admin,
                'news' => $this->separateTab($news),
                'articles' => $this->separateTab($articles),
                'vehicles' => $this->separateTab($vehicles)
            );
            array_push($result, $result1);
        }

        return $this->sendResponse($result, 'Admins successfully retreived.');
    }

    function separateTab($tab)
    {
        $pending = array();
        $interested = array();
        $sold = array();
        $embarked = array();
        $arrived = array();
        $released = array();
        $finished = array();

        foreach ($tab as $value) {
            if ($value['status'] == 'PENDING') {
                array_push($pending, $value);
            } elseif ($value['status'] == 'INTERESTED') {
                array_push($interested, $value);
            } elseif ($value['status'] == 'SOLD') {
                array_push($sold, $value);
            } elseif ($value['status'] == 'EMBARKED') {
                array_push($embarked, $value);
            } elseif ($value['status'] == 'ARRIVED') {
                array_push($arrived, $value);
            } elseif ($value['status'] == 'RELEASED') {
                array_push($released, $value);
            } elseif ($value['status'] == 'FINISHED') {
                array_push($finished, $value);
            }

        }

        $result1 = array(
            'pending' => count($pending),
            'interested' => count($interested),
            'sold' => count($sold),
            'embarked' => count($embarked),
            'arrived' => count($arrived),
            'released' => count($released),
            'finished' => count($finished),
        );

        return $result1;
    }

     /**
     * @OA\Get(
     ** path="/admins/{id}",
     *   tags={"Admin Controller"},
     *   summary="Get an admin with his ID",
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
    public function getAdmin($id)
    {

        $post = Post::find($id);

        if ($post == null) {
            return $this->sendError('Not found', 404);
        }

        return $this->sendResponse($post, 'Post successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/admins/{id}/details",
     *   tags={"Admin Controller"},
     *   summary="Get an admin details with his ID",
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
    public function getAdminInformations($id)
    {

        $admin = User::find($id);

        if ($admin == null) {
            return $this->sendError('Not found', 404);
        }

        $news = Post::select('*')
            ->where('post_type', 'NEWS')
            ->where('user_id', $admin->id)->get()->toArray();
        $articles = Post::select('*')
            ->where('post_type', 'ARTICLE')
            ->where('user_id', $admin->id)->get()->toArray();
        $vehicles = Post::select('*')
            ->where('post_type', 'VEHICLE')
            ->where('user_id', $admin->id)->get()->toArray();

//        $users = DB::table('users')
//            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
//            ->get();
//        $users = DB::table('users')->count();

        $result = array(
            'admin' => $admin,
            'news' => array(
                'value' => $this->separateTab($news),
                'comments' => DB::table('comments AS c')
                    ->Join('posts AS p', 'c.post_id', '=', 'p.id')
                    ->Join('users AS u', 'u.id', '=', 'p.user_id')
                    ->where('p.post_type', 'LIKE', 'NEWS')
                    ->where('u.id', '=', $admin->id)
                    ->count(),
                'likes' => DB::table('likes AS l')
                    ->Join('posts AS p', 'l.post_id', '=', 'p.id')
                    ->Join('users AS u', 'u.id', '=', 'p.user_id')
                    ->where('p.post_type', 'LIKE', 'NEWS')
                    ->where('u.id', '=', $admin->id)
                    ->count()
            ),
            'articles' => array(
                'value' => $this->separateTab($articles),
                'comments' => DB::table('comments AS c')
                    ->Join('posts AS p', 'c.post_id', '=', 'p.id')
                    ->Join('users AS u', 'u.id', '=', 'p.user_id')
                    ->where('p.post_type', 'LIKE', 'ARTICLE')
                    ->where('u.id', '=', $admin->id)
                    ->count(),
                'likes' => DB::table('likes AS l')
                    ->Join('posts AS p', 'l.post_id', '=', 'p.id')
                    ->Join('users AS u', 'u.id', '=', 'p.user_id')
                    ->where('p.post_type', 'LIKE', 'ARTICLE')
                    ->where('u.id', '=', $admin->id)
                    ->count()
            ),
            'vehicles' => array(
                'value' => $this->separateTab($vehicles),
                'comments' => DB::table('comments AS c')
                    ->Join('posts AS p', 'c.post_id', '=', 'p.id')
                    ->Join('users AS u', 'u.id', '=', 'p.user_id')
                    ->where('u.id', '=', $admin->id)
                    ->where('p.post_type', 'LIKE', 'VEHICLE')
                    ->count(),
                'likes' => DB::table('likes AS l')
                    ->Join('posts AS p', 'l.post_id', '=', 'p.id')
                    ->Join('users AS u', 'u.id', '=', 'p.user_id')
                    ->where('p.post_type', 'LIKE', 'VEHICLE')
                    ->where('u.id', '=', $admin->id)
                    ->count()
            ),
        );

        return $this->sendResponse($result, 'User details');
    }

    /**
     * @OA\Post(
     *      path="/create/admin",
     *      summary="Create a new admin",
     *      tags={"Admin Controller"},
     *      @OA\RequestBody(
     *          required=false,
     *          description="Create a new admin",
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="first_name", type="string"),
     *              @OA\Property(property="last_name", type="string"),
     *              @OA\Property(property="email", type="string"),
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
    public function saveAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
//            'first_name' => 'required|string|max:255',
//            'last_name' => 'required|string|max:255',
            'email' => 'required|unique:users|email',
//            'password' => 'required|min:10|regex:/(?=.*[a-zA-Z])(?=.*[0-9])/',
//            'password_confirmation' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $email = $request->email;

        $input = $request->all();

        if (User::where('email', $email)->exists()) {
            return $this->sendError('An user with that email already exists', "403");
        }

        $password = substr(sha1(time()), 0, 16);
        $input['password'] = Hash::make($password);
        $user = User::create($input);
        $user->is_admin = 1;
        $user->is_activated = 1;
        $user->status = 'ACCEPTED';
        //$user->password = Hash::make($password);
        $user->temp_password = $password;
        $user->save();
        $success['token'] = $user->createToken('MyApp')->accessToken;
        $success['user'] = $user;

        $details = [
            'title' => 'Admin Account Creation',
            'body' => 'Connected to activate your account',
            'password' => $password,
            'email' => $email,
        ];

        Mail::to($email)->send(new AdminMail($details));

        return $this->sendResponse($success, 'Admin register successfully.');

    }

    /**
     * @OA\Post(
     *      path="/resend/mail",
     *      summary="Resend mail to an admin",
     *      tags={"Admin Controller"},
     *      @OA\RequestBody(
     *          required=false,
     *          description="Resend mail",
     *          @OA\JsonContent(
     *              required={"email"},
     *              @OA\Property(property="email", type="string"),
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
    public function ResendAdminMail(Request $request)
    {
//        $validator = Validator::make($request->all(), [
//            'email' => 'required|unique:users|email',
//        ]);
//
//        if($validator->fails()){
//            $error = $validator->errors()->first();
//            return $this->sendError($error, 500);
//        }

        $email = $request->email;

//        if(!User::where('email',$email)->exists()){
//            return $this->sendError('This user does not exists', "404");
//        }

        $password = substr(sha1(time()), 0, 16);

        $input['password'] = Hash::make($password);
        $user = User::where('email', $email)->first();

        $user->password = Hash::make($password);
        $user->status = 'PENDING';

        //$user->password = Hash::make($password);
        $user->temp_password = $password;
        $user->save();
        $success['token'] = $user->createToken('MyApp')->accessToken;
        $success['user'] = $user;

        $details = [
            'title' => 'Admin Account Creation',
            'body' => 'Connect yourself to activate your account',
            'password' => $password,
            'email' => $email,
        ];

        Mail::to($email)->send(new AdminMail($details));

        return $this->sendResponse($success, 'Admin mail send successfully.');
    }

    /**
     * @OA\Put(
     *      path="/admin/{id}",
     *      tags={"Admin Controller"},
     *      summary="Update one user by id",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="first_name", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="last_name", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="email", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="username", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="phone", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="address", in="query", required=false, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param UpdateRequest $request
     * @param int $id
     * @return Response
     */
    public function updateAdminInformation(Request $request, int $id): Response
    {
        try {
            $admin = User::find($id);

            if ($admin == null) {
                return response([
                    'message' => 'User not found',
                    'admin' => null
                ], 404);
            }

            if ($request['first_name'] != null) {
                $admin->first_name = $request['first_name'];
            }
            if ($request['last_name'] != null) {
                $admin->last_name = $request['last_name'];
            }
            if ($request['email'] != null) {
                $admin->email = $request['email'];
            }
            if ($request['username'] != null) {
                $admin->username = $request['username'];
            }
            if ($request['country'] != null) {
                $admin->country = $request['country'];
            }
            if ($request['phone'] != null) {
                $admin->phone = $request['phone'];
            }
            if ($request['city'] != null) {
                $admin->city = $request['city'];
            }
            if ($request['address'] != null) {
                $admin->address = $request['address'];
            }

            $admin->save();

            $details = [
                'title' => 'Update Profile',
                'body' => 'Your personal information has been edited',
                'password' =>  $admin->email ,
                'email' =>  $admin->email ,
            ];

           // Mail::to($admin->email)->send(new AdminMail($details));

            return response([
                'message' => 'Updated Successfull',
                //'user' => $admin
            ], 200);

        } catch (Exception $exception) {

            return response([
                'message' => $exception->getMessage()
            ], 400);
        }
    }

    public function updateAvatar(Request $request, $id)
    {
        $admin = User::find($id);

/*         if ($request->file('avatar') == null) {
            return response([
                'message' => 'Updated Successfull',
                'user' => $admin
            ], 200);
        } */

        if ($admin == null) {
            return response([
                'message' => 'User not found',
                'admin' => null
            ], 404);
        }

            try {
                $file = $request->file('avatar');

                $name = time() . '.' . $file->getClientOriginalName();
                $file->move(public_path() . '/media/', $name);

                $media = new MediaLibrary;
                $media->path = $name;
                $media->referral = 2;
                $media->save();

                $admin->avatar = $name;
                $admin->save();
                return $this->sendResponse($admin, 'Avatar successfully updated !', 200);
            } catch (Exception $exception) {
                return $this->sendError($exception->getMessage(), 500);
            }
    }
    public function getOne($id)
    {
        $admin = User::find($id);

/*         if ($request->file('avatar') == null) {
            return response([
                'message' => 'Updated Successfull',
                'user' => $admin
            ], 200);
        } */

        if ($admin == null) {
            return response([
                'message' => 'User not found',
                'admin' => null
            ], 404);
        }

        return $this->sendResponse($admin, 'Admin found successfully !', 200);
    }

    public function removeAvatar(Request $request, $id)
    {

        $admin = User::find($id);

        if ($admin == null) {
            return response([
                'message' => 'User not found',
                'admin' => null
            ], 404);
            //return $this->sendError('Not found', 404);
        }
        if ($admin->is_admin != 1) {
            return response([
                'message' => 'User is not an admin',
                'admin' => null
            ], 403);
            //return $this->sendError('The user is not an admin', 403);
        }

        if (is_null($admin)) {
            return response([
                'message' => 'Unauthorized'
            ], 401);
        }

        try {

            $admin->avatar = "user.png";
            $admin->save();

            return response([
                'message' => 'Updated Successfully',
                'user' => $admin
            ], 200);
        } catch (Exception $exception) {
            return response([
                'message' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Put(
     ** path="/status/admin/{id}",
     *   tags={"Admin Controller"},
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
    public function updateStatus($id)
    {

        $admin = User::find($id);

        if ($admin == null) {
//            return response([
//                'message' => 'User not found',
//                'admin' => null
//            ], 404);
            return $this->sendError('Not found', 404);
        }
        if ($admin->is_admin != 1) {
//            return response([
//                'message' => 'User is not an admin',
//                'admin' => null
//            ], 403);
            return $this->sendError('The user is not an admin', 403);
        }

        if ($admin->status !== 'BANNED') {
            $admin->status = 'BANNED';
        } else {
            $admin->status = 'ACCEPTED';
        }

        $admin->save();

        return $this->sendResponse($admin, 'Admin status updated');
    }

    /**
     * @OA\Delete(
     ** path="/admin/{id}",
     *   tags={"Admin Controller"},
     *   summary="Delete an admin",
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
     *)S
     *
     * @return Response
     */
    public function deleteAdmin($id)
    {

        $admin = User::find($id);

        if ($admin == null) {
            return $this->sendError('Not found', 404);
        }
//        if ($admin->is_admin != 1) {
//            return $this->sendError('The user is not an admin', 403);
//        }

        $admin->delete();

        return $this->sendResponse("Admin Delete", 'Admin successfully Deleted.');
    }


    public function ChangePasswordAdmin(Request $request, $id)
    {


        $user = User::find($id);

        if ($user == null) {
            return $this->sendError('User with id '+$id +' Not found user', 404);
        }

        $input = $request->all();

        $password = $request->input('new_password');
        $oldPassword = $request->input( 'password');

        $userPassword = $user->password;

//        if (User::where('email', $email)->exists()) {
//            return $this->sendError('An user with that email already exists', "403");
//        }

        if( Hash::check($oldPassword, $userPassword) != true){
            return $this->sendError('Mot de passe incorrect', "403");
        }

        $user->password = Hash::make($password);
        $user->save();
//        $email = $user->email;
//        $success['token'] = $user->createToken('MyApp')->accessToken;
//        $success['user'] = $user;
//
//        $details = [
//            'title' => 'Password modification',
//            'body' => 'The password has been changed successfully',
//            'password' => $password,
//            'email' => $email,
//        ];
//
//        Mail::to($email)->send(new AdminMail($details));

        return $this->sendResponse($user, 'Password updated successfully');
//        return $this->sendResponse("success", 'Password updated successfully');

    }


    public function getAdminWithIndicator($indicator)
    {

        $admins = User::where('is_admin', 1)
            ->where('is_super_admin', 0)
            ->orWhere('email', 'LIKE', "%{$indicator}%")
            ->get();


        return $this->sendResponse($admins, 'Users successfully retreived.');
    }

    public function overview()
    {
        $year = date("Y");
        $months = ['01','02','03','04','05','06','07','08','09','10','11','12'];

        $result = [];

        foreach ($months as $mt) {
            $plot= Purchase::whereYear('created_at', '=', $year)->whereMonth('created_at', '=', $mt)->sum('quantity');
            $result [] = (int)$plot;
        }

        $articles = DB::table('articles') ->orderByDesc('id')->limit(5)->get();
        $purchases = DB::table('purchases')->where('status', 2)->orWhere('status', 4)->orWhere('status', 5)->orderByDesc('id')->limit(5)->get();
        $nbSales = DB::table('purchases')->selectRaw('sum(quantity) as cnt')->pluck('cnt');

//        $post = Purchase::whereYear('created_at', '=', $year)
//            ->whereMonth('created_at', '=', '06')
//            ->get();

        $details = [
            'nb_users' => DB::table('users')->where('is_admin', '=', 0)->count(),
            'sales' => DB::table('purchases')->where('status', '=', 'SUCCESS')->count(),
            'nb_sales' => $nbSales,
            'recent_articles'=> $articles,
            'recent_purchases' => $purchases,
            'total_purchase' => DB::table('purchases')->where('status', '=', 'SUCCESS')->sum('amount'),
            'total_article' => DB::table('articles')->count(),
            'plot' => $result
        ];

        return $this->sendResponse($details, 'Users successfully retrieved.');
    }


    /**
     * @OA\Post(
     ** path="/create/faq",
     *   tags={"Admin Controller"},
     *   summary="Create Faq",
     *      @OA\Parameter(name="question", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="answer", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function createFaq(Request $request)
    {
        $faq = new Faq;
        //$faq->type = $request->get('type');
        $faq->question_en = $request->get('question');
        $faq->answer_en = $request->get('answer');
        //$faq->question_fr = $request->get('question_fr');
        //$faq->answer_fr= $request->get('answer_fr');
        //$faq->question_de = $request->get('question_de');
        //$faq->answer_de = $request->get('answer_de');

        $faq->save();

        return $this->sendResponse($faq, 'faq successfully created.');
    }

    public function updateFaq(Request $request, $id)
    {
        $faq = Faq::find($id);

        if ($faq == null) {
            return $this->sendError('Not found ', 400);
        }

        if ($request->get('question') != '') {
            $faq->question_en = $request->get('question');
        }

        if ($request->get('answer') != '') {
            $faq->answer_en = $request->get('answer');
        }

/*         if ($request->get('question_fr') != '') {
            $faq->question_fr = $request->get('question_fr');
        }

        if ($request->get('answer_fr') != '') {
            $faq->answer_fr = $request->get('answer_fr');
        }

        if ($request->get('question_de') != '') {
            $faq->question_de = $request->get('question_de');
        }

        if ($request->get('answer_de') != '') {
            $faq->answer_de = $request->get('answer_de');
        } */

        $faq->save();

        return $this->sendResponse($faq, 'faq successfully updated.');
    }

    public function deleteFaq($id)
    {

        $faq = Faq::find($id);

        if ($faq == null) {
            return $this->sendError('Not found', 404);
        }

        $faq->delete();

        return $this->sendResponse(null, 'faq successfully deleted.');
    }

        /**
     * @OA\Get(
     ** path="/get/faqs/type/{type}",
     *   tags={"Admin Controller"},
     *   summary="Get all Faqs",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getFaqs()
    {
        $faqs = Faq::all()->reverse();
        return $this->sendResponse($faqs, 'Faqs successfully retrieved.');
    }

    /**
     * @OA\Post(
     ** path="/create/agb",
     *   tags={"Admin Controller"},
     *   summary="Create Agb",
     *      @OA\Parameter(name="agb", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function createAgb(Request $request)
    {
        $agb = new Agb;
        $agb->agb = $request->get('agb');

        $agb->save();

        return $this->sendResponse($agb, 'agb successfully created.');
    }

    public function updateAgb(Request $request, $id)
    {
        $agb = Agb::find($id);

        if ($agb == null) {
            return $this->sendError('Not found ', 400);
        }

        if ($request->get('agb') != '') {
            $agb->agb = $request->get('agb');
        }

        $agb->save();

        return $this->sendResponse($agb, 'agb successfully updated.');
    }

    public function deleteAgb($id)
    {

        $agb = Agb::find($id);

        if ($agb == null) {
            return $this->sendError('Not found', 404);
        }

        $agb->delete();

        return $this->sendResponse(null, 'agb successfully deleted.');
    }

        /**
     * @OA\Get(
     ** path="/get/agbs",
     *   tags={"Admin Controller"},
     *   summary="Get all agbs",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getAgbs()
    {
        $agbs = Agb::all()->reverse();
        return $this->sendResponse($agbs, 'agbs successfully retrieved.');
    }

    /**
     * @OA\Get(
     ** path="/admin/contact",
     *   tags={"Admin Controller"},
     *   summary="Get admin email and phone number",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getAdminContact()
    {
        $admin = User::where('is_admin', 1)->first();
        return $this->sendResponse($admin, 'Admin contact successfully retrieved.');
    }
}
