<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Users\Models\User;
use Illuminate\Console\Command;

/**
 * MakeUserAdmin command allows making a user an administrator by email.
 */
class MakeUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin {email : The email address of the user to make admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user an administrator by email address';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return self::FAILURE;
        }

        if ($user->role === 'admin') {
            $this->info("User '{$email}' is already an admin.");
            return self::SUCCESS;
        }

        $user->update([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->info("✓ User '{$email}' has been made an administrator.");
        return self::SUCCESS;
    }
}
