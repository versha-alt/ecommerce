/* Parse the catalogue's real key/value specifications once for filters and comparison. */
import type {Product} from './store';
export function productSpecs(product:Product):Record<string,string>{return Object.fromEntries((product.specifications||'').split(/\n/).flatMap(line=>{const index=line.indexOf(':');return index>0?[[line.slice(0,index).trim(),line.slice(index+1).trim()]]:[]}));}
