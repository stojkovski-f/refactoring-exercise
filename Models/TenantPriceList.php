<?php

namespace App\Models;

class TenantPriceList extends PriceList
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices()
    {
        return $this->hasMany(Price::class, 'price_list_id', 'id');
    }
}
