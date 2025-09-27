<?php
<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;

class AdminReplyToGuest extends Mailable
{
    use Queueable, SerializesModels;

    public $original;
    public $replyText;

    public function __construct(Message $original, string $replyText)
    {
        $this->original = $original;
        $this->replyText = $replyText;
    }

    public function build()
    {
        return $this->subject('Reply from InkWise')
                    ->view('emails.admin_reply_guest')
                    ->with(['original' => $this->original, 'replyText' => $this->replyText]);
    }
}