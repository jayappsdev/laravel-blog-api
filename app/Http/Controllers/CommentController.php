<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use App\Models\Blog;
use Illuminate\Support\Facades\Validator;
use App\Models\Comment;

class CommentController extends Controller
{
    public function __construct(ResponseService $responseService) {
        $this->responseService = $responseService;
    }

    public function list($blog_id,Request $request) {
        $blog=Blog::where('id',$blog_id)->first();
        if ($blog) {
            $perPage=($request->perPage)?$request->perPage:5;
            $comments=Comment::with(['user'])->where('blog_id',$blog_id)->orderBy('id','desc')->paginate($perPage);

            return $this->responseService->successResponse('Comments successfully fetched', [
                'data' => $comments
            ]);
        } else {
            return $this->responseService->errorResponse('No blog found', 400);
        }
    }

    public function create($blog_id,Request $request) {
        $blog=Blog::where('id',$blog_id)->first();
        if ($blog) {
            $validator = Validator::make($request->all(), [
                'message'=>'required',
            ]);

            if ($validator->fails()) {
                return $this->responseService->validationErrorResponse($validator->messages());
            }

            $comment=Comment::create([
                'message'=>$request->message,
                'blog_id'=>$blog->id,
                'user_id'=>$request->user()->id
            ]);

            $comment->load('user');

            return $this->responseService->successResponse('Comment successfully created', [
                'data' => $comment
            ]);
        } else {
            return $this->responseService->errorResponse('No blog found', 400);
        }
    }

    public function update($comment_id,Request $request) {
        $comment=Comment::with(['user'])->where('id',$comment_id)->first();
        if ($comment) {
            if ($comment->user_id==$request->user()->id) {
                $validator = Validator::make($request->all(), [
                    'message'=>'required',
                ]);

                if ($validator->fails()) {
                    return $this->responseService->validationErrorResponse($validator->messages());
                }

                $comment->update([
                    'message'=>$request->message
                ]);

                return $this->responseService->successResponse('Comment successfully updated', [
                    'data' => $comment
                ]);
            } else {
                return $this->responseService->errorResponse('Access denied', 403);
            }
        } else {
            return $this->responseService->errorResponse('No comments found', 400);
        }
    }

    public function delete($comment_id,Request $request) {
        $comment=Comment::where('id',$comment_id)->first();
        if ($comment) {
            if ($comment->user_id==$request->user()->id) {
                $comment->delete();

                return $this->responseService->successResponse('Comment successfully deleted');
            } else {
                return $this->responseService->errorResponse('Access denied', 403);
            }
        } else {
            return $this->responseService->errorResponse('No comments found', 400);
        }
    }
}
