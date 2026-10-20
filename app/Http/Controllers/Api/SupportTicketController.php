<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\MiscellaneousController;
use App\Models\Conversation;
use App\Models\SupportTicket;
use App\Models\SupportTicketStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Purifier;

class SupportTicketController extends Controller
{
  public function index()
  {
    $s_status = SupportTicketStatus::first();
    if ($s_status->support_ticket_status != 'active') {
      return response()->json([
        'status' => 'error',
        'message' => 'Support ticket service is currently unavailable. Please try again later.'
      ], 403);
    }

    $misc = new MiscellaneousController();
    $language = $misc->getLanguage();
    $queryResult['pageHeading'] = $misc->getPageHeading($language);

    $queryResult['bgImg'] = asset('assets/img/' . @$misc->getBreadcrumb()->breadcrumb);

    $collection = SupportTicket::where([['user_id', Auth::guard('sanctum')->user()->id], ['user_type', 'user']])->orderBy('id', 'desc')->paginate(10);

    $collection->transform(function ($col) {
      $col->attachment = HelperController::getImagePath('assets/admin/img/support-ticket/attachment/', $col->attachment);
      return $col;
    });

    $queryResult['collection'] = $collection;

    return response()->json([
      'status' => 'success',
      'data' => $queryResult,
    ], 200);
  }

  public function create()
  {
    $s_status = SupportTicketStatus::first();
    if ($s_status->support_ticket_status != 'active') {
      return response()->json([
        'status' => 'error',
        'message' => 'Support ticket service is currently unavailable. Please try again later.'
      ], 403);
    }

    $misc = new MiscellaneousController();
    $language = $misc->getLanguage();

    $queryResult['pageHeading'] = $misc->getPageHeading($language);
    $queryResult['bgImg'] = asset('assets/img/' . @$misc->getBreadcrumb()->breadcrumb);
    return response()->json([
      'status' => 'success',
      'data' => $queryResult,
    ], 200);
  }

  //store
  public function store(Request $request)
  {
    $rules = [
      'subject' => 'required',
      'email' => 'required',
      'description' => 'required',
    ];
    if ($request->attachment) {
      $rules['attachment'] = 'file';
    }

    if ($request->hasFile('attachment')) {
      $rules['attachment'] = 'mimes:zip|max:20000';
    }

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    $in = $request->all();
    $in['user_id'] = Auth::guard('sanctum')->user()->id;
    $in['user_type'] = 'user';
    $file = $request->file('attachment');
    if ($file) {
      $extension = $file->getClientOriginalExtension();
      $directory = public_path('assets/admin/img/support-ticket/attachment/');
      $fileName = uniqid() . '.' . $extension;
      @mkdir($directory, 0775, true);
      $file->move($directory, $fileName);
      $in['attachment'] = $fileName;
    }
    $in['description'] = Purifier::clean($request->description, 'youtube');
    SupportTicket::create($in);

    return response()->json([
      'success' => true,
      'message' => __('Ticket has been submitted successfully.')
    ]);
  }

  //message
  public function message($id)
  {
    $s_status = SupportTicketStatus::first();
    if ($s_status->support_ticket_status != 'active') {
      return response()->json([
        'status' => 'error',
        'message' => 'Support ticket service is currently unavailable. Please try again later.'
      ], 403);
    }

    $misc = new MiscellaneousController();
    $language = $misc->getLanguage();

    $queryResult['pageHeading'] = $misc->getPageHeading($language);
    $queryResult['bgImg'] = asset('assets/img/' . @$misc->getBreadcrumb()->breadcrumb);
    $bex = SupportTicketStatus::first();

    if ($bex->support_ticket_status == 0) {
      return back();
    }
    $ticket = SupportTicket::where('id', $id)->with('messages')->firstOrFail();
    $ticket->attachment = HelperController::getImagePath('assets/admin/img/support-ticket/attachment/', $ticket->attachment);

    $ticket->messages->transform(function ($message) {
      $message->file = HelperController::getImagePath(
        'assets/admin/img/support-ticket/',
        $message->file
      );
      return $message;
    });

    $queryResult['ticket'] = $ticket;
    return response()->json([
      'status' => 'success',
      'data' => $queryResult,
    ], 200);
  }
  //reply
  public function reply(Request $request, $id)
  {
    $s_status = SupportTicketStatus::first();
    if ($s_status->support_ticket_status != 'active') {
      return response()->json([
        'status' => 'error',
        'message' => 'Support ticket service is currently unavailable. Please try again later.'
      ], 403);
    }

    $file = $request->file('file');
    $allowedExts = array('zip');
    $rules = [
      'reply' => 'required',
      'file' => [
        function ($attribute, $value, $fail) use ($file, $allowedExts) {

          $ext = $file->getClientOriginalExtension();
          if (!in_array($ext, $allowedExts)) {
            return $fail("Only zip file supported");
          }
        },
        'max:20000'
      ],
    ];

    $messages = [
      'file.max' => ' zip file may not be greater than 20 MB',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }
    $input = $request->all();

    $input['reply'] = Purifier::clean($request->reply, 'youtube');
    $input['type'] = 1;
    $input['user_id'] = Auth::guard('sanctum')->user()->id;
    $input['support_ticket_id'] = $id;
    if ($request->hasFile('file')) {
      $file = $request->file('file');
      $filename = uniqid() . '.' . $file->getClientOriginalExtension();
      @mkdir(public_path('assets/admin/img/support-ticket/'), 0775, true);
      $file->move(public_path('assets/admin/img/support-ticket/'), $filename);
      $input['file'] = $filename;
    }

    $data = new Conversation();
    $data->create($input);

    SupportTicket::where('id', $id)->update([
      'last_message' => Carbon::now()
    ]);

    return response()->json([
      'success' => true,
      'message' => __('Message Sent Successfully')
    ]);
  }
}
