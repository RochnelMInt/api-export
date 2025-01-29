<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActualiteResource;
use App\Http\Resources\ArticleResource;
use App\Models\Actualite;
use App\Models\Category;
use App\Models\MediaLibrary;
use App\Models\SubCategory;
use App\Models\Tag;
use Illuminate\Http\Request;
use Validator;
use File;

class ActualiteController extends BaseController
{

        /**
     * @OA\Get(
     ** path="/actulite/{id}",
     *   tags={"Actualite Controller"},
     *   summary="Get an actualite",
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
    public function getActualite($id){
        $actualite = Actualite::find($id);

        if($actualite == null){
            return $this->sendError('Not found', 404);
        }

        return $this->sendResponse(new ActualiteResource($actualite), 'Actualite successfully retrieved.');
    }

        /**
     * @OA\Get(
     ** path="/get/actualites",
     *   tags={"Actualite Controller"},
     *   summary="Get all actualites",
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
    public function getActualites(){
        $Actualites = Actualite::all()->reverse();

        return $this->sendResponse(ActualiteResource::collection($Actualites), 'Actualites successfully retreived.');
    }

            /**
     * @OA\Get(
     ** path="/actualites/pagesize/{pagesize}",
     *   tags={"Actualite Controller"},
     *   summary="Get all actualites",
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
    public function getPaginatedActualites($pagesize){
        $actualites = Actualite::all()->reverse()->paginate($pagesize);
        return $this->sendResponse(ActualiteResource::collection($actualites)->response()->getData(true), 'Actualites successfully Deleted.');
    }

    public function showPaginatedActualites(Request $request)
    {
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }

        $actualites = Actualite::paginate($numberElement);

        return $this->sendResponse(ActualiteResource::collection($actualites)->response()->getData(true), 'Actualites successfully retrieved.');
    }

    public function formatToCamelCase($tag)
    {
        // Split the tag by spaces
        $words = explode(' ', trim($tag));
        
        // Remove any empty elements caused by multiple spaces
        $words = array_filter($words, function($word) {
            return !empty($word);
        });
    
        // Capitalize the first letter of each word and lower the rest
        $camelCaseTag = implode('', array_map(function ($word) {
            return ucfirst(strtolower($word));
        }, $words));
    
        return $camelCaseTag;
    }

        /**
     * @OA\Post(
     *      path="/create/actualite",
     *      summary="Create a new actualite",
     *      tags={"Actualite Controller"},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Create a new actualite",
     *          @OA\JsonContent(
     *              required={"association","name","description"},
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="short description", type="string"),
     *              @OA\Property(property="quantity", type="number"),
     *              @OA\Property(property="price", type="number"),
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
    public function createActualite(Request $request)
    {
        //$this->out->writeln("From Laravel API : " . $request->get('has_visibility'));
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'short_description' => 'required',
            'images.*' => 'image|max:51200',
            'tags' => 'required',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        // Split the comma-separated tags and trim spaces
        $tagsArray = array_map('trim', explode(',', $request->get('tags')));

        // Check for the number of tags
        if (count($tagsArray) < 3 || count($tagsArray) > 10) {
            return $this->sendError('The number of tags must be between 3 and 10.', 422);
        }

        $category = Category::findOrFail((int)$request->get('sub_category'));

        if ($category == null) {
            return $this->sendError('Category with the id ' . $request->get('sub_category') . ' not found', 400);
        }

        $actualite = New Actualite;

        if ($request->get('name') != null){
            $actualite->name = $request->get('name');
        }

        if ($request->get('short_description') != null){
            $actualite->short_description = $request->get('short_description');
        }

        if ($request->get('long_description') != null){
            $actualite->long_description = $request->get('long_description');
        }

        if ($request->get('quotation') != null){
            $actualite->quotation = $request->get('quotation');
        }

        if ($request->get('quotation_owner') != null){
            $actualite->quotation_owner = $request->get('quotation_owner');
        }

        $actualite->category()->associate($category);

        $actualite->save();

        if (!empty($request->get('tags'))) {
            // Split the tags string into an array
            $tagsArray = explode(',', $request->get('tags'));
    
            // Trim whitespace from each tag
            $tagsArray = array_map('trim', $tagsArray);
    
            // Attach tags to the actualite
            $tags = collect($tagsArray)->map(function($tag) {
                return Tag::firstOrCreate(['name' => $this->formatToCamelCase($tag)])->id;
            });
    
            $actualite->tags()->sync($tags);
        }

         if($request->hasfile('images'))
        {
            foreach($request->file('images') as $file)
            {
                $name = time().'.'.$file->getClientOriginalName();
                $file->move(public_path().'/media/', $name);

                $media = New MediaLibrary;
                $media->path = $name;
                $media->type = 1;
                $media->referral = 3;
                $media->actualite()->associate($actualite);
                $media->save();
            }
        }

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
                $media->actualite()->associate($actualite);
                $media->save();
            }
        }

        return $this->sendResponse(new ActualiteResource($actualite), 'Articles successfully created.');
    }

    public function getHomeActualites(){
        $Actualites = Actualite::latest()->take(6)->get();

        return $this->sendResponse(ActualiteResource::collection($Actualites), 'Actualites successfully retrieved.');
    }

    public function updateActualite(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'short_description' => 'required',
            'images.*' => 'image|max:51200',
            'tags' => 'required',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        // Split the comma-separated tags and trim spaces
        $tagsArray = array_map('trim', explode(',', $request->get('tags')));

        // Check for the number of tags
        if (count($tagsArray) < 3 || count($tagsArray) > 10) {
            return $this->sendError('The number of tags must be between 3 and 10.', 422);
        }

        $actualite = Actualite::find($id);

        if($actualite == null){
            return $this->sendError('Not found', 404);
        }

        $category = Category::findOrFail((int)$request->get('sub_category'));

        if ($category == null) {
            return $this->sendError('Sub Category with the id ' . $request->get('sub_category') . ' not found', 400);
        }

        if ($request->get('name') != null){
            $actualite->name = $request->get('name');
        }

        if ($request->get('short_description') != null){
            $actualite->short_description = $request->get('short_description');
        }

        if ($request->get('long_description') != null){
            $actualite->long_description = $request->get('long_description');
        }

        if ($request->get('quotation') != null){
            $actualite->quotation = $request->get('quotation');
        }

        if ($request->get('quotation_owner') != null){
            $actualite->quotation_owner = $request->get('quotation_owner');
        }

        $actualite->category()->associate($category);

        $actualite->save();

        if (!empty($request->get('tags'))) {
            // Split the tags string into an array
            $tagsArray = explode(',', $request->get('tags'));
    
            // Trim whitespace from each tag
            $tagsArray = array_map('trim', $tagsArray);
    
            // Sync tags to the article
            $tags = collect($tagsArray)->map(function($tag) {
                return Tag::firstOrCreate(['name' => $tag])->id;
            });
    
            $actualite->tags()->sync($tags);
        } else {
            // If tags are empty, detach all tags
            $actualite->tags()->detach();
        }

         if($request->hasfile('images'))
        {
            foreach($request->file('images') as $file)
            {
                $name = time().'.'.$file->getClientOriginalName();
                $file->move(public_path().'/media/', $name);

                $media = New MediaLibrary;
                $media->path = $name;
                $media->type = 1;
                $media->referral = 3;
                $media->actualite()->associate($actualite);
                $media->save();
            }
        }

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
                $media->actualite()->associate($actualite);
                $media->save();
            }
        }

        $imgToDeleteIds = $request->get('to_delete_img');
        $imagesToDeleteIds = explode(",", $imgToDeleteIds);

        foreach($imagesToDeleteIds as $id){
            if($id != "0" && $id != null && $id !=""){
                $img = MediaLibrary::findOrFail((int)$id);
                $imgPath = $img->path;

                $filePath = "/media/" . $imgPath;
                if(File::exists($filePath)) {
                    File::delete($filePath);
                }
                $img->delete();
            }
        }


        $filToDeleteIds = $request->get('to_delete_fil');
        $filesToDeleteIds = explode(",", $filToDeleteIds);

         foreach($filesToDeleteIds as $id){
            if($id != "0" && $id != null && $id !=""){
                $fil = MediaLibrary::findOrFail((int)$id);
                $filPath = $fil->path;

                $filePath = "/media/" . $filPath;
                if(File::exists($filePath)) {
                    File::delete($filePath);
                }

                $fil->delete();
            }
        }

        return $this->sendResponse(new ActualiteResource($actualite), 'News successfully updated.');
    }

    public function getOneActualites($id)
    {
        $actu = Actualite::find($id);
        if($actu){
            return $this->sendResponse(new ActualiteResource($actu), 'New found');
        }
        return $this->sendError("This new does not exist", 500);
    }

    public function delete($id){
        $actu = Actualite::find($id);

        if($actu == null){
            return $this->sendError('Not found', 404);
        }

        $actu->delete();

        return $this->sendResponse("News Delete", 'News successfully Deleted.');
    }
     
    public function getRelatedActualites($id){

        $category = Actualite::where('id', $id)->value('category_id');

        $relatedArticles = Actualite::where('category_id', $category)
            ->where('id', '!=', $id) // Excluez l'article spécifique
            ->latest()
            ->take(6)
            ->get();

        return $this->sendResponse(ActualiteResource::collection($relatedArticles), 'Actualites successfully retrieved.');
    }
}
