<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateGroupChargeStatus extends Command
{
    protected $signature = 'app:update-status';
    protected $description = 'Update charge_status based on numeric status in group_charges table';

    public function handle()
    {
        $charges = DB::table('group_charges')->whereDate('created_at', '<=', '2025-04-19')->get();

        foreach ($charges as $charge) {
            $statusText = match ($charge->status) {
                0 => 'pending',
                1 => 'done',
                2 => 'canceled',
                default => null,
            };

            if ($statusText !== null) {
                DB::table('group_charges')
                    ->where('id', $charge->id)
                    ->update(['charge_status' => $statusText]);

                $this->info("Updated charge ID {$charge->id} to '{$statusText}'");
            } else {
                $this->warn("Charge ID {$charge->id} has an unknown status: {$charge->status}");
            }
        }

        $this->info('Charge statuses updated successfully.');
    }
}
