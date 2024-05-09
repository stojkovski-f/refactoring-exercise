<?php

namespace App\Policies;

use App\Auth\UnauthorizedMessage;
use App\Models\PriceList;
use App\Models\TenantPriceList;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantPriceListPolicy implements UnauthorizedMessage
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the priceList.
     *
     * @param  \App\User  $user
     * @param  \App\Models\TenantPriceList  $priceList
     * @return mixed
     */
    public function view(User $user, TenantPriceList $priceList)
    {
        switch ($priceList->type) {
            case PriceList::TYPE_COUNTY_COUNCIL:
                return false;
                break;
            case PriceList::TYPE_CLINIC:
                return config('tenant.clinic')->id == $priceList->clinic_id;
                break;
        }

        return true;
    }

    /**
     * Determine whether the user can create priceLists.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the priceList.
     *
     * @param  \App\User  $user
     * @param  \App\Models\TenantPriceList  $priceList
     * @return mixed
     */
    public function update(User $user, TenantPriceList $priceList)
    {
        //
    }

    /**
     * Determine whether the user can delete the priceList.
     *
     * @param  \App\User  $user
     * @param  \App\Models\TenantPriceList  $priceList
     * @return mixed
     */
    public function delete(User $user, TenantPriceList $priceList)
    {
        //
    }

    public function getUnauthorizedMessage()
    {
        return 'You are unauthorized for requested price list.';
    }
}
