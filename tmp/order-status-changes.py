from pathlib import Path
p=Path('apps/backend/app/Services/CustomerPayments.php');s=p.read_text();s=s.replace("return Record::create(['id'", "$payment = Record::create(['id'",1)
needle="'Customer checkout']]]]);\n    }"
s=s.replace(needle,"'Customer checkout']]]]);\n        $this->updateOrderStatus($order, 'Pending', 'Customer checkout started');\n\n        return $payment;\n    }",1)
s=s.replace("'status' => 'required|in:Successful,Failed'", "'status' => 'required|in:Successful,Failed,Pending,Incomplete'")
s=s.replace("if (! in_array($data['status'], ['Pending', 'Failed'], true))", "if (! in_array($data['status'], ['Pending', 'Incomplete', 'Failed'], true) || ($data['status'] === 'Failed' && in_array($input['status'], ['Pending', 'Incomplete'], true)))")
a=s.index("            if ($input['status'] === 'Successful') {");b=s.index("            $this->audit('Verified payment",a)
s=s[:a]+"            $this->updateOrderStatus($order, $input['status'] === 'Successful' ? 'Paid' : $input['status'], 'Verified payment event');\n"+s[b:]
pos=s.index('    public function synchronizeRefunds')
s=s[:pos]+'''    private function updateOrderStatus(Record $order, string $status, string $source): void
    {
        $before = $order->row();
        if ($status !== 'Paid' && in_array($before['payment_status'] ?? '', ['Paid', 'Refunded', 'Partially refunded'], true)) {
            return;
        }
        if ($status === ($before['payment_status'] ?? null) || in_array($before['payment_status'] ?? '', ['Refunded', 'Partially refunded'], true)) {
            return;
        }
        $data = $order->data;
        $data['payment_status'] = $status;
        $data['payment_status_history'][] = ['from' => $before['payment_status'] ?? null, 'to' => $status, 'actor' => $source, 'at' => now()->toISOString()];
        $order->data = $data;
        $order->version++;
        $order->save();
        $this->audit($source, 'orders', $before, $order->row());
    }

'''+s[pos:];p.write_text(s)
p=Path('apps/backend/app/Services/Commerce.php');s=p.read_text();pos=s.index('    public function settings(): array')
s=s[:pos]+'''    public function updateOrderStatuses(string $id, array $input, string $key, User $actor): array
    {
        Validator::make($input, ['version' => 'required|integer|min:1', 'status' => 'nullable|in:Pending,Confirmed,Dispatched,Delivered,Cancelled', 'payment_status' => 'nullable|in:Unpaid,Pending,Incomplete,Failed,Paid,Refunded,Partially refunded', 'evidence' => 'required|string|max:10000'])->validate();
        return $this->idempotent('order-status:'.$key, $input + ['order_id' => $id], function () use ($id, $input, $key, $actor) {
            $order = $this->find('orders', $id, true);
            abort_if($order->version !== $input['version'], 409, 'This order changed. Reload before continuing.');
            if (isset($input['payment_status']) && $input['payment_status'] !== ($order->data['payment_status'] ?? 'Unpaid')) {
                $before = $order->row();
                $data = $order->data;
                $data['payment_status'] = $input['payment_status'];
                $data['payment_status_history'][] = ['from' => $before['payment_status'], 'to' => $input['payment_status'], 'actor' => $actor->email, 'at' => now()->toISOString(), 'note' => $input['evidence'], 'source' => 'Manual admin override'];
                $order->data = $data;
                $order->version++;
                $order->save();
                $this->audit($actor, 'Manual payment status override', 'orders', $before, $order->row());
            }
            if (isset($input['status']) && $input['status'] !== $order->data['status']) {
                $action = ['Confirmed' => 'confirm', 'Dispatched' => 'dispatch', 'Delivered' => 'deliver', 'Cancelled' => 'cancel'][$input['status']] ?? null;
                if (! $action) {
                    $this->fail('An order cannot be moved backwards to Pending.');
                }
                return $this->orderAction($id, ['action' => $action, 'version' => $order->version, 'evidence' => $input['evidence']], 'status-transition:'.$key, $actor);
            }
            return $order->row();
        });
    }

'''+s[pos:];p.write_text(s)
p=Path('apps/backend/app/Http/Controllers/AdminController.php');s=p.read_text();pos=s.index('    public function settings(')
s=s[:pos]+'''    public function orderStatuses(Request $request, string $id): array
    {
        abort_unless($request->user()->role === 'Admin', 403, 'Only administrators can override order and payment statuses.');
        return $this->commerce->updateOrderStatuses($id, $request->all(), $request->header('Idempotency-Key', ''), $request->user());
    }

'''+s[pos:];p.write_text(s)
p=Path('apps/backend/routes/web.php');s=p.read_text().replace('use App\\Http\\Controllers\\PaymentEventController;', 'use App\\Http\\Controllers\\PaymentEventController;\nuse App\\Http\\Controllers\\OrderEventController;');s=s.replace("Route::post('payment-events'", "Route::post('order-events', OrderEventController::class)->middleware('throttle:120,1');\n    Route::post('payment-events'",1);s=s.replace("Route::post('order-actions/{id}'", "Route::post('order-status/{id}', [AdminController::class, 'orderStatuses']);\n        Route::post('order-actions/{id}'",1);p.write_text(s)
p=Path('apps/backend/config/commerce.php');s=p.read_text().replace("'payment_events_secret' =>", "'order_events_secret' => env('ORDER_EVENTS_SECRET'), 'payment_events_secret' =>");p.write_text(s)
p=Path('apps/admin/src/main.tsx');s=p.read_text().replace('refetchInterval: 30000','refetchInterval: 5000');s=s.replace('order={order} onClose',"order={(data.orders??[]).find((row:Row)=>row.id===order.id)??order} onClose");
pos=s.index('function OrderDetail(');b=s.index('function SettingsPage',pos);section=s[pos:b];section=section.replace('<StatusHistory entries={order.status_history??[]}/>', '<StatusHistory entries={order.status_history??[]}/><span className="section-label spaced">PAYMENT STATUS HISTORY</span><StatusHistory entries={order.payment_status_history??[]}/>{canPay&&<OrderStatusEditor order={order} refresh={refresh} notify={notify}/>}');s=s[:pos]+section+s[b:]
pos=s.index('function SettingsPage')
s=s[:pos]+'''function OrderStatusEditor({order,refresh,notify}:any){
 const [status,setStatus]=useState(order.status),[payment,setPayment]=useState(order.payment_status),[reason,setReason]=useState(''),[busy,setBusy]=useState(false),[error,setError]=useState('');
 useEffect(()=>{setStatus(order.status);setPayment(order.payment_status);},[order.version]);
 const options:Record<string,string[]>={Pending:['Pending','Confirmed','Cancelled'],Confirmed:['Confirmed','Dispatched','Cancelled'],Dispatched:['Dispatched','Delivered'],Delivered:['Delivered'],Cancelled:['Cancelled']};
 return <SubmissionForm className="action-confirm" onSubmit={async event=>{event.preventDefault();if(busy)return;setBusy(true);setError('');try{await api(`order-status/${order.id}`,'POST',{version:order.version,status,payment_status:payment,evidence:reason});await refresh();setReason('');notify('Order statuses updated.');}catch(error:any){setError(error.message);}finally{setBusy(false);}}}><h3>Admin status update</h3><p>Changes require a reason and are recorded in the audit history. Updating the payment status does not charge or refund the customer.</p>{error&&<div className="form-error" role="alert">{error}</div>}<div className="form-grid"><label>Order Status<select value={status} onChange={event=>setStatus(event.target.value)}>{(options[order.status]??[order.status]).map(value=><option key={value}>{value}</option>)}</select></label><label>Payment Status<select value={payment} onChange={event=>setPayment(event.target.value)}>{['Unpaid','Pending','Incomplete','Failed','Paid','Refunded','Partially refunded'].map(value=><option key={value}>{value}</option>)}</select></label><label className="wide">Reason / reference<input required maxLength={10000} value={reason} onChange={event=>setReason(event.target.value)}/></label></div><button className="button primary" disabled={busy||(status===order.status&&payment===order.payment_status)}>{busy?'Saving…':'Update statuses'}</button></SubmissionForm>;
}
'''+s[pos:];p.write_text(s)
