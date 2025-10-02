<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use MongoDB\Laravel\Eloquent\Builder as MongoBuilder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use MongoDB\Laravel\Eloquent\Model as MongoDBModel;

class Activity extends MongoDBModel implements ActivityContract
{
    protected $connection = 'mongodb';
    protected $collection = 'activity_log';

    protected $fillable = [
        'log_name', 'description', 'subject_type', 'subject_id',
        'causer_type', 'causer_id', 'properties', 'batch_uuid',
        'event', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Required relationship methods
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // Required scope methods with EloquentBuilder type hints
    public function scopeInLog(EloquentBuilder $query, ...$logNames): EloquentBuilder
    {
        /** @var MongoBuilder $query */
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('log_name', $logNames);
    }

    public function scopeForEvent(EloquentBuilder $query, string $event): EloquentBuilder
    {
        /** @var MongoBuilder $query */
        return $query->where('event', $event);
    }

    public function scopeForSubject(EloquentBuilder $query, Model $subject): EloquentBuilder
    {
        /** @var MongoBuilder $query */
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    public function scopeCausedBy(EloquentBuilder $query, Model $causer): EloquentBuilder
    {
        /** @var MongoBuilder $query */
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    // Interface methods
    public function changes(): Collection
    {
        return new Collection([
            'attributes' => $this->properties['attributes'] ?? [],
            'old' => $this->properties['old'] ?? [],
        ]);
    }

    public function getExtraProperty(string $propertyName, mixed $defaultValue = null): mixed
    {
        return $this->properties[$propertyName] ?? $defaultValue;
    }

    public function getCauser($field = null)
    {
        if (!$this->causer_type || !$this->causer_id) {
            return null;
        }

        if ($field && $this->causer) {
            return $this->causer->$field ?? null;
        }

        return $this->causer;
    }

    public function getSubject($field = null)
    {
        if (!$this->subject_type || !$this->subject_id) {
            return null;
        }

        if ($field && $this->subject) {
            return $this->subject->$field ?? null;
        }

        return $this->subject;
    }
}
