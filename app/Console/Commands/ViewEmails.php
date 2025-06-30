<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ViewEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View emails stored in the array driver';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Email Configuration:');
        $this->line('MAIL_MAILER: ' . config('mail.default'));
        $this->newLine();

        if (config('mail.default') === 'array') {
            $this->info('Array driver is configured. Emails are stored in memory.');
            $this->line('To view emails, check the Laravel log files or use a different mail driver.');
        } elseif (config('mail.default') === 'log') {
            $this->info('Log driver is configured. Emails are saved to log files.');
            $this->line('Check storage/logs/laravel.log for email content.');
        } else {
            $this->info('Other mail driver configured: ' . config('mail.default'));
        }

        $this->newLine();
        $this->info('To test email verification:');
        $this->line('1. Register a new user');
        $this->line('2. Check the logs or use the verification URL from the email');
        $this->line('3. Or use the "Resend verification email" button on the login page');
    }
}
