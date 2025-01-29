<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;
use App\Http\Resources\SubCategoryResource;
use Illuminate\Support\Facades\DB;

class SubCategoryController extends BaseController
{
        /**
     * @OA\Get(
     ** path="/sub-category/{id}",
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
    public function getSubCategory($id)
    {
        $subCategory = SubCategory::find($id);

        if ($subCategory == null) {

                return $this->sendError('The sub category not found', '404');

        }

        return $this->sendResponse(new SubCategoryResource($subCategory), 'Category retreived successfully.');
    }


    /**
    * @OA\Post(
    *      path="/create/sub-category",
    *      summary="Create a new sub category",
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
   public function createSubCategory(Request $request)
   {
       $subCategory = DB::table('sub_categories')->where('name', $request->get('name'))->first();

       if ($subCategory != null) {
           return $this->sendError('A category already exist with the name ' . $request->get('name'), 400);
       }

       $category = Category::findOrFail((int)$request->get('category'));

       if ($category == null) {
           return $this->sendError('Category with the id ' . $request->get('category') . ' not found', 400);
       }

       $subCategory = new SubCategory;

       if ($request->get('name') != '') {
           $subCategory->name = $request->get('name');
       }

       if ($request->get('description') != '') {
           $subCategory->description = $request->get('description');
       }

       $subCategory->type = (int)$request->get('type');

       $subCategory->category()->associate($category);

       $subCategory->save();

       return $this->sendResponse(new SubCategoryResource($subCategory), 'Sub category successfully created.');
   }

   /**
    * @OA\Post(
    *      path="/update/sub-category/{id}",
    *      summary="Update a sub category",
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
   public function updateSubCategory(Request $request, $id)
   {
       $subCategory = SubCategory::find($id);
       if ($subCategory == null) {
           return $this->sendError('Not found ', 400);
       }

       $category = Category::find((int)$request->get('category'));
       if ($category == null) {
           return $this->sendError('Not found ', 400);
       }

       if ($request->get('name') != '') {
           $subCategory->name = $request->get('name');
       }

       if ($request->get('description') != '') {
           $subCategory->description = $request->get('description');
       }

       $subCategory->type = (int)$request->get('type');

       $subCategory->category()->associate($category);

       $subCategory->save();

       return $this->sendResponse(new SubCategoryResource($subCategory), 'Sub category successfully updated.');
   }

       /**
     * @OA\Get(
     ** path="/subcategories",
     *   tags={"Category Controller"},
     *   summary="Get all sub categories",
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
    public function getAllSubCategories()
    {
        $subCategories = SubCategory::all();
        return $this->sendResponse(SubCategoryResource::collection($subCategories), 'Categories successfully created.');
    }

    public function getAllPaginatedSubCategories(Request $request)
    {
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }
        $subCategories = SubCategory::paginate($numberElement);
        return $this->sendResponse(SubCategoryResource::collection($subCategories)->response()->getData(true), 'Sub Categories successfully retrieved.');
    }

    /**
     * @OA\Delete(
     ** path="/sub-category/{id}",
     *   tags={"Category sub Controller"},
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
    public function deleteSubCategory($id)
    {

        $subCategory = SubCategory::find($id);

        if ($subCategory == null) {
            return $this->sendError('Not found ', 400);
        }

        if($subCategory->articles->count() > 0){
            return $this->sendError('Can not delete. The category is already in use !', 400);
        }

        $subCategory->delete();

        return $this->sendResponse("Sub category Delete", 'Category deleted successfully.');
    }
}
