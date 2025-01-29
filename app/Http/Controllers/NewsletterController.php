<?php

namespace App\Http\Controllers;

use App\Http\Resources\NewsLetterResource;
use App\Http\Resources\NewsSubscriberResource;
use App\Jobs\SendNewsletterJob;
use App\Mail\Sendmail;
use App\Models\NewsLetter;
use App\Models\NewsSubscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class NewsletterController extends BaseController
{

    public function subscribe(Request $request){

        $newsletter = new NewsSubscriber;

        if ($request->get('email') != null){
            $newsletter->email = $request->get('email');
        }else{
            $errorNews = "Veillez entrez votre email";
            return $this->sendError($errorNews, 500);
        }

        $emailExist = NewsSubscriber::where('email', $request->get('email'))->first();

        if($emailExist != null){
            $errorNews = "Vous avez déjà souscrit à la newsletter";
            return $this->sendError($errorNews, 500);
        }

        $newsletter->save();

        $message = "Vous avez suscrit à la newsletter";
        return $this->sendResponse($newsletter, $message);
    }

    public function saveNewsletter(Request $request){

        $newsletter = new NewsLetter;
        if ($request->get('subject') != ""){
            $newsletter->subject = $request->get('subject');
        }
        if ($request->get('message') != null){
            $newsletter->message = $request->get('message');
        }
        $newsletter->status = 2;
        $newsletter->save();

        $success="Votre newsletter est bien cree";
        return $this->sendResponse($newsletter, $success);
    }

    public function editNewsletter(Request $request, $id){

        $newsletter = NewsLetter::findOrFail($id);

        if ($request->get('message') != null){
            $newsletter->message = $request->get('message');
        }
        $newsletter->status = 2;
        $newsletter->save();

        $success="Votre newsletter est bien cree";
        return $this->sendResponse($newsletter, $success);
    }


    public function sendNewsletter($id){

        $newsletter = NewsLetter::findOrFail($id);
        $details = [
            'title' => $newsletter->subject,
            'body' => $newsletter->message
        ];

        $newsletter->status = 3;
        $curTime = new \DateTime();
        $newsletter->last_sent_date = $curTime->format("Y-m-d H:i:s");
        $newsletter->save();

        SendNewsletterJob::dispatch($details);

        //$newsSubscribers = NewsSubscriber::all();

        //foreach ($newsSubscribers as $subscriber) {
            //, $subscriber->emai
            //dispatch(new SendNewsletterJob($details));
            //SendNewsletterJob::dispatch($details, $subscriber->email);
        //}

        $success="Votre Mail a été diffusé à tous les utilisateurs inscrits à la newsletter";
        return $this->sendResponse($newsletter, $success);
    }

    public function getNewsletters(Request $request, $status)
    {
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }
        $newsletters = NewsLetter::where('status', (int)$status)->paginate($numberElement);
        return $this->sendResponse(NewsLetterResource::collection($newsletters)->response()->getData(true), 'Newsletters successfully returned');
    }

    public function allNewsletters($status)
    {
        $newsletters = NewsLetter::where('status', (int)$status)->paginate(25);
        return $this->sendResponse(NewsLetterResource::collection($newsletters)->response()->getData(true), 'Newsletters successfully returned');
    }

    public function getSubscribers(Request $request)
    {
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }
        $newsSubscribers = NewsSubscriber::paginate($numberElement);
        return $this->sendResponse(NewsSubscriberResource::collection($newsSubscribers)->response()->getData(true), 'Newsletters subscribers successfully returned');
    }

    public function contact(Request $request){

        $admins = User::where('is_admin', 1)->get();

        $adminMessage = "L'Utilisateur " . $request->get('name') . " vient de vous envoyer le  message suivant : " . $request->get('message') . ". Son email est  : " . $request->get('email');

        $details = [
            'title' => $request->get('subject'),
            'body' => $adminMessage
        ];

        foreach($admins as $admin){
            Mail::to($admin->email)->send(new Sendmail($details)); // send mail to admin
        }

        $success="Votre Mail a été diffusé à tous les utilisateurs inscrits à la newsletter";
        return $this->sendResponse($success, $success);
    }

    public function delete($id)
    {
        $newsletter = NewsLetter::findOrFail($id);

        if ($newsletter == null) {
            return $this->sendError('Not found ', 400);
        }

        $newsletter->delete();

        return $this->sendResponse("newsletter Removed", 'newsletter deleted successfully.');
    }

}
