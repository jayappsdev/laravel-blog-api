<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use App\Models\Blog;
use App\Models\BlogLike;
use Illuminate\Support\Facades\File;

class BlogController extends Controller
{
    public function __construct(ResponseService $responseService) {
        $this->responseService = $responseService;
    }

    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'title'=>'required|max:250',
            'short_description'=>'required',
            'long_description'=>'required',
            'category_id'=>'required',
            'image'=>'required|image|mimes:jpg,bmp,png'
        ]);

        if ($validator->fails()) {
            return $this->responseService->validationErrorResponse($validator->errors());
        }

        $image_name=time().'.'.$request->image->extension();
        $request->image->move(public_path('/uploads/blog_images'),$image_name);

        $blog=Blog::create([
            'title'=>$request->title,
            'short_description'=>$request->short_description,
            'long_description'=>$request->long_description,
            'user_id'=>$request->user()->id,
            'category_id'=>$request->category_id,
            'image'=>$image_name
        ]);

        $blog->load('user','category');

        return $this->responseService->successResponse('Blog successfully created', [
            'data' => $blog,
        ]);
    }

    public function list(Request $request) {
        $blog_query=Blog::withCount(['comments','likes'])->with(['user','category']);

        $this->applyFilters($request, $blog_query);

        $sortBy = $request->sortBy && in_array($request->sortBy, ['id','created_at','comments_count','likes_count']) ? $request->sortBy : 'id';
        $sortOrder = $request->sortOrder && in_array($request->sortOrder, ['asc','desc']) ? $request->sortOrder : 'desc';
        $perPage = $request->perPage ?? 5;

        $blogs = $request->paginate ? $blog_query->orderBy($sortBy, $sortOrder)->paginate($perPage) :
        $blog_query->orderBy($sortBy, $sortOrder)->get();

        return $this->responseService->successResponse('Blog successfully fetched', [
            'data' => $blogs,
        ]);
    }

    private function applyFilters(Request $request, $query) {
        if ($request->keyword) {
            $query->where('title', 'LIKE', '%' . $request->keyword . '%');
        }

        if ($request->category) {
            $query->whereHas('category', function ($query) use ($request) {
                $query->where('slug', $request->category);
            });
        }
    
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
    }

    public function details($id) {
        $blog=Blog::withCount(['comments','likes'])->with(['user','category'])->where('id',$id)->first();

        if ($blog) {
            return $this->responseService->successResponse('Blog successfully fetched', [
                'data' => $blog,
            ]);
        } else {
            return $this->responseService->errorResponse('Nothing to found', 400);
        }
    }

    public function update($id,Request $request) {
        $blog=Blog::with(['user','category'])->where('id',$id)->first();
        if ($blog) {
            if ($blog->user_id==$request->user()->id) {
                $validator = Validator::make($request->all(), [
                    'title'=>'required|max:250',
                    'short_description'=>'required',
                    'long_description'=>'required',
                    'category_id'=>'required',
                    'image'=>'nullable|image|mimes:jpg,bmp,png'
                ]);

                if ($validator->fails()) {
                    return $this->responseService->validationErrorResponse($validator->messages());
                }

                if ($request->hasFile('image')) {
                    $image_name=time().'.'.$request->image->extension();
                    $request->image->move(public_path('/uploads/blog_images'),$image_name);
                    $old_path=public_path().'uploads/blog_images'.$blog->image;
                    if (File::exists($old_path)) {
                        File::delete($old_path);
                    }
                } else {
                    $image_name=$blog->image;
                }

                $blog->update([
                    'title'=>$request->title,
                    'short_description'=>$request->short_description,
                    'long_description'=>$request->long_description,
                    'category_id'=>$request->category_id,
                    'image'=>$image_name
                ]);

                return $this->responseService->successResponse('Blog successfully updated', [
                    'data' => $blog,
                ]);
            } else {
                return $this->responseService->errorResponse('Access denied', 403);
            }
        } else {
            return $this->responseService->errorResponse('Nothing to found', 400);
        }
    }

    public function delete($id,Request $request) {
        $blog=Blog::where('id',$id)->first();
        if ($blog) {
            if ($blog->user_id==$request->user()->id) {
                $old_path=public_path().'uploads/blog_images'.$blog->image;
                if (File::exists($old_path)) {
                    File::delete($old_path);
                }

                $blog->delete();

                return $this->responseService->successResponse('Blog successfully deleted');
            } else {
                return $this->responseService->errorResponse('Access denied', 403);
            }
        } else {
            return $this->responseService->errorResponse('Nothing to found', 400);
        }
    }

    public function toggle_like($id,Request $request) {
        $blog=Blog::where('id',$id)->first();
        if ($blog) {
            $user=$request->user();
            $blog_like=BlogLike::where('blog_id',$blog->id)->where('user_id',$user->id)->first();
            if ($blog_like) {
                $blog_like->delete();
                return $this->responseService->successResponse('Like successfully deleted');
            } else {
                BlogLike::create([
                    'blog_id'=>$blog->id,
                    'user_id'=>$user->id
                ]);
                return $this->responseService->successResponse('Blog successfully liked');
            }
        } else {
            return $this->responseService->errorResponse('Nothing to found', 400);
        }
    }
}
