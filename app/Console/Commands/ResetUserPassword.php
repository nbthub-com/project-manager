<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     *   php artisan user:reset-password {identifier} {password}
     *
     * identifier can be either email OR username
     */
    protected $signature = 'user:reset-password {identifier} {password}';

    protected $description = 'Reset a user password by email or username';

    public function handle()
    {
        $identifier = $this->argument('identifier');
        $password   = $this->argument('password');

        // First try email, then username
        $user = User::where('email', $identifier)
                    ->orWhere('username', $identifier)
                    ->first();

        if (! $user) {
            $this->error("No user found with email/username: {$identifier}");
            return Command::FAILURE;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password reset successfully for {$user->email} (username: {$user->username})");
        return Command::SUCCESS;
    }
}
