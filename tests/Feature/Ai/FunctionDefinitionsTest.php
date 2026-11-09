<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\FunctionDefinitions;
use Tests\TestCase;

class FunctionDefinitionsTest extends TestCase
{
    public function test_defines_all_eleven_functions(): void
    {
        $this->assertSame([
            'tambah_transaksi', 'ubah_transaksi', 'hapus_transaksi', 'cari_transaksi',
            'ringkasan', 'tampilkan_grafik', 'atur_budget', 'cek_budget',
            'atur_transaksi_rutin', 'lihat_transaksi_rutin', 'nonaktifkan_transaksi_rutin',
        ], FunctionDefinitions::names());
    }

    public function test_each_declaration_has_name_description_and_object_input_schema(): void
    {
        foreach (FunctionDefinitions::all() as $declaration) {
            $this->assertArrayHasKey('name', $declaration);
            $this->assertArrayHasKey('description', $declaration);
            $this->assertArrayHasKey('input_schema', $declaration);
            $this->assertSame('object', $declaration['input_schema']['type']);
        }
    }

    public function test_parameter_types_use_lowercase_schema_values(): void
    {
        $allowed = ['object', 'string', 'integer', 'array', 'number', 'boolean'];

        $walk = function (array $schema) use (&$walk, $allowed): void {
            $this->assertContains($schema['type'], $allowed);

            foreach ($schema['properties'] ?? [] as $property) {
                $walk($property);
            }

            if (isset($schema['items'])) {
                $walk($schema['items']);
            }
        };

        foreach (FunctionDefinitions::all() as $declaration) {
            $walk($declaration['input_schema']);
        }
    }
}
