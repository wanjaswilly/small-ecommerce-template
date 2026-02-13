<?php

namespace   App\Services;

use App\Exceptions\ValidationException;
use App\Models\ContactMessage;
use Carbon\Carbon;
use Psr\Http\Message\ServerRequestInterface;

class ContactMessageService
{

    public function allMessages(): array
    {
        return [
            'messages' => ContactMessage::latest()->get(),
        ];
    }
    public function singleMessageData(int $messageId):array
    {        
        if(!$message = ContactMessage::with('repliedby')->find($messageId)){
            throw new ValidationException('Message not Found',['error' => "Contact message not found, try again"]);
        }
        return [
            'message' => $message,
        ];
    }
    public function saveReply(ServerRequestInterface $request, int $messageId):array
    {
        $data = $request->getParsedBody();
        if(empty($data['reply'])){
            throw new ValidationException('Empty Message Reply', ['error' => "reply to a contact message cannot be empty"]);
        }

        if(!$message = ContactMessage::with('repliedby')->find($messageId)){
            throw new ValidationException('Message not Found',['error' => "Contact message not found, try again"]);
        }
        $message->reply = $data['reply'];
        $message->repliedat = Carbon::now();
        $message->user_id = $_SESSION['user_id'];
        if($message->save()){
            $_SESSION['success'] = "Your reply was saved successfully";
        }else{
            throw new ValidationException('Error while saving reply',['error' => "An error occured while saving your reply"]);
        }

        return [
            'message' => $message,
        ];
    }
}