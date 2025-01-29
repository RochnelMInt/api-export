<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Http\Resources\VariantResource;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Feature;
use App\Models\Article;
use App\Models\Variant;
use App\Models\MediaLibrary;
use App\Models\FeatureValue;
use Validator;
use File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\SelectedArticle;
use App\Http\Resources\SelectedArticleResource;
use App\Models\ArticleFileLibrary;
use App\Models\Tag;
use TCPDF;

class ArticleController extends BaseController
{
    private $out;

    public function __construct()
    {
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

        /**
     * @OA\Get(
     ** path="/article/{id}",
     *   tags={"Article Controller"},
     *   summary="Get an article",
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
    public function getArticle($id){
        $article = Article::find($id);

        if($article == null){
            return $this->sendError('Not found', 404);
        }

        return $this->sendResponse(new ArticleResource($article), 'Article successfully retrieved.');
    }

    public function getHomeArticles(){
        $articles = Article::latest()->take(6)->get();

        return $this->sendResponse(ArticleResource::collection($articles), 'Actualites successfully retrieved.');
    }
    public function getRelatedArticles($id){

        $category = Article::where('id', $id)->value('category_id');

        $relatedArticles = Article::where('category_id', $category)
            ->where('id', '!=', $id) // Excluez l'article spécifique
            ->latest()
            ->take(6)
            ->get();

        return $this->sendResponse(ArticleResource::collection($relatedArticles), 'Actualites successfully retrieved.');
    }

    /**
     * @OA\Get(
     ** path="/articles/category/{id}/association/{ass}",
     *   tags={"Article Controller"},
     *   summary="Get all articles of category",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="ass", in="path", required=true, @OA\Schema(type="string")),
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
    public function getArticlesByCategoryAndAss($id, $ass){
        $category = Category::find($id);

        if($category == null){
            return $this->sendError('Not found', 404);
        }

        $categories = $category->getDescendants($category);

        $articles = new Collection();

        foreach($categories as $cat){
            $arts = $cat->articles;
            $articles = $articles->merge($arts);
        }

        $associatedArticles = $articles->whereIn('association', [strtoupper($ass),  "UNISEX"]);

        return $this->sendResponse(ArticleResource::collection($associatedArticles), 'Articles successfully Deleted.');
    }

    /**
     * @OA\Post(
     ** path="/articles/filter",
     *   tags={"Article Controller"},
     *   summary="Get articles by filter",
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
    public function getArticlesByFilter(Request $request){

        $key_word = $request->get('key_word');
        $min_price = $request->get('min_price');
        $max_price = $request->get('max_price');
        $category_ids = $request->get('category_ids');
        $reduction = $request->get('reduction');
        $min_reduction = $request->get('min_reduction');
        $max_reduction = $request->get('max_reduction');

        $query = DB::table('articles');

        if(!empty($category_ids)){
            $this->out->writeln("From Laravel API : nous fouillons les categories ids");
            $query->whereIn('article_category.category_id', $category_ids);
        }

        $query->whereBetween('price', [(int)$min_price, (int)$max_price]);

        if($key_word != ""){
            $this->out->writeln("From Laravel API : nous fouillons le key word");
            $query->where(function ($q) use ($key_word) {
                $q->where('name', 'like', "%{$key_word}%");
                $q->orWhere('short_description', 'like', "%{$key_word}%");
                $q->orWhere('long_description', 'like', "%{$key_word}%");
            });
        }

        //$query->distinct();
        $article_ids = $query->get(['articles.id']);

        $ids = array();
        foreach($article_ids as $id){
            array_push($ids, $id->id);
        }

        $articles = Article::find($ids);

        return $this->sendResponse(ArticleResource::collection($articles), 'Articles successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/get/articles",
     *   tags={"Article Controller"},
     *   summary="Get all articles",
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
    public function getArticles(){
        $articles = Article::all()->reverse();

        return $this->sendResponse(ArticleResource::collection($articles), 'Articles successfully retreived.');
    }

    public function getArticlesByAssociation($start_as){
        $articles = Article::whereIn('association', [strtoupper($start_as),  "UNISEX"])->take(10)
        ->get()->reverse();

        return $this->sendResponse(ArticleResource::collection($articles), 'Articles successfully retreived.');
    }

    public function showPaginatedArticles(Request $request)
    {
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }
        $articles = Article::paginate($numberElement);
        return $this->sendResponse(ArticleResource::collection($articles)->response()->getData(true), 'Articles successfully retrieved.');
    }

        /**
     * @OA\Get(
     ** path="/articles/category/{id}/association/{ass}/pagesize/{pagesize}",
     *   tags={"Article Controller"},
     *   summary="Get all articles of category",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="ass", in="path", required=true, @OA\Schema(type="string")),
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
    public function getPaginatedArticles($id, $ass, $pagesize){
        $category = Category::find($id);

        if($category == null){
            return $this->sendError('Not found', 404);
        }

        $categories = $category->getDescendants($category);

        $articles = new Collection();

        foreach($categories as $cat){
            $arts = $cat->articles;
            $articles = $articles->merge($arts);
        }

        $ids = $articles->whereIn('association', [strtoupper($ass),  "UNISEX"])->pluck('id');;
        $associatedArticles = Article::whereIn("id", $ids)->paginate($pagesize);
        return $this->sendResponse(ArticleResource::collection($associatedArticles)->response()->getData(true), 'Articles successfully Deleted.');
    }


    /**
     * @OA\Post(
     *      path="/create/article",
     *      summary="Create a new article",
     *      tags={"Article Controller"},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Create a new article",
     *          @OA\JsonContent(
     *              required={"association","name","description"},
     *              @OA\Property(property="association", type="string"),
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="description", type="string"),
     *              @OA\Property(property="price", type="number"),
     *              @OA\Property(property="reduction_price", type="number"),
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
    public function createArticle(Request $request)
    {
        //$this->out->writeln("From Laravel API : " . $request->get('has_visibility'));
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'short_description' => 'required',
            'images.*' => 'image|max:51200',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $category = Category::findOrFail((int)$request->get('sub_category'));

        if ($category == null) {
            return $this->sendError('Sub Category with the id ' . $request->get('sub_category') . ' not found', 400);
        }

        $article = New Article;

        $article->type = (int)$request->get('type');
        if ($request->get('name') != null){
            $article->name = $request->get('name');
        }

        if ($request->get('reduction_type') != ""){
            $article->reduction_type = (int)$request->get('reduction_type');
        }

        if ($request->get('short_description') != null){
            $article->short_description = $request->get('short_description');
        }

        if ($request->get('long_description') != null){
            $article->long_description = $request->get('long_description');
        }

        if ($request->get('price') != null){
            $article->price = round($request->get('price'),  2);
        }

        if ($request->get('reduction_price') != null){
            $article->reduction_price = round($request->get('reduction_price'), 2);
        }

        $article->category()->associate($category);

        $article->save();

        if($request->hasfile('article_file')){
            $file = $request->file('article_file');

            $name = time() . '.' . $file->getClientOriginalName();
            //$file->move(public_path().'/articles/', $name);
            $file->storeAs('articles', $name);

            $article->path = $name;
        }

        if($request->hasfile('preview_file')){
            $file = $request->file('preview_file');

            $name = time() . '.' . $file->getClientOriginalName();
            $file->move(public_path().'/articles/', $name);
            //$file->storeAs('articles', $name);

            $article->preview_path = $name;
        }

         if($request->hasfile('images')){
            foreach($request->file('images') as $file){
                $name = time().'.'.$file->getClientOriginalName();
                $file->move(public_path().'/media/', $name);

                $media = New MediaLibrary;
                $media->path = $name;
                $media->type = 1;
                $media->referral = 2;
                $media->article()->associate($article);
                $media->save();
            }
        }

        return $this->sendResponse(new ArticleResource($article), 'Articles successfully created.');
    }

    /**
     * @OA\Post(
     *      path="/add/article/{id}/variant",
     *      summary="Add variant to article",
     *      tags={"Article Controller"},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
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
    public function addVariant(Request $request, $id)
    {
        $article = Article::find($id);

        if($article == null){
            return $this->sendError('Not found', 404);
        }

        foreach ($request->get('variants') as $variant) {
            $var = json_decode($variant);
            $this->out->writeln("From Laravel API : " . $var->variant_key);

            $newVariant = New Variant;
            $newVariant->price = $var->variant_price;
            $newVariant->quantity = $var->variant_quantity;
            $newVariant->reduction_price = $var->variant_reduction_price;
            $newVariant->reduction_type = $var->variant_reduction_type;
            $article->variants()->save($newVariant);

            if ($var->variant_feature_values != null) {
                foreach ($var->variant_feature_values as $value) {
                    $featureValue = FeatureValue::findOrFail((int)$value->feature_value_id);
                    $newVariant->featureValues()->attach($featureValue);
                }
            }

            if($request->hasfile($var->variant_key))
            {
                foreach($request->file($var->variant_key) as $file)
                {
                    $name = time().'.'.$file->getClientOriginalName();
                    $this->out->writeln("ici l image: " . $name);
                    $file->move(public_path().'/media/', $name);

                    $media = New MediaLibrary;
                    $media->path = $name;
                    $media->type = 1;
                    $media->referral = 3;
                    $media->variant()->associate($newVariant);
                    $media->save();
                }
            }
        }

        return $this->sendResponse(new ArticleResource($article), 'Articles successfully created.');
    }

        /**
     * @OA\Post(
     *      path="/update/article/{id}",
     *      summary="Update a new article",
     *      tags={"Article Controller"},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Update a new article",
     *          @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *          @OA\JsonContent(
     *              required={"association","name","description"},
     *              @OA\Property(property="association", type="string"),
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="description", type="string"),
     *              @OA\Property(property="price", type="number"),
     *              @OA\Property(property="reduction_price", type="number"),
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
    public function updateArticle(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'short_description' => 'required',
            'images.*' => 'image|max:51200',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $article = Article::find($id);

        if($article == null){
            return $this->sendError('Not found', 404);
        }

        $category = Category::findOrFail((int)$request->get('sub_category'));

        if ($category == null) {
            return $this->sendError('Sub Category with the id ' . $request->get('sub_category') . ' not found', 400);
        }

        if ($request->get('type') != 0){
            $article->type = (int)$request->get('type');
        }
        
        if ($request->get('name') != null){
            $article->name = $request->get('name');
        }

        if ($request->get('reduction_type') != ""){
            $article->reduction_type = (int)$request->get('reduction_type');
        }

        if ($request->get('short_description') != null){
            $article->short_description = $request->get('short_description');
        }

        if ($request->get('long_description') != null){
            $article->long_description = $request->get('long_description');
        }

        if ($request->get('price') != null){
            $article->price = round($request->get('price'),  2);
        }

        if ($request->get('reduction_price') != null){
            $article->reduction_price = round($request->get('reduction_price'), 2);
        }

        $article->category()->associate($category);

        $article->save();

        if($request->hasfile('article_file')){

            $filePath = storage_path('app/articles/'. $article->path); 
            if(File::exists($filePath)) {
                File::delete($filePath);
            }

            $file = $request->file('article_file');

            $name = time() . '.' . $file->getClientOriginalName();
            //$file->move(public_path().'/articles/', $name);
            $file->storeAs('articles', $name);

            $article->path = $name;
        }

        if($request->hasfile('preview_file')){

            $filePath = public_path('articles/'. $article->preview_path);
            if(File::exists($filePath)) {
                File::delete($filePath);
            }

            $file = $request->file('preview_file');

            $name = time() . '.' . $file->getClientOriginalName();
            $file->move(public_path().'/articles/', $name);
            //$file->storeAs('articles', $name);

            $article->preview_path = $name;
        }

        $article->save();

         if($request->hasfile('images'))
        {
            foreach($request->file('images') as $file)
            {
                $name = time().'.'.$file->getClientOriginalName();
                $file->move(public_path().'/media/', $name);

                $media = New MediaLibrary;
                $media->path = $name;
                $media->type = 1;
                $media->referral = 2;
                $media->article()->associate($article);
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

        return $this->sendResponse(new ArticleResource($article), 'Article successfully updated.');
    }

    /**
     * @OA\Post(
     *      path="/update/variant/{id}",
     *      summary="Update a variant",
     *      tags={"Article Controller"},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Update a variant",
     *          @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *          @OA\JsonContent(
     *              required={"price","quantity"},
     *              @OA\Property(property="price", type="number"),
     *              @OA\Property(property="quantity", type="number"),
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
    public function updateVariant(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'required',
            'quantity' => 'required',
            'images.*' => 'image|max:2048',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error, 500);
        }

        $variant = Variant::find((int)$id);

        if($variant == null){
            return $this->sendError('Not found', 404);
        }

        if ($request->get('price') != null){
            $variant->price = $request->get('price');
        }

        if ($request->get('quantity') != null){
            $variant->quantity = $request->get('quantity');
        }
        if ($request->get('reduction_price') != null){
            $variant->reduction_price = $request->get('reduction_price');
        }
        if ($request->get('reduction_type')){
            $variant->reduction_type = $request->get('reduction_type');
        }

        $variant->save();

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
                $media->variant()->associate($variant);
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

        $article = Article::find($variant->article_id);

        return $this->sendResponse(new ArticleResource($article), 'Variant successfully updated.');
    }

    /**
     * @OA\Delete(
     ** path="/article/{id}",
     *   tags={"Article Controller"},
     *   summary="Delete a article",
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
    public function delete($id){
        $article = Article::find($id);

        if($article == null){
            return $this->sendError('Not found', 404);
        }

        $article->delete();

        return $this->sendResponse("Article Delete", 'Article successfully Deleted.');
    }

    public function getOne($id){
        $article = Article::find($id);

        if($article == null){
            return $this->sendError('Not found', 404);
        }
        return $this->sendResponse($article, 'Article successfully retrieved.');
    }

    /**
     * @OA\Get(
     ** path="/add/article/{id}",
     *   tags={"Article Controller"},
     *   summary="Add an article to home âge",
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
    public function addArticle($id)
    {

        $article = Article::find($id);

        if ($article == null) {
            return $this->sendError('Not found ', 400);
        }

        if($article->mediaLibraries->count() < 4){
            return $this->sendError('An artilce must have at least four images to be added to home page !', 400);
        }

        $sArticle = SelectedArticle::where('article_id', $id)->first();

        if ($sArticle != null) {
            return $this->sendError('You have already added this artilce', 400);
        }

        $selectedArticle = New SelectedArticle;
        $selectedArticle->article()->associate($article);
        $selectedArticle->save();

        return $this->sendResponse("Article selected successfully", 'Article selected successfully.');
    }

    public function getSelectedArticlesByAssociation($start_as)
    {
        $this->out->writeln("From Laravel API mais voici ca no combi: " . $start_as);
        //$articles = SelectedArticle::all();
        $articles = SelectedArticle::whereHas('article', function ($query) use ($start_as) {
            return $query->whereIn('association', [ $start_as, "UNISEX"]);
        })->get();

        return $this->sendResponse(SelectedArticleResource::collection($articles), 'Selected articles successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/get/selected/articles",
     *   tags={"Article Controller"},
     *   summary="Get selected articles",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getSelectedArticles()
    {
        $articles = SelectedArticle::all();

        return $this->sendResponse(SelectedArticleResource::collection($articles), 'Selected articles successfully retreived.');
    }


        /**
     * @OA\Delete(
     ** path="/article/{id}/selected",
     *   tags={"Article Controller"},
     *   summary="Delete a selected article",
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
    public function deleteSelectedArticle($id)
    {

        $sArticle = SelectedArticle::find($id);

        if ($sArticle == null) {
            return $this->sendError('Not found ', 400);
        }

        $sArticle->delete();

        return $this->sendResponse("Article Removed", 'Article deleted successfully.');
    }
}
