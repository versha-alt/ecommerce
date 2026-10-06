"use client";
import {useState} from 'react';
import type {InputHTMLAttributes} from 'react';
import {Eye,EyeOff} from 'lucide-react';
export default function PasswordInput({label='Password',...props}:InputHTMLAttributes<HTMLInputElement>&{label?:string}){const [visible,setVisible]=useState(false);return <div className="password-control"><input {...props} type={visible?'text':'password'}/><button type="button" className="password-toggle" aria-label={(visible?'Hide ':'Show ')+label.toLowerCase()} aria-pressed={visible} disabled={props.disabled} onMouseDown={event=>event.preventDefault()} onClick={()=>setVisible(!visible)}>{visible?<EyeOff size={18} aria-hidden="true"/>:<Eye size={18} aria-hidden="true"/>}</button></div>}
