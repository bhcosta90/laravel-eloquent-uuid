<?php
namespace Costa\LaravelUuid;

use Ramsey\Uuid\Uuid as RamseyUuid;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

trait Uuids
{

    /**
     * Boot function from laravel.
     */
    protected static function bootUuids()
    {
        static::creating(function ($model) {
            if (!$model->{config('uuid.default_uuid_column')}) {
                $model->{config('uuid.default_uuid_column')} = $model->generateUuid();
            }
        });
        static::saving(function ($model) {
            $original_uuid = $model->getOriginal(config('uuid.default_uuid_column'));
            if ($original_uuid !== $model->{config('uuid.default_uuid_column')}) {
                $model->{config('uuid.default_uuid_column')} = $original_uuid;
            }
        });
    }

    /**
     * @throws \Exception
     * @return string
     */
    protected function generateUuid(): string
    {
        switch ($this->uuidVersion()) {
            case 1:
                return RamseyUuid::uuid1()->toString();
            case 4:
                return RamseyUuid::uuid4()->toString();
        }

        throw new Exception("UUID version [{$this->uuidVersion()}] not supported.");
    }

    /**
     * The UUID version to use.
     *
     * @return int
     */
    protected function uuidVersion(): int
    {
        return 4;
    }

    /**
     * Scope  by uuid 
     * @param  string  uuid of the model.
     * 
    */
    public function scopeUuid($query, $uuid, $first = true)
    {
        $match = preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuid);

        if (!is_string($uuid) || $match !== 1)
        {
            throw (new ModelNotFoundException)->setModel(get_class($this));
        }
    
        $results = $query->where(config('uuid.default_uuid_column'), $uuid);
    
        return $first ? $results->firstOrFail() : $results;
    }

    private function verify(): bool
    {
        $field = config('uuid.default_uuid_column');
        if (method_exists($this, 'getFieldUuid')) {
            $field = $this->getFieldUuid();
        }
        return (bool) $field == $this->getKeyName();
    }

    public function getIncrementing()
    {
        return $this->verify();
    }

    public function getKeyType()
    {
        return $this->verify() ? 'string' : 'integer';
    }

}
