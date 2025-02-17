<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Validation\ValidationException;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\TableBuilderContract;
use MoonShine\Contracts\UI\TableRowContract;
use MoonShine\Laravel\Contracts\Notifications\MoonShineNotificationContract;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\Pages\QuickPage;
use MoonShine\Laravel\Traits\Controller\InteractsWithAuth;
use MoonShine\Laravel\Traits\Controller\InteractsWithUI;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use MoonShine\Support\Enums\ToastType;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

abstract class MoonShineController extends BaseController
{
    use InteractsWithUI;
    use InteractsWithAuth;

    public function __construct(
        protected MoonShineNotificationContract $notification,
    ) {
    }

    protected function json(
        string $message = '',
        array $data = [],
        ?string $redirect = null,
        ToastType $messageType = ToastType::SUCCESS,
        int $status = Response::HTTP_OK
    ): MoonShineJsonResponse {
        return MoonShineJsonResponse::make(data: $data)
            ->setStatusCode($status)
            ->toast($message, $messageType)
            ->when(
                $redirect,
                static fn (MoonShineJsonResponse $response): MoonShineJsonResponse => $response->redirect($redirect)
            );
    }

    protected function view(string $path, array $data = []): PageContract
    {
        return QuickPage::make()->setContentView($path, $data);
    }

    /**
     * @throws Throwable
     */
    protected function reportAndResponse(bool $isAjax, Throwable $e, ?string $redirectRoute): Response
    {
        report_if(moonshine()->isProduction(), $e);

        $data = [];
        $message = moonshine()->isProduction() ? __('moonshine::ui.saved_error') : $e->getMessage();
        $type = ToastType::ERROR;
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($flash = session()->get('toast')) {
            session()->forget(['toast', '_flash.old', '_flash.new']);

            $message = $flash['message'] ?? $message;
        }

        if ($e instanceof ValidationException) {
            $status = $e->status;
            $data = [
                'errors' => $e->errors(),
            ];
        }

        if ($isAjax) {
            return $this->json(message: __($message), data: $data, messageType: $type, status: $status);
        }

        throw_if(! moonshine()->isProduction() && ! $e instanceof ValidationException, $e);

        $this->toast(__($message), $type);

        if (\is_null($redirectRoute)) {
            return back()->withInput();
        }

        return redirect($redirectRoute)->withInput();
    }

    /**
     * @throws Throwable
     */
    protected function responseWithTable(TableBuilderContract $table): TableBuilderContract|TableRowContract|string
    {
        if (! request()->filled('_key')) {
            return $table;
        }

        /** @var ModelCaster $cast */
        $cast = $table->getCast();

        $class = $table->hasCast()
            ? new ($cast->getClass())
            : null;

        if (! $class instanceof Model) {
            return $table->getRows()->first(
                static fn (TableRowContract $row): bool => $row->getKey() === request()->getScalar('_key'),
            );
        }

        $item = $class::query()->find(
            request()->getScalar('_key')
        );

        if (blank($item)) {
            return '';
        }

        return $table->items([
            $item,
        ])->getRows()->first();
    }
}
