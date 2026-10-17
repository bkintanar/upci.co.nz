<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        // Optional (owner, 2026-10-17).
        'phone',
        'message',
        // The page the form was on (/connect-with-us, the ABC Connect
        // page, ...), so each inbox knows whose messages are whose.
        'source_page',
    ];
}
