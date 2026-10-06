from pathlib import Path
p=Path('apps/backend/app/Services/Commerce.php');s=p.read_text();s=s.replace("'returns', 'enquiries', 'emails', 'settings'];", "'returns', 'reviews', 'enquiries', 'emails', 'settings'];",1)
s=s.replace("'returns', 'enquiries', 'reports', 'activity']", "'returns', 'reviews', 'enquiries', 'reports', 'activity']",1)
s=s.replace("in_array($resource, ['customers', 'returns', 'payments'])", "in_array($resource, ['customers', 'returns', 'payments', 'reviews'])",1)
pos=s.index("            if ($resource === 'payments') {",s.index('public function save('))
s=s[:pos]+'''            if ($resource === 'reviews') {
                Validator::make($input, ['status' => 'required|in:Pending,Approved,Rejected', 'notes' => 'nullable|string|max:20000'])->validate();
                foreach ($input as $field => $value) {
                    if (! in_array($field, ['id', 'version', 'created_at', 'updated_at', 'status', 'notes']) && $value !== ($old->row()[$field] ?? null)) {
                        $this->fail('Customer review details cannot be changed.');
                    }
                }
                if ($input['status'] === 'Pending' && $old->data['status'] !== 'Pending') {
                    $this->fail('Choose Approved or Rejected for a moderated review.');
                }
                $data = $old->data;
                if ($input['status'] !== $data['status']) {
                    $data['status_history'][] = ['from' => $data['status'], 'to' => $input['status'], 'actor' => $actor->email, 'at' => now()->toISOString(), 'note' => $input['notes'] ?? ''];
                }
                $data['status'] = $input['status'];
                $data['notes'] = $input['notes'] ?? $data['notes'] ?? '';
                $old->data = $data;
                $old->version++;
                $old->save();
                $this->audit($actor, 'Review moderated', 'reviews', $before, $old->row());
                return $old->row();
            }
'''+s[pos:]
a=s.index('    public function retire(');pos=s.index('\n',s.index('    {',a))+1;s=s[:pos]+"        abort_if($resource === 'reviews', 405, 'Use Approved or Rejected to moderate reviews.');\n"+s[pos:];p.write_text(s)
p=Path('apps/backend/routes/web.php');s=p.read_text().replace('use App\\Http\\Controllers\\PaymentEventController;', 'use App\\Http\\Controllers\\PaymentEventController;\nuse App\\Http\\Controllers\\ProductReviewController;');s=s.replace("Route::post('order-events'", "Route::post('review-submissions', [ProductReviewController::class, 'submit'])->middleware('throttle:30,1');\n    Route::get('products/{product}/reviews', [ProductReviewController::class, 'published'])->middleware('throttle:120,1');\n    Route::post('order-events'",1);p.write_text(s)
p=Path('apps/backend/config/commerce.php');s=p.read_text().replace("'demo' =>", "'review_events_secret' => env('REVIEW_EVENTS_SECRET'), 'demo' =>");p.write_text(s)
p=Path('apps/backend/.env.example');s=p.read_text();s+='\nREVIEW_EVENTS_SECRET=\n' if 'REVIEW_EVENTS_SECRET=' not in s else '';p.write_text(s)
p=Path('apps/admin/src/config.ts');s=p.read_text();pos=s.index(" {key:'enquiries'");s=s[:pos]+" {key:'reviews',label:'Product Reviews',singular:'product review',description:'Review customer feedback and approve it for your storefront.',group:'Commerce',columns:['product_name','customer_name','rating','title','status','created_at'],fields:[]},\n"+s[pos:];p.write_text(s)
p=Path('apps/admin/src/main.tsx');s=p.read_text();s=s.replace("returns:RotateCcw, enquiries", "returns:RotateCcw, reviews:MessageSquare, enquiries");s=s.replace("['orders','customers','returns','payments']", "['orders','customers','returns','payments','reviews']")
s=s.replace("editor.module.key==='coupons'?", "editor.module.key==='reviews'?<ReviewEditor module={editor.module} record={editor.record} onClose={()=>setEditor(null)} onSaved={()=>{refresh();setEditor(null);notify('Review moderation saved.');}}/>:editor.module.key==='coupons'?",1)
pos=s.index('function ProductGallery(');s=s[:pos]+'''function ReviewEditor({module,record,onClose,onSaved}:any){
 const [status,setStatus]=useState(record.status),[notes,setNotes]=useState(record.notes??''),[busy,setBusy]=useState(false),[error,setError]=useState('');
 return <Overlay onClose={onClose}><div className="drawer-heading"><div><span className="eyebrow olive">CUSTOMER FEEDBACK</span><h2>Moderate product review</h2></div><button className="icon-button" aria-label="Close review" onClick={onClose}><X/></button></div><SubmissionForm onSubmit={async event=>{event.preventDefault();if(busy)return;setBusy(true);setError('');try{await api(`reviews/${record.id}`,'PATCH',{version:record.version,status,notes});onSaved();}catch(error:any){setError(error.message);}finally{setBusy(false);}}}><div className="drawer-body">{error&&<div className="form-error" role="alert">{error}</div>}<Badge value={record.status}/><h3>{record.product_name}</h3><p>{record.customer_name} · {record.rating}/5 stars · {date(record.created_at)}</p><h3>{record.title||'Product review'}</h3><p className="preserve-lines">{record.body}</p><div className="info-banner"><Eye size={20}/><span>Only approved reviews are visible on the storefront. Customer ratings and text cannot be edited.</span></div><fieldset className="form-grid form-fieldset" disabled={module.readOnly||busy}><label>Review status<select required value={status} onChange={event=>setStatus(event.target.value)}>{record.status==='Pending'&&<option>Pending</option>}<option>Approved</option><option>Rejected</option></select></label><label className="wide">Internal moderation notes<textarea maxLength={20000} value={notes} onChange={event=>setNotes(event.target.value)}/></label></fieldset><StatusHistory entries={record.status_history??[]}/></div><div className="drawer-footer"><button type="button" className="button secondary" onClick={onClose}>Cancel</button>{!module.readOnly&&<button className="button primary" disabled={busy}>{busy?'Saving…':'Save moderation'}</button>}</div></SubmissionForm></Overlay>;
}
'''+s[pos:];p.write_text(s)
