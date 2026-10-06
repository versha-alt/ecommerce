from pathlib import Path
p=Path('apps/storefront/components/store.tsx');s=p.read_text(encoding='utf-8').replace('Check,Star,Tag}', 'Check,Star,Tag,Facebook,Instagram}').replace('Header(){const {data,cart,wishlist}=useStore();','Header(){const {data,cart,wishlist,account}=useStore();').replace('<Link href="/account" aria-label="My account"><UserRound size={22}/><small>Account</small></Link>','<Link href="/account" className={account?"header-account signed-in":"header-account"} aria-label={account?"My account: "+account.name:"My account"} title={account?.name||"My account"}><UserRound size={22}/><small>{account?.name||"Account"}</small></Link>')
old="{['facebook','instagram'].filter(key=>/^https?:\\/\\//.test(settings[key]||'')).map(key=><a key={key} href={settings[key]} target=\"_blank\" rel=\"noreferrer\">{key[0].toUpperCase()+key.slice(1)} <ArrowRight size={13}/></a>)}"
assert old in s
s=s.replace(old,'')
needle='<span className="footer-location">Kenya / KES</span>'
new=needle+'''<div className="footer-socials" aria-label="Follow Olive">{[{name:'Facebook',url:settings.facebook,Icon:Facebook},{name:'Instagram',url:settings.instagram,Icon:Instagram},{name:'WhatsApp',url:whatsapp?'https://wa.me/'+whatsapp:'',Icon:MessageCircle}].filter(social=>/^https?:\\/\\//.test(social.url||'')).map(({name,url,Icon})=><a key={name} href={url} target="_blank" rel="noopener noreferrer" aria-label={name} title={name}><Icon size={19} aria-hidden="true"/></a>)}</div>'''
s=s.replace(needle,new);p.write_text(s,encoding='utf-8')
