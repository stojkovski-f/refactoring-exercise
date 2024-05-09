<?php

namespace App\Http\Controllers\API\V1;

use App\Facades\CacheService;
use App\Helpers\TenantDbHelper;
use App\Http\Controllers\API\ApiBaseController;
use App\Models\Price;
use App\Models\TenantPriceList;
use App\Transformers\PriceTransformer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class PriceController extends ApiBaseController
{
    protected $key = 'price';

    protected $transformer = PriceTransformer::class;

    public function index(Request $request)
    {
        $this->validate($request->only('query'), [
            'query' => 'nullable|string|max:50',
        ]);

        $lists = [];
        if ($request->has('list_id')) {
            $lists[] = $request->get('list_id');
        } else if ($request->has('list_ids')) {
            $lists = $request->array('list_ids');
        } else if ($request->has('reimbursement_type_id')) {
            $query = TenantPriceList::forReimbursementType($request->get('reimbursement_type_id'))
                ->used();

            if ($priceList = $query->first()) {
                $lists[] = $priceList;
            }
        }

        if (count($lists)) {
            foreach ($lists as $key => $list) {
                $list = is_numeric($list) ? TenantPriceList::find($list) : $list;
                $this->authorize('view', [$list, TenantPriceList::class]);
                $lists[$key] = $list->id;
            }
        } else {
            $this->authorize('view', [null, TenantPriceList::class]);
        }

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

            return $this->emptyItem();
        }

        if ($request->has('price_code')) {
            $query->where('price_code', $request->get('price_code'));
            if ($price = $query->first()) {
                return $this->item($price);
            }

            return $this->emptyItem();
        }

        if ($request->has('query')) {
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
        }

        if ($request->has('sort_by')) {
            $query->orderBy('prices.'.$request->get('sort_by'), $request->get('order') ?: 'asc');
        }

		return $this->paginator($query->paginate($request->get('per_page') ?: 30));
    }
}