<?php

declare(strict_types=1);

namespace MoonShine\Contracts\Core\DependencyInjection;

use SplFileInfo;

interface StorageContract
{
    public function store(string $path, mixed $file = null, array $options = []): false|string;

    public function storeAs(string $path, mixed $file, $name = null, array $options = []): false|string;

    public function delete(string|array $paths): bool;

    public function makeDirectory(string $path): bool;

    public function deleteDirectory(string $directory): bool;

    public function exists(string $path): bool;

    public function getPath(string $path): string;

    public function getUrl(string $path): string;

    /**
     * @return list<SplFileInfo>
     */
    public function getFiles(string $directory, bool $recursive = false): array;

    /**
     * @return string[]
     */
    public function getDirectories(string $directory, bool $recursive = false): array;
}
