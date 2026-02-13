<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $table = 'contactmessages';
    protected $fillable = [
        'fullname',
        'phonenumber',
        'emailaddress',
        'message',
        'reply',
        'user_id', # replied by who
        'repliedat',
    ];
    
    /**
     * repliedby - return instance of the user who replied the message
     *
     * @return BelongsTo
     */
    public function repliedby():BelongsTo
    {
        return $this->belongsTo(User::class,'user_id', 'id');
    }
}