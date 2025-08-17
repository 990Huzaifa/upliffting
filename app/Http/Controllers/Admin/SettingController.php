<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function dataset(Request $request): JsonResponse
    {   
        try{
            $title = $request->title;
            $role = $request->role;
            $data = Page::where('title', $title)->where('role', $role)->first();
            return response()->json($data,200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function datasetStore(Request $request): JsonResponse
    {   
        try{
            $validator = Validator::make($request->all(),[
                'title' => 'required|exists:pages,title',
                'role' => 'required|exists:pages,role',
                'content' => 'required',
            ],[
                'content.required' => 'Content is required',
                'title.required' => 'Title is required',
                'role.required' => 'Role is required',
            ]);

            if ($validator->fails()) throw new Exception($validator->errors()->first(), 400);
            

            $data = Page::where('title', $request->title)->where('role', $request->role)->first();
            $data->update([
                'content' => $request->content,
            ]);
            return response()->json($data,200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    
}
