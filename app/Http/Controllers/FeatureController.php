<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Feature;
use App\Models\FeatureValue;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\FeatureResource;

class FeatureController extends BaseController
{
    private $out;

    public function __construct()
    {
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * @OA\Post(
     ** path="/create/feature",
     *   tags={"Feature Controller"},
     *   summary="Create Feature",
     *      @OA\Parameter(name="name", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="type", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="unit", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function createFeature(Request $request)
    {

        $feature = Feature::where('name', $request->get('name'))->first();

        if ($feature != null) {
            return $this->sendError('A feature already exist with the name ' . $request->get('name'), 400);
        }

        $feature = new Feature;
        $feature->name = $request->get('name');
        $feature->unit = $request->get('unit');
        $feature->type = $request->get('type');

        $feature->save();

        return $this->sendResponse($feature, 'Feature successfully created.');
    }

    /**
     * @OA\Post(
     ** path="/update/feature/{id}",
     *   tags={"Feature Controller"},
     *   summary="Update Feature",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="name", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="type", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="unit", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function updateFeature(Request $request, $id)
    {

        $feature = Feature::find($id);

        if($feature == null){
            return $this->sendError('Not found ', 400);
        }

        if ($request->get('name') != ''){
            $feature->name = $request->get('name');
        }
        if ($request->get('type') != ''){
            $feature->type = $request->get('type');
        }
        if ($request->get('unit') != ''){
            $feature->unit = $request->get('unit');
        }

        $feature->save();

        return $this->sendResponse($feature, 'Feature successfully updated.');
    }

    /**
     * @OA\Get(
     ** path="/get/features/type/{type}/paginate",
     *   tags={"Feature Controller"},
     *   summary="Update Feature",
     *      @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getFeatureByTypePaginate(Request $request, $type)
    {

        $numberElement = (int)$request->numberElement;

        if ($numberElement <= 0) {
            $numberElement = 25;
        }

        // $posts = Feature::where('post_type', (int)$type)->paginate($numberElement);
        $features = Feature::all();

        //return $this->sendResponse(PostResource::collection($posts)->response()->getData(true), 'Posts successfully retreived.');
        return $this->sendResponse($features, 'Features successfully retreived.');
        //return $this->sendResponse($result, 'Posts successfully retreived.');
    }

    /**
     * @OA\Get(
     ** path="/get/features/paginate",
     *   tags={"Feature Controller"},
     *   summary="Get all Features",
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function getFeaturesPaginate()
    {
        $features = Feature::all()->reverse();
        return $this->sendResponse(FeatureResource::collection($features), 'Features successfully retrieved.');
    }

    public function paginatedFeatures(Request $request)
    {
        $numberElement = (int)$request->numberElement;
        if($numberElement <= 0){
            $numberElement = 25;
        }
        $features = Feature::paginate($numberElement);
        return $this->sendResponse(FeatureResource::collection($features)->response()->getData(true), 'Features successfully retrieved.');
    }

        /**
     * @OA\Delete(
     ** path="/feature/{id}",
     *   tags={"Feature Controller"},
     *   summary="Delete a feature",
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
    public function deleteFeature($id)
    {

        $feature = Feature::findOrFail($id);

        if($feature == null) {
            return $this->sendError('Not found ', 400);
        }

        if($feature->categories->count() > 0){
            return $this->sendError('Can not delete. The feature is already in use !', 400);
        }

        $feature->delete();

        return $this->sendResponse("Feature Delete", 'Category deleted successfully.');
    }

        /**
     * @OA\Post(
     ** path="/add/feature/{id}/value",
     *   tags={"Feature Controller"},
     *   summary="Add Value to Feature",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Parameter(name="value", in="query", required=true, @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *      ),
     *  )
     *
     * @param Request $request
     */
    public function addFeatureValue(Request $request, $id)
    {
        $feature = Feature::findOrFail($id);

        if ($feature == null) {
            return $this->sendError('The feature is not found', 400);
        }

       // $featureValue = FeatureValue::where('feature_id', $id)->where("value", "LIKE", "%" . $request->get('value') . "%")->first();
        $featureValue = FeatureValue::where('feature_id', $id)->where("value", "=", $request->get('value'))->first();

        if ($featureValue != null) {
            return $this->sendError('Can not add. This value already exists !', 400);
        }

        $featureValue = New FeatureValue;
        $featureValue->value = $request->get('value');
        $featureValue->feature()->associate($feature);
        $featureValue->save();

        return $this->sendResponse($featureValue, 'Feature value successfully added.');
    }

    public function addManyFeatureValue(Request $request, $id)
    {
        $feature = Feature::findOrFail($id);

        if ($feature == null) {
            return $this->sendError('The feature is not found', 400);
        }

        $featureValues = $request->get('value');

        if(count($featureValues) > 0){
            foreach ($featureValues as $fv) {
                $fvtrim = trim($fv);
                //$featureValue = FeatureValue::where('feature_id', $id)->where("value", "LIKE", "%" . $fvtrim . "%")->first();
                $featureValue = FeatureValue::where('feature_id', $id)->where("value", "=", $fvtrim)->first();
                if ($featureValue != null) {
                    return $this->sendError('Can not add. This value already exists !', 400);
                }

                $featureValue = New FeatureValue;
                $featureValue->value = $fvtrim;
                $featureValue->feature()->associate($feature);
                $featureValue->save();
            }

            return $this->sendResponse($featureValue, 'Feature value successfully added.');
        }

        return $this->sendError('No feature values has been send', 400);

    }
}
