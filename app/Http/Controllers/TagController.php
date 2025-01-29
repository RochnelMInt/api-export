<?php

namespace App\Http\Controllers;

use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends BaseController
{

    /**
     * @OA\Get(
     *      path="/tags/popular",
     *      summary="Get 10 most popular tags",
     *      tags={"Tags Controller"},
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
    public function getPopularTags()
    {
        // Get the 10 most popular tags used in articles and news
        $popularTags = Tag::select('tags.name')
            ->join('taggables', 'tags.id', '=', 'taggables.tag_id')
            ->selectRaw('count(*) as occurrences, tags.name')
            ->groupBy('tags.name')
            ->orderBy('occurrences', 'desc')
            ->limit(10)
            ->get();

        return TagResource::collection($popularTags);
    }
}
