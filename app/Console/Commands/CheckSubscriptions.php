<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;

use App\Models\SubscriptionDetail;

use App\Models\User;

use App\Models\UserSubscription;
use Carbon\Carbon;

class CheckSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update subscription statuses based on end date';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */

    public function handle()
    {
        // Fetch all active subscriptions
        $subscriptions = UserSubscription::where('status', 1)->get();
        
        $subscriptionEnd = UserSubscription::where('status', 0)->get();
    
        // Log the number of active subscriptions found
        $this->info("Active Subscriptions Found: " . $subscriptions->count());
        $this->info("Inactive Subscriptions Found: " . $subscriptionEnd->count());
    
        foreach ($subscriptionEnd as $subend) 
        {
            $user = User::where('id', $subend->user_id)->first();
            $SubType=SubscriptionDetail::where('id',$subend->subscription_id)->first();
            if ($user) {
                $this->info("Inactive Subscription: {$user->name} , Subscription Type: {$SubType->sub_type}");
            } else {
                $this->info("User not found for inactive subscription ID: {$subend->id}");
            }
        }
    
        foreach ($subscriptions as $subscription) {
            // Fetch the user associated with the subscription
            $user = User::where('id', $subscription->user_id)->first();
            $SubType=SubscriptionDetail::where('id',$subscription->subscription_id)->first();
            
            if ($user) {
                $this->info("Active Subscription: {$user->name} , Subscription Type: {$SubType->sub_type}");

                $now = Carbon::now();
                $endDate = Carbon::parse($subscription->end_date);
                $totalDuration = $endDate->diffInMinutes($subscription->created_at);
                $remainingTime = $endDate->diffInMinutes($now);
                $remainingPercentage = ($remainingTime / $totalDuration) * 100;

                if ($now->greaterThanOrEqualTo($endDate)) {
                    // Update the status to inactive
                    $subscription->update(['status' => 0]);
                    $this->info("Subscription for user {$user->name} has been deactivated.");
                } 
        
                // Check if only 10% or less of the time remains
                else if ($remainingPercentage <= 10) {
                    // Notify the user that the subscription is ending soon
                    $this->info("Subscription for user {$user->name} is about to expire in {$remainingTime} minutes.");
                }
            } else {
                $this->info("User not found for active subscription ID: {$subscription->id}");
            }
        }
        $this->info('Subscription check completed.');
    }
}
