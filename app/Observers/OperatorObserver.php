<?php

namespace App\Observers;

use App\Models\Operator;

class OperatorObserver
{
    /**
     * Handle the Operator "created" event.
     */
    public function created(Operator $operator): void
    {
        //
    }

    /**
     * Handle the Operator "updated" event.
     */
    public function updated(Operator $operator): void
    {
        event(new \App\Events\OperatorUpdated($operator));
    }

    /**
     * Handle the Operator "deleted" event.
     */
    public function deleted(Operator $operator): void
    {
        //
    }

    /**
     * Handle the Operator "restored" event.
     */
    public function restored(Operator $operator): void
    {
        //
    }

    /**
     * Handle the Operator "force deleted" event.
     */
    public function forceDeleted(Operator $operator): void
    {
        //
    }
}
