<?php

namespace App\Models;

use App\Traits\ScopeOnlyTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PriceList
 * @version March 3, 2017, 10:03 am UTC
 */
class PriceList extends Model
{
    use ScopeOnlyTrait;

    const TYPE_STATE = 1;

    const TYPE_COUNTY_COUNCIL = 2;

    const TYPE_CLINIC = 3;

    public $table = 'price_lists';

    public $fillable = [
        'reimbursement_type_id',
        'name',
        'used',
        'deleted',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'reimbursement_type_id' => 'integer',
        'name' => 'string',
        'used' => 'boolean',
        'deleted' => 'boolean',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'reimbursement_type_id' => 'required|integer|exists:reimbursement_types,id',
        'name' => 'required',
        'used' => 'boolean',
        'deleted' => 'boolean',
    ];

    public function scopeForReimbursementType($query, int $typeId)
    {
        return $query->where($this->table . '.reimbursement_type_id', $typeId);
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where( $this->table . '.clinic_id', $clinicId);
    }

    public function scopeUsed($query, bool $isUsed = true)
    {
        return $query->where($this->table . '.used', $isUsed);
    }

    public function scopeOptions($query)
    {
        return $query->orderBy('id', 'desc')->get()->pluck('name', 'id');
    }

    public function getTypeAttribute()
    {
        if ($this->country_id || $this->state_price_list_id) {
            return self::TYPE_STATE;
        }

        if ($this->county_council_id) {
            return self::TYPE_COUNTY_COUNCIL;
        }

        if ($this->clinic_id) {
            return self::TYPE_CLINIC;
        }

        return 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country_uses()
    {
        return $this->country();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reimbursement_type()
    {
        return $this->belongsTo(ReimbursementType::class, 'reimbursement_type_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices()
    {
        return $this->hasMany(PriceListReference::class, 'price_list_id', 'id');
    }
}
