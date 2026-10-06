import {editorContent} from '@/lib/rich-text';
export default function RichContent({value,className=''}:{value:string;className?:string}){return <div className={'rich-content '+className} dangerouslySetInnerHTML={{__html:editorContent(value||'')}}/>}
