import React, { createContext, useRef, useState } from 'react';
import type { Field } from './config';

type Errors = Record<string, string[]>;
export const FormValidationContext = createContext<{errors:Errors}>({errors:{}});
export async function readResponse(response:Response, login=false, inspect?:(body:any)=>void) {
 const result=await response.json().catch(()=>null);
 if(response.status===401&&!login){sessionStorage.removeItem('olive-token');window.dispatchEvent(new Event('session-expired'));}
 if(result)inspect?.(result);
 if(!response.ok){
  const errors:Errors=result?.errors??{};
  window.dispatchEvent(new CustomEvent('submission-errors',{detail:errors}));
  const details=Object.values(errors).flat().join(' ');
  const message=response.status>=500?'The server could not complete this request. Please try again.':response.status===429?'Too many requests. Please wait a moment and try again.':details||result?.message||(response.status===403?'You do not have permission to perform this action.':'The request could not be completed.');
  throw new Error(message);
 }
 if(!result)throw new Error('The server returned an unexpected response. Please try again.');
 return result;
}
export async function request(url:string, options:RequestInit, login=false){
 const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),30000);
 try{return await readResponse(await fetch(url,{...options,signal:controller.signal}),login);}
 catch(error){const failure=error instanceof TypeError?new Error('Unable to connect. Check your connection and try again.'):error instanceof DOMException&&error.name==='AbortError'?new Error('The request timed out. Check the record before retrying.'):error;if(!['GET','HEAD'].includes((options.method||'GET').toUpperCase()))window.dispatchEvent(new CustomEvent('admin-save-error',{detail:failure instanceof Error?failure.message:'The update could not be saved.'}));throw failure;}
 finally{clearTimeout(timeout);}
}
const integerKeys=['stock','low_stock_threshold','quantity','minimum_quantity','buy_quantity','get_quantity','max_applications','usage_limit','smtp_port'];
export function applyInputRules(field:Field):React.InputHTMLAttributes<HTMLInputElement>{
 const key=field.key;const rules:React.InputHTMLAttributes<HTMLInputElement>={maxLength:key==='sku'?80:key==='slug'||key==='name'?180:2000};
 if(field.type==='number'){rules.step=integerKeys.includes(key)?1:0.01;if(key!=='quantity')rules.min=['minimum_quantity','buy_quantity','get_quantity','max_applications','usage_limit','smtp_port'].includes(key)?1:0;rules.max=key==='rate'?100:key==='smtp_port'?65535:100000000;}
 if(key==='slug')rules.pattern='[a-z0-9]+(-[a-z0-9]+)*';
 if(key==='code')rules.maxLength=50;
 if(key==='password'){rules.minLength=12;rules.maxLength=1024;}
 return rules;
}
export function SubmissionForm({onSubmit,children,...props}:React.FormHTMLAttributes<HTMLFormElement>){
 const [errors,setErrors]=useState<Errors>({});const [message,setMessage]=useState('');const pending=useRef(false);const formRef=useRef<HTMLFormElement>(null);
 React.useEffect(()=>{const listener=(event:Event)=>{if(!pending.current)return;const raw=(event as CustomEvent<Errors>).detail;const mapped:Errors={};Object.entries(raw).forEach(([key,value])=>{const base=key.split('.')[0];mapped[base]=[...(mapped[base]??[]),...value];});setErrors(mapped);requestAnimationFrame(()=>{const first=Object.keys(mapped)[0];const field=Array.from(formRef.current?.querySelectorAll<HTMLElement>('[data-field]')??[]).find(el=>el.dataset.field===first);field?.scrollIntoView({block:'center',behavior:'smooth'});(field?.querySelector<HTMLElement>('[role="textbox"]')??field?.querySelector<HTMLElement>('input,select,textarea'))?.focus();});};window.addEventListener('submission-errors',listener);return()=>window.removeEventListener('submission-errors',listener);},[]);
 return <FormValidationContext.Provider value={{errors}}><form {...props} ref={formRef} noValidate aria-busy={pending.current} onInput={()=>{setErrors({});setMessage('');}} onChange={()=>{setErrors({});setMessage('');}} onSubmit={async event=>{
  event.preventDefault();if(pending.current)return;
  const form=event.currentTarget;const next:Errors={};let invalid:HTMLElement|null=null;
  for(const element of Array.from(form.elements)){
   if(!(element instanceof HTMLInputElement||element instanceof HTMLSelectElement||element instanceof HTMLTextAreaElement)||element.disabled||!element.willValidate)continue;
   if(element.required&&element.type!=='checkbox'&&!element.value.trim()||!element.validity.valid){const key=element.name||element.closest<HTMLElement>('[data-field]')?.dataset.field||'form';next[key]=[element.value.trim()?element.validationMessage:'This field is required.'];invalid??=element;}
  }
  for(const editor of form.querySelectorAll<HTMLElement>('[data-editor]')){if(editor.dataset.disabled==='true')continue;const key=editor.dataset.editor!;if(editor.dataset.required==='true'&&editor.dataset.empty==='true')next[key]=['This field is required.'];else if(Number(editor.dataset.valueLength)>Number(editor.dataset.maxLength))next[key]=['Content is too long. Please shorten it before saving.'];if(next[key])invalid??=editor.querySelector<HTMLElement>('[role="textbox"]');}
  if(form.querySelector('[data-field="county_codes"]')&&Number(form.querySelector('[data-field="county_codes"]')?.getAttribute('data-selected-count'))<1)next.county_codes=['Select at least one county.'];
  const value=(key:string)=>form.querySelector<HTMLInputElement>(`[name="${key}"]`)?.value??'';
  if(value('sale_price')&&value('price')&&Number(value('sale_price'))>Number(value('price')))next.sale_price=['Sale price cannot exceed the regular price.'];
  if(value('starts_at')&&value('ends_at')&&value('ends_at')<value('starts_at'))next.ends_at=['End date must be on or after the start date.'];
  if(value('discount_type')==='Percentage'&&Number(value('value'))>100)next.value=['Percentage cannot exceed 100%.'];
  for(const key of ['credentials','configuration']){const content=value(key);if(content.trim()){try{const parsed=JSON.parse(content);if(!parsed||Array.isArray(parsed)||typeof parsed!=='object')throw new Error();}catch{next[key]=['Enter a valid JSON object.'];}}}
  setErrors(next);if(Object.keys(next).length){setMessage('Please correct the highlighted fields before submitting.');window.dispatchEvent(new CustomEvent('admin-save-error',{detail:'Changes were not saved. Please correct the highlighted fields.'}));(invalid??form.querySelector<HTMLElement>(`[name="${Object.keys(next)[0]}"]`))?.focus();return;}
  if(form.querySelector('input[type="file"]:disabled')){setMessage('Wait for the file upload to finish before saving.');window.dispatchEvent(new CustomEvent('admin-save-error',{detail:'Wait for the file upload to finish before saving.'}));return;}
  setMessage('');pending.current=true;try{await onSubmit?.(event);}finally{pending.current=false;}
 }}>{message&&<div className="form-error" role="alert">{message}</div>}{children}</form></FormValidationContext.Provider>;
}
