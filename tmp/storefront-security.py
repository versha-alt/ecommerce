from pathlib import Path
p=Path('apps/backend/app/Services/Commerce.php');s=p.read_text(encoding='utf-8');s=s.replace("app(CustomerPayments::class)->begin($order, $this->active('payment-methods', $input['payment_method_id']));","app(CustomerPayments::class)->begin($order, $this->active('payment-methods', $input['payment_method_id']));\n                $order->refresh();");p.write_text(s,encoding='utf-8')
p=Path('apps/backend/app/Http/Controllers/StorefrontController.php');s=p.read_text(encoding='utf-8-sig');needle='    public function register(Request $request): array';helper='''    private function orderData(array $order): array
    {
        $public = array_intersect_key($order, array_flip(['id', 'version', 'created_at', 'reference', 'customer_name', 'customer_id', 'delivery_address', 'tracking_reference', 'lines', 'subtotal', 'discount', 'tax_total', 'tax_inclusive', 'shipping_total', 'total', 'status', 'payment_status', 'fulfilment_status', 'status_history']));
        $public['status_history'] = array_map(fn (array $entry): array => array_intersect_key($entry, array_flip(['to', 'at'])), $public['status_history'] ?? []);
        return $public;
    }

''';s=s.replace(needle,helper+needle);s=s.replace("map(fn ($row) => $row->row())->all(), 'returns'", "map(fn ($row) => $this->orderData($row->row()))->all(), 'returns'");s=s.replace("return ['order' => $order];","return ['order' => $this->orderData($order)];");s=s.replace("return ['order' => $order->row()];","return ['order' => $this->orderData($order->row())];");s=s.replace("return ['order' => app(Commerce::class)->orderAction($id, $input + ['action' => 'cancel'], $request->header('Idempotency-Key', ''), $actor)];","return ['order' => $this->orderData(app(Commerce::class)->orderAction($id, $input + ['action' => 'cancel'], $request->header('Idempotency-Key', ''), $actor))];");s=s.replace("$customer->data['name'], 'email' => $customer->data['email']", "$customer->data['name'], 'email' => $customer->data['email']")
s=s.replace("        $input = $request->validate(['version'", "        abort_unless($request->header('Idempotency-Key') && strlen($request->header('Idempotency-Key')) <= 100, 422, 'A valid cancellation idempotency key is required.');\n        $input = $request->validate(['version'")
p.write_text(s,encoding='utf-8')
