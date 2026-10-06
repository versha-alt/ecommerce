from pathlib import Path
p=Path('apps/backend/tests/Feature/StorefrontTest.php');s=p.read_text();i=s.rfind('}');s=s[:i]+'''    public function test_public_catalog_hides_internal_notes_and_gateway_credentials(): void
    {
        $fixtures = $this->fixture();
        $fixtures[2]->data = array_merge($fixtures[2]->data, ['credentials' => 'private-secret', 'notes' => 'Internal']);
        $fixtures[2]->save();
        $response = $this->getJson('/api/v1/store/catalog')->assertOk();
        $response->assertJsonMissingPath('payment_methods.0.credentials')->assertJsonMissingPath('payment_methods.0.notes');
        $this->assertSame(47, count($response->json('locations.countries.KE.counties')));
    }

    public function test_return_requests_are_owned_idempotent_and_visible_without_internal_notes(): void
    {
        $this->fixture();
        $token = $this->member();
        $customer = Record::where('resource', 'customers')->firstOrFail();
        $order = Record::create(['resource' => 'orders', 'data' => ['customer_id' => $customer->id, 'status' => 'Delivered', 'payment_status' => 'Paid', 'total' => 1000, 'refunded_amount' => 0]]);
        $body = ['order_id' => $order->id, 'reason' => 'Damaged item', 'refund_amount' => 100];
        $this->withToken($token)->withHeader('Idempotency-Key', 'return-one')->postJson('/api/v1/store/returns', $body)->assertOk()->assertJsonPath('return.status', 'Requested');
        $this->postJson('/api/v1/store/returns', $body)->assertOk();
        $this->assertSame(1, Record::where('resource', 'returns')->count());
        $this->getJson('/api/v1/store/account')->assertOk()->assertJsonCount(1, 'returns')->assertJsonMissingPath('returns.0.notes');
        $other = $this->member('other@example.com');
        $this->withToken($other)->withHeader('Idempotency-Key', 'other-return')->postJson('/api/v1/store/returns', $body)->assertNotFound();
    }
'''+s[i:];p.write_text(s)
