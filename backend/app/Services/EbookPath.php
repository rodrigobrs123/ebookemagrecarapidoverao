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

        return array_values(array_unique(array_filter([
            $configured !== '' ? $configured : null,
            storage_path('app/private/ebook.pdf'),
            storage_path('app/ebook.pdf'),
            base_path('../Como_Emagrecer_Rapido_para_o_Verao.pdf'),
            '/var/ebook/ebook.pdf',
        ])));
    }
}
