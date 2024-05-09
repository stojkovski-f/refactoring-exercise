<?php

namespace App\Http\Controllers\API\V1;

use App\Facades\CacheService;
use App\Http\Controllers\API\ApiBaseController;
use App\Models\Price;
use App\Models\TenantPriceList;
use App\Transformers\PriceTransformer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Helpers\PriceQueryHelper;


class PriceController extends ApiBaseController
{
    protected $key = 'price';

    protected $transformer = PriceTransformer::class;

    public function index(Request $request)
    {
        $this->validate($request->only('query'), [
            'query' => 'nullable|string|max:50',
        ]);

        $lists = $this->buildListsArrayFromRequestParams($request);
        $authIssueResponse = $this->authorizationCheckForLists($lists);
        if ($authIssueResponse) {
            return $authIssueResponse;
        }

        $query  = PriceQueryHelper::buildPriceOnlyQuery($request,$lists);
        if(empty($query)) return $this->emptyItem();

        if ($request->has('query')) {
           $query = PriceQueryHelper::applyQueryParamFromRequest($request,$query);
        }

        if ($request->has('sort_by')) {
            $query->orderBy('prices.'.$request->get('sort_by'), $request->get('order') ?: 'asc');
        }

		return $this->paginator($query->paginate($request->get('per_page') ?: 30));
    }

    /**
     * Builds an array of List IDs from request parameters.
     *
     * @param  \Illuminate\\Http\Request  $request
     * @return array
     */
    private function buildListsArrayFromRequestParams(Request $request){

        $lists = [];

        if ($request->has('list_id')) {
            $lists[] = $request->get('list_id');
            return $lists;
        }

        if ($request->has('list_ids')) {
            $lists = [];
            foreach ( $request->array('list_ids') as $key => $listId ){
                $lists[] = $listId;
            }
            return $lists;
        }

        if ($request->has('reimbursement_type_id')) {
            $query = TenantPriceList::forReimbursementType($request->get('reimbursement_type_id'))
                ->used();
            if ($priceList = $query->first()) {
                $lists[] = $priceList->id;
            }
        }
        return $lists;
    }

    /**
     * Checks for authorization on the provided lists array agianst TenantPriceList objects.
     *
     * @param  array  $lists
     * @return void | Illuminate\Http\JsonResponse
     */
    private function authorizationCheckForLists(Array $lists){
        try {
            if (count($lists)) {
                foreach ($lists as $key => $list) {
                    $this->authorize('view', [$list, TenantPriceList::class]);
                }
            } else {
                $this->authorize('view', [null, TenantPriceList::class]);
            }
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Illuminate\Support\Facades\Log::warning('Possible security concern.',$e);
            return response()->json(['message' => $this->authorize('getUnauthorizedMessage')], 403);
        }
    }
}