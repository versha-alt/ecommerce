from pathlib import Path
names=['Mombasa','Kwale','Kilifi','Tana River','Lamu','Taita/Taveta','Garissa','Wajir','Mandera','Marsabit','Isiolo','Meru','Tharaka-Nithi','Embu','Kitui','Machakos','Makueni','Nyandarua','Nyeri','Kirinyaga',"Murang'a",'Kiambu','Turkana','West Pokot','Samburu','Trans Nzoia','Uasin Gishu','Elgeyo/Marakwet','Nandi','Baringo','Laikipia','Nakuru','Narok','Kajiado','Kericho','Bomet','Kakamega','Vihiga','Bungoma','Busia','Siaya','Kisumu','Homa Bay','Migori','Kisii','Nyamira','Nairobi']
def quote(s):return "'"+s.replace("'","\\'")+"'"
entries=',\n'.join('            '+quote(f'{i:03}')+' => '+quote(name) for i,name in enumerate(names,1))
Path('apps/backend/config/shipping.php').write_text("<?php\n\nreturn ['countries' => ['KE' => ['name' => 'Kenya', 'counties' => [\n"+entries+"\n]]]];\n")
p=Path('apps/backend/app/Http/Controllers/AdminController.php');s=p.read_text().replace("return ['records' => $records,", "return ['shipping_locations' => config('shipping'), 'records' => $records,",1);p.write_text(s)
p=Path('apps/backend/app/Services/Commerce.php');s=p.read_text();old="'delivery-zones' => ['charge' => 'required|numeric|min:0', 'free_threshold' => 'nullable|numeric|min:0', 'towns' => 'required|array|min:1', 'towns.*' => 'string|max:150'],";new="'delivery-zones' => ['country_code' => ['required', Rule::in(array_keys(config('shipping.countries')))], 'county_codes' => 'required|array|min:1|max:47', 'county_codes.*' => ['required', 'string', 'distinct', Rule::in(array_keys(config('shipping.countries.'.($data['country_code'] ?? 'KE').'.counties', [])))], 'charge' => 'required|numeric|min:0', 'free_threshold' => 'nullable|numeric|min:0', 'status' => 'required|in:Active,Inactive,Retired'],";assert old in s;s=s.replace(old,new)
pos=s.index("        if ($resource === 'products') {",s.index('public function validate('))
s=s[:pos]+'''        if ($resource === 'delivery-zones') {
            $counties = config('shipping.countries.'.$clean['country_code'].'.counties');
            $clean['country_name'] = config('shipping.countries.'.$clean['country_code'].'.name');
            $clean['county_codes'] = array_values($clean['county_codes']);
            $clean['county_names'] = array_map(fn (string $code): string => $counties[$code], $clean['county_codes']);
            $clean['towns'] = $clean['county_names'];
            unset($clean['legacy_unmapped_locations']);
        }
'''+s[pos:];p.write_text(s)
p=Path('apps/admin/src/config.ts');s=p.read_text();s=s.replace("columns:['name','towns','charge','free_threshold','status'],fields:[name,{key:'towns',label:'Counties / towns',type:'textarea',required:true,hint:'One county or town per line',wide:true},", "columns:['name','country_name','county_names','charge','free_threshold','status'],fields:[name,{key:'country_code',label:'Country',type:'country',required:true},{key:'county_codes',label:'Select Counties',type:'counties',required:true,wide:true},");p.write_text(s)
p=Path('apps/admin/src/main.tsx');s=p.read_text();s=s.replace("const data=workspace.data?.records??{};", "const data={...(workspace.data?.records??{}),shipping_locations:workspace.data?.shipping_locations};")
s=s.replace(" if(field.type==='gallery')", " if(field.type==='country')return <label className=\"form-field\"><span>Country</span><select aria-label=\"Country\" value={value||'KE'} disabled><option value=\"KE\">Kenya</option></select></label>;\n if(field.type==='counties')return <CountyPicker field={field} value={Array.isArray(value)?value:[]} onChange={onChange} data={data}/>;\n if(field.type==='gallery')",1)
s=s.replace("f.type==='checkbox'?false:", "f.type==='country'?'KE':f.type==='counties'?[]:f.type==='checkbox'?false:",1)
pos=s.index('function ReviewEditor(');s=s[:pos]+'''function CountyPicker({field,value,onChange,data}:{field:Field;value:string[];onChange:(codes:string[])=>void;data:any}){
 const [search,setSearch]=useState('');const validation=React.useContext(FormValidationContext);
 const counties=Object.entries(data.shipping_locations?.countries?.KE?.counties??{}).sort((a,b)=>String(a[1]).localeCompare(String(b[1])));
 const visible=counties.filter(([,name])=>String(name).toLowerCase().includes(search.toLowerCase()));
 return <div className="form-field wide county-field" data-field={field.key}><div className="county-picker-heading"><span>{field.label}<b> *</b></span><small>{value.length} selected</small></div><div className="county-picker"><div className="county-search"><Search size={16}/><input type="search" placeholder="Search counties..." aria-label="Search Kenyan counties" value={search} onChange={event=>setSearch(event.target.value)}/></div><div className="county-options" role="group" aria-label="Select Kenyan counties">{visible.map(([code,name])=><label key={code}><input name="county_codes" type="checkbox" value={code} checked={value.includes(code)} onChange={event=>onChange(event.target.checked?[...value,code]:value.filter(selected=>selected!==code))}/><span>{String(name)}</span></label>)}{!visible.length&&<small>No matching counties.</small>}</div></div><small>Select one or more counties covered by this delivery zone.</small>{validation.errors[field.key]&&<small className="field-error" role="alert">{validation.errors[field.key].join(' ')}</small>}</div>;
}

'''+s[pos:]
s=s.replace("country_id:'", "country_id:'")
s=s.replace("const colLabels:Record<string,string>={", "const colLabels:Record<string,string>={country_name:'Country',county_names:'Counties',",1)
s=s.replace("<fieldset className=\"form-grid form-fieldset\" disabled={readOnly}>", "{module.key==='delivery-zones'&&record?.legacy_unmapped_locations?.length>0&&<div className=\"info-banner\"><AlertTriangle size={18}/><div><strong>Confirm county coverage</strong><span>Previous locations need county selection: {record.legacy_unmapped_locations.join(', ')}.</span></div></div>}<fieldset className=\"form-grid form-fieldset\" disabled={readOnly}>",1)
p.write_text(s)
p=Path('apps/admin/src/validation.tsx');s=p.read_text();s=s.replace("  const value=(key:string)", "  if(form.querySelector('[data-field=\"county_codes\"]')&&!form.querySelector('[data-field=\"county_codes\"] input[type=\"checkbox\"]:checked'))next.county_codes=['Select at least one county.'];\n  const value=(key:string)");p.write_text(s)
p=Path('apps/admin/src/styles.css');s=p.read_text();s+='''
.county-picker{border:1px solid #dfe4d7;border-radius:10px;overflow:hidden}.county-picker-heading{display:flex;align-items:center;justify-content:space-between}.county-search{display:flex;align-items:center;gap:8px;padding:9px 12px;border-bottom:1px solid var(--line);color:var(--olive)}.county-search input{border:0!important;box-shadow:none!important;padding:5px!important;background:transparent!important}.county-options{display:grid;grid-template-columns:1fr 1fr;max-height:280px;overflow-y:auto;padding:12px;gap:5px 10px}.county-options label{display:flex;align-items:center;gap:10px;padding:8px;border-radius:6px;font-size:12px;color:#59664e;cursor:pointer}.county-options label:has(input:checked){background:var(--olive-light);color:var(--olive)}.county-options input{width:15px!important;flex-shrink:0}.county-field:has(.field-error) .county-picker{border-color:#a63131}@media(max-width:400px){.county-options{grid-template-columns:1fr}}
''';p.write_text(s)
