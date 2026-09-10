<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Payments\CreatePaymentAction;
use App\Actions\Payments\DeletePaymentAction;
use App\Actions\Payments\UpdatePaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Http\Requests\Payments\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\ConstructionObject;
use App\Models\Payment;
use App\Models\Workspace;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly CreatePaymentAction $createPayment,
        private readonly UpdatePaymentAction $updatePayment,
        private readonly DeletePaymentAction $deletePayment,
    ) {}

    public function index(Workspace $workspace, ConstructionObject $object): JsonResponse
    {
        $this->authorize('view', $workspace);

        $payments = $this->payments->listForObject($object);

        return ApiResponse::success(
            PaymentResource::collection($payments)->resolve(),
            meta: ['total' => $payments->count()],
        );
    }

    public function store(
        StorePaymentRequest $request,
        Workspace $workspace,
        ConstructionObject $object,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $payment = $this->createPayment->handle($object, $request->toData());

        return ApiResponse::created(new PaymentResource($payment), __('messages.payments.created'));
    }

    public function update(
        UpdatePaymentRequest $request,
        Workspace $workspace,
        ConstructionObject $object,
        Payment $payment,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $payment = $this->updatePayment->handle($payment, $request->toData());

        return ApiResponse::success(new PaymentResource($payment), __('messages.payments.updated'));
    }

    public function destroy(
        Workspace $workspace,
        ConstructionObject $object,
        Payment $payment,
    ): JsonResponse {
        $this->authorize('view', $workspace);

        $this->deletePayment->handle($payment);

        return ApiResponse::success(null, __('messages.payments.deleted'));
    }
}
