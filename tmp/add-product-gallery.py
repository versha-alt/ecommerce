from pathlib import Path
p=Path('apps/admin/src/config.ts');s=p.read_text();s=s.replace("{key:'image',label:'Product image',type:'image',wide:true}","{key:'image',label:'Main Image',type:'image',wide:true,hint:'Primary product image. Upload one image.'},{key:'gallery_images',label:'Gallery Images',type:'gallery',wide:true,hint:'Upload multiple product images. JPG, PNG or WebP, up to 5 MB each.'}");p.write_text(s)
p=Path('apps/admin/src/main.tsx');s=p.read_text();s=s.replace(" const choices=field.resource?", " if(field.type==='gallery')return <ProductGallery field={field} value={value??[]} onChange={onChange}/>;\n const choices=field.resource?")
s=s.replace("onChange={v=>setForm({...form,[f.key]:v})}","onChange={v=>setForm((previous:any)=>({...previous,[f.key]:v}))}")
s=s.replace("<small>{uploading?'Uploading…':uploadError||'JPG, PNG, WebP or PDF · maximum 5 MB'}</small>", "{value&&field.key==='image'&&field.label==='Main Image'&&<button type=\"button\" className=\"text-button danger-text\" disabled={uploading} onClick={()=>onChange('')}>Remove main image</button>}<small role={uploadError?'alert':undefined}>{uploading?'Uploading…':uploadError||'JPG, PNG, WebP or PDF · maximum 5 MB'}</small>")
pos=s.index('function Overlay(')
s=s[:pos]+'''function ProductGallery({field,value,onChange}:{field:Field;value:string[];onChange:(images:string[])=>void}){
 const validation=React.useContext(FormValidationContext);
 const [busy,setBusy]=useState(false),[error,setError]=useState('');
 const upload=async(files:File[])=>{
  if(busy||!files.length)return;
  if(files.some(file=>!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>5*1024*1024)){setError('Select JPG, PNG or WebP images, up to 5 MB each.');return;}
  setBusy(true);setError('');const uploaded:string[]=[];
  try{for(const file of files){const body=new FormData();body.append('file',file);const result=await request('/api/v1/uploads',{method:'POST',headers:{Authorization:`Bearer ${token}`,Accept:'application/json'},body});uploaded.push(result.url);}}
  catch(error:any){setError(`${error.message} Successfully uploaded images have been kept; select the remaining files to retry.`);}
  finally{if(uploaded.length)onChange([...value,...uploaded]);setBusy(false);}
 };
 return <div className="form-field wide" data-field={field.key}><span>{field.label}</span><div className="upload-field"><input type="file" multiple accept="image/jpeg,image/png,image/webp" aria-label="Gallery Images" disabled={busy} onChange={event=>{const files=Array.from(event.currentTarget.files??[]);event.currentTarget.value='';void upload(files);}}/><small role="status">{busy?'Uploading gallery images…':field.hint}</small>{error&&<small className="field-error" role="alert">{error}</small>}{validation.errors[field.key]&&<small className="field-error" role="alert">{validation.errors[field.key].join(' ')}</small>}<div className="product-gallery">{value.map((url,index)=><div className="gallery-item" key={`${url}-${index}`}><a href={url} target="_blank" rel="noreferrer"><img src={url} alt={`Gallery image ${index+1}`}/></a><button type="button" className="text-button danger-text" disabled={busy} aria-label={`Remove gallery image ${index+1}`} onClick={()=>onChange(value.filter((_,position)=>position!==index))}><X size={14}/>Remove</button></div>)}</div></div></div>;
}
'''+s[pos:];p.write_text(s)
p=Path('apps/backend/app/Services/Commerce.php');s=p.read_text();s=s.replace("'products' => ['slug'", "'products' => ['gallery_images' => 'nullable|array', 'gallery_images.*' => ['required', 'string', 'max:2000', 'distinct', 'regex:~^(https?://|/api/v1/media/)~'], 'slug'",1)
s=s.replace("if ($resource === 'products') {\n            foreach", "if ($resource === 'products') {\n            $clean['gallery_images'] = array_values($clean['gallery_images'] ?? $old?->data['gallery_images'] ?? []);\n            foreach",1);p.write_text(s)
