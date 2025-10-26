<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

abstract class BaseModel extends Model
{
    public $timestamps = true;
    protected $guarded = [];

    protected static $statusField = 'estatus';
    protected static $primaryKeyField = 'id';
    protected static $uuidField = null;
    protected static $searchableField = 'nombre';

    // ===== GETTERS =====
    
    /**
     * Get the name of the status field.
     * @return string
     * @example
     *   $field = static::getStatusField();
     */
    protected static function getStatusField(): string
    {
        return static::$statusField;
    }

    /**
     * Get the name of the primary key field.
     * @return string
     * @example
     *   $pk = static::getPrimaryKeyName();
     */
    protected static function getPrimaryKeyName(): string
    {
        return static::$primaryKeyField;
    }

    /**
     * Get the name of the UUID field if it exists.
     * @return string|null
     * @example
     *   $uuidField = static::getUuidField();
     */
    protected static function getUuidField(): ?string
    {
        return static::$uuidField;
    }

    /**
     * Get the name of the searchable field.
     * @return string
     * @example
     *   $searchField = static::getSearchableField();
     */
    protected static function getSearchableField(): string
    {
        return static::$searchableField;
    }

    // ===== SCOPES =====

    /**
     * ✅ CORREGIDO: Debe ser public, no protected static
     * Scope para registros activos (estatus = 1)
     */
    /**
     * Scope to filter active records (status = 1).
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     * @example
     *   Model::active()->get();
     */
    public function scopeActive($query)
    {
        return $query->where(static::getStatusField(), 1);
    }

    /**
     * ✅ NUEVO: Scope para registros inactivos
     */
    /**
     * Scope to filter inactive records (status = 0).
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     * @example
     *   Model::inactive()->get();
     */
    public function scopeInactive($query)
    {
        return $query->where(static::getStatusField(), 0);
    }

    /**
     * ✅ NUEVO: Scope para buscar por palabra clave
     */
    /**
     * Scope to search records by keyword.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $keyword
     * @return \Illuminate\Database\Eloquent\Builder
     * @example
     *   Model::search('John')->get();
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(static::getSearchableField(), 'LIKE', "%{$keyword}%");
    }

    /**
     * ✅ NUEVO: Scope para buscar por UUID
     */
    /**
     * Scope to search records by UUID.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $uuid
     * @return \Illuminate\Database\Eloquent\Builder
     * @example
     *   Model::byUuid($uuid)->first();
     */
    public function scopeByUuid($query, $uuid)
    {
        if (static::getUuidField()) {
            return $query->where(static::getUuidField(), $uuid);
        }
        return $query;
    }

    /**
     * ✅ NUEVO: Scope para ordenar registros
     */
    /**
     * Scope to order records by column and direction.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $column
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     * @example
     *   Model::ordered('name', 'desc')->get();
     */
    public function scopeOrdered($query, $column = null, $direction = 'asc')
    {
        $column = $column ?? static::getSearchableField();
        return $query->orderBy($column, $direction);
    }

