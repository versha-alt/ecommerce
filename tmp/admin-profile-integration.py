from pathlib import Path
import re
p=Path('apps/admin/src/main.tsx');s=p.read_text(encoding='utf-8').replace("import './styles.css';","import './styles.css';\nimport AdminProfile from './admin-profile';")
s=s.replace(" const go=(key:string)=>", " const signOut=async()=>{await api('auth/logout','POST').catch(()=>{});token='';sessionStorage.removeItem('olive-token');setSignedIn(false);client.clear();};\n const go=(key:string)=>")
s=s.replace("activity:'Activity log'} as any)","activity:'Activity log',profile:'Admin profile'} as any)")
old="<button className=\"user-profile\" onClick={async()=>{await api('auth/logout','POST').catch(()=>{});token='';sessionStorage.removeItem('olive-token');setSignedIn(false);client.clear();}}>"
new='<button className="user-profile" aria-label="View admin profile" onClick={()=>go(\'profile\')}>'
assert old in s;s=s.replace(old,new).replace('<small>{user.role}</small></div><LogOut size={16}/></button>','<small>{user.role}</small></div><ChevronRight size={16}/></button>')
old='<span className="header-avatar">{user.name?.[0]}</span>';new='<button className="header-avatar" aria-label="View admin profile" title="Admin profile" onClick={()=>go(\'profile\')}>{user.name?.[0]}</button>';assert old in s;s=s.replace(old,new)
s=s.replace("    {page==='dashboard'&&", "    {page==='profile'&&<AdminProfile user={user} permissions={permissions} onSignOut={signOut}/>}\n    {page==='dashboard'&&")
p.write_text(s,encoding='utf-8')
p=Path('apps/admin/src/styles.css');s=p.read_text(encoding='utf-8')
def larger(m):
 size=float(m.group(1));size+=1 if size<20 else 2 if size<40 else 0;return 'font-size:'+format(size,'g')+'px'
s=re.sub(r'font-size:\s*(\d+(?:\.\d+)?)px',larger,s);p.write_text(s,encoding='utf-8')
