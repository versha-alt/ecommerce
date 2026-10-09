import {Tv,Refrigerator,WashingMachine,Microwave,Blender,Fan,HeartPulse,createLucideIcon} from 'lucide-react';

/* Shared decorative icons; category labels and destinations remain unchanged. */
// Lucide has no Oven export; use its lightweight icon factory.
const Oven=createLucideIcon('Oven',[
 ['rect',{x:3,y:2,width:18,height:20,rx:2,key:'body'}],
 ['path',{d:'M3 8h18M7 5h.01M12 5h.01M17 5h.01',key:'controls'}],
 ['rect',{x:6,y:11,width:12,height:8,rx:1,key:'window'}],
 ['path',{d:'M8 14h8',key:'handle'}],
]);
const icons={
 'tvs-audio':Tv,'fridges-freezers':Refrigerator,'washers-dryers':WashingMachine,
 'cookers-microwaves':Microwave,'small-appliances':Blender,'built-in-appliances':Oven,
 'acs-fans-heaters':Fan,'health-personal-care':HeartPulse,
};
export default function CategorySymbol({id,size=20}:{id:string;size?:number}){
 const Icon=icons[id as keyof typeof icons];
 return Icon?<Icon className="category-symbol" size={size} strokeWidth={1.7} aria-hidden="true"/>:null;
}
