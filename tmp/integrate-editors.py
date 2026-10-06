from pathlib import Path
p=Path('apps/admin/src/main.tsx');s=p.read_text(encoding='utf-8');s=s.replace("import './styles.css';","import './styles.css';\nimport {TextEditor, RichContent} from './text-editor';\nimport {richFields, plainText} from './rich-text';")
old="<textarea name={field.key} maxLength={field.key==='body'?50000:20000} required={field.required} rows={field.key==='body'?8:3} value={Array.isArray(value)?value.join('\\n'):value??''} onChange={e=>onChange(e.target.value)}/>"
assert old in s
s=s.replace(old,"<TextEditor name={field.key} label={field.label} maxLength={field.key==='body'?50000:20000} required={field.required} plain={!richFields.has(field.key)} invalid={!!validation.errors[field.key]} value={Array.isArray(value)?value.join('\\n'):value??''} onChange={onChange}/>")
s=s.replace('<textarea maxLength={10000} value={note} onChange={e=>setNote(e.target.value)} placeholder="Add a note for your team…"/>','<TextEditor name="order-note" label="Internal order note" maxLength={10000} value={note} onChange={setNote} disabled={busy} placeholder="Add a note for your team…"/>')
s=s.replace('<p className="preserve-lines">{order.notes||\'No notes yet.\'}</p>',"<RichContent value={order.notes||'No notes yet.'}/>").replace('{entry.note&&<p>{entry.note}</p>}','{entry.note&&<RichContent value={entry.note}/>}').replace("return String(value??'—');","return plainText(String(value??'—'));")
p.write_text(s,encoding='utf-8')
p=Path('apps/admin/src/validation.tsx');s=p.read_text(encoding='utf-8').replace("field?.querySelector<HTMLElement>('input,select,textarea')?.focus();","(field?.querySelector<HTMLElement>('[role=\"textbox\"]')??field?.querySelector<HTMLElement>('input,select,textarea'))?.focus();")
s=s.replace('onChange={()=>{setErrors({});setMessage(\'\');}}','onInput={()=>{setErrors({});setMessage(\'\');}} onChange={()=>{setErrors({});setMessage(\'\');}}').replace('let invalid:HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement|null=null;','let invalid:HTMLElement|null=null;')
s=s.replace("  if(form.querySelector('[data-field=\"county_codes\"]')", "  for(const editor of form.querySelectorAll<HTMLElement>('[data-editor]')){if(editor.dataset.disabled==='true')continue;const key=editor.dataset.editor!;if(editor.dataset.required==='true'&&editor.dataset.empty==='true')next[key]=['This field is required.'];else if(Number(editor.dataset.valueLength)>Number(editor.dataset.maxLength))next[key]=['Content is too long. Please shorten it before saving.'];if(next[key])invalid??=editor.querySelector<HTMLElement>('[role=\"textbox\"]');}\n  if(form.querySelector('[data-field=\"county_codes\"]')")
p.write_text(s,encoding='utf-8')
p=Path('apps/admin/src/text-editor.tsx');s=p.read_text(encoding='utf-8').replace('CharacterCount.configure({limit:maxLength})','CharacterCount.configure({limit:maxLength,trimInitialContent:false})').replace('<input type="url" aria-label=', '<input type="text" aria-label=');p.write_text(s,encoding='utf-8')
rich=Path('apps/admin/src/rich-text.ts').read_text(encoding='utf-8')
Path('apps/storefront/lib/rich-text.ts').write_text(rich,encoding='utf-8')
Path('apps/storefront/components/rich-content.tsx').write_text("import {editorContent} from '@/lib/rich-text';\nexport default function RichContent({value,className=''}:{value:string;className?:string}){return <div className={'rich-content '+className} dangerouslySetInnerHTML={{__html:editorContent(value||'')}}/>}\n",encoding='utf-8')
changes={
'apps/storefront/app/products/[slug]/page.tsx': [('description:p?.seo_description||p?.description','description:plainText(p?.seo_description||p?.description||\'\')'),('<p>{product.description}</p>','<RichContent value={product.description||\'\'}/>'),('<p className="preserve-lines">{product.description}</p>','<RichContent value={product.description||\'\'}/>')],
'apps/storefront/app/brands/[slug]/page.tsx':[('<p>{brand.description}</p>',"<RichContent value={brand.description||''}/>")],
'apps/storefront/components/listing.tsx':[('<p>{description}</p>','<RichContent value={description}/>')],
'apps/storefront/components/hero.tsx':[('<p>{banner?.description||', '<RichContent className="hero-description" value={banner?.description||'),("Reliably yours.'}</p>","Reliably yours.'}/>")],
'apps/storefront/components/checkout.tsx':[('<small>{method.instructions}</small>',"<RichContent className=\"payment-instructions\" value={method.instructions||''}/>")],
'apps/storefront/app/[slug]/page.tsx':[("<article className=\"information-body\">{page.body.replace(/<[^>]*>/g,'')}</article>",'<RichContent value={page.body} className="information-body"/>')]
}
for path,replacements in changes.items():
 p=Path(path);s=p.read_text(encoding='utf-8');idx=s.index('\n')+1 if s.startswith(('"use client"',"'use client'")) else 0;s=s[:idx]+"import RichContent from '@/components/rich-content';\n"+s[idx:]
 if '/products/[slug]' in path:s="import {plainText} from '@/lib/rich-text';\n"+s
 for old,new in replacements:
  assert old in s,(path,old);s=s.replace(old,new)
 p.write_text(s,encoding='utf-8')
