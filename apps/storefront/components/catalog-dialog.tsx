"use client";
import {useEffect,useRef,type ReactNode} from 'react';
import {createPortal} from 'react-dom';
import {X} from 'lucide-react';
/* Native modal supplies focus trapping, Escape, and focus restoration. */
export default function CatalogDialog({title,children,onClose,drawer=false}:{title:string;children:ReactNode;onClose:()=>void;drawer?:boolean}){
 const ref=useRef<HTMLDialogElement>(null);
 useEffect(()=>{const dialog=ref.current!;const previous=document.activeElement as HTMLElement;const overflow=document.body.style.overflow;document.body.style.overflow='hidden';dialog.showModal();return()=>{dialog.close();document.body.style.overflow=overflow;previous?.focus();};},[]);
 return createPortal(<dialog ref={ref} className={'catalog-dialog'+(drawer?' catalog-filter-drawer':'')} aria-labelledby="catalog-dialog-title" onCancel={event=>{event.preventDefault();onClose();}} onClick={event=>{if(event.target===event.currentTarget){const r=event.currentTarget.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)onClose();}}}><div className="catalog-dialog-heading"><h2 id="catalog-dialog-title">{title}</h2><button type="button" aria-label="Close dialog" onClick={onClose}><X size={22}/></button></div>{children}</dialog>,document.body);
}
