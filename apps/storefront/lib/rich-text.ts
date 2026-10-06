import sanitizeHtml from 'sanitize-html';
export const richFields=new Set(['description','body','instructions','notes','message']);
export const richOptions: sanitizeHtml.IOptions={allowedTags:['p','br','strong','em','u','s','h2','h3','ul','ol','li','blockquote','a','code','pre'],allowedAttributes:{a:['href','title','target','rel']},allowedSchemes:['http','https','mailto'],allowProtocolRelative:false,transformTags:{a:(_tag,attributes)=>({tagName:'a',attribs:{...attributes,target:'_blank',rel:'noopener noreferrer'}})}};
export const cleanRich=(value:string)=>sanitizeHtml(value,richOptions);
export const escapeText=(value:string)=>value.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
export const isRich=(value:string)=>/<\/?(?:p|br|strong|em|u|s|h[23]|ul|ol|li|blockquote|a|code|pre)(?:\s|>|\/)/i.test(value);
export const editorContent=(value:string,plain=false)=>!plain&&isRich(value)?cleanRich(value):'<p>'+escapeText(value).replace(/\n/g,'<br>')+'</p>';
export function plainText(value:string){if(!isRich(value))return value;return sanitizeHtml(value.replace(/<br\s*\/?\s*>/gi,'\n').replace(/<\/(p|h[23]|li|blockquote|pre)>/gi,'\n'),{allowedTags:[],allowedAttributes:{}}).replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"').replace(/&#39;/g,"'").replace(/&nbsp;/g,' ').trim();}
