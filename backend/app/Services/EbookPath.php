<?php

namespace App\Services;

class EbookPath
{
    public function resolve(): ?string
    {
        foreach ($this->candidates() as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function candidates(): array
    {
        $configured = trim((string) config('services.asaas.ebook_path'));
        $paths = [];

        if ($configured !== '') {
            $paths[] = $configured;
            if (! str_starts_with($configured, '/')) {
                $paths[] = base_path($configured);
                $paths[] = storage_path(str_starts_with($configured, 'storage/')
                    ? substr($configured, strlen('storage/'))
                    : $configured);
            }
        }

        $paths[] = storage_path('app/private/ebook.pdf');
        $paths[] = storage_path('app/ebook.pdf');
        $paths[] = base_path('../Como_Emagrecer_Rapido_para_o_Verao.pdf');

        return array_values(array_unique(array_filter($paths)));
    }
}
