import {Check,FilePenLine,CheckCircle2,PauseCircle} from 'lucide-react';
type Props={value:string;options:string[];onChange:(value:string)=>void;label:string;disabled?:boolean;compact?:boolean;name?:string;required?:boolean;product?:boolean};
export default function StatusControl({value,options,onChange,label,disabled=false,compact=false,name,required,product=false}:Props){
 const binary=options.length===2;const positive=options.find(option=>['Active','Approved','Enabled','Published'].includes(option))||options[1];const negative=options.find(option=>option!==positive)||options[0];const checked=value===positive;
 const reversible=binary;
 return <div className={'status-control '+(compact?'status-control-compact':'')+(product?' product-status-control':'')}>
 {name&&<input type="hidden" name={name} value={value||''} required={required} disabled={disabled}/>}
 {reversible?<div className="status-switch-row">{!compact&&<span className={!checked?'chosen':''}>{negative}</span>}<button type="button" role="switch" aria-label={label} aria-checked={checked} title={negative+' / '+positive} disabled={disabled} className={'status-switch '+(checked?'is-on':'')} onClick={()=>onChange(checked?negative:positive)}><span>{checked&&<Check size={11}/>}</span></button><span className={checked?'chosen':''}>{compact?value:positive}</span></div>:<div className="status-segments" role="group" aria-label={label}>{options.map(option=><button type="button" key={option} disabled={disabled} aria-label={product?option:undefined} title={product?option:undefined} aria-pressed={value===option} className={value===option?'selected':''} onClick={()=>{if(value!==option)onChange(option);}}>{product?(option==='Draft'?<FilePenLine size={15} aria-hidden="true"/>:option==='Active'?<CheckCircle2 size={15} aria-hidden="true"/>:<PauseCircle size={15} aria-hidden="true"/>):option}</button>)}</div>}
 </div>;
}
