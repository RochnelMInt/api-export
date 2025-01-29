<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\Sendmail;
use DB;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Notification;

use App\Models\Post;

class MailController extends Controller
{
    public function contact(Request $request){
        
        $contact = new Contact;

        if ($request->get('email') != null){
            $contact->email = $request->get('email');
        }

        if ($request->get('name') != null){
            $contact->name = $request->get('name');
        }

        if ($request->get('message') != null){
            $contact->messege = $request->get('message');
        }

        $contact->status = 1;
        $contact->save();

        $message = "Votre message a bien été envoyé !";

        return redirect()->back()->with('message', $message);
    }


    public function viewContact(){
    
        $contacts = Contact::all();

        $id = Auth::user()->id;
        $notifications = DB::table('notifications')
        ->where('user_id', '=', $id)
        ->where('status', '=', 1)
        ->get();

        return view('admin.admin-contact', compact('contacts', 'notifications'));
    }


    public function answerContact(Request $request, $id){

        $details = [
            'title' => $request->get('title'),
            'body' => $request->get('body')
        ];

        $contact = Contact::findOrFail($id);
        $contact->answer = $request->get('body');
        $contact->status = 2;
        $contact->save();

        Mail::to($contact->email)->send(new Sendmail($details));
        
        $success="Reponse Envoyé";
        return redirect()->back()->with('message', $success);
    }
}
