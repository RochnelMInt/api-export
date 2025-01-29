<?php

use App\Http\Controllers\ActualiteController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatBotPDFController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\stripeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\VerifyEmailController;
use App\Mail\PurchaseMail;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['cors', 'json.response']], function () {

    Route::get("/media/{file_name}", function($file_name) {
        return response()->file(public_path().'/media/' . $file_name);
    });

    Route::get("/preview/{file_name}", function($file_name) {
        return response()->file(public_path().'/articles/' . $file_name);
    });

    Route::get("/test-email", function() {
        
        $details = [
            'title' => 'Validation d\'une commande',
            'body' => 'Test message'
        ];

        $article_file = '1712512530.car1.jpeg';

        Mail::to('aymartchimwa@gmail.com')->send(new PurchaseMail($details, $article_file));
    });

    Route::get('/get/alert',[UserController::class, 'getAlert']);

    //Auth Routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/admin', [AuthController::class, 'loginAdmin']);
    Route::get('/verify', [AuthController::class, 'UserConnected'])->middleware('auth:api');
    Route::get('/generatetoken', [AuthController::class, 'generateToken']);

    Route::get('/login/redirect/{provider}', [SocialController::class, 'redirectToProvider'])->where('provider', '[A-Za-z]+');
    Route::get('/login/{provider}/callback', [SocialController::class, 'handleProviderCallback'])->where('provider', '[A-Za-z]+');


    // Verify email
    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

    // Resend link to verify email
    Route::post('/email/verify/resend', [AuthController::class, 'resentEmail']);

    //Articles Routes
    Route::get('/article/{id}', [ArticleController::class, 'getArticle']);
    Route::get('/articles/category/{id}/association/{ass}', [ArticleController::class, 'getArticlesByCategoryAndAss']);
    Route::get('/articles/category/{id}/association/{ass}/pagesize/{pagesize}', [ArticleController::class, 'getPaginatedArticles']);
    Route::post('/articles/filter', [ArticleController::class, 'getArticlesByFilter']);
    Route::get('/get/articles', [ArticleController::class, 'getArticles']);
    Route::get('/get/articles/{start_as}', [ArticleController::class, 'getArticlesByAssociation']);
    Route::post('/articles/paginate', [ArticleController::class, 'showPaginatedArticles']);
    Route::post('/create/article', [ArticleController::class, 'createArticle']);
    Route::post('/add/article/{id}/variant', [ArticleController::class, 'addVariant'])->middleware('auth:api');
    Route::post('/update/article/{id}', [ArticleController::class, 'updateArticle'])->middleware('auth:api');
    Route::post('/update/variant/{id}', [ArticleController::class, 'updateVariant'])->middleware('auth:api');
    Route::delete('/article/{id}', [ArticleController::class, 'delete']);
    Route::get('/articles/{id}', [ArticleController::class, 'getOne']);
    Route::get('/add/article/{id}', [ArticleController::class, 'addArticle'])->middleware('auth:api');
    Route::get('/get/selected/articles/{start_as}', [ArticleController::class, 'getSelectedArticlesByAssociation']);
    Route::get('/get/selected/articles', [ArticleController::class, 'getSelectedArticles']);
    Route::delete('/article/{id}/selected', [ArticleController::class, 'deleteSelectedArticle']);
    Route::get('/get/home/articles', [ArticleController::class, 'getHomeArticles']);
    Route::get('/get/related/articles/{id}', [ArticleController::class, 'getRelatedArticles']);

    // Actualites Routes
    Route::get('/actualite/{id}', [ActualiteController::class, 'getActualite']);
    Route::get('/actualites/pagesize/{pagesize}', [ActualiteController::class, 'getPaginatedActualites']);
    Route::post('/actualites/paginate', [ActualiteController::class, 'showPaginatedActualites']);
    Route::get('/get/actualites', [ActualiteController::class, 'getActualites']);
    Route::get('/get/home/actualites', [ActualiteController::class, 'getHomeActualites']);
    Route::post('/create/actualite', [ActualiteController::class, 'createActualite']);
    Route::post('/update/actualite/{id}', [ActualiteController::class, 'updateActualite'])->middleware('auth:api');
    Route::get('/get/actualites/{id}', [ActualiteController::class, 'getOneActualites']);
    Route::delete('/actualite/{id}', [ActualiteController::class, 'delete']);
    Route::get('/get/related/actualites/{id}', [ActualiteController::class, 'getRelatedActualites']);

    // Job Routes
    Route::get('/job/{id}', [JobController::class, 'getJob']);
    Route::get('/jobs/pagesize/{pagesize}', [JobController::class, 'getPaginatedJobs']);
    Route::post('/jobs/paginate', [JobController::class, 'showPaginatedJobs']);
    Route::get('/get/jobs', [JobController::class, 'getJobs']);
    Route::get('/get/filter/jobs', [JobController::class, 'filterJobs']);
    Route::post('/create/job', [JobController::class, 'createJob']);
    Route::post('/update/job/{id}', [JobController::class, 'updateJob'])->middleware('auth:api');
    Route::post('/apply/job/{job_id}/user/{user_id}', [JobController::class, 'applyJob']);
    Route::post('/jobs/applications/pagesize', [JobController::class, 'getPaginatedJobsApplications']);
    Route::get('/get/job/{id}/applications', [JobController::class, 'getJobApplication']);
    Route::delete('/job/{id}', [JobController::class, 'deleteJob']);

    //Cart Routes
    Route::post('/add/article/{article_id}/cart/user/{user_id}', [CartController::class, 'addCart']);
    Route::get('/get/carts/user/{id}', [CartController::class, 'getCartsOfUser']);
    Route::delete('/carts/user/{id}', [CartController::class, 'clearCart']);
    Route::delete('/article/{article_id}/cart/user/{user_id}', [CartController::class, 'removeCartsElement']);
    Route::post('/add/article/{article_id}/cart/user/{user_id}/quantity', [CartController::class, 'updateCartArticleQuantity']);
    Route::get('/get/purchase/{id}', [CartController::class, 'getPurchase'])->middleware('auth:api');
    Route::get('/change/status/{status}/purchase/{id}', [CartController::class, 'changePurchaseStatus'])->middleware('auth:api');

    Route::get('/paginate/purchases', [CartController::class, 'getPaginatedPurchases'])->middleware('auth:api');
    Route::post('/purchases/paginate', [CartController::class, 'ShowPaginatedPurchases'])->middleware('auth:api');
    Route::post('/all/purchases/paginate', [CartController::class, 'allPaginatedPurchases'])->middleware('auth:api');

    ///Invoice route
    Route::get('/invoice/purchase/{id}', [CartController::class, 'getInvoice'])->name('get.invoice')->middleware('auth:api');

    //paypal payment
    Route::get('/initiate/cinet/payment', [CartController::class, 'initiateCinetPayment']);
    Route::post('/notify-cinet-pay', [CartController::class, 'notifyCinetPay']);
    Route::get('/notify-cinet-pay', [CartController::class, 'notifyCinetPay']);

    Route::get('/return-cinet-pay', [CartController::class, 'returnCinetPay']);
    Route::post('/return-cinet-pay', [CartController::class, 'returnCinetPay']);


    Route::get('/payment', [CartController::class, 'pay']);
    Route::get('/payment/status', [CartController::class, 'getPaymentStatus'])->name('payment.status');
    Route::get('/invoice/purchase/{id}', [CartController::class, 'getInvoice'])->name('invoice');

    //Newsletter apis
    Route::post('/subscribe', [NewsletterController::class, 'subscribe']);
    Route::post('/save/newsletter', [NewsletterController::class, 'saveNewsletter']);
    Route::post('/edit/newsletter/{id}', [NewsletterController::class, 'editNewsletter']);
    Route::post('/send/newsletter/{id}', [NewsletterController::class, 'sendNewsletter']);
    Route::post('/subscribers/paginate', [NewsletterController::class, 'getSubscribers']);
    Route::post('/newsletters/status/{status}/paginate', [NewsletterController::class, 'getNewsletters']);
    Route::get('/get/newsletters/status/{status}/paginate', [NewsletterController::class, 'allNewsletters']);
    Route::post('/contact', [NewsletterController::class, 'contact']);
    Route::delete('/newsletter/{id}', [NewsletterController::class, 'delete']);


    //User Routes
    Route::post('/forgetpassword',[UserController::class, 'forgetPassword']);
    Route::post('/resetpassword',[UserController::class, 'resetPassword']);
    Route::post('/user/{id}/avatar',[UserController::class, 'changeAvatar']);
    Route::post('/getuser',[UserController::class, 'getUserByMail']);//->middleware('auth:api');
    Route::put('/user/{id}/avatar/remove', [UserController::class, 'removeUserAvatar'])->middleware('auth:api');
    Route::post('/user/{id}/change/password',[UserController::class, 'changePassword'])->middleware('auth:api');
    Route::get('/me',[UserController::class, 'me'])->middleware('auth:api')->middleware('auth:api');
    Route::get('/get-max-price',[UserController::class, 'getMaxPrice'])->middleware('auth:api');
    Route::post('/update/{id}',[UserController::class, 'updateInformation'])->middleware('auth:api');
    Route::post('/upload-image/{id}', [UserController::class, 'upload'])->middleware('auth:api');
    Route::get('/storage/images/{filename}', [UserController::class, 'downloadImage'])->middleware('auth:api');
    Route::put('/status/user/{id}',[UserController::class, 'updateUserStatus'])->middleware('auth:api');
    Route::post('/privacy/user/{id}',[UserController::class, 'updatePrivacy'])->middleware('auth:api');
    Route::post('/user/{id}/purchases', [UserController::class, 'getPurchases']);
    Route::get('/get/users/{userId}/purchases/{purchaseId}', [UserController::class, 'getUserPurchase'])->middleware('auth:api');
    Route::post('/send/user/mail',[UserController::class, 'sendUserMail']);

    //Admin Routes
    Route::get('/users', [AdminController::class, 'getAllUsers'])->middleware('auth:api');
    Route::post('/paginate/admins', [AdminController::class, 'getPaginatedAdmins'])->middleware('auth:api');
    Route::post('/paginate/users', [AdminController::class, 'getPaginatedUsers'])->middleware('auth:api');
    Route::post('/paginate/users/range', [AdminController::class, 'getPaginatedUsersWithRange'])->middleware('auth:api');
    Route::get('/admin/overview', [AdminController::class, 'overview'])->middleware('auth:api');

    Route::get('/admins', [AdminController::class, 'getAllAdmins'])->middleware('auth:api');
    Route::get('/admin/contact', [AdminController::class, 'getAdminContact']);
    Route::post('/admins/{id}/avatar', [AdminController::class, 'updateAvatar'])->middleware('auth:api');
    Route::get('/admins/{id}', [AdminController::class, 'getOne'])->middleware('auth:api');
    Route::put('/admins/{id}/avatar/remove', [AdminController::class, 'removeAvatar'])->middleware('auth:api');
    Route::get('/admins/{id}/details', [AdminController::class, 'getAdminInformations'])->middleware('auth:api');
    Route::post('/create/admin/',[AdminController::class, 'saveAdmin'])->middleware('auth:api');
    Route::put('/status/admin/{id}',[AdminController::class, 'updateStatus'])->middleware('auth:api');
    Route::put('/admin/{id}',[AdminController::class, 'updateAdminInformation'])->middleware('auth:api');
    Route::put('/admin/{id}/password',[AdminController::class, 'ChangePasswordAdmin'])->middleware('auth:api');
    Route::delete('/admin/{id}',[AdminController::class, 'deleteAdmin'])->middleware('auth:api');
    Route::post('/resend/mail',[AdminController::class, 'ResendAdminMail'])->middleware('auth:api');
    Route::get('/search-admin/{id}',[AdminController::class, 'getAdminWithIndicator'])->middleware('auth:api');

    Route::post('/create/faq',[AdminController::class, 'createFaq'])->middleware('auth:api');
    Route::put('/update/faq/{id}',[AdminController::class, 'updateFaq'])->middleware('auth:api');
    Route::delete('/delete/faq/{id}',[AdminController::class, 'deleteFaq'])->middleware('auth:api');
    Route::get('/get/faqs',[AdminController::class, 'getFaqs']);

    Route::post('/create/agb',[AdminController::class, 'createAgb'])->middleware('auth:api');
    Route::put('/update/agb/{id}',[AdminController::class, 'updateAgb'])->middleware('auth:api');
    Route::delete('/delete/agb/{id}',[AdminController::class, 'deleteAgb'])->middleware('auth:api');
    Route::get('/get/agbs',[AdminController::class, 'getAgbs']);

    // Feature Routes
    Route::post('/create/feature', [FeatureController::class, 'createFeature'])->middleware('auth:api');
    Route::post('/update/feature/{id}', [FeatureController::class, 'updateFeature'])->middleware('auth:api');
    Route::post('/get/features/type/{type}/paginate', [FeatureController::class, 'getFeatureByTypePaginate'])->middleware('auth:api');
    Route::get('/get/features/paginate', [FeatureController::class, 'getFeaturesPaginate'])->middleware('auth:api');
    Route::post('/features/paginate', [FeatureController::class, 'paginatedFeatures'])->middleware('auth:api');
    Route::delete('/feature/{id}',[FeatureController::class, 'deleteFeature'])->middleware('auth:api');
    Route::post('/add/feature/{id}/value', [FeatureController::class, 'addFeatureValue'])->middleware('auth:api');
    Route::post('/add/many/feature/{id}/value', [FeatureController::class, 'addManyFeatureValue'])->middleware('auth:api');

        //ChatBotPDF Routes
        Route::post('/chat/response', [ChatBotPDFController::class, 'fetchChatPDFResponse']); //->middleware('auth:api','verified');
        Route::post('/upload/pdf', [ChatBotPDFController::class, 'uploadPDFToChatAPI']);//->middleware('auth:api','verified');

    // Category Routes
    Route::post('/create/category', [CategoryController::class, 'createCategory'])->middleware('auth:api');
    Route::post('/update/category/{id}', [CategoryController::class, 'updateCategory'])->middleware('auth:api');
    Route::get('/get/categories/without/child/{type}', [CategoryController::class, 'getCategoriesWithoutChild']);
    Route::get('/get/categories/without/parents', [CategoryController::class, 'getCategoriesWithoutParents']);
    Route::get('/get/categories', [CategoryController::class, 'getAllCategories']);
    Route::post('/categories/paginate', [CategoryController::class, 'getAllPaginatedCategories']);
    Route::get('/categories', [CategoryController::class, 'getAllCategories']);
    Route::get('/category/{id}', [CategoryController::class, 'getCategory']);
    Route::get('/category/{id}/descent', [CategoryController::class, 'findCategoryDescent']);
    Route::get('/get/child/categories', [CategoryController::class, 'getChildCategories']);
    Route::delete('/category/{id}', [CategoryController::class, 'deleteCategory']);
    Route::get('/add/category/{id}', [CategoryController::class, 'addCategory']);
    Route::get('/get/selected/categories/association/{ass}', [CategoryController::class, 'getSelectedCategoriesByAss']);
    Route::get('/get/number/articles/category/{id}/association/{ass}', [CategoryController::class, 'getNumberOfArticlesByAss']);
    Route::get('/get/selected/categories', [CategoryController::class, 'getSelectedCategories']);
    Route::delete('/category/{id}/selected', [CategoryController::class, 'deleteSelectedCategory']);

    // SubCategory Routes
    Route::post('/create/sub-category', [SubCategoryController::class, 'createSubCategory'])->middleware('auth:api');
    Route::post('/update/sub-category/{id}', [SubCategoryController::class, 'updateSubCategory'])->middleware('auth:api');
    Route::get('/get/sub-categories', [SubCategoryController::class, 'getAllSubCategories']);
    Route::post('/sub-categories/paginate', [SubCategoryController::class, 'getAllPaginatedSubCategories']);
    Route::get('/sub-categories', [SubCategoryController::class, 'getAllSubCategories']);
    Route::get('/sub-category/{id}', [SubCategoryController::class, 'getSubCategory']);
    Route::delete('/sub-category/{id}', [SubCategoryController::class, 'deleteSubCategory']);

    // Stripe routes
    Route::post('/create/payment-intent', [stripeController::class, 'createPaymentIntent']);
    Route::post('/retrieve/payment-intent', [stripeController::class, 'retrievePaymentIntent']);
    Route::post('/confirm/payment-intent', [stripeController::class, 'confirmPaymentIntent']);

    //OTP routes
    Route::get('/generate/otp/{id}',[AuthController::class, 'generateOtp']);
    Route::post('/validate/otp',[AuthController::class, 'validateOtp']);

    Route::get('/users-simple',[AuthController::class, 'getAAllUsers']);

    Route::post('/create/client',[AuthController::class, 'CreateClient']);

    Route::post('/edit/password',[UserController::class, 'EditPassword']);

    //Tag routes
    Route::get('/tags/popular', [TagController::class, 'getPopularTags']);


});
