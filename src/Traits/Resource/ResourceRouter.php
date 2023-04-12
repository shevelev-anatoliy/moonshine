<?php

declare(strict_types=1);

namespace MoonShine\Traits\Resource;

use Illuminate\Support\Facades\Cache;
use MoonShine\MoonShineRouter;

trait ResourceRouter
{
    /**
     * Take route of resource from alias or composite from resource and table names.
     * @return string
     */
    public function routeNameAlias(): string
    {
        return (string)($this->routAlias ?
            str($this->routAlias)
                ->lcfirst()
                ->squish() :
            str(static::class)
                ->classBasename()
                ->replace(['Resource'], '')
                ->plural()
                ->lcfirst());
    }

    public function routeParam(): string
    {
        return (string) str($this->routeNameAlias())->singular();
    }

    public function routeName(?string $action = null): string
    {
        return (string) str('moonshine')
            ->append('.')
            ->append($this->routeNameAlias())
            ->when($action, fn ($str) => $str->append('.')->append($action));
    }

    public function currentRoute(array $query = []): string
    {
        return request()->url()
            .($query ? '?'.http_build_query($query) : '');
    }

    public function getRouteAfterSave(): string
    {
        return match ($this->stayAfterSave) {
            'show', 'edit' => $this->route(
                $this->stayAfterSave,
                $this?->item->getKey()
            ),
            default => $this->route('index')
        };
    }

    public function route(string $action = null, int|string $id = null, array $query = []): string
    {
        if (empty($query) && Cache::has($this->queryCacheKey())) {
            parse_str(Cache::get($this->queryCacheKey(), ''), $query);
        }

        unset($query['change-moonshine-locale']);

        if ($this->isRelatable()) {
            $query['relatable_mode'] = 1;
        }

        return MoonShineRouter::to(
            $this->routeName($action),
            $id ? array_merge([$this->routeParam() => $id], $query) : $query
        );
    }
}
