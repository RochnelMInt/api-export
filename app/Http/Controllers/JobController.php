<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActualiteResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\JobApplicationResource;
use App\Http\Resources\JobResource;
use App\Mail\Sendmail;
use App\Models\Actualite;
use App\Models\Category;
use App\Models\MyJob;
use App\Models\JobApplication;
use App\Models\MediaLibrary;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class JobController extends BaseController
{
    /**
     * @OA\Get(
     ** path="/job/{id}",
     *   tags={"Job Controller"},
     *   summary="Get an job",
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
    public function getJob($id){
        $job = MyJob::find($id);

        if($job == null){
            return $this->sendError('Not found', 404);
        }

        return $this->sendResponse(new JobResource($job), 'Job successfully retrieved.');
    }

        /**
     * @OA\Get(
     ** path="/get/jobs",
     *   tags={"Job Controller"},
     *   summary="Get all jobs",
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
    public function getJobs(){
        $jobs = MyJob::all()->reverse();

        return $this->sendResponse(JobResource::collection($jobs), 'Jobs successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/jobs/pagesize/{pagesize}",
     *   tags={"Job Controller"},
     *   summary="Get all jobs",
     * @OA\Parameter(name="pagesize", in="path", required=true, @OA\Schema(type="integer")),
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
    public function getPaginatedJobs($pagesize){
        $jobs = MyJob::all()->reverse()->paginate($pagesize);
        return $this->sendResponse(JobResource::collection($jobs)->response()->getData(true), 'Jobs successfully Deleted.');
    }

    public function showPaginatedJobs(Request $request)
    {
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }

        $jobs = MyJob::paginate($numberElement);

        return $this->sendResponse(JobResource::collection($jobs)->response()->getData(true), 'Jobs successfully retrieved.');
    }

    /**
     * @OA\Post(
     *      path="/create/job",
     *      summary="Create a new job",
     *      tags={"Job Controller"},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Create a new actualite",
     *          @OA\JsonContent(
     *              required={"type","title","description"},
     *              @OA\Property(property="title", type="string"),
     *              @OA\Property(property="description", type="string"),
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
    public function createJob(Request $request)
    {
        //$this->out->writeln("From Laravel API : " . $request->get('has_visibility'));
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $job = New MyJob;

        $job->type = (int)$request->get('type');

        if ($request->get('title') != null){
            $job->title = $request->get('title');
        }

        if ($request->get('description') != null){
            $job->description = $request->get('description');
        }

        if ($request->get('domain') != null){
            $job->domain = $request->get('domain');
        }

        if ($request->get('salary_start') != null){
            $job->salary_start = $request->get('salary_start');
        }

        if ($request->get('salary_end') != null){
            $job->salary_end = $request->get('salary_end');
        }

        if ($request->has('start_date')) {
            $job->start_date = $request->input('start_date');
        } 
        //else {
        //    $job->start_date = null;
        //}
        
        if ($request->has('end_date')) {
            $job->end_date = $request->input('end_date');
        } 
        //else {
         //   $job->end_date = null;
        //}

        if ($request->get('contact_first_name') != null){
            $job->contact_first_name = $request->get('contact_first_name');
        }

        if ($request->get('contact_last_name') != null){
            $job->contact_last_name = $request->get('contact_last_name');
        }

        if ($request->get('contact_email') != null){
            $job->contact_email = $request->get('contact_email');
        }

        if ($request->get('contact_phone') != null){
            $job->contact_phone = $request->get('contact_phone');
        }

        if ($request->get('expectations') != null){
            $job->expectations = $request->get('expectations');
        }

        if ($request->get('qualifications') != null){
            $job->qualifications = $request->get('qualifications');
        }

        if ($request->get('benefits') != null){
            $job->benefits = $request->get('benefits');
        }

        $job->save();

        return $this->sendResponse(new JobResource($job), 'Job successfully created.');
    }

    public function updateJob(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $job = MyJob::find($id);

        if($job == null){
            return $this->sendError('Not found', 404);
        }

        $job->type = (int)$request->get('type');

        if ($request->get('title') != null){
            $job->title = $request->get('title');
        }

        if ($request->get('description') != null){
            $job->description = $request->get('description');
        }

        if ($request->get('domain') != null){
            $job->domain = $request->get('domain');
        }

        if ($request->get('salary_start') != null){
            $job->salary_start = $request->get('salary_start');
        }

        if ($request->get('salary_end') != null){
            $job->salary_end = $request->get('salary_end');
        }

        if ($request->get('start_date') !== null){
            $job->start_date = $request->get('start_date', null);
        }

        if ($request->get('end_date') !== null){
            $job->end_date = $request->get('end_date', null);
        }

        if ($request->get('contact_first_name') != null){
            $job->contact_first_name = $request->get('contact_first_name');
        }

        if ($request->get('contact_last_name') != null){
            $job->contact_last_name = $request->get('contact_last_name');
        }

        if ($request->get('contact_email') != null){
            $job->contact_email = $request->get('contact_email');
        }

        if ($request->get('contact_phone') != null){
            $job->contact_phone = $request->get('contact_phone');
        }

        if ($request->get('expectations') != null){
            $job->expectations = $request->get('expectations');
        }

        if ($request->get('qualifications') != null){
            $job->qualifications = $request->get('qualifications');
        }

        if ($request->get('benefits') != null){
            $job->benefits = $request->get('benefits');
        }

        $job->save();


       
        return $this->sendResponse(new JobResource($job), 'Job successfully updated.');
    }

    public function applyJob(Request $request, $job_id, $user_id)
    {
        $validator = Validator::make($request->all(), [
            'last_name' => 'required',
            'email' => 'required',
            'files.*' => 'max:51200',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $job = MyJob::find((int)$job_id);

        if($job == null){
            return $this->sendError('Not found', 404);
        }

        $user = User::find((int)$user_id);

        if($user == null){
            return $this->sendError('Not found', 404);
        }

        $jobApplication = New JobApplication();

        if ($request->get('first_name') != null){
            $jobApplication->first_name = $request->get('first_name');
        }

        if ($request->get('last_name') != null){
            $jobApplication->last_name = $request->get('last_name');
        }

        if ($request->get('email') != null){
            $jobApplication->email = $request->get('email');
        }

        if ($request->get('phone') != null){
            $jobApplication->phone = $request->get('phone');
        }

        if ($request->get('about_me') != null){
            $jobApplication->about_me = $request->get('about_me');
        }

        $jobApplication->job()->associate($job);
        $jobApplication->user()->associate($user);

        $jobApplication->save();

        if($request->hasfile('files'))
        {
            foreach($request->file('files') as $file)
            {
                $name = time().'.'.$file->getClientOriginalName();
                $file->move(public_path().'/media/', $name);

                $media = New MediaLibrary;
                $media->path = $name;
                $media->type = 8;
                $media->referral = 4;
                $media->jobApplication()->associate($jobApplication);
                $media->save();
            }
        }

        $admins = User::where('is_admin', 1)->get();

        $adminMessage = "L'Utilisateur " . $user->username . " vient de soummetre sa candidature pour le job : " . $job->title . ".";

        $adminDetails = [
            'title' => "Postulation",
            'body' => $adminMessage
        ];

        foreach($admins as $admin){
            Mail::to($admin->email)->send(new Sendmail($adminDetails)); // send mail to admin
        }

        $userMessage = "Votre candidature pour le job : " . $job->title . " a bien été pris en considération par nos administrateurs !";

        $userDetails = [
            'title' => "Postulation",
            'body' => $userMessage
        ];

        Mail::to($user->email)->send(new Sendmail($userDetails)); // send mail to admin

        return $this->sendResponse(new JobApplicationResource($jobApplication), 'Articles successfully created.');
    }

    /**
     * @OA\Get(
     ** path="/jobs/applications/pagesize/{pagesize}",
     *   tags={"Job Controller"},
     *   summary="Get all jobs applications",
     * @OA\Parameter(name="pagesize", in="path", required=true, @OA\Schema(type="integer")),
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
    public function getPaginatedJobsApplications(Request $request){
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }

        $jobApplications = JobApplication::paginate($numberElement);
        return $this->sendResponse(JobApplicationResource::collection($jobApplications)->response()->getData(true), 'Jobs application successfully retrieved.');
    }

    /**
     * @OA\Get(
     ** path="/get/job/{id}/applications",
     *   tags={"Job Controller"},
     *   summary="Get applications ofone job",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getJobApplication($id)
    {
        
        $job = MyJob::findOrFail($id);
        $jobApplications = $job->jobApplications()->paginate();
        return $this->sendResponse(JobApplicationResource::collection($jobApplications)->response()->getData(true), 'Jobs application successfully retrieved.');
    }

    public function filterJobs(Request $request){

        $keyword = $request->get('keyword');
        $type = $request->get('type');


            $jobs = MyJob::where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%");
                $q->orWhere('description', 'like', "%{$keyword}%");
                $q->orWhere('domain', 'like', "%{$keyword}%");
            })->get();

        
/*         $typeMapping = [
            '1' => 'CDI',
            '2' => 'CDD',
        ];
        
        if ($type != "" && array_key_exists($type, $typeMapping)) {
            $jobs->whereEnum('type', $typeMapping[$type]);
        } */
        
        //$jobs = $jobs->get();

        return $this->sendResponse(JobResource::collection($jobs), 'Jobs successfully retreived.');;
    }

    public function deleteJob($id)
    {

        $job = MyJob::findOrFail($id)->first();

        if ($job == null) {
            return $this->sendError('Not found ', 400);
        }

        $job->delete();

        return $this->sendResponse("job Removed", 'Job deleted successfully.');
    }


}
