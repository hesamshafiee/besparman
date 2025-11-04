<?php

namespace App\Http\Controllers\V1;


use App\Http\Requests\StoreWorkRequest;
use App\Http\Controllers\Controller;

use App\Http\Requests\UpdateWorkRequest;
use App\Http\Resources\V1\WorkResource;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;



class WorkController extends Controller
{

    /**
     * Undocumented function
     *
     * @param Request $request
     * @return JsonResponse
     * @group Work
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new WorkResource(
                Work::where(
                    [
                        'user_id' => Auth::id(),
                        'id' => $id
                    ]
                )->firstOrFail()
            ));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(WorkResource::collection(Work::where('user_id', Auth::id())->orderBy($order, $typeOrder)->paginate($perPage)));
    }





    /**
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @group Work
     */
    public function clientStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'is_published'  => ['sometimes', 'boolean'],
            'published_at'  => ['nullable', 'date'],
        ]);

        $work = new Work();
        $work->fill($request->only(['title', 'description']));
        $work->user_id      = \Illuminate\Support\Facades\Auth::id();
        $work->slug         = Work::makeSlug($validated['title']);
        $work->is_published = (bool) $request->boolean('is_published', true);
        $work->published_at = $request->date('published_at') ?? now();

        if (! $work->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }
        return response()->ok(__('general.savedSuccessfully'));
    }



    /**
     *
     * @param Request $request
     * @param Work    $work
     * @return JsonResponse
     * @group Work
     */
    public function clientUpdate(Request $request, Work $work): JsonResponse
    {
        $validated = $request->validate([
            'title'         => ['sometimes', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'is_published'  => ['sometimes', 'boolean'],
            'published_at'  => ['nullable', 'date'],
            'image_driver'  => ['sometimes', 'in:local,public'],
        ]);

        if ($work->user_id !== Auth::id()) {
            return response()->forbidden(__('general.forbidden'));
        }

        if (array_key_exists('title', $validated)) {
            $work->title = $validated['title'];
            $work->slug  = Work::makeSlug($validated['title']);
        }
        if (array_key_exists('description', $validated)) {
            $work->description = $validated['description'];
        }
        if (array_key_exists('is_published', $validated)) {
            $work->is_published = (bool) $validated['is_published'];
        }
        if (array_key_exists('published_at', $validated)) {
            $work->published_at = $validated['published_at'];
        }


        if (! $work->save()) {
            return response()->serverError(__('general.somethingWrong'));
        }
        return response()->ok(__('general.updatedSuccessfully'));
    }


    /**
     *
     * @param Work $work
     * @return void
     * @group Work
     */
    public function clientDestroy(Work $work)
    {
        if ($work->user_id !== Auth::id()) {
            return response()->forbidden(__('general.forbidden'));
        }
        if ($work->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $work->id]));
        }
        return response()->serverError(__('general.somethingWrong'));
    }


    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Work
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Work::class);

        $id        = (int) $request->query('id', 0);
        $order     = $request->query('order', 'id');
        $typeOrder = strtolower($request->query('type_order', 'desc'));
        $perPage   = (int) $request->query('per_page', 10);

        // کنترل soft delete
        $onlyTrashed = (bool) $request->boolean('only_trashed', false);
        $withTrashed = (bool) $request->boolean('with_trashed', false);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }
        if (!in_array($typeOrder, ['asc', 'desc'], true)) {
            $typeOrder = 'desc';
        }

        $base = Work::query();

         if ($onlyTrashed) {
            $base->onlyTrashed();
        } elseif ($withTrashed) {
            $base->withTrashed();
        }

        if ($id) {
            $item = (clone $base)->where('id', $id)->firstOrFail();
            return response()->jsonMacro(new WorkResource($item));
        }

        $paginator = $base->orderBy($order, $typeOrder)->paginate($perPage);
        return response()->jsonMacro(WorkResource::collection($paginator));
    }

    /**
     *
     * @param Work $work
     * @return void
     * @group Work
     */
    public function destroy(Work $work)
    {
        $this->authorize('show', Work::class);
        if ($work->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $work->id]));
        }
        return response()->serverError(__('general.somethingWrong'));
    }

    public function restore(int $id)
    {
        $this->authorize('create', Work::class);

        $artwork = Work::onlyTrashed()->findOrFail($id);
        $artwork->restore();
        return response()->ok(__('general.restoredSuccessfully'));
    }
}
