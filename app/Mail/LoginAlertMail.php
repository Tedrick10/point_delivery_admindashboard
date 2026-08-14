<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $storeData;
    /**
     * Create a new message instance.
     */
    public function __construct($storeData)
    {
        $this->storeData = $storeData;
    }

    public function build()
    {
        $content = "
            <p><strong>New login detected on your account:</strong></p>
            <ul>
                <li><strong>IP Address:</strong> {$this->storeData['ip_address']}</li>
                <li><strong>City:</strong> {$this->storeData['city']}</li>
                <li><strong>Region:</strong> {$this->storeData['region']}</li>
                <li><strong>Country:</strong> {$this->storeData['country']}</li>
                <li><strong>Browser:</strong> {$this->storeData['browser']}</li>
                <li><strong>Platform:</strong> {$this->storeData['platform']}</li>
            </ul>
        ";

        return $this->from(config('mail.from.address'), config('mail.from.name')) 
                    ->subject('⚠️ New Login Detected on Your Account')
                    ->view('emails.email_notification')
                    ->with([
                        'title'   => 'Login Alert',
                        'content' => $content,
                    ]);
    }
}
