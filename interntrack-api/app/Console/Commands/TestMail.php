<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test mail configuration by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info('Testing mail configuration...');
        $this->info('Recipient: ' . $email);
        $this->info('MAIL_MAILER: ' . config('mail.default'));
        $this->info('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->info('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        $this->info('MAIL_FROM_ADDRESS: ' . config('mail.from.address'));
        $this->newLine();

        try {
            Mail::raw('This is a test email from InternTrack API. If you received this, your mail configuration is working correctly!', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email from InternTrack API');
            });

            $this->info('✓ Email sent successfully!');
            $this->info('Check the inbox (and spam folder) at: ' . $email);
            
            return 0;
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $this->error('✗ Mail transport error: ' . $e->getMessage());
            Log::error('Mail test failed (TransportException): ' . $e->getMessage());
            
            $this->newLine();
            $this->warn('Possible issues:');
            $this->line('1. SendGrid API key is invalid or expired');
            $this->line('2. Mail server is unreachable');
            $this->line('3. Port ' . config('mail.mailers.smtp.port') . ' is blocked');
            
            return 1;
        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            Log::error('Mail test failed: ' . $e->getMessage());
            
            return 1;
        }
    }
}
