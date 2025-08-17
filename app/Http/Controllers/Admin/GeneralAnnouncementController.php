<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralAnnouncement;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GeneralAnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try{
            $query = GeneralAnnouncement::orderBy('id', 'desc');
            $perPage = $request->query('per_page', 25);
            $searchQuery = $request->query('search');
            $status = $request->query('status');
            if (!empty($searchQuery)) {
                $query->where('title', 'like', '%' . $searchQuery . '%');
            }
            if (!empty($status)) {
                $query->where('status', $status);
            }
            $data = $query->paginate($perPage);
            return response()->json($data);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try{
            $validator = Validator::make($request->all(),[
                'title' => 'required',
                'message' => 'required',
                'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,svg|max:2048',
                'image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'priority' => 'required|in:1,2,3,4,5',
                'audience' => 'required|in:all,riders,customers',
                'status' => 'required|in:draft,approved,pending',
                'scheduled_at' => 'nullable',
            ],[
                'title.required' => 'Title is required',
                'message.required' => 'Message is required',
                'attachment.mimes' => 'Attachment must be a file of type: pdf, doc, docx, jpg, jpeg, png, gif, svg',
                'attachment.max' => 'Attachment may not be greater than 2mb',
                'image.mimes' => 'Image must be an image',
                'image.max' => 'Image may not be greater than 2mb',
                'priority.required' => 'Priority is required',
                'priority.in' => 'Priority must be 1, 2, 3, 4 or 5',
                'audience.required' => 'Audience is required',
                'audience.in' => 'Audience must be all, riders or customers',
                'status.required' => 'Status is required',
                'status.in' => 'Status must be draft, approved or pending',
            ]);
            DB::beginTransaction();
            if($validator->fails())throw new Exception($validator->errors()->first(),400);
            $image = null;
            $attachment = null;
            if($request->has('image')){
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('announcements'), $imageName);
                $image = 'announcements/' . $imageName;
            }

            if($request->has('attachment')){
                $attachment = $request->file('attachment');
                $attachmentName = time() . '.' . $attachment->getClientOriginalExtension();
                $attachment->move(public_path('announcements'), $attachmentName);
                $attachment = 'announcements/' . $attachmentName;
            }
            $formattedDateTime = ipToUtc($request->ip(), $request->scheduled_at,'Y-m-d H:i:s');
            $data = GeneralAnnouncement::create([
                'title' => $request->title,
                'message' => $request->message,
                'image' => $image,
                'attachment' => $attachment,
                'priority' => $request->priority,
                'audience' => $request->audience,
                'status' => $request->status,
                'scheduled_at' => $formattedDateTime ?? null,
                'is_sent' => $request->scheduled_at ? 0 : 1
            ]);
            DB::commit();
            return response()->json($data, 201);
        }catch(QueryException $e){  
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try{
            $announcement = GeneralAnnouncement::findOrFail($id);
            return response()->json($announcement);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try{
            $announcement = GeneralAnnouncement::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'message' => 'required',
                'priority' => 'required|in:1,2,3,4,5',
                'audience' => 'required|in:all,riders,customers',
                'status' => 'required|in:draft,approved,pending',
                'scheduled_at' => 'nullable',
            ]);
            if($validator->fails()) throw new Exception($validator->errors()->first(),400);
            
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $announcement = GeneralAnnouncement::findOrFail($id);
            $announcement->delete();
            Session::flash('success', ['text' => 'Announcement deleted successfully']);
            return redirect()->route('admin.general-announcement.index');
        }catch(Exception $e){
            Session::flash('error', ['text' => $e->getMessage()]);
            return redirect()->back();
        }
    }
}
