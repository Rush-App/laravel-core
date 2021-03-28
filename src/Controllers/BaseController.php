<?php

namespace RushApp\Core\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use RushApp\Core\Enums\ModelRequestParameters;
use RushApp\Core\Models\BaseModelTrait;

abstract class BaseController extends Controller
{
    protected string $modelClassController;
    protected Model|BaseModelTrait $baseModel;
    protected ?string $storeRequestClass = null;
    protected ?string $updateRequestClass = null;
    protected array $withRelationNames = [];

    public function __construct(Request $request)
    {
        $parameterName = Str::singular(resolve($this->modelClassController)->getTable());
        $entityId = $request->route($parameterName);

        $this->baseModel = $entityId ? $this->modelClassController::find($entityId) : new $this->modelClassController;
    }

    public function index(Request $request)
    {
        //check for paginate data
        $paginate = $request->get(ModelRequestParameters::PAGINATE, false);

        $query = $this->baseModel->getQueryBuilder($request->all(), $this->withRelationNames);

        return $paginate
            ? $this->successResponse($query->paginate($paginate))
            : $this->successResponse($query->get());
    }

    public function show(Request $request)
    {
        $entityId = $request->route($this->baseModel->getTableSingularName());
        $query = $this->baseModel->getQueryBuilderOne($request->all(), $entityId, $this->withRelationNames);

        return $this->successResponse($query->first());
    }

    public function store(Request $request)
    {
        $this->validateRequest($request, $this->storeRequestClass);

        $modelAttributes = $this->baseModel->createOne($request->all());

        return $this->successResponse($modelAttributes);
    }

    public function update(Request $request)
    {
        $validationRequestClass = $this->updateRequestClass ?: $this->storeRequestClass;
        $this->validateRequest($request, $validationRequestClass);

        $entityId = $request->route($this->baseModel->getTableSingularName());
        $modelAttributes = $this->baseModel->updateOne($request->all(), $entityId, Auth::id());

        return $this->successResponse($modelAttributes);
    }

    public function destroy(Request $request)
    {
        $entityId = $request->route($this->baseModel->getTableSingularName());
        $this->baseModel->deleteOne($entityId, Auth::id());

        return $this->successResponse([
            'message' => __('response_messages.deleted')
        ]);
    }

    public function validateRequest(Request $request, ?string $requestClass)
    {
        if ($requestClass) {
            $validator = Validator::make(
                $request->all(),
                resolve($requestClass)->rules()
            );
            $validator->validate();
        }
    }

    protected function successResponse($responseData)
    {
        return response()->json($responseData, 200);
    }

    protected function responseWithError(string $error, int $code)
    {
        return response()->json(['error' => $error], $code);
    }

    public function getBaseModel(): Model
    {
        return $this->baseModel;
    }
}
