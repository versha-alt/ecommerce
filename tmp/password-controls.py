from pathlib import Path
component='''import {useState} from 'react';
import type {InputHTMLAttributes} from 'react';
import {Eye,EyeOff} from 'lucide-react';
export default function PasswordInput({label='Password',...props}:InputHTMLAttributes<HTMLInputElement>&{label?:string}){const [visible,setVisible]=useState(false);return <div className="password-control"><input {...props} type={visible?'text':'password'}/><button type="button" className="password-toggle" aria-label={(visible?'Hide ':'Show ')+label.toLowerCase()} aria-pressed={visible} disabled={props.disabled} onMouseDown={event=>event.preventDefault()} onClick={()=>setVisible(!visible)}>{visible?<EyeOff size={18} aria-hidden="true"/>:<Eye size={18} aria-hidden="true"/>}</button></div>}
'''
Path('apps/admin/src/password-input.tsx').write_text(component,encoding='utf-8');Path('apps/storefront/components/password-input.tsx').write_text('"use client";\n'+component,encoding='utf-8')
p=Path('apps/admin/src/main.tsx');s=p.read_text(encoding='utf-8').replace("import AdminProfile from './admin-profile';","import AdminProfile from './admin-profile';\nimport PasswordInput from './password-input';")
s=s.replace('<input name="password" type="password" maxLength={1024} required value={password}', '<PasswordInput name="password" label="Password" maxLength={1024} required value={password}')
s=s.replace("{field.type==='textarea'?", "{field.type==='password'?<PasswordInput label={field.label} {...applyInputRules(field)} name={field.key} aria-invalid={!!validation.errors[field.key]} aria-describedby={validation.errors[field.key]?`error-${field.key}`:undefined} required={field.required} value={value??''} onChange={event=>onChange(event.target.value)} placeholder={field.placeholder} autoComplete=\"new-password\"/>:field.type==='textarea'?")
p.write_text(s,encoding='utf-8')
p=Path('apps/storefront/components/account.tsx');s=p.read_text(encoding='utf-8').replace('"use client";','"use client";\nimport PasswordInput from \'./password-input\';')
s=s.replace('<input name="password" type="password" required minLength=', '<PasswordInput key={mode} label="Password" name="password" required minLength=')
s=s.replace('</small>}</label>{message&&', '</small>}</label>{mode===\'register\'&&<label>Confirm password<PasswordInput label="Confirm password" name="password_confirmation" required maxLength={72} autoComplete="new-password"/></label>}{message&&',1)
s=s.replace('<input name="current_password" type="password" maxLength={72}/>', '<PasswordInput label="Current password" name="current_password" maxLength={72} autoComplete="current-password"/>').replace('<input name="password" type="password" minLength={12} maxLength={72}/>', '<PasswordInput label="New password" name="password" minLength={12} maxLength={72} autoComplete="new-password"/>')
old="event.preventDefault();if(busy)return;setBusy(true);setMessage('');const input=Object.fromEntries(new FormData(event.currentTarget));try{await storeRequest(mode,'POST',input);"
new="event.preventDefault();if(busy)return;const input=Object.fromEntries(new FormData(event.currentTarget));if(mode==='register'&&input.password!==input.password_confirmation){setMessage('Passwords do not match. Please confirm your password.');event.currentTarget.querySelector<HTMLInputElement>('[name=\"password_confirmation\"]')?.focus();return;}setBusy(true);setMessage('');try{await storeRequest(mode,'POST',input);"
assert old in s;s=s.replace(old,new);assert 'name="password_confirmation"' in s;p.write_text(s,encoding='utf-8')
p=Path('apps/backend/app/Http/Controllers/StorefrontController.php');s=p.read_text(encoding='utf-8');old="'password' => 'required|string|min:12|max:72', 'phone' => 'nullable|string|max:50'";new="'password' => 'required|string|min:12|max:72|confirmed', 'password_confirmation' => 'required|string|max:72', 'phone' => 'nullable|string|max:50'";assert old in s;s=s.replace(old,new,1);p.write_text(s,encoding='utf-8')
p=Path('apps/backend/tests/Feature/StorefrontTest.php');s=p.read_text(encoding='utf-8');s=s.replace("'password' => 'CustomerTest!2026'])->", "'password' => 'CustomerTest!2026', 'password_confirmation' => 'CustomerTest!2026'])->")
idx=s.index('    public function test_registration_sessions')
s=s[:idx]+'''    public function test_registration_requires_matching_password_confirmation(): void
    {
        $input = ['name' => 'Customer', 'email' => 'confirmation@example.com', 'password' => 'CustomerTest!2026'];
        $this->postJson('/api/v1/store/register', $input)->assertUnprocessable()->assertJsonValidationErrors('password_confirmation');
        $this->postJson('/api/v1/store/register', $input + ['password_confirmation' => 'DifferentTest!2026'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertSame(0, Record::where('resource', 'customers')->count());
        $this->assertDatabaseCount('customer_sessions', 0);
        $response = $this->postJson('/api/v1/store/register', $input + ['password_confirmation' => 'CustomerTest!2026'])->assertOk();
        $this->assertArrayNotHasKey('password_confirmation', Record::where('resource', 'customers')->firstOrFail()->data);
        $response->assertJsonMissingPath('customer.password_confirmation');
    }

'''+s[idx:];p.write_text(s,encoding='utf-8')
