"use client";

import {useRouter} from 'next/navigation';
import {useEffect} from 'react';

export default function CatalogSync(){
 const router=useRouter();
 useEffect(()=>{
  const refresh=()=>router.refresh();
  const timer=window.setInterval(refresh,15000);
  const onVisibility=()=>{if(document.visibilityState==='visible')refresh();};
  document.addEventListener('visibilitychange',onVisibility);
  return()=>{window.clearInterval(timer);document.removeEventListener('visibilitychange',onVisibility);};
 },[router]);
 return null;
}
