<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailboxModel extends Model
{
    use HasFactory;
    
    protected $table = 'mailbox';
    
    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'subject',
        'content',
        'type',
        'scope',
        'is_read',
        'is_starred'
    ];
}