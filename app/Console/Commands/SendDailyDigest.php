<?php

namespace App\Console\Commands;

use App\Mail\DailyBookDigest;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digest:send {--user-id= : Send digest to specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily book quote digest to all users or a specific user';

    protected $quoteService;

    public function __construct(QuoteService $quoteService)
    {
        parent::__construct();
        $this->quoteService = $quoteService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            $users = collect([$user]);
            $this->info("Sending digest to user: {$user->name}");
        } else {
            $users = User::whereHas('books')->get();
            $this->info("Sending digest to " . $users->count() . " users with books.");
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($users as $user) {
            try {
                $result = $this->quoteService->generateDailyQuotesForUser($user);
                
                if ($result['success']) {
                    Mail::to($user->email)->send(new DailyBookDigest($user, $result['quotes']));
                    $successCount++;
                    $this->line("✓ Sent digest to {$user->name} ({$user->email})");
                } else {
                    $errorCount++;
                    $message = isset($result['message']) ? $result['message'] : 'Failed to generate quotes';
                    $this->line("✗ Failed for {$user->name}: {$message}");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Error sending to {$user->name}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("✓ Successfully sent: {$successCount}");
        if ($errorCount > 0) {
            $this->warn("✗ Errors: {$errorCount}");
        }
        
        $this->info('Daily digest sending completed!');
        return 0;
    }
}
