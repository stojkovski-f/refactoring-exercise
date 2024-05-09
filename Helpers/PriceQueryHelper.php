<?php

namespace App\Helpers;

use App\Models\Price;
use App\Helpers\TenantDbHelper;

class PriceQueryHelper
{
  
  public static function buildPriceOnlyQuery($request,$lists){

    $query = Price::only()
            ->forPriceLists($lists)
            ->when($request->has('deleted'), function ($query) use ($request) {
                return $query->where('prices.deleted', $request->boolean('deleted'));
            });


    if ($request->has('code')) {
        $query->withSocialInsuranceAgencyId($request->get('code'));
        if ($price = $query->first()) {
            return $this->item($price);
        }
        return null;
    }

    if ($request->has('price_code')) {
        $query->where('price_code', $request->get('price_code'));
        if ($price = $query->first()) {
            return $this->item($price);
        }
        return null;
    }

    return $query;
  }

  public static function applyQueryParamFromRequest($request,$query){
    
    $query->leftJoin(TenantDbHelper::getMasterDbAlias().'.price_list_references', 'price_list_references.id', '=', 'prices.state_price_id');

    $query = $this->addQueryClause($request, $query, [
        'prices.description',
        'prices.price_code',
        'price_list_references.social_insurance_agency_id',
        'price_list_references.description',
    ]);

    $this->addRelevanceOrderClause($request, $query, [
        'prices.price_code',
        'prices.description',
    ]);

    return $query;
  }
}