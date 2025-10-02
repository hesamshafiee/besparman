<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CommentRequest;
use App\Http\Resources\V1\CommentResource;
use App\Models\Comment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Comment
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Comment::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new CommentResource(Comment::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(CommentResource::collection(Comment::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param CommentRequest $request
     * @return JsonResponse
     * @group Comment
     */
    public function store(CommentRequest $request): JsonResponse
    {
        $commentId = null;

        $nameSpace = "App\Models\\" . ucfirst($request->model);

        $model = $nameSpace::findOrFail($request->id);


        if ($request->exists('comment_id')) {
            $rootComment = Comment::findOrFail($request->comment_id);
            $commentId = $rootComment->id;
        }

        $comment = new Comment();
        $comment->comment = $request->comment;
        $comment->comment_id = $commentId;
        $comment->user_id = Auth::id();
        $comment->commentable_type = $nameSpace;
        $comment->commentable_id = $model->id;

        if ($comment->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param CommentRequest $request
     * @param Comment $comment
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Comment
     */
    public function update(CommentRequest $request, Comment $comment): JsonResponse
    {
        if ($comment->user_id === Auth::id()) {
            $comment->comment = $request->comment;
            $comment->status = 0;

            if ($comment->save()) {
                return response()->ok(__('general.updatedSuccessfully', ['id' => $comment->id]));
            }
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param CommentRequest $request
     * @param Comment $comment
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Comment
     */
    public function status(CommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', Comment::class);

        $comment->status = !$comment->status;

        if ($comment->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $comment->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
