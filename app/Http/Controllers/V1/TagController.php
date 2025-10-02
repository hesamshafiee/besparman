<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagRequest;
use App\Http\Resources\V1\TagResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Tags\Tag;

class TagController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Tag
     */
    public function get(Request $request): JsonResponse
    {
        $this->authorize('show', Tag::class);

        $validated = $request->validate([
            'table' => ['required', 'string'],
            'tags.*' => ['required', 'string', ' max:100'],
        ]);


        $type = $validated['table'];
        $model = 'App\Models\\' . ucfirst($type);
        $tags = $validated['tags'];

        return response()->json($model::withAllTags($tags, $type)->paginate(50));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Tag
     */
    public function index(Request $request) : JsonResponse
    {
        $this->authorize('show', Tag::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new TagResource(Tag::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(TagResource::collection(Tag::orderBy($order, $typeOrder)->paginate($perPage)));
    }


    /**
     * @param TagRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Tag
     */
    public function store(TagRequest $request): JsonResponse
    {
        $this->authorize('create', Tag::class);

        $tag = Tag::findOrCreate($request->get('title'), $request->get('type'));


        if ($tag) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));

    }

    /**
     * @param Tag $tag
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Tag
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $this->authorize('delete', Tag::class);

        $isUsed = DB::table('taggables')
            ->where('tag_id', $tag->id)
            ->exists();

        if (!$isUsed) {
            if ($tag->delete()) {
                return response()->ok(__('general.deletedSuccessfully', ['id' => $tag->id]));
            }
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Tag $tag
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Tag
     */
    public function forceDestroy(Tag $tag): JsonResponse
    {
        $this->authorize('delete', Tag::class);

        $taggables = DB::table('taggables')
            ->where('tag_id', $tag->id)
            ->get();

        if ($taggables->isNotEmpty()) {
            foreach ($taggables as $taggable) {
                $modelClass = $taggable->taggable_type;
                $modelId = $taggable->taggable_id;

                $model = app($modelClass)::find($modelId);

                if ($model && method_exists($model, 'detachTag')) {
                    $model->detachTag($tag);
                }
            }
        }


        if ($tag->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $tag->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