    /**
     * ✅ NUEVO: Scope para registros recientes
     */
    /**
     * Scope to filter recent records by days.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     * @example
     *   Model::recent(7)->get(); // Records from last 7 days
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * ✅ NUEVO: Scope para búsqueda avanzada con múltiples campos
     */
    /**
     * Scope for advanced search with multiple fields.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     * @example
     *   Model::advancedFilter(['name' => 'John', 'email' => 'example@domain.com'])->get();
     */
    public function scopeAdvancedFilter($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                $query->where($field, 'LIKE', "%{$value}%");
            }
        }
        return $query;
    }

    // ===== MÉTODOS EXISTENTES (mantenerlos) =====

    /**
     * Create a new record with the provided data.
     * @param array $data
     * @return static
     * @example
     *   Model::createRecord(['name' => 'John']);
     */
    public static function createRecord(array $data)
    {
        return static::create($data);
    }

    // ✅ REFACTORIZADO: Ahora usa el scope active()
    /**
     * Get all active records.
     * @return \Illuminate\Database\Eloquent\Collection
     * @example
     *   Model::getActiveRecords();
     */
    public static function getActiveRecords()
    {
        return static::active()->get();
    }

    // ✅ REFACTORIZADO: Usa scope
    /**
     * Find an active record by its ID.
     * @param mixed $id
     * @return static|null
     * @example
     *   Model::findActiveById(1);
     */
    public static function findActiveById($id)
    {
        return static::active()
            ->where(static::getPrimaryKeyName(), $id)
            ->first();
    }

    // ✅ REFACTORIZADO: Usa scopes
    /**
     * Search active records by keyword.
     * @param string $keyword
     * @return \Illuminate\Database\Eloquent\Collection
     * @example
     *   Model::searchByKeyword('John');
     */
    public static function searchByKeyword($keyword)
    {
        return static::active()
            ->search($keyword)
            ->get();
    }

    /**
     * Update a record by its ID with the provided data.
     * @param mixed $id
     * @param array $data
     * @return int
     * @example
     *   Model::updateRecord(1, ['name' => 'Jane']);
     */
    public static function updateRecord($id, array $data)
    {
        return static::where(static::getPrimaryKeyName(), $id)
            ->update($data);
    }

    /**
     * Perform a soft delete of a record by its ID.
     * @param mixed $id
     * @return int
     * @example
     *   Model::softDelete(1);
     */
    public static function softDelete($id)
    {
        return static::where(static::getPrimaryKeyName(), $id)
            ->update([static::getStatusField() => 0]);
    }

    /**
     * Restore a previously soft-deleted record by its ID.
     * @param mixed $id
     * @return int
     * @example
     *   Model::restoreRecord(1);
     */
    public static function restoreRecord($id)
    {
        return static::where(static::getPrimaryKeyName(), $id)
            ->update([static::getStatusField() => 1]);
    }

    /**
     * Get all records from the table.
     * @return \Illuminate\Database\Eloquent\Collection
     * @example
     *   Model::getAllRecords();
     */
    public static function getAllRecords()
    {
        return static::all();
    }

    /**
     * Get paginated records.
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @example
     *   Model::getPaginatedRecords(15);
     */
    public static function getPaginatedRecords($perPage = 10)
    {
        return static::paginate($perPage);
    }

    // ✅ REFACTORIZADO: Usa scopes
    /**
     * Find an active record by UUID.
     * @param string $uuid
     * @return static|null
     * @example
     *   Model::findByUuid($uuid);
     */
    public static function findByUuid(string $uuid)
    {
        return static::active()
            ->byUuid($uuid)
            ->first();
    }

    /**
     * Create multiple records in a transaction.
     * @param array $records
     * @return \Illuminate\Support\Collection
     * @example
     *   Model::bulkCreate([
     *     ['name' => 'John'],
     *     ['name' => 'Jane']
     *   ]);
     */
    public static function bulkCreate(array $records)
    {
        $instances = [];
        DB::transaction(function () use ($records, &$instances) {
            foreach ($records as $data) {
                $instances[] = static::create($data);
            }
        });
        return collect($instances);
    }

    /**
     * Update multiple records by conditions.
     * @param array $conditions
     * @param array $data
     * @return int
     * @example
     *   Model::bulkUpdate(['status' => 1], ['status' => 0]);
     */
    public static function bulkUpdate(array $conditions, array $data)
    {
        return static::where($conditions)->update($data);
    }

    /**
     * Perform soft delete on multiple records by conditions.
     * @param array $conditions
     * @return int
     * @example
     *   Model::bulkSoftDelete(['status' => 1]);
     */
    public static function bulkSoftDelete(array $conditions)
    {
        return static::where($conditions)
            ->update([static::getStatusField() => 0]);
    }

    // ✅ REFACTORIZADO: Usa scopes
    /**
     * Get active records ordered by column and direction.
     * @param string $column
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Collection
     * @example
     *   Model::getOrderedRecords('name', 'desc');
     */
    public static function getOrderedRecords(string $column, string $direction = 'asc')
    {
        return static::active()
            ->ordered($column, $direction)
            ->get();
    }

    /**
     * Check if a record exists by its ID.
     * @param mixed $id
     * @return bool
     * @example
     *   Model::existsById(1);
     */
    public static function existsById($id): bool
    {
        return static::where(static::getPrimaryKeyName(), $id)->exists();
    }

    // ✅ REFACTORIZADO: Usa scope
    /**
     * Perform advanced search in active records.
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Collection
     * @example
     *   Model::advancedSearch(['name' => 'John']);
     */
    public static function advancedSearch(array $fields)
    {
        return static::active()
            ->advancedFilter($fields)
            ->get();
    }

    /**
     * Get related records through an Eloquent relationship.
     * @param string $relationship
     * @param mixed $id
     * @return \Illuminate\Support\Collection
     * @example
     *   Model::getRelatedRecords('posts', 1);
     */
    public static function getRelatedRecords(string $relationship, $id)
    {
        $record = static::findActiveById($id);
        return $record ? $record->$relationship()->get() : collect([]);
    }

    /**
     * Validate received data against the model's fillable fields.
     * @param array $data
     * @return array
     * @example
     *   $validData = Model::validateData($data);
     */
    public static function validateData(array $data): array
    {
        $fillable = (new static)->getFillable();
        return array_intersect_key($data, array_flip($fillable));
    }
}