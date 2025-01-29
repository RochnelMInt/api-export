<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Feature;
use App\Models\JobUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\Job;
use App\Models\User;
use App\Models\Task;
use App\Models\Qualification;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\JobResource;
use \Firebase\JWT\JWT;
use PeterPetrus\Auth\PassportToken;
use App\Http\Resources\CategoryResource;
use App\Models\MediaLibrary;
use App\Models\SelectedCategory;
use App\Http\Resources\SelectedCategoryResource;
use Illuminate\Support\Collection;

class CategoryController extends BaseController
{
    private $out;

    public function __construct()
    {
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * @OA\Get(
     ** path="/category/{id}",
     *   tags={"Category Controller"},
     *   summary="Get a category",
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
    public function getCategory($id)
    {
        $category = Category::find($id);

        if ($category == null) {

            return $this->sendError('The category not found', '404');

        }

        return $this->sendResponse(new CategoryResource($category), 'Category retreived successfully.');
    }

    /**
     * @OA\Post(
     *      path="/create/category",
     *      summary="Create a new category",
     *      tags={"Category Controller"},
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
    public function createCategory(Request $request)
    {
        $category = DB::table('categories')->where('name', $request->get('name'))->first();

        if ($category != null) {
            return $this->sendError('A category already exist with the name ' . $request->get('name'), 400);
        }

        $category = new category;

        if ($request->get('name') != '') {
            $category->name = $request->get('name');
        }

        if ($request->get('description') != '') {
            $category->description = $request->get('description');
        }

        if($request->get('parent_category') != "0"){
            $category_id = (int)$request->get('parent_category');
            $cat = Category::find($category_id);
            $category->type = $cat->type_number;
        }else{
            $category->type = (int)$request->get('type');
        }

        $category->save();

        if($request->get('parent_category') != "0"){
            $category_id = (int)$request->get('parent_category');
            $cat = Category::find($category_id);
            $category->parent()->attach($cat);
        }

        return $this->sendResponse(new CategoryResource($category), 'Category successfully created.');
    }

    public function getCategoriesWithoutChild($type)
    {

        $parent_ids = DB::table('parent_category')->select('child_category_id')->distinct()->pluck('child_category_id');
        $this->out->writeln("parent categories ids with doubles : " . $parent_ids);
        //$array_parent_ids = array();
        //if($parent_ids->count() > 1){
        //    $filtered_parent_ids = $parent_ids->duplicates()->unique();
        //    $array_parent_ids = json_decode($filtered_parent_ids, true);
        //}else{
        $array_parent_ids = json_decode($parent_ids, true);
        //}
        
        $this->out->writeln("parent categories ids : " . implode(', ', $array_parent_ids));
        $category_ids = Category::where('type', (int)$type)->pluck('id');
        $array_cat_ids = $category_ids->toArray();
        $this->out->writeln("all categories ids : " . implode(', ', $array_cat_ids));

        $diff_ids = array_diff($array_cat_ids, $array_parent_ids);
        $this->out->writeln(" diff categories ids: " . implode(', ', $diff_ids));

        $ids = array();
        foreach($diff_ids as $id){
            array_push($ids, $id);
        }

        $categoriesWithoutChild = Category::whereIn("id", $ids)->get();

        return $this->sendResponse(CategoryResource::collection($categoriesWithoutChild), 'Categories without child successfully retrieved.');
    }

    public function getCategoriesWithoutParents()
    {
        // Retrieve categories without parents
        $categoriesWithoutParents = Category::whereNotIn('id', function ($query) {
            $query->select('child_category_id')
                ->from('parent_category');
        })->get();

        return $this->sendResponse(CategoryResource::collection($categoriesWithoutParents), 'Categories without parents successfully retrieved.');
    }

    /**
     * @OA\Post(
     *      path="/update/category/{id}",
     *      summary="Update a category",
     *      tags={"Category Controller"},
     *      @OA\Parameter(name="name", in="path", required=true, @OA\Schema(type="string")),
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
    public function updateCategory(Request $request, $id)
    {
        $category = Category::find($id);
        if ($category == null) {
            return $this->sendError('Not found ', 400);
        }

        if ($request->get('name') != '') {
            $category->name = $request->get('name');
        }

        if ($request->get('description') != '') {
            $category->description = $request->get('description');
        }

        if($request->get('parent_category') != "0"){
            $category_id = (int)$request->get('parent_category');
            $cat = Category::find($category_id);
            $category->parent()->attach($cat);
            $category->type = $cat->type_number;
        }else{
            $category_id = $category->getParent();
            $cat = Category::find($category_id);
            $category->type = (int)$request->get('type');
            $category->parent()->detach($cat);
        }

        $category->save();

        return $this->sendResponse(new CategoryResource($category), 'Category successfully created.');
    }

    /**
     * @OA\Get(
     ** path="/categories",
     *   tags={"Article Controller"},
     *   summary="Get all categories",
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
    public function getAllCategories()
    {
        $categories = Category::all();
        return $this->sendResponse(CategoryResource::collection($categories), 'Categories successfully created.');
    }

    public function getAllPaginatedCategories(Request $request)
    {
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }
        $categories = Category::paginate($numberElement);
        return $this->sendResponse(CategoryResource::collection($categories)->response()->getData(true), 'Categories successfully retrieved.');
    }

    /**
     * @OA\Delete(
     ** path="/category/{id}",
     *   tags={"Category Controller"},
     *   summary="Delete a category",
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
    public function deleteCategory($id)
    {

        $category = Category::find($id);

        if ($category == null) {
            return $this->sendError('Not found ', 400);
        }

        if($category->articles->count() > 0){
            return $this->sendError('Can not delete. The category is already in use !', 400);
        }

        $category->delete();

        return $this->sendResponse("Category Delete", 'Category deleted successfully.');
    }

        /**
     * @OA\Get(
     ** path="/add/category/{id}",
     *   tags={"Category Controller"},
     *   summary="Add a category to home âge",
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
    public function addCategory($id)
    {

        $category = Category::find($id);

        if ($category == null) {
            return $this->sendError('Not found ', 400);
        }

        if($category->mediaLibraries->count() <= 0){
            return $this->sendError('A category must have an image to be added to home page !', 400);
        }

        $sCat = SelectedCategory::where('category_id', $id)->first();

        if ($sCat != null) {
            return $this->sendError('You have already added this category', 400);
        }

        $selectedCategory = New SelectedCategory;
        $selectedCategory->category()->associate($category);
        $selectedCategory->save();

        return $this->sendResponse("Category selected successfully", 'Category selected successfully.');
    }

        /**
     * @OA\Get(
     ** path="/category/{id}/descent",
     *   tags={"Category Controller"},
     *   summary="Get a category descents",
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
    public function findCategoryDescent($id)
    {
        $category = Category::find($id);

        if ($category == null) {
            return $this->sendError('Not found ', 400);
        }

        $categories = $category->getDescendants($category);

        return $this->sendResponse(CategoryResource::collection($categories), 'Category descents successfully retreived.');
    }

    public function getPaginatedJobsWithRange(Request $request)
    {

        $numberElement = (int)$request->numberElement;

        if ($numberElement <= 0) {
            $numberElement = 25;
        }

        $users = Job::paginate($numberElement);

        //$admins = User::paginate(25);

        return $this->sendResponse($users, 'Users successfully retreived.');
    }

    public function getCategories(Request $request)
    {

        $numberElement = (int)$request->numberElement;

        if ($numberElement <= 0) {
            $numberElement = 25;
        }

        // $posts = Feature::where('post_type', (int)$type)->paginate($numberElement);
        $categories = Category::all();
        return $this->sendResponse(CategoryResource::collection($categories), 'Posts successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/get/child/categories",
     *   tags={"Category Controller"},
     *   summary="Get child Categories",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getChildCategories()
    {
        $categories = Category::where('type', 'CHILD')->get();
        return $this->sendResponse(CategoryResource::collection($categories), 'Child categories successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/get/selected/categories/association/{ass}",
     *   tags={"Category Controller"},
     *   summary="Get selected Categories",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getSelectedCategoriesByAss($ass)
    {
        $categories = SelectedCategory::all();
        $number0fMen = 0;
        $number0fMen = 0;
        $number0fMen = 0;

        foreach($categories as $cat) {
            $category = $cat->category;
            foreach($category->articles as $article) {
                if($article->association == $ass || $article->association == "UNISEX"){
                    array_push($wantedCategories, $cat);
                }
            }
        }

        $query = DB::table('articles')
        ->leftJoin('variants', 'articles.id', '=', 'variants.article_id')
        ->leftJoin('feature_value_variant', 'variants.id', '=', 'feature_value_variant.variant_id')
        ->leftJoin('article_category', 'articles.id', '=', 'article_category.article_id');

        return $this->sendResponse(SelectedCategoryResource::collection($categories), 'Selected categories successfully retreived.');
    }

        /**
     * @OA\Get(
     ** path="/get/number/articles/category/{id}/association/{ass}",
     *   tags={"Category Controller"},
     *   summary="Get selected Categories",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getNumberOfArticlesByAss($id, $ass)
    {
        $category = Category::find($id);
        $number = 0;

        foreach($category->articles as $article) {
            if($article->association == $ass || $article->association == "UNISEX"){
                $number = $number + 1;
            }
        }

        return $this->sendResponse($number, 'Number of articles successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/get/selected/categories",
     *   tags={"Category Controller"},
     *   summary="Get selected Categories",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getSelectedCategories()
    {
        $categories = SelectedCategory::all();

        return $this->sendResponse(SelectedCategoryResource::collection($categories), 'Selected categories successfully retreived.');
    }

        /**
     * @OA\Delete(
     ** path="/category/{id}/selected",
     *   tags={"Category Controller"},
     *   summary="Delete a selected category",
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
    public function deleteSelectedCategory($id)
    {

        $sCategory = SelectedCategory::where('category_id', $id)->first();

        if ($sCategory == null) {
            return $this->sendError('Not found ', 400);
        }

        $sCategory->delete();

        return $this->sendResponse("Category Removed", 'Category deleted successfully.');
    }
}
