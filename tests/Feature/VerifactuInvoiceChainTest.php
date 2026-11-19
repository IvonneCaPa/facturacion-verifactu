<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Emisor;
use App\Models\Factura;
use App\Models\LineaFactura;
use App\Models\SerieFacturacion;
use App\Models\User;
use App\Services\InvoiceIssuerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class VerifactuInvoiceChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_chains_sha256_hashes_and_enforces_immutability_across_sequential_invoices(): void
    {
        // 1. Crear usuario, perfil de autónomo (Emisor), serie y cliente empresa
        $user = User::factory()->create();

        $emisor = Emisor::create([
            'user_id' => $user->id,
            'nif' => '12345678Z',
            'razon_social' => 'Ivonne Desarrollo Web SLU',
            'nombre_comercial' => 'CreateDevs',
            'direccion' => 'Calle Mayor 10',
            'codigo_postal' => '08001',
            'municipio' => 'Barcelona',
            'provincia' => 'Barcelona',
            'email' => 'hola@createdevs.es',
            'irpf_por_defecto' => 15.00,
            'verifactu_activo' => true,
        ]);

        $serie = SerieFacturacion::create([
            'emisor_id' => $emisor->id,
            'prefijo' => 'F2026-',
            'ultimo_numero' => 0,
            'tipo' => 'ordinaria',
            'activa' => true,
        ]);

        $clienteEmpresa = Cliente::create([
            'emisor_id' => $emisor->id,
            'nif' => 'B98765432',
            'nombre_razon_social' => 'Cliente Empresa SA',
            'direccion' => 'Avinguda Diagonal 400',
            'codigo_postal' => '08037',
            'municipio' => 'Barcelona',
            'provincia' => 'Barcelona',
            'pais' => 'ES',
            'es_profesional_o_empresa' => true,
        ]);

        /** @var InvoiceIssuerService $issuer */
        $issuer = app(InvoiceIssuerService::class);

        // 2. Crear y emitir la PRIMERA factura (1.000 € base + 21% IVA - 15% IRPF = 1.060 €)
        $borrador1 = Factura::create([
            'emisor_id' => $emisor->id,
            'cliente_id' => $clienteEmpresa->id,
            'serie_id' => $serie->id,
            'numero_completo' => 'BORRADOR-1',
            'tipo_factura' => 'F1',
            'fecha_expedicion' => '2026-11-19',
            'estado' => 'borrador',
        ]);

        LineaFactura::create([
            'factura_id' => $borrador1->id,
            'concepto' => 'Desarrollo Web Laravel + Nuxt',
            'cantidad' => 1,
            'precio_unitario' => 1000.00,
            'descuento_porcentaje' => 0,
            'tipo_iva' => 21.00,
            'aplica_irpf' => true,
            'subtotal_base' => 1000.00,
        ]);

        $factura1 = $issuer->issue($borrador1, entornoPruebas: true);

        // Comprobaciones de la primera factura
        $this->assertSame('emitida', $factura1->estado);
        $this->assertSame('F2026-0001', $factura1->numero_completo);
        $this->assertNull($factura1->hash_anterior);
        $this->assertNotNull($factura1->hash_actual);
        $this->assertSame(64, strlen($factura1->hash_actual));
        $this->assertEquals(1060.00, (float) $factura1->total_factura);
        $this->assertStringContainsString('https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR', (string) $factura1->qr_url);

        // 3. Crear y emitir la SEGUNDA factura (500 € base + 21% IVA - 15% IRPF = 530 €)
        $borrador2 = Factura::create([
            'emisor_id' => $emisor->id,
            'cliente_id' => $clienteEmpresa->id,
            'serie_id' => $serie->id,
            'numero_completo' => 'BORRADOR-2',
            'tipo_factura' => 'F1',
            'fecha_expedicion' => '2026-11-19',
            'estado' => 'borrador',
        ]);

        LineaFactura::create([
            'factura_id' => $borrador2->id,
            'concepto' => 'Mantenimiento técnico mensual',
            'cantidad' => 1,
            'precio_unitario' => 500.00,
            'descuento_porcentaje' => 0,
            'tipo_iva' => 21.00,
            'aplica_irpf' => true,
            'subtotal_base' => 500.00,
        ]);

        $factura2 = $issuer->issue($borrador2, entornoPruebas: true);

        // Comprobación crítica del encadenamiento SHA-256 Veri*Factu
        $this->assertSame('F2026-0002', $factura2->numero_completo);
        $this->assertSame($factura1->hash_actual, $factura2->hash_anterior);
        $this->assertNotSame($factura1->hash_actual, $factura2->hash_actual);
        $this->assertEquals(530.00, (float) $factura2->total_factura);

        // 4. Verificar que se grabaron los 2 eventos de auditoría en registro_eventos
        $this->assertDatabaseCount('registro_eventos', 2);

        // 5. Verificar que la ley de inalterabilidad bloquea cualquier edición o borrado posterior
        $this->expectException(RuntimeException::class);
        $factura1->update(['base_imponible' => 999.00]);
    }
}