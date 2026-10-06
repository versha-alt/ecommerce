from pathlib import Path
p=Path('apps/admin/src/main.tsx');s=p.read_text();s=s.replace('ExternalLink, Upload }','ExternalLink, Upload, Star }',1)
a=s.index('function ReviewEditor(');b=s.index('function ProductGallery(',a)
s=s[:a]+'''function ReviewEditor({module,record,onClose,onSaved}:any){
 const [status,setStatus]=useState(record.status),[notes,setNotes]=useState(record.notes??''),[busy,setBusy]=useState(false),[error,setError]=useState('');
 return <Overlay onClose={onClose}>
  <div className="drawer-heading review-drawer-heading"><div><span className="eyebrow olive">CUSTOMER FEEDBACK</span><h2>Product review details</h2><p>Review customer feedback and manage publication.</p></div><button className="icon-button" aria-label="Close review" onClick={onClose}><X/></button></div>
  <SubmissionForm className="review-detail-form" onSubmit={async event=>{event.preventDefault();if(busy)return;setBusy(true);setError('');try{await api(`reviews/${record.id}`,'PATCH',{version:record.version,status,notes});onSaved();}catch(error:any){setError(error.message);}finally{setBusy(false);}}}>
   <div className="drawer-body review-drawer-body">
    {error&&<div className="form-error" role="alert">{error}</div>}
    <section className="review-summary-card"><div className="review-product-row"><div><span className="section-label">PRODUCT</span><h3>{record.product_name}</h3></div><Badge value={record.status}/></div><div className="review-customer-row"><div className="mini-avatar">{record.customer_name?.[0]??'C'}</div><div><strong>{record.customer_name}</strong><span>Submitted {date(record.created_at)}</span></div></div><div className="review-rating" aria-label={`${record.rating} out of 5 stars`}><span aria-hidden="true">{[1,2,3,4,5].map(value=><Star key={value} size={17} fill={value<=record.rating?'currentColor':'none'} className={value<=record.rating?'filled':'unfilled'}/>)}</span><strong>{record.rating}<small> / 5</small></strong></div></section>
    <section className="review-content-section"><span className="section-label">CUSTOMER REVIEW</span><h3>{record.title||'Product review'}</h3><p className="preserve-lines">{record.body}</p></section>
    <div className="info-banner review-visibility-notice"><Eye size={19}/><div><strong>Approval controls storefront visibility</strong><span>Only approved reviews are published. Ratings and review text remain as submitted by the customer.</span></div></div>
    <section className="review-moderation-section"><h3>Moderation</h3><fieldset className="form-grid form-fieldset" disabled={module.readOnly||busy}><FormField field={{key:'status',label:'Review status',type:'select',required:true,wide:true,options:record.status==='Pending'?['Pending','Approved','Rejected']:['Approved','Rejected']}} value={status} onChange={setStatus} data={{}}/><FormField field={{key:'notes',label:'Internal moderation notes',type:'textarea',wide:true,hint:'Visible to your team only.'}} value={notes} onChange={setNotes} data={{}}/></fieldset></section>
    <div className="review-history-section"><StatusHistory entries={record.status_history??[]}/></div>
   </div>
   <div className="drawer-footer review-drawer-footer"><button type="button" className="button secondary" onClick={onClose}>Cancel</button>{!module.readOnly&&<button className="button primary" disabled={busy}>{busy?<><Loader2 size={16} className="spin"/>Saving...</>:<><Check size={16}/>Save moderation</>}</button>}</div>
  </SubmissionForm>
 </Overlay>;
}

'''+s[b:];p.write_text(s,encoding='utf-8')
p=Path('apps/admin/src/styles.css');s=p.read_text();s+='''
.drawer:has(.review-detail-form){display:flex;flex-direction:column;overflow:hidden}
.review-drawer-heading{flex-shrink:0;align-items:flex-start;padding:26px 28px}
.review-drawer-heading .eyebrow{display:block;margin-bottom:8px}
.review-drawer-heading h2{font-size:21px;line-height:1.4}
.review-drawer-heading p{color:#7b8474;font-size:12px;line-height:1.6;margin-top:6px}
.review-detail-form{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}
.review-drawer-body{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:26px 28px 28px;scrollbar-width:thin}
.review-summary-card{padding:20px;border:1px solid var(--line);border-radius:12px;background:#fafbf7}
.review-product-row{display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.review-product-row>div{min-width:0}.review-product-row h3{margin-top:7px;font-size:16px;line-height:1.5;overflow-wrap:anywhere}.review-product-row .badge{flex-shrink:0;margin-top:2px}
.review-customer-row{display:flex;align-items:center;gap:11px;margin-top:20px}.review-customer-row .mini-avatar{width:34px;height:34px;flex-shrink:0}.review-customer-row strong{font-size:13px;font-weight:600}.review-customer-row span{display:block;font-size:11px;color:#7d8776;margin-top:5px}
.review-rating{display:flex;align-items:center;gap:12px;margin-top:18px}.review-rating>span{display:flex;gap:4px}.review-rating .filled{color:#a38a3e}.review-rating .unfilled{color:#cdd2c3}.review-rating strong{font-size:13px}.review-rating small{font-size:12px;color:#7c8672;font-weight:400}
.review-content-section{margin:26px 0}.review-content-section h3{margin-top:10px;font-size:16px;line-height:1.55;overflow-wrap:anywhere}.review-content-section p{margin-top:10px;font-size:13px;line-height:1.85;color:#5f6b57;overflow-wrap:anywhere}
.review-visibility-notice{margin-bottom:26px;padding:15px 16px}.review-visibility-notice strong{color:#5e7045}.review-visibility-notice span{color:#79866a}
.review-moderation-section{padding-top:22px;border-top:1px solid var(--line)}.review-moderation-section h3{font-size:15px;margin-bottom:18px}.review-moderation-section .form-grid{gap:18px}.review-moderation-section .form-field{font-size:12px;color:#667359;gap:9px}.review-moderation-section select,.review-moderation-section textarea{font-size:13px;padding:12px}.review-moderation-section textarea{min-height:112px;resize:vertical}.review-history-section .status-history{margin:24px 0 0;padding-bottom:0;border-bottom:0}.review-history-section .status-history small{line-height:1.6;overflow-wrap:anywhere}
.review-drawer-footer{position:static;flex-shrink:0;padding:18px 28px;gap:12px}.review-drawer-footer .button{min-height:40px;font-size:12px}
@media(max-width:600px){.review-drawer-heading{padding:22px 20px}.review-drawer-heading h2{font-size:19px}.review-drawer-body{padding:20px}.review-summary-card{padding:16px}.review-drawer-footer{padding:16px 20px}.review-product-row{gap:10px}}
''';p.write_text(s,encoding='utf-8')
