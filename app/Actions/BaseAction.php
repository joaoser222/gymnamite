<?php

namespace App\Actions;

use App\Actions\Contracts\ActionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseAction implements ActionInterface
{
    protected string $ability = '';

    protected string $modelClass = '';

    public function execute(mixed $input): mixed
    {
        $actionClass = static::class;
        $inputSummary = $this->summarizeInput($input);

        Log::info("Action started: {$actionClass}", ['input' => $inputSummary]);

        if (! $this->authorize($input)) {
            Log::warning("Action unauthorized: {$actionClass}", ['input' => $inputSummary]);
            $this->handleUnauthorized();
        }

        try {
            $result = DB::transaction(function () use ($input) {
                return $this->handle($input);
            });

            $this->dispatchEvents($result, $input);

            Log::info("Action succeeded: {$actionClass}", ['result' => $this->summarizeResult($result)]);

            return $this->wrapResult($result);
        } catch (Throwable $e) {
            Log::error("Action failed: {$actionClass}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $inputSummary,
            ]);

            throw $e;
        }
    }

    public function authorize(mixed $input): bool
    {
        if ($this->ability === '' || $this->modelClass === '') {
            return true;
        }

        $model = $this->resolveModelForAuthorization($input);

        return Gate::allows($this->ability, $model ?? $this->modelClass);
    }

    abstract protected function handle(mixed $input): mixed;

    protected function resolveModelForAuthorization(mixed $input): ?Model
    {
        return null;
    }

    protected function dispatchEvents(mixed $result, mixed $input): void
    {
        // Override in concrete actions to dispatch domain events
    }

    protected function wrapResult(mixed $result): mixed
    {
        return $result;
    }

    protected function handleUnauthorized(): never
    {
        abort(403, 'Unauthorized action.');
    }

    protected function summarizeInput(mixed $input): array
    {
        if (is_array($input)) {
            return $input;
        }

        if (is_object($input) && method_exists($input, 'toArray')) {
            return $input->toArray();
        }

        return ['raw' => $input];
    }

    protected function summarizeResult(mixed $result): array
    {
        if ($result instanceof Model) {
            return ['model' => get_class($result), 'id' => $result->getKey()];
        }

        if (is_array($result) || $result instanceof \Countable) {
            return ['count' => count($result)];
        }

        return ['type' => gettype($result)];
    }
}
